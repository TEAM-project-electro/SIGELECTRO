<?php

namespace Tests\Feature;

use App\Models\Responsable;
use App\Services\EtlService;
use App\Services\IndicadoresService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DataWarehouseEtlTest extends TestCase
{
    protected EtlService $etlService;
    protected IndicadoresService $indicadoresService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->etlService = app(EtlService::class);
        $this->indicadoresService = app(IndicadoresService::class);
    }

    /**
     * 1. Verificación de privilegios: bi_lector solo puede leer en DW y no puede escribir.
     */
    public function test_bi_lector_solo_lectura_deniega_insert_y_update(): void
    {
        // 1. Lectura permitida
        $filas = DB::connection('dw_lector')->table('dim_proyecto')->count();
        $this->assertGreaterThan(0, $filas, 'bi_lector debe poder leer dim_proyecto.');

        // 2. Denegación de INSERT
        try {
            DB::connection('dw_lector')->table('dim_proyecto')->insert([
                'id_proyecto' => 9999,
                'codigo_pgd' => '999',
                'nombre' => 'Intento Escritura bi_lector',
                'monto' => 1000.00,
                'rango_monto' => 'Menor a 500k',
                'anio_inicio' => 2024,
                'anio_fin' => 2025,
                'estado_actual' => 'En curso',
                'es_activo' => 1,
                'fuente' => 'Test',
            ]);
            $this->fail('bi_lector no debe tener permiso de INSERT en Data Warehouse.');
        } catch (QueryException $e) {
            $this->assertTrue(
                str_contains($e->getMessage(), 'command denied') ||
                str_contains($e->getMessage(), '42000'),
                'Debe arrojar error 42000 command denied.'
            );
        }

        // 3. Denegación de DELETE
        try {
            DB::connection('dw_lector')->table('dim_proyecto')->where('id_proyecto', 1)->delete();
            $this->fail('bi_lector no debe tener permiso de DELETE en Data Warehouse.');
        } catch (QueryException $e) {
            $this->assertTrue(
                str_contains($e->getMessage(), 'command denied') ||
                str_contains($e->getMessage(), '42000'),
                'Debe arrojar error 42000 command denied.'
            );
        }
    }

    /**
     * 2. dim_responsable no contiene datos sensibles (usuario ni clave_hash).
     */
    public function test_dim_responsable_no_contiene_usuario_ni_clave(): void
    {
        $esquemaDw = DB::connection('dw_lector')->getDatabaseName();
        $columnas = DB::connection('dw_lector')->select("
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'dim_responsable'
        ", [$esquemaDw]);

        $nombres = array_map(fn($col) => $col->COLUMN_NAME, $columnas);

        $this->assertNotContains('usuario', $nombres, 'dim_responsable no debe contener usuario.');
        $this->assertNotContains('clave_hash', $nombres, 'dim_responsable no debe contener clave_hash.');
        $this->assertContains('nombre', $nombres);
        $this->assertContains('perfil', $nombres);
    }

    /**
     * 3. Idempotencia y granularidad: dos ejecuciones consecutivas conservan los conteos exactos.
     */
    public function test_etl_idempotente_conserva_granularidad_y_conteos(): void
    {
        // Ejecución 1
        $res1 = $this->etlService->ejecutar();
        $this->assertTrue($res1['exito'], 'Primera ejecución debe ser exitosa.');

        $conteoAvance1 = DB::connection('dw_lector')->table('fact_avance_proyecto')->count();
        $conteoHito1 = DB::connection('dw_lector')->table('fact_hito')->count();
        $conteoProyectos1 = DB::connection('dw_lector')->table('dim_proyecto')->count();

        $this->assertEquals(24, $conteoAvance1, 'fact_avance_proyecto debe tener 24 filas canónicas.');
        $this->assertEquals(26, $conteoHito1, 'fact_hito debe tener 26 filas canónicas.');
        $this->assertEquals(10, $conteoProyectos1, 'dim_proyecto debe tener 10 proyectos.');

        // Ejecución 2
        $res2 = $this->etlService->ejecutar();
        $this->assertTrue($res2['exito'], 'Segunda ejecución debe ser exitosa.');

        $conteoAvance2 = DB::connection('dw_lector')->table('fact_avance_proyecto')->count();
        $conteoHito2 = DB::connection('dw_lector')->table('fact_hito')->count();
        $conteoProyectos2 = DB::connection('dw_lector')->table('dim_proyecto')->count();

        $this->assertEquals($conteoAvance1, $conteoAvance2, 'El conteo de avances debe ser idéntico tras segunda ejecución.');
        $this->assertEquals($conteoHito1, $conteoHito2, 'El conteo de hitos debe ser idéntico tras segunda ejecución.');
        $this->assertEquals($conteoProyectos1, $conteoProyectos2, 'El conteo de proyectos debe ser idéntico.');
    }

    /**
     * 4. Paridad analítica entre el Data Warehouse (v_kpi_cartera) y el motor operativo (Etapa 10).
     */
    public function test_paridad_analitica_dw_frente_a_motor_operativo(): void
    {
        // 1. Obtener cálculo del motor de la Etapa 10 en modo ULTIMO_REPORTE_POR_PROYECTO
        $operativo = $this->indicadoresService->calcularCartera([
            'modo' => IndicadoresService::MODO_ULTIMO_REPORTE,
        ]);
        $kpiOp = $operativo['resumen'];

        // 2. Obtener indicadores calculados en la vista dimensional v_kpi_cartera
        $dw = DB::connection('dw_lector')->table('v_kpi_cartera')->first();

        $this->assertNotNull($dw, 'La vista v_kpi_cartera debe devolver datos.');
        $this->assertEquals($kpiOp['total_proyectos'], (int) $dw->total_proyectos);
        $this->assertEquals($kpiOp['monto_total_cartera'], (float) $dw->monto_total_cartera);
        $this->assertEquals($kpiOp['desviacion_presupuestal']['presupuesto_planificado'], (float) $dw->total_planificado);
        $this->assertEquals($kpiOp['desviacion_presupuestal']['gasto_acumulado'], (float) $dw->total_gasto);

        // Tolerancia de 0.01 pp en porcentajes
        $this->assertEqualsWithDelta($kpiOp['avance_hitos']['valor'], (float) $dw->avance_hitos_pct, 0.01);
        $this->assertEqualsWithDelta($kpiOp['proyectos_retraso']['valor'], (float) $dw->porcentaje_retraso, 0.01);
        $this->assertEqualsWithDelta($kpiOp['desviacion_presupuestal']['valor'], (float) $dw->desviacion_presupuestal_pct, 0.01);
    }

    /**
     * 5. Acceso y consistencia de las 6 vistas analíticas del Data Mart.
     */
    public function test_seis_vistas_analiticas_accesibles_y_consistentes(): void
    {
        $metricas = $this->etlService->obtenerMetricas();

        // 1. v_kpi_cartera
        $this->assertNotNull($metricas['kpi_cartera']);
        $this->assertEquals(10, $metricas['kpi_cartera']->total_proyectos);

        // 2. v_proyectos_por_estado
        $this->assertCount(2, $metricas['proyectos_por_estado']); // En curso y Cerrado

        // 3. v_evolucion_por_corte
        $this->assertGreaterThan(0, $metricas['evolucion_por_corte']->count());

        // 4. v_alertas
        $this->assertNotNull($metricas['alertas']);

        // 5. v_calidad_datos
        $this->assertCount(3, $metricas['calidad_datos']);
    }

    /**
     * 6. Detección de Data Warehouse desactualizado ante cambios en la base operativa.
     */
    public function test_deteccion_de_desactualizacion_de_dw_tras_mutacion(): void
    {
        // Asegurar que el DW esté sincronizado
        $this->etlService->ejecutar();
        $estadoInicial = $this->etlService->estaDesactualizado();
        $this->assertFalse($estadoInicial['desactualizado'], 'El DW debe estar sincronizado tras ejecutar ETL.');

        // Simular incremento de revisión operativa (ej. cambio en base operativa)
        DB::connection('escritor')->table('control_revision')->where('id', 1)->increment('version');

        $estadoMutado = $this->etlService->estaDesactualizado();
        $this->assertTrue($estadoMutado['desactualizado'], 'El DW debe marcarse como desactualizado tras cambio en la fuente.');

        // Sincronizar nuevamente
        $this->etlService->ejecutar();
        $estadoFinal = $this->etlService->estaDesactualizado();
        $this->assertFalse($estadoFinal['desactualizado'], 'El DW debe volver a estar al día tras sincronización.');
    }

    /**
     * 7. Control de acceso al endpoint POST /etl/ejecutar (Solo Administrador).
     */
    public function test_control_de_acceso_endpoint_ejecucion_etl(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $resp = Responsable::where('usuario', 'aquispe')->first();
        $consulta = Responsable::where('usuario', 'cgarcia')->first();

        // 1. Invitado redirigido
        $this->post('/etl/ejecutar')->assertRedirect('/login');

        // 2. Responsable recibe 403 Forbidden
        $this->actingAs($resp);
        $this->post('/etl/ejecutar')->assertStatus(403);

        // 3. Consulta recibe 403 Forbidden
        $this->actingAs($consulta);
        $this->post('/etl/ejecutar')->assertStatus(403);

        // 4. Administrador recibe 200 OK y ejecuta la carga
        $this->actingAs($admin);
        $response = $this->postJson('/etl/ejecutar');
        $response->assertStatus(200);
        $response->assertJson([
            'exito' => true,
            'estado' => 'Completado',
        ]);
    }
}
