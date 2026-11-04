<?php

namespace Tests\Feature;

use App\Models\Responsable;
use Tests\TestCase;

class TableroGerencialTest extends TestCase
{
    /**
     * 1. Control de acceso: requiere autenticación y permite acceso a los 3 perfiles.
     */
    public function test_control_de_acceso_al_tablero_para_todos_los_perfiles(): void
    {
        // 1. Invitado redirigido
        $this->get('/tablero')->assertRedirect('/login');

        $admin = Responsable::where('usuario', 'msalas')->first();
        $resp = Responsable::where('usuario', 'aquispe')->first();
        $consulta = Responsable::where('usuario', 'cgarcia')->first();

        // 2. Administrador
        $this->actingAs($admin)->get('/tablero')->assertStatus(200);

        // 3. Responsable
        $this->actingAs($resp)->get('/tablero')->assertStatus(200);

        // 4. Consulta
        $this->actingAs($consulta)->get('/tablero')->assertStatus(200);
    }

    /**
     * 2. El tablero muestra los cuatro indicadores canónicos y el distintivo demostrativo.
     */
    public function test_tablero_renderiza_cuatro_kpis_canonicos(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $this->actingAs($admin);

        $response = $this->get('/tablero?modo=CORTE_COMUN&fecha_corte=2024-06-30');
        $response->assertStatus(200);

        // 1. Distintivo obligatorio de datos demostrativos
        $response->assertSee('DATOS DEMOSTRATIVOS');
        $response->assertSee('Tablero Gerencial de Seguimiento PGD');

        // 2. Los cuatro indicadores
        $response->assertSee('Avance de Hitos (LB)');
        $response->assertSee('Proyectos con Retraso');
        $response->assertSee('Desviación Presupuestal');
        $response->assertSee('Proyectos por Estado');

        // 3. Tabla de proyectos
        $response->assertSee('Detalle de Proyectos en el Corte Evaluado');
    }

    /**
     * 3. Selector de modalidad temporal funciona tanto en CORTE_COMUN como en ULTIMO_REPORTE.
     */
    public function test_modalidades_temporales_en_tablero(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $this->actingAs($admin);

        // 1. Modo Corte Común
        $resComun = $this->get('/tablero?modo=CORTE_COMUN&fecha_corte=2024-06-30');
        $resComun->assertStatus(200);
        $resComun->assertSee('Fecha común: 2024-06-30');

        // 2. Modo Último Reporte por Proyecto
        $resUltimo = $this->get('/tablero?modo=ULTIMO_REPORTE_POR_PROYECTO');
        $resUltimo->assertStatus(200);
        $resUltimo->assertSee('Reportes efectivos entre');
    }

    /**
     * 4. Origen de datos intercambiable entre Base Operativa y Data Warehouse.
     */
    public function test_intercambio_de_origen_operativo_y_dw(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $this->actingAs($admin);

        // 1. Operativo
        $resOp = $this->get('/tablero?origen=OPERATIVO');
        $resOp->assertStatus(200);
        $resOp->assertSee('Base Operativa en Vivo');

        // 2. Data Warehouse
        $resDw = $this->get('/tablero?origen=DW');
        $resDw->assertStatus(200);
        $resDw->assertSee('Data Mart Analítico');
    }

    /**
     * 5. Filtros de búsqueda en la cartera del tablero.
     */
    public function test_filtros_de_busqueda_y_estado_vacio(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $this->actingAs($admin);

        // 1. Filtro por estado En curso
        $resEstado = $this->get('/tablero?estado=En+curso');
        $resEstado->assertStatus(200);
        $resEstado->assertSee('En curso');

        // 2. Filtro vacío por búsqueda inexistente
        $resVacio = $this->get('/tablero?q=TEXTO_INEXISTENTE_XYZ');
        $resVacio->assertStatus(200);
        $resVacio->assertSee('No se encontraron proyectos');
    }

    /**
     * 6. Medición de rendimiento: el tablero responde en menos del umbral de 3 segundos.
     */
    public function test_tiempo_de_respuesta_tablero_menor_a_tres_segundos(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $this->actingAs($admin);

        $inicio = microtime(true);
        $response = $this->get('/tablero');
        $duracion = microtime(true) - $inicio;

        $response->assertStatus(200);
        $this->assertLessThan(3.0, $duracion, "El tiempo de carga ({$duracion}s) debe ser inferior a 3.0 segundos.");
    }
}
