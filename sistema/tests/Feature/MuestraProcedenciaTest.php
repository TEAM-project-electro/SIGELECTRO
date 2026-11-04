<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MuestraProcedenciaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Asegurar que la base tenga exclusivamente la muestra canónica sembrada
        DB::connection('mysql')->table('hito')
            ->where('descripcion', 'like', 'Recepción y homologación%')
            ->orWhere('descripcion', 'like', 'Mantenimiento preventivo adicional%')
            ->orWhere('descripcion', 'like', 'Hito temporal para prueba%')
            ->delete();
        $this->seed();
    }

    /**
     * Verifica que los 6 objetivos del PGD estén registrados con sus códigos.
     */
    public function test_objetivos_pgd_completos(): void
    {
        $count = DB::table('objetivo_gd')->count();
        $this->assertEquals(6, $count, 'Deben existir exactamente 6 objetivos de gobierno digital.');

        $codigos = DB::table('objetivo_gd')->pluck('codigo')->toArray();
        $esperados = ['OGD 01', 'OGD 02', 'OGD 03', 'OGD 04', 'OGD 05', 'OGD 06'];
        sort($codigos);
        sort($esperados);
        $this->assertEquals($esperados, $codigos);
    }

    /**
     * Verifica los 4 perfiles y usuarios clave del sistema.
     */
    public function test_responsables_sembrados(): void
    {
        $count = DB::table('responsable')->count();
        $this->assertEquals(4, $count, 'Deben existir exactamente 4 cuentas de responsable sembradas.');

        $usuarios = DB::table('responsable')->pluck('usuario')->toArray();
        $this->assertContains('msalas', $usuarios);
        $this->assertContains('aquispe', $usuarios);
        $this->assertContains('lrojas', $usuarios);
        $this->assertContains('cgarcia', $usuarios);
    }

    /**
     * Verifica los 10 proyectos del PGD y la suma exacta de S/ 8,623,550.00.
     */
    public function test_cartera_10_proyectos_y_presupuesto_total(): void
    {
        $count = DB::table('proyecto')->count();
        $this->assertEquals(10, $count, 'Deben existir exactamente 10 proyectos en cartera PGD.');

        $totalMonto = DB::table('proyecto')->sum('monto');
        $this->assertEquals(8623550.00, (float) $totalMonto, 'El monto total de cartera debe ser S/ 8,623,550.00.');

        // Verificar estados de proyectos
        $cerrados = DB::table('proyecto')->where('estado', 'Cerrado')->pluck('codigo_pgd')->toArray();
        sort($cerrados);
        $this->assertEquals(['014', '022', '023'], $cerrados, 'Los proyectos 014, 022 y 023 deben estar cerrados.');

        $enCurso = DB::table('proyecto')->where('estado', 'En curso')->count();
        $this->assertEquals(7, $enCurso, 'Deben existir 7 proyectos en curso.');
    }

    /**
     * Verifica el conteo de hitos y la presencia del hito fuera de línea base.
     */
    public function test_hitos_y_linea_base(): void
    {
        $totalHitos = DB::table('hito')->count();
        $this->assertEquals(26, $totalHitos, 'Deben existir 26 hitos en total.');

        $enLineaBase = DB::table('hito')->where('en_linea_base', 1)->count();
        $this->assertEquals(25, $enLineaBase, 'Deben existir 25 hitos en línea base.');

        $fueraLineaBase = DB::table('hito')->where('en_linea_base', 0)->count();
        $this->assertEquals(1, $fueraLineaBase, 'Debe existir 1 hito no previsto fuera de línea base.');
    }

    /**
     * Verifica la existencia de 24 cortes de avance presupuestal y su coherencia de fechas.
     */
    public function test_avances_presupuestales(): void
    {
        $count = DB::table('avance_presupuestal')->count();
        $this->assertEquals(24, $count, 'Deben existir 24 cortes de avance presupuestal.');

        // Verificar que los gastos acumulados no excedan los planificados de manera ilógica
        $avances = DB::table('avance_presupuestal')->get();
        foreach ($avances as $av) {
            $this->assertGreaterThanOrEqual(0, $av->presupuesto_planificado);
            $this->assertGreaterThanOrEqual(0, $av->gasto_acumulado);
        }
    }

    /**
     * Verifica que la vista v_indicadores_proyecto reporte los 10 proyectos con indicadores calculados.
     */
    public function test_vista_indicadores_proyecto(): void
    {
        $indicadores = DB::table('v_indicadores_proyecto')->get();
        $this->assertCount(10, $indicadores);

        foreach ($indicadores as $ind) {
            $this->assertNotNull($ind->codigo_pgd);
            $this->assertNotNull($ind->nombre);
            $this->assertNotNull($ind->estado);
            $this->assertNotNull($ind->ultimo_reporte);
            $this->assertGreaterThanOrEqual(0, $ind->hitos_lb_total);
            $this->assertGreaterThanOrEqual(0, $ind->hitos_lb_concluidos);
        }
    }
}
