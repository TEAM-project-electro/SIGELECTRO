<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use App\Services\DatabaseTransactionService;
use Throwable;

class BaseOperativaReglasTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Asegurar que las conexiones estén configuradas
    }

    /**
     * Limpia datos de prueba en orden inverso respetando FKs.
     * Solo opera sobre esquemas de prueba (*_test).
     */
    protected function limpiarTablas(): void
    {
        $db = DB::connection('mysql'); // Conexión migradora para mantenimiento de tests
        if (!str_ends_with($db->getDatabaseName(), '_test')) {
            throw new \RuntimeException("PELIGRO (A02): Intento de limpiar base no de prueba: " . $db->getDatabaseName());
        }
        $db->statement('SET @id_responsable = 1;');
        $db->statement("SET @motivo_cambio = 'Limpieza de prueba';");
        $db->statement('SET FOREIGN_KEY_CHECKS=0;');
        $db->statement('DELETE FROM bitacora_motivo;');
        $db->statement('DELETE FROM bitacora_cambio;');
        $db->statement('DELETE FROM avance_presupuestal;');
        // TRUNCATE (cuenta migradora, solo esquemas _test) no dispara triggers: el borrado de hitos de línea base
        // exige un Administrador como actor, y aquí el actor 1 de la siembra de prueba es un Responsable.
        $db->statement('TRUNCATE TABLE hito;');
        // Tablas técnicas que dependen de proyectos e hitos (línea base y gestión gerencial).
        foreach (['hito_linea_base_version', 'hito_peso', 'evaluacion_priorizacion', 'riesgo_proyecto', 'compromiso_presupuestal',
            'proyecto_presupuesto_version', 'documento_proyecto', 'beneficio_proyecto'] as $tabla) {
            if ($db->getSchemaBuilder()->hasTable($tabla)) {
                $db->statement("TRUNCATE TABLE {$tabla};");
            }
        }
        $db->statement('DELETE FROM proyecto;');
        $db->statement('DELETE FROM responsable;');
        $db->statement('DELETE FROM objetivo_gd;');
        $db->statement('ALTER TABLE responsable AUTO_INCREMENT = 1;');
        $db->statement('ALTER TABLE objetivo_gd AUTO_INCREMENT = 1;');
        $db->statement('ALTER TABLE proyecto AUTO_INCREMENT = 1;');
        $db->statement('ALTER TABLE hito AUTO_INCREMENT = 1;');
        $db->statement('ALTER TABLE avance_presupuestal AUTO_INCREMENT = 1;');
        $db->statement('ALTER TABLE bitacora_cambio AUTO_INCREMENT = 1;');
        $db->statement('UPDATE control_revision SET version = 1, fecha_hora = NOW() WHERE id = 1;');
        $db->statement('SET FOREIGN_KEY_CHECKS=1;');
        $db->statement('SET @id_responsable = NULL;');
        $db->statement('SET @motivo_cambio = NULL;');
    }

    protected function tearDown(): void
    {
        // Restaurar la siembra canónica en la base de prueba para no afectar a las siguientes suites
        if (str_ends_with(DB::connection('mysql')->getDatabaseName(), '_test')) {
            $this->limpiarTablas();
            $this->artisan('db:seed', ['--force' => true]);
        }
        parent::tearDown();
    }

    protected function sembrarBasePrueba(): array
    {
        $this->limpiarTablas();
        $db = DB::connection('mysql');
        $db->statement("SET @id_responsable = 1;");
        $db->statement("SET @motivo_cambio = 'Siembra base de prueba';");
        DB::connection('escritor')->statement("SET @id_responsable = 1;");
        DB::connection('escritor')->statement("SET @motivo_cambio = 'Siembra base de prueba';");

        // Sembrar responsable primero para satisfacer la FK de bitacora_cambio
        $db->table('responsable')->insert([
            'id_responsable' => 1,
            'nombre' => 'Ana Quispe',
            'area' => 'TIC',
            'perfil' => 'Responsable',
            'usuario' => 'aquispe',
            'clave_hash' => password_hash('Demo2024!', PASSWORD_BCRYPT),
            'activo' => 1,
        ]);

        // Sembrar objetivo
        $db->table('objetivo_gd')->insert([
            'id_objetivo' => 1,
            'codigo' => 'OGD 04',
            'descripcion' => 'Mejora continua del Sistema de Seguridad de la Informacion',
        ]);

        // Sembrar proyecto de S/ 500,000.00
        $db->table('proyecto')->insert([
            'id_proyecto' => 1,
            'codigo_pgd' => '005',
            'nombre' => 'Equipos de ciberseguridad',
            'monto' => 500000.00,
            'anio_inicio' => 2023,
            'anio_fin' => 2024,
            'estado' => 'En curso',
            'id_objetivo' => 1,
            'id_responsable' => 1,
            'fuente' => 'Cartera PGD 2023 a 2026',
        ]);

        return ['id_proyecto' => 1, 'id_responsable' => 1];
    }

    // ==========================================
    // PRUEBAS REGLA R1: LÍMITES FINANCIEROS
    // ==========================================

    public function test_r1_rechaza_presupuesto_planificado_mayor_al_monto_total(): void
    {
        $this->sembrarBasePrueba();

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessage('El presupuesto planificado supera el monto del proyecto');

        DB::connection('escritor')->table('avance_presupuestal')->insert([
            'id_proyecto' => 1,
            'fecha_reporte' => '2023-03-31',
            'presupuesto_planificado' => 600000.00, // Mayor a 500,000
            'gasto_acumulado' => 100000.00,
        ]);
    }

    public function test_r1_rechaza_gasto_acumulado_mayor_al_monto_total(): void
    {
        $this->sembrarBasePrueba();

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessage('El gasto acumulado supera el monto del proyecto');

        DB::connection('escritor')->table('avance_presupuestal')->insert([
            'id_proyecto' => 1,
            'fecha_reporte' => '2023-03-31',
            'presupuesto_planificado' => 200000.00,
            'gasto_acumulado' => 550000.00, // Mayor a 500,000
        ]);
    }

    public function test_r1_reforzada_rechaza_reducir_monto_por_debajo_de_cortes_existentes(): void
    {
        $this->sembrarBasePrueba();

        // Corte válido: plan 300,000, gasto 250,000
        DB::connection('escritor')->table('avance_presupuestal')->insert([
            'id_proyecto' => 1,
            'fecha_reporte' => '2023-06-30',
            'presupuesto_planificado' => 300000.00,
            'gasto_acumulado' => 250000.00,
        ]);

        // Reducir monto a 200,000 (menor a los 250,000 ya gastados)
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessage('No se puede reducir el monto');

        DatabaseTransactionService::run(1, function ($db) {
            $db->table('proyecto')->where('id_proyecto', 1)->update([
                'monto' => 200000.00,
            ]);
        }, 'Reducción de alcance no permitida');
    }

    // ==========================================
    // PRUEBAS REGLA R2: MONOTONICIDAD DEL GASTO
    // ==========================================

    public function test_r2_rechaza_gasto_menor_al_reporte_cronologico_anterior(): void
    {
        $this->sembrarBasePrueba();

        // Corte 1: 31/03/2023 -> gasto 150,000
        DB::connection('escritor')->table('avance_presupuestal')->insert([
            'id_proyecto' => 1,
            'fecha_reporte' => '2023-03-31',
            'presupuesto_planificado' => 200000.00,
            'gasto_acumulado' => 150000.00,
        ]);

        // Corte 2 posterior: 30/06/2023 -> gasto 120,000 (disminuye)
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessage('El gasto acumulado es menor que el de un reporte anterior');

        DB::connection('escritor')->table('avance_presupuestal')->insert([
            'id_proyecto' => 1,
            'fecha_reporte' => '2023-06-30',
            'presupuesto_planificado' => 300000.00,
            'gasto_acumulado' => 120000.00,
        ]);
    }

    public function test_r2_rechaza_corte_intermedio_con_gasto_superior_al_corte_posterior(): void
    {
        $this->sembrarBasePrueba();

        // Corte 1: 31/03/2023 -> gasto 100,000
        DB::connection('escritor')->table('avance_presupuestal')->insert([
            'id_proyecto' => 1,
            'fecha_reporte' => '2023-03-31',
            'presupuesto_planificado' => 150000.00,
            'gasto_acumulado' => 100000.00,
        ]);

        // Corte 3 posterior: 30/09/2023 -> gasto 300,000
        DB::connection('escritor')->table('avance_presupuestal')->insert([
            'id_proyecto' => 1,
            'fecha_reporte' => '2023-09-30',
            'presupuesto_planificado' => 350000.00,
            'gasto_acumulado' => 300000.00,
        ]);

        // Corte 2 intermedio: 30/06/2023 -> intento con gasto 350,000 (superior al posterior 300,000)
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessage('El gasto acumulado supera al de un reporte posterior');

        DB::connection('escritor')->table('avance_presupuestal')->insert([
            'id_proyecto' => 1,
            'fecha_reporte' => '2023-06-30',
            'presupuesto_planificado' => 250000.00,
            'gasto_acumulado' => 350000.00,
        ]);
    }

    // ==========================================
    // PRUEBAS REGLA R3: CIERRE Y REAPERTURA
    // ==========================================

    public function test_r3_reforzada_rechaza_cierre_de_proyecto_con_cero_hitos_de_linea_base(): void
    {
        $this->sembrarBasePrueba();

        // Proyecto sin hitos
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessage('requiere al menos un hito en linea base');

        DatabaseTransactionService::run(1, function ($db) {
            $db->table('proyecto')->where('id_proyecto', 1)->update([
                'estado' => 'Cerrado',
            ]);
        });
    }

    public function test_r3_rechaza_cierre_con_hitos_de_linea_base_abiertos(): void
    {
        $this->sembrarBasePrueba();

        // Hito en línea base pero sin fecha_cierre
        DB::connection('escritor')->table('hito')->insert([
            'id_proyecto' => 1,
            'descripcion' => 'Hito 1 Adquisición',
            'fecha_prevista' => '2023-06-15',
            'fecha_cierre' => null,
            'en_linea_base' => 1,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessage('hay hitos de la linea base sin cierre');

        DatabaseTransactionService::run(1, function ($db) {
            $db->table('proyecto')->where('id_proyecto', 1)->update([
                'estado' => 'Cerrado',
            ]);
        });
    }

    public function test_r3_permite_cierre_cuando_todos_los_hitos_de_linea_base_estan_cerrados(): void
    {
        $this->sembrarBasePrueba();

        // Hito cerrado
        DB::connection('escritor')->table('hito')->insert([
            'id_proyecto' => 1,
            'descripcion' => 'Hito 1 Adquisición',
            'fecha_prevista' => '2023-06-15',
            'fecha_cierre' => '2023-06-10',
            'en_linea_base' => 1,
        ]);

        DatabaseTransactionService::run(1, function ($db) {
            $db->table('proyecto')->where('id_proyecto', 1)->update([
                'estado' => 'Cerrado',
            ]);
        });

        $estado = DB::connection('lector')->table('proyecto')->where('id_proyecto', 1)->value('estado');
        $this->assertEquals('Cerrado', $estado);
    }

    public function test_r3_reapertura_requiere_motivo_obligatorio(): void
    {
        $this->sembrarBasePrueba();

        // Preparar proyecto cerrado válido
        DB::connection('escritor')->table('hito')->insert([
            'id_proyecto' => 1,
            'descripcion' => 'Hito 1 Adquisición',
            'fecha_prevista' => '2023-06-15',
            'fecha_cierre' => '2023-06-10',
            'en_linea_base' => 1,
        ]);

        DatabaseTransactionService::run(1, function ($db) {
            $db->table('proyecto')->where('id_proyecto', 1)->update(['estado' => 'Cerrado']);
        });

        // Intento de reapertura sin motivo
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessage('Reabrir un proyecto cerrado requiere registrar un motivo justificado');

        DatabaseTransactionService::run(1, function ($db) {
            $db->table('proyecto')->where('id_proyecto', 1)->update(['estado' => 'En curso']);
        }, null); // Sin motivo
    }

    public function test_r3_reapertura_exitosa_con_motivo_guarda_en_bitacora_motivo(): void
    {
        $this->sembrarBasePrueba();

        DB::connection('escritor')->table('hito')->insert([
            'id_proyecto' => 1,
            'descripcion' => 'Hito 1 Adquisición',
            'fecha_prevista' => '2023-06-15',
            'fecha_cierre' => '2023-06-10',
            'en_linea_base' => 1,
        ]);

        DatabaseTransactionService::run(1, function ($db) {
            $db->table('proyecto')->where('id_proyecto', 1)->update(['estado' => 'Cerrado']);
        });

        // Reapertura con motivo formal
        $motivo = 'Observación en auditoría de entregables técnicos';
        DatabaseTransactionService::run(1, function ($db) {
            $db->table('proyecto')->where('id_proyecto', 1)->update(['estado' => 'En curso']);
        }, $motivo);

        $estado = DB::connection('lector')->table('proyecto')->where('id_proyecto', 1)->value('estado');
        $this->assertEquals('En curso', $estado);

        // Verificar registro en bitacora_motivo
        $motivoGuardado = DB::connection('lector')->table('bitacora_motivo')->orderByDesc('id_cambio')->value('motivo');
        $this->assertEquals($motivo, $motivoGuardado);
    }

    // ==========================================
    // PRUEBAS REGLA R4: AUDITORÍA Y LIMPIEZA
    // ==========================================

    public function test_r4_rechaza_modificacion_sin_id_responsable(): void
    {
        $this->sembrarBasePrueba();

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessage('Defina @id_responsable antes de modificar datos');

        // Modificar un hito sin fijar @id_responsable activa trg_hito_au que llama a sp_registrar_cambio
        DB::connection('escritor')->table('hito')->insert([
            'id_proyecto' => 1,
            'descripcion' => 'Hito Inicial',
            'fecha_prevista' => '2023-05-10',
            'en_linea_base' => 1,
        ]);

        // Modificación directa sin @id_responsable
        DB::connection('escritor')->statement("SET @id_responsable = NULL;");
        DB::connection('escritor')->table('hito')->where('id_proyecto', 1)->update([
            'descripcion' => 'Hito Renombrado sin Autor',
        ]);
    }

    public function test_r4_limpieza_estricta_de_actor_y_motivo_tras_excepcion(): void
    {
        $this->sembrarBasePrueba();

        try {
            DatabaseTransactionService::run(1, function ($db) {
                // Forzar excepción dentro de la transacción
                throw new \RuntimeException('Error simulado en servicio');
            }, 'Motivo efímero');
        } catch (\RuntimeException $e) {
            // Esperada
        }

        // Comprobar que en la conexión 'escritor' las variables quedaron en NULL
        $vars = DB::connection('escritor')->select('SELECT @id_responsable as r, @motivo_cambio as m');
        $this->assertNull($vars[0]->r);
        $this->assertNull($vars[0]->m);
    }

    public function test_r4_inmutabilidad_de_bitacora_deniega_update_y_delete_en_escritor(): void
    {
        $this->sembrarBasePrueba();

        // Modificar para generar bitácora (cambio de estado de En curso a Pendiente)
        DatabaseTransactionService::run(1, function ($db) {
            $db->table('proyecto')->where('id_proyecto', 1)->update([
                'estado' => 'Pendiente',
            ]);
        });

        $this->assertGreaterThanOrEqual(1, DB::table('bitacora_cambio')->count());

        // Intento de DELETE en bitácora con cuenta de escritura debe fallar por privilegios
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessage('DELETE command denied');

        DB::connection('escritor')->table('bitacora_cambio')->where('id_cambio', 1)->delete();
    }

    // ==========================================
    // PRUEBAS DE INTEGRIDAD REFERENCIAL Y UQ
    // ==========================================

    public function test_unicidad_de_codigo_pgd_rechaza_duplicados(): void
    {
        $this->sembrarBasePrueba();

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessage('uq_proyecto_codigo');

        DB::connection('escritor')->table('proyecto')->insert([
            'codigo_pgd' => '005', // Duplicado del existente
            'nombre' => 'Otro proyecto',
            'monto' => 100000.00,
            'anio_inicio' => 2023,
            'anio_fin' => 2024,
            'id_objetivo' => 1,
            'id_responsable' => 1,
        ]);
    }

    public function test_unicidad_de_avance_por_proyecto_y_fecha_rechaza_duplicados(): void
    {
        $this->sembrarBasePrueba();

        DB::connection('escritor')->table('avance_presupuestal')->insert([
            'id_proyecto' => 1,
            'fecha_reporte' => '2023-06-30',
            'presupuesto_planificado' => 100000.00,
            'gasto_acumulado' => 80000.00,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessage('uq_avance_proyecto_fecha');

        DB::connection('escritor')->table('avance_presupuestal')->insert([
            'id_proyecto' => 1,
            'fecha_reporte' => '2023-06-30', // Misma fecha y proyecto
            'presupuesto_planificado' => 120000.00,
            'gasto_acumulado' => 90000.00,
        ]);
    }

    public function test_on_delete_restrict_impide_borrar_proyecto_con_hitos(): void
    {
        $this->sembrarBasePrueba();

        DB::connection('escritor')->table('hito')->insert([
            'id_proyecto' => 1,
            'descripcion' => 'Hito dependiente',
            'fecha_prevista' => '2023-08-15',
            'en_linea_base' => 1,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessage('a foreign key constraint fails');

        // Se usa conexion migrador que tiene privilegios DDL/DML completos para verificar la restriccion FK en el motor
        DB::connection('mysql')->statement("SET @id_responsable = 1;");
        DB::connection('mysql')->statement("SET @motivo_cambio = 'Test FK';");
        DB::connection('mysql')->table('proyecto')->where('id_proyecto', 1)->delete();
    }
}
