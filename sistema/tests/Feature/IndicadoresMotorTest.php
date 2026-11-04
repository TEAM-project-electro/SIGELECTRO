<?php

namespace Tests\Feature;

use App\Models\ObjetivoGd;
use App\Models\Proyecto;
use App\Models\Responsable;
use App\Services\DatabaseTransactionService;
use App\Services\IndicadoresService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IndicadoresMotorTest extends TestCase
{
    protected IndicadoresService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(IndicadoresService::class);
        $this->limpiarDatosPrueba();
    }

    protected function tearDown(): void
    {
        $this->limpiarDatosPrueba();
        parent::tearDown();
    }

    private function limpiarDatosPrueba(): void
    {
        try {
            DB::connection('escritor')->statement('SET @id_responsable = 1;');
            DB::connection('escritor')->statement('SET @motivo_cambio = "Limpieza de tests de indicadores";');

            // Limpiar proyectos de prueba oráculo (codigos 801, 802, 803, etc.)
            $ids = DB::table('proyecto')->whereIn('codigo_pgd', ['801', '802', '803', '804'])->pluck('id_proyecto')->toArray();
            if (!empty($ids)) {
                DB::connection('escritor')->table('avance_presupuestal')->whereIn('id_proyecto', $ids)->delete();
                DB::connection('escritor')->table('hito')->whereIn('id_proyecto', $ids)->delete();
                DB::connection('escritor')->table('proyecto')->whereIn('id_proyecto', $ids)->delete();
            }
        } finally {
            DB::connection('escritor')->statement('SET @id_responsable = NULL;');
            DB::connection('escritor')->statement('SET @motivo_cambio = NULL;');
        }
    }

    /**
     * 1. Oráculo Canónico Independiente (Corte 30/06/2024):
     * Proyectos A, B y C exactamente como define docs/contrato_indicadores.md
     * - Avance: 4/6 = 66.6666...% -> 66.67%
     * - Retraso: 1/2 = 50.00% (B no retrasado porque su hito vencido tiene en_linea_base=0)
     * - Desviación: (410 - 400)/400 = +2.50%
     * - Tolerancia máxima: 0.01 pp
     */
    public function test_oraculo_canonico_independiente_corte_comun_2024_06_30(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $objetivo = ObjetivoGd::first();

        DatabaseTransactionService::run($admin->id_responsable, function () use ($admin, $objetivo) {
            // Proyecto A: En curso, Monto 500
            $idA = DB::connection('escritor')->table('proyecto')->insertGetId([
                'codigo_pgd' => '801',
                'nombre' => 'Proyecto A - Test Oráculo',
                'monto' => 500.00,
                'anio_inicio' => 2024,
                'anio_fin' => 2025,
                'estado' => 'En curso',
                'id_objetivo' => $objetivo->id_objetivo,
                'id_responsable' => $admin->id_responsable,
                'fuente' => 'Recursos Propios',
            ]);

            // Hitos A
            // Hito 1: LB=1, Prev=2024-03-15, Cierre=2024-03-15 (Concluido)
            DB::connection('escritor')->table('hito')->insert([
                'id_proyecto' => $idA,
                'descripcion' => 'A - Hito 1',
                'fecha_prevista' => '2024-03-15',
                'fecha_cierre' => '2024-03-15',
                'en_linea_base' => 1,
            ]);
            // Hito 2: LB=1, Prev=2024-05-15, Cierre=2024-08-01 (Vencido al corte C=2024-06-30, cierre posterior)
            DB::connection('escritor')->table('hito')->insert([
                'id_proyecto' => $idA,
                'descripcion' => 'A - Hito 2',
                'fecha_prevista' => '2024-05-15',
                'fecha_cierre' => '2024-08-01',
                'en_linea_base' => 1,
            ]);
            // Avance A: Plan=100, Gasto=80 al 2024-06-30
            DB::connection('escritor')->table('avance_presupuestal')->insert([
                'id_proyecto' => $idA,
                'fecha_reporte' => '2024-06-30',
                'presupuesto_planificado' => 100.00,
                'gasto_acumulado' => 80.00,
            ]);

            // Proyecto B: En curso, Monto 1000
            $idB = DB::connection('escritor')->table('proyecto')->insertGetId([
                'codigo_pgd' => '802',
                'nombre' => 'Proyecto B - Test Oráculo',
                'monto' => 1000.00,
                'anio_inicio' => 2024,
                'anio_fin' => 2025,
                'estado' => 'En curso',
                'id_objetivo' => $objetivo->id_objetivo,
                'id_responsable' => $admin->id_responsable,
                'fuente' => 'Recursos Propios',
            ]);

            // Hitos B
            // Hito 1: LB=1, Prev=2024-01-10, Cierre=2024-01-10 (Concluido)
            DB::connection('escritor')->table('hito')->insert([
                'id_proyecto' => $idB,
                'descripcion' => 'B - Hito 1',
                'fecha_prevista' => '2024-01-10',
                'fecha_cierre' => '2024-01-10',
                'en_linea_base' => 1,
            ]);
            // Hito 2: LB=1, Prev=2024-02-20, Cierre=2024-02-20 (Concluido)
            DB::connection('escritor')->table('hito')->insert([
                'id_proyecto' => $idB,
                'descripcion' => 'B - Hito 2',
                'fecha_prevista' => '2024-02-20',
                'fecha_cierre' => '2024-02-20',
                'en_linea_base' => 1,
            ]);
            // Hito 3: LB=1, Prev=2024-04-15, Cierre=2024-04-10 (Concluido anticipado)
            DB::connection('escritor')->table('hito')->insert([
                'id_proyecto' => $idB,
                'descripcion' => 'B - Hito 3',
                'fecha_prevista' => '2024-04-15',
                'fecha_cierre' => '2024-04-10',
                'en_linea_base' => 1,
            ]);
            // Hito 4: LB=1, Prev=2024-09-30, Cierre=NULL (En plazo, vence después de C)
            DB::connection('escritor')->table('hito')->insert([
                'id_proyecto' => $idB,
                'descripcion' => 'B - Hito 4',
                'fecha_prevista' => '2024-09-30',
                'fecha_cierre' => null,
                'en_linea_base' => 1,
            ]);
            // Hito 5: LB=0 (Fuera de LB), Prev=2024-02-01, Cierre=NULL (Vencido fuera de LB -> NO debe dar retraso a B)
            DB::connection('escritor')->table('hito')->insert([
                'id_proyecto' => $idB,
                'descripcion' => 'B - Hito 5 Fuera LB',
                'fecha_prevista' => '2024-02-01',
                'fecha_cierre' => null,
                'en_linea_base' => 0,
            ]);
            // Avance B: Plan=300, Gasto=330 al 2024-06-30
            DB::connection('escritor')->table('avance_presupuestal')->insert([
                'id_proyecto' => $idB,
                'fecha_reporte' => '2024-06-30',
                'presupuesto_planificado' => 300.00,
                'gasto_acumulado' => 330.00,
            ]);

            // Proyecto C: Pendiente, Monto 200, Sin LB, Sin avances
            DB::connection('escritor')->table('proyecto')->insert([
                'codigo_pgd' => '803',
                'nombre' => 'Proyecto C - Test Oráculo',
                'monto' => 200.00,
                'anio_inicio' => 2024,
                'anio_fin' => 2025,
                'estado' => 'Pendiente',
                'id_objetivo' => $objetivo->id_objetivo,
                'id_responsable' => $admin->id_responsable,
                'fuente' => 'Recursos Propios',
            ]);
        }, 'Sembrado de proyectos para el Oráculo de Indicadores');

        // Ejecutar cálculo aislado filtrando por el universo del oráculo (códigos 801, 802, 803)
        $resultado = $this->service->calcularCartera([
            'modo' => IndicadoresService::MODO_CORTE_COMUN,
            'fecha_corte' => '2024-06-30',
            'q' => 'Test Oráculo',
        ]);

        $resumen = $resultado['resumen'];

        // 1. Avance de Hitos: 4 / 6 = 66.6666...%
        $this->assertEquals(4, $resumen['avance_hitos']['numerador'], 'Numerador de hitos concluidos LB debe ser 4');
        $this->assertEquals(6, $resumen['avance_hitos']['denominador'], 'Denominador de hitos LB debe ser 6');
        $this->assertEqualsWithDelta(66.6667, $resumen['avance_hitos']['valor'], 0.01, 'Avance debe ser 66.67% con tolerancia <= 0.01 pp');
        $this->assertEquals('66.67 %', $resumen['avance_hitos']['valor_formateado']);
        $this->assertEquals('2 de 3', $resumen['avance_hitos']['cobertura_proyectos']);

        // 2. Proyectos con Retraso: 1 / 2 = 50.00%
        $this->assertEquals(1, $resumen['proyectos_retraso']['numerador'], 'Solo Proyecto A debe estar retrasado');
        $this->assertEquals(2, $resumen['proyectos_retraso']['denominador'], 'Denominador activo evaluable debe ser 2 (A y B)');
        $this->assertEqualsWithDelta(50.00, $resumen['proyectos_retraso']['valor'], 0.01, 'Retraso debe ser 50.00% con tolerancia <= 0.01 pp');
        $this->assertEquals('50.00 %', $resumen['proyectos_retraso']['valor_formateado']);

        // 3. Desviación Presupuestal: (410 - 400)/400 = +2.50%
        $this->assertEquals(400.00, $resumen['desviacion_presupuestal']['presupuesto_planificado']);
        $this->assertEquals(410.00, $resumen['desviacion_presupuestal']['gasto_acumulado']);
        $this->assertEqualsWithDelta(2.50, $resumen['desviacion_presupuestal']['valor'], 0.01, 'Desviación debe ser +2.50%');
        $this->assertEquals('+2.50 %', $resumen['desviacion_presupuestal']['valor_formateado']);
        $this->assertEquals('2 de 3', $resumen['desviacion_presupuestal']['cobertura_proyectos']);

        // 4. Conteo de Estados
        $this->assertEquals(2, $resumen['conteo_estados']['En curso']);
        $this->assertEquals(1, $resumen['conteo_estados']['Pendiente']);
        $this->assertEquals(0, $resumen['conteo_estados']['Cerrado']);
    }

    /**
     * 2. Borde Temporal: Vencimiento exactamente en fecha de corte (fecha_prevista == C)
     * NO se considera retraso ese mismo día.
     */
    public function test_vencimiento_exacto_en_dia_del_corte_no_cuenta_como_retraso(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $objetivo = ObjetivoGd::first();

        DatabaseTransactionService::run($admin->id_responsable, function () use ($admin, $objetivo) {
            $id = DB::connection('escritor')->table('proyecto')->insertGetId([
                'codigo_pgd' => '804',
                'nombre' => 'Proyecto Borde Vencimiento',
                'monto' => 300.00,
                'anio_inicio' => 2024,
                'anio_fin' => 2025,
                'estado' => 'En curso',
                'id_objetivo' => $objetivo->id_objetivo,
                'id_responsable' => $admin->id_responsable,
                'fuente' => 'Recursos Propios',
            ]);

            // Hito con fecha prevista exactamente el 2024-06-30
            DB::connection('escritor')->table('hito')->insert([
                'id_proyecto' => $id,
                'descripcion' => 'Hito que vence hoy',
                'fecha_prevista' => '2024-06-30',
                'fecha_cierre' => null,
                'en_linea_base' => 1,
            ]);
        }, 'Creación de proyecto borde');

        $resultado = $this->service->calcularCartera([
            'modo' => IndicadoresService::MODO_CORTE_COMUN,
            'fecha_corte' => '2024-06-30',
            'q' => 'Borde Vencimiento',
        ]);

        $proyecto = $resultado['proyectos'][0];
        $this->assertFalse($proyecto['hitos']['con_retraso'], 'Un hito que vence hoy no debe contar como vencido al corte.');
        $this->assertEquals(0, $proyecto['hitos']['vencidos_lb']);
        $this->assertEquals(0, $resultado['resumen']['proyectos_retraso']['numerador']);
    }

    /**
     * 3. Borde: Hito cerrado exactamente en fecha de corte (fecha_cierre == C)
     * Debe contarse como concluido al corte.
     */
    public function test_cierre_exacto_en_dia_del_corte_cuenta_como_concluido(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $objetivo = ObjetivoGd::first();

        DatabaseTransactionService::run($admin->id_responsable, function () use ($admin, $objetivo) {
            $id = DB::connection('escritor')->table('proyecto')->insertGetId([
                'codigo_pgd' => '804',
                'nombre' => 'Proyecto Cierre Exacto',
                'monto' => 300.00,
                'anio_inicio' => 2024,
                'anio_fin' => 2025,
                'estado' => 'En curso',
                'id_objetivo' => $objetivo->id_objetivo,
                'id_responsable' => $admin->id_responsable,
                'fuente' => 'Recursos Propios',
            ]);

            DB::connection('escritor')->table('hito')->insert([
                'id_proyecto' => $id,
                'descripcion' => 'Hito cerrado hoy',
                'fecha_prevista' => '2024-06-30',
                'fecha_cierre' => '2024-06-30',
                'en_linea_base' => 1,
            ]);
        }, 'Creación de proyecto cierre exacto');

        $resultado = $this->service->calcularCartera([
            'modo' => IndicadoresService::MODO_CORTE_COMUN,
            'fecha_corte' => '2024-06-30',
            'q' => 'Cierre Exacto',
        ]);

        $proyecto = $resultado['proyectos'][0];
        $this->assertEquals(1, $proyecto['hitos']['concluidos_lb']);
        $this->assertEquals(100.00, $proyecto['hitos']['avance_pct']);
        $this->assertEquals('100.00 %', $resultado['resumen']['avance_hitos']['valor_formateado']);
    }

    /**
     * 4. Borde: Denominador de presupuesto cero produce 'No aplica'.
     */
    public function test_denominador_presupuestal_cero_produce_no_aplica(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $objetivo = ObjetivoGd::first();

        DatabaseTransactionService::run($admin->id_responsable, function () use ($admin, $objetivo) {
            $id = DB::connection('escritor')->table('proyecto')->insertGetId([
                'codigo_pgd' => '804',
                'nombre' => 'Proyecto Presupuesto Cero',
                'monto' => 300.00,
                'anio_inicio' => 2024,
                'anio_fin' => 2025,
                'estado' => 'En curso',
                'id_objetivo' => $objetivo->id_objetivo,
                'id_responsable' => $admin->id_responsable,
                'fuente' => 'Recursos Propios',
            ]);

            DB::connection('escritor')->table('avance_presupuestal')->insert([
                'id_proyecto' => $id,
                'fecha_reporte' => '2024-06-30',
                'presupuesto_planificado' => 0.00,
                'gasto_acumulado' => 0.00,
            ]);
        }, 'Proyecto con corte 0.00');

        $resultado = $this->service->calcularCartera([
            'modo' => IndicadoresService::MODO_CORTE_COMUN,
            'fecha_corte' => '2024-06-30',
            'q' => 'Presupuesto Cero',
        ]);

        $this->assertEquals('No aplica', $resultado['resumen']['desviacion_presupuestal']['valor_formateado']);
        $this->assertEquals('NO_APLICA', $resultado['resumen']['desviacion_presupuestal']['estado']);
    }

    /**
     * 5. Cartera con filtro vacío produce 'Sin datos' y conteos 0.
     */
    public function test_cartera_filtro_vacio_produce_sin_datos(): void
    {
        $resultado = $this->service->calcularCartera([
            'q' => 'INEXISTENTE_XYZ_999',
        ]);

        $this->assertEquals(0, $resultado['resumen']['total_proyectos']);
        $this->assertEquals('Sin datos', $resultado['resumen']['avance_hitos']['valor_formateado']);
        $this->assertEquals('SIN_DATOS', $resultado['resumen']['avance_hitos']['estado']);
        $this->assertEquals('Sin datos', $resultado['resumen']['proyectos_retraso']['valor_formateado']);
        $this->assertEquals('Sin datos', $resultado['resumen']['desviacion_presupuestal']['valor_formateado']);
    }

    /**
     * 6. Modo ULTIMO_REPORTE_POR_PROYECTO toma el máximo corte de cada proyecto.
     */
    public function test_modo_ultimo_reporte_por_proyecto_toma_maximo_corte(): void
    {
        $resultado = $this->service->calcularCartera([
            'modo' => IndicadoresService::MODO_ULTIMO_REPORTE,
        ]);

        $this->assertEquals(IndicadoresService::MODO_ULTIMO_REPORTE, $resultado['contexto']['modo']);
        $this->assertNotNull($resultado['contexto']['fecha_minima_reporte']);
        $this->assertNotNull($resultado['contexto']['fecha_maxima_reporte']);
        $this->assertStringContainsString('Reportes efectivos entre', $resultado['contexto']['rango_fechas_texto']);
    }

    /**
     * 7. Verificación de cálculo sobre la cartera real canónica (10 proyectos, S/ 8,623,550.00).
     */
    public function test_calculo_cartera_canonica_completa(): void
    {
        $resultado = $this->service->calcularCartera([
            'modo' => IndicadoresService::MODO_CORTE_COMUN,
            'fecha_corte' => '2024-06-30',
        ]);

        $this->assertEquals(10, $resultado['resumen']['total_proyectos']);
        $this->assertEquals(8623550.00, $resultado['resumen']['monto_total_cartera']);
        $this->assertNotNull($resultado['resumen']['avance_hitos']['valor']);
        $this->assertNotNull($resultado['resumen']['proyectos_retraso']['valor']);
        $this->assertNotNull($resultado['resumen']['desviacion_presupuestal']['valor']);
    }

    /**
     * 8. Endpoint de consulta JSON protegido /indicadores/consulta.
     */
    public function test_endpoint_indicadores_consulta_requiere_autenticacion_y_responde_json(): void
    {
        // 1. Invitado es redirigido a login
        $this->get('/indicadores/consulta')->assertRedirect('/login');

        // 2. Usuario autenticado recibe 200 con payload canónico
        $user = Responsable::where('usuario', 'cgarcia')->first();
        $this->actingAs($user);

        $response = $this->getJson('/indicadores/consulta?modo=CORTE_COMUN&fecha_corte=2024-06-30');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'contexto' => ['modo', 'fecha_corte', 'origen', 'revision_datos', 'filtros'],
            'resumen' => [
                'total_proyectos',
                'conteo_estados',
                'avance_hitos' => ['valor', 'valor_formateado', 'numerador', 'denominador'],
                'proyectos_retraso' => ['valor', 'valor_formateado', 'numerador', 'denominador'],
                'desviacion_presupuestal' => ['valor', 'valor_formateado', 'presupuesto_planificado', 'gasto_acumulado'],
            ],
            'proyectos',
        ]);
    }
}
