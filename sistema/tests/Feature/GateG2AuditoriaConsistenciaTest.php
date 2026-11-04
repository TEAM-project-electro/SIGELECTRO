<?php

namespace Tests\Feature;

use App\Models\BitacoraCambio;
use App\Models\Proyecto;
use App\Models\Responsable;
use App\Services\DatabaseTransactionService;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Suite de Verificación de la Compuerta Gate G2: Auditoría, Inmutabilidad y Consistencia.
 * Valida inmutabilidad de bitácoras (sin UPDATE/DELETE en escritor), Regla R4 de actor obligatorio,
 * limpieza incondicional en finally, y autorización exclusiva del módulo /auditoria para Administrador.
 */
class GateG2AuditoriaConsistenciaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Asegurar que la base tenga la muestra canónica sembrada
        $this->seed();
    }

    /**
     * 1. Inmutabilidad de bitacora_cambio: Rechaza UPDATE en la conexión de ejecución (sig_escritor).
     */
    public function test_inmutabilidad_bitacora_cambio_rechaza_update_en_sig_escritor(): void
    {
        $idCambio = DB::connection('mysql')->table('bitacora_cambio')->value('id_cambio');
        $this->assertNotNull($idCambio, 'Debe existir al menos un registro en bitacora_cambio.');

        $this->expectException(QueryException::class);
        // Error esperado de MariaDB: 1142 UPDATE command denied to user 'sig_escritor'@'localhost'
        DB::connection('escritor')->table('bitacora_cambio')
            ->where('id_cambio', $idCambio)
            ->update(['campo' => 'campo_modificado']);
    }

    /**
     * 2. Inmutabilidad de bitacora_cambio: Rechaza DELETE en la conexión de ejecución (sig_escritor).
     */
    public function test_inmutabilidad_bitacora_cambio_rechaza_delete_en_sig_escritor(): void
    {
        $idCambio = DB::connection('mysql')->table('bitacora_cambio')->value('id_cambio');
        $this->assertNotNull($idCambio);

        $this->expectException(QueryException::class);
        // Error esperado de MariaDB: 1142 DELETE command denied to user 'sig_escritor'@'localhost'
        DB::connection('escritor')->table('bitacora_cambio')
            ->where('id_cambio', $idCambio)
            ->delete();
    }

    /**
     * 3. Inmutabilidad de bitacora_motivo: Rechaza UPDATE en sig_escritor.
     */
    public function test_inmutabilidad_bitacora_motivo_rechaza_update_en_sig_escritor(): void
    {
        $this->expectException(QueryException::class);
        DB::connection('escritor')->table('bitacora_motivo')
            ->where('id_cambio', 1)
            ->update(['motivo' => 'Alteración fraudulenta']);
    }

    /**
     * 4. Inmutabilidad de bitacora_motivo: Rechaza DELETE en sig_escritor.
     */
    public function test_inmutabilidad_bitacora_motivo_rechaza_delete_en_sig_escritor(): void
    {
        $this->expectException(QueryException::class);
        DB::connection('escritor')->table('bitacora_motivo')
            ->where('id_cambio', 1)
            ->delete();
    }

    /**
     * 5. Regla R4: Rechaza cualquier modificación de negocio sin @id_responsable definido.
     */
    public function test_regla_r4_rechaza_mutacion_sin_id_responsable(): void
    {
        $proyecto = DB::table('proyecto')->where('codigo_pgd', '005')->first();

        // Limpiar explícitamente la variable de sesión
        DB::connection('escritor')->statement('SET @id_responsable = NULL;');

        try {
            DB::connection('escritor')->table('proyecto')
                ->where('id_proyecto', $proyecto->id_proyecto)
                ->update(['monto' => (float)$proyecto->monto + 500.00]);
            $this->fail('El trigger debió abortar por falta de @id_responsable.');
        } catch (QueryException $e) {
            $this->assertTrue(
                str_contains($e->getMessage(), 'Defina @id_responsable') ||
                str_contains($e->getMessage(), '45000'),
                'Debe abortar con error 45000 por actor no definido: ' . $e->getMessage()
            );
        }
    }

    /**
     * 6. Limpieza estricta de actor y motivo tras transacción exitosa en DatabaseTransactionService.
     */
    public function test_limpieza_estricta_de_actor_y_motivo_tras_transaccion_exitosa(): void
    {
        $proyecto = Proyecto::where('codigo_pgd', '005')->first();

        DatabaseTransactionService::run(1, function () use ($proyecto) {
            DB::connection('escritor')->table('proyecto')
                ->where('id_proyecto', $proyecto->id_proyecto)
                ->update(['nombre' => $proyecto->nombre . ' - Temp']);
        }, 'Motivo de prueba');

        // Verificamos que las variables de sesión hayan sido limpiadas
        $actor = DB::connection('escritor')->select('SELECT @id_responsable as act')[0]->act;
        $motivo = DB::connection('escritor')->select('SELECT @motivo_cambio as mot')[0]->mot;

        $this->assertNull($actor, 'La variable @id_responsable debe ser NULL tras la transacción.');
        $this->assertNull($motivo, 'La variable @motivo_cambio debe ser NULL tras la transacción.');
    }

    /**
     * 7. Limpieza estricta de actor y motivo en bloque finally tras ocurrir una excepción.
     */
    public function test_limpieza_estricta_de_actor_y_motivo_tras_excepcion(): void
    {
        try {
            DatabaseTransactionService::run(1, function () {
                throw new Exception('Falla intencional en transacción');
            }, 'Motivo que debe limpiarse');
        } catch (Exception $e) {
            // Esperada
        }

        $actor = DB::connection('escritor')->select('SELECT @id_responsable as act')[0]->act;
        $motivo = DB::connection('escritor')->select('SELECT @motivo_cambio as mot')[0]->mot;

        $this->assertNull($actor, 'El bloque finally debe limpiar incondicionalmente @id_responsable.');
        $this->assertNull($motivo, 'El bloque finally debe limpiar incondicionalmente @motivo_cambio.');
    }

    /**
     * 8. Solo el Administrador puede acceder a la ruta /auditoria (Gate G2).
     */
    public function test_control_de_acceso_exclusivo_a_modulo_auditoria(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $resp = Responsable::where('usuario', 'aquispe')->first();
        $consulta = Responsable::where('usuario', 'cgarcia')->first();

        // 1. Invitado redirigido a login
        $this->get('/auditoria')->assertRedirect('/login');

        // 2. Responsable recibe 403 Forbidden
        $this->actingAs($resp);
        $this->get('/auditoria')->assertStatus(403);

        // 3. Consulta recibe 403 Forbidden
        $this->actingAs($consulta);
        $this->get('/auditoria')->assertStatus(403);

        // 4. Administrador accede con 200 OK
        $this->actingAs($admin);
        $response = $this->get('/auditoria');
        $response->assertStatus(200);
        $response->assertSee('Bitácora Técnica de Auditoría');
        $response->assertSee('Inmutabilidad BD Activa');
    }

    /**
     * 9. Filtros de búsqueda en la bitácora técnica.
     */
    public function test_filtros_de_busqueda_en_bitacora(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $this->actingAs($admin);

        $response = $this->get('/auditoria?tabla_afectada=proyecto');

        $response->assertStatus(200);
        $response->assertSee('proyecto');
    }

    /**
     * 10. Reapertura de proyecto genera entrada auditable con motivo en bitacora_motivo.
     */
    public function test_reapertura_de_proyecto_audita_motivo_en_bitacora(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $proyectoCerrado = Proyecto::where('estado', 'Cerrado')->first();

        $this->actingAs($admin);

        $motivoAuditado = 'Reapertura oficial para adición de alcance técnico complementario 2024';

        $response = $this->post("/proyectos/{$proyectoCerrado->id_proyecto}/reabrir", [
            'motivo' => $motivoAuditado,
        ]);

        $response->assertRedirect("/proyectos/{$proyectoCerrado->id_proyecto}");

        // Verificamos que aparezca en la bitácora
        $bitacora = BitacoraCambio::where('tabla_afectada', 'proyecto')
            ->where('id_registro', $proyectoCerrado->id_proyecto)
            ->where('campo', 'estado')
            ->orderByDesc('id_cambio')
            ->first();

        $this->assertNotNull($bitacora);
        $this->assertEquals((int) $admin->id_responsable, (int) $bitacora->id_responsable);
        $this->assertNotNull($bitacora->motivoExtension);
        $this->assertEquals($motivoAuditado, $bitacora->motivoExtension->motivo);

        // Verificamos que el Administrador vea el motivo en la pantalla /auditoria
        $respAuditoria = $this->get('/auditoria');
        $respAuditoria->assertStatus(200);
        $respAuditoria->assertSee('Reapertura oficial para adición');
    }
}
