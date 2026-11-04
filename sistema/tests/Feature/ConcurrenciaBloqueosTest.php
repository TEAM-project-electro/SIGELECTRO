<?php

namespace Tests\Feature;

use App\Models\Responsable;
use App\Services\DatabaseTransactionService;
use App\Services\UsuarioService;
use Exception;
use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;
use Tests\TestCase;

class ConcurrenciaBloqueosTest extends TestCase
{
    private function comprobarEspera(PDO $pdo): void
    {
        $pdo->exec('SET innodb_lock_wait_timeout = 1');
        $pdo->beginTransaction();
        $inicio = microtime(true);
        try {
            $pdo->query('SELECT id FROM control_revision WHERE id = 1 FOR UPDATE')->fetch();
            $this->fail('La segunda conexión no debe pasar el bloqueo mantenido por A.');
        } catch (\PDOException $e) {
            $this->assertEquals(1205, $e->errorInfo[1]);
            $this->assertGreaterThanOrEqual(0.8, microtime(true) - $inicio);
        } finally {
            $pdo->rollBack();
        }
    }
    private function crearPdoEscritor(): PDO
    {
        $db = config('database.connections.escritor.database');
        $host = config('database.connections.escritor.host');
        $port = config('database.connections.escritor.port');
        $user = config('database.connections.escritor.username');
        $pass = config('database.connections.escritor.password');

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        return $pdo;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->limpiarDatosTest();
    }

    private function limpiarDatosTest(): void
    {
        try {
            $db = DB::connection('mysql');
            $db->statement("SET @id_responsable = 1;");
            $db->statement("SET @motivo_cambio = 'Limpieza preventiva de tests concurrentes';");
            $ids = $db->table('proyecto')->whereIn('codigo_pgd', ['PGD-C01', 'PGD-C02'])->pluck('id_proyecto')->toArray();
            if (!empty($ids)) {
                $db->table('hito')->whereIn('id_proyecto', $ids)->delete();
                $db->table('avance_presupuestal')->whereIn('id_proyecto', $ids)->delete();
                $db->table('proyecto')->whereIn('id_proyecto', $ids)->delete();
            }
            $adminIds = $db->table('responsable')->where('usuario', 'admindos_test')->pluck('id_responsable');
            $cambios = $db->table('bitacora_cambio')->whereIn('id_responsable',$adminIds)->pluck('id_cambio');
            $db->table('bitacora_motivo')->whereIn('id_cambio',$cambios)->delete();
            $db->table('bitacora_cambio')->whereIn('id_cambio',$cambios)->delete();
            $db->table('responsable')->whereIn('id_responsable', $adminIds)->delete();
        } finally {
            $db->statement("SET @id_responsable = NULL, @motivo_cambio = NULL;");
        }
    }

    /**
     * Test 1: Concurrencia entre cierre formal de proyecto e inserción de hito (A03).
     * La transacción de cierre adquiere bloqueo FOR UPDATE; la inserción concurrente espera
     * y es rechazada por el trigger trg_hito_bi tras consolidarse el cierre.
     */
    public function test_concurrencia_cierre_proyecto_e_insercion_hito(): void
    {
        $pdoA = $this->crearPdoEscritor();
        $pdoB = $this->crearPdoEscritor();

        // 1. Crear un proyecto demostrable con 1 hito cerrado para que sea apto para cierre
        $pdoA->exec("SET @id_responsable = 1; SET @motivo_cambio = 'Setup prueba concurrencia 1';");
        $pdoA->exec("
            INSERT INTO proyecto (codigo_pgd, nombre, monto, anio_inicio, anio_fin, estado, id_objetivo, id_responsable, fuente, fecha_actualizacion)
            VALUES ('PGD-C01', 'Proyecto Test Concurrencia 1', 100000, 2024, 2025, 'En curso', 1, 1, 'Cartera PGD', NOW())
        ");
        $idProyecto = (int) $pdoA->lastInsertId();

        $pdoA->exec("
            INSERT INTO hito (id_proyecto, descripcion, fecha_prevista, fecha_cierre, en_linea_base)
            VALUES ({$idProyecto}, 'Hito Inicial Cerrado', '2024-05-01', '2024-05-01', 1)
        ");

        // 2. Conexión A inicia transacción de cierre y bloquea el proyecto FOR UPDATE
        $pdoA->beginTransaction();
        $pdoA->exec("SET @id_responsable = 1; SET @motivo_cambio = 'Cierre concurrente';");
        $pdoA->query("SELECT id, version FROM control_revision WHERE id = 1 FOR UPDATE")->fetch();
        $pdoA->query("SELECT id_proyecto FROM proyecto WHERE id_proyecto = {$idProyecto} FOR UPDATE")->fetch();

        $pdoA->exec("UPDATE proyecto SET estado = 'Cerrado', fecha_actualizacion = NOW() WHERE id_proyecto = {$idProyecto}");

        // 3. Conexión A hace commit cerrando el proyecto
        $this->comprobarEspera($pdoB);
        $pdoA->commit();

        // 4. Conexión B intenta insertar un hito en el proyecto ahora cerrado; debe fallar por trg_hito_bi
        $pdoB->exec("SET @id_responsable = 1; SET @motivo_cambio = 'Intento inserción concurrente';");
        $fallo = false;
        try {
            $pdoB->exec("
                INSERT INTO hito (id_proyecto, descripcion, fecha_prevista, fecha_cierre, en_linea_base)
                VALUES ({$idProyecto}, 'Hito Bloqueado Post Cierre', '2024-06-01', NULL, 1)
            ");
        } catch (\PDOException $e) {
            $fallo = true;
            $this->assertStringContainsString('No se pueden agregar hitos a un proyecto cerrado', $e->getMessage());
        }

        $this->assertTrue($fallo, 'El trigger trg_hito_bi debió rechazar la inserción de hitos en el proyecto cerrado.');
    }

    /**
     * Test 2: Concurrencia en avances presupuestales bajo la Regla R2 (A03).
     * Dos cortes cronológicos concurrentes deben respetar la monotonía del gasto acumulado.
     */
    public function test_concurrencia_avance_presupuestal_regla_r2(): void
    {
        $pdoA = $this->crearPdoEscritor();
        $pdoB = $this->crearPdoEscritor();

        // Crear proyecto base para avance
        $pdoA->exec("SET @id_responsable = 1; SET @motivo_cambio = 'Setup prueba avance R2';");
        $pdoA->exec("
            INSERT INTO proyecto (codigo_pgd, nombre, monto, anio_inicio, anio_fin, estado, id_objetivo, id_responsable, fuente, fecha_actualizacion)
            VALUES ('PGD-C02', 'Proyecto Test Avance R2', 200000, 2024, 2025, 'En curso', 1, 1, 'Cartera PGD', NOW())
        ");
        $idProyecto = (int) $pdoA->lastInsertId();

        // Conexión A inserta corte inicial al 2024-03-31 con gasto de S/ 50,000
        $pdoA->beginTransaction();
        $pdoA->query("SELECT id, version FROM control_revision WHERE id = 1 FOR UPDATE")->fetch();
        $pdoA->query("SELECT id_proyecto FROM proyecto WHERE id_proyecto = {$idProyecto} FOR UPDATE")->fetch();
        $pdoA->exec("
            INSERT INTO avance_presupuestal (id_proyecto, fecha_reporte, presupuesto_planificado, gasto_acumulado)
            VALUES ({$idProyecto}, '2024-03-31', 40000, 50000)
        ");
        $this->comprobarEspera($pdoB);
        $pdoA->commit();

        // Conexión B intenta insertar corte posterior al 2024-06-30 con gasto menor de S/ 30,000 (Violación R2)
        $falloR2 = false;
        try {
            $pdoB->exec("SET @id_responsable = 1; SET @motivo_cambio = 'Intento corte R2 inválido';");
            $pdoB->exec("
                INSERT INTO avance_presupuestal (id_proyecto, fecha_reporte, presupuesto_planificado, gasto_acumulado)
                VALUES ({$idProyecto}, '2024-06-30', 60000, 30000)
            ");
        } catch (\PDOException $e) {
            $falloR2 = true;
            $this->assertStringContainsString('El gasto acumulado es menor que el de un reporte anterior', $e->getMessage());
        }

        $this->assertTrue($falloR2, 'sp_validar_avance debió rechazar el corte con gasto no monótono bajo la Regla R2.');
    }

    /**
     * Test 3: Protección del último administrador bajo concurrencia (A03, A09).
     * Si dos administradores intentan desactivarse mutuamente de forma concurrente,
     * la serialización pesimista garantiza que el último quede protegido y no se permita un sistema acéfalo.
     */
    public function test_proteccion_concurrente_ultimo_administrador(): void
    {
        // 1. Asegurar la existencia de exactamente 2 administradores activos de prueba
        $pdo = $this->crearPdoEscritor();
        $pdo->exec("SET @id_responsable = 1; SET @motivo_cambio = 'Setup prueba admin';");
        
        // Crear segundo administrador
        $pdo->exec("
            INSERT INTO responsable (nombre, area, perfil, usuario, clave_hash, activo)
            VALUES ('Admin Dos', 'Gerencia', 'Administrador', 'admindos_test', 'hash', 1)
            ON DUPLICATE KEY UPDATE activo = 1;
        ");
        $idAdmin2 = (int) $pdo->lastInsertId();
        if ($idAdmin2 === 0) {
            $idAdmin2 = (int) $pdo->query("SELECT id_responsable FROM responsable WHERE usuario = 'admindos_test'")->fetchColumn();
        }

        $admin1 = Responsable::where('usuario', 'msalas')->first();
        $this->assertNotNull($admin1);

        // Desactivar al admin 1
        $pdo->beginTransaction();
        $pdo->query('SELECT id FROM control_revision WHERE id = 1 FOR UPDATE')->fetch();
        $pdo->exec('UPDATE responsable SET activo = 0 WHERE id_responsable = '.$admin1->id_responsable);
        $this->comprobarEspera($this->crearPdoEscritor());
        $pdo->commit();
        $this->actingAs(Responsable::findOrFail($idAdmin2));

        // Intentar desactivar al admin 2 (que ahora es el único administrador activo)
        $rechazado = false;
        try {
            UsuarioService::cambiarEstado($idAdmin2, 0, 'Intento de desactivar último admin');
        } catch (RuntimeException $e) {
            $rechazado = true;
            $this->assertStringContainsString('No se puede desactivar al único Administrador activo', $e->getMessage());
        }

        $this->assertTrue($rechazado, 'UsuarioService debió abortar e impedir que se desactive al único Administrador activo restante.');

        // Restaurar estado de msalas
        UsuarioService::cambiarEstado($admin1->id_responsable, 1, 'Restaurar msalas activo');
    }

    protected function tearDown(): void
    {
        $this->limpiarDatosTest();
        parent::tearDown();
    }
}
