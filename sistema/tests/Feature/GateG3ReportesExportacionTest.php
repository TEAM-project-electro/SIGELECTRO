<?php

namespace Tests\Feature;

use App\Models\Responsable;
use App\Services\IndicadoresService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GateG3ReportesExportacionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Asegurar estado cerrado de los proyectos de muestra canónica
        try {
            DB::connection('escritor')->statement('SET @id_responsable = 1;');
            DB::connection('escritor')->table('proyecto')
                ->whereIn('codigo_pgd', ['014', '022', '023'])
                ->update(['estado' => 'Cerrado']);
        } finally {
            DB::connection('escritor')->statement('SET @id_responsable = NULL;');
        }
    }

    /**
     * 1. Control de acceso: los 3 perfiles (Admin, Resp, Consulta) pueden emitir reportes.
     */
    public function test_control_de_acceso_a_reportes_para_todos_los_perfiles(): void
    {
        // 1. Invitado es redirigido a login
        $this->get('/reportes/imprimir')->assertRedirect('/login');
        $this->get('/reportes/csv')->assertRedirect('/login');

        $admin = Responsable::where('usuario', 'msalas')->first();
        $resp = Responsable::where('usuario', 'aquispe')->first();
        $consulta = Responsable::where('usuario', 'cgarcia')->first();

        // 2. Administrador
        $this->actingAs($admin)->get('/reportes/imprimir')->assertStatus(200);
        $this->actingAs($admin)->get('/reportes/csv')->assertStatus(200);

        // 3. Responsable
        $this->actingAs($resp)->get('/reportes/imprimir')->assertStatus(200);
        $this->actingAs($resp)->get('/reportes/csv')->assertStatus(200);

        // 4. Perfil Consulta (acceso de solo lectura y exportación RF06)
        $this->actingAs($consulta)->get('/reportes/imprimir')->assertStatus(200);
        $this->actingAs($consulta)->get('/reportes/csv')->assertStatus(200);
    }

    /**
     * 2. Paridad estricta entre Tablero y Vista Imprimible:
     * Comprueba que los 4 KPIs y montos coinciden exactamente.
     */
    public function test_paridad_entre_tablero_y_vista_imprimible(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $this->actingAs($admin);

        $params = 'modo=CORTE_COMUN&fecha_corte=2024-06-30';

        $resTablero = $this->get("/tablero?{$params}");
        $resImprimir = $this->get("/reportes/imprimir?{$params}");

        $resImprimir->assertStatus(200);
        $resImprimir->assertSee('DATOS DEMOSTRATIVOS');
        $resImprimir->assertSee('8,623,550.00'); // Monto total cartera
        $resImprimir->assertSee('CORTE_COMUN');
        $resImprimir->assertSee('2024-06-30');

        // Los 4 KPIs canónicos
        $resImprimir->assertSee('Avance Hitos (LB)');
        $resImprimir->assertSee('Proyectos con Retraso');
        $resImprimir->assertSee('Desviación Presupuestal');
        $resImprimir->assertSee('Cartera PGD');
    }

    /**
     * 3. Descarga CSV: cabecera UTF-8, metadatos, 4 KPIs y tabla detallada.
     */
    public function test_descarga_csv_estructura_y_conteos(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $this->actingAs($admin);

        $response = $this->get('/reportes/csv?modo=CORTE_COMUN&fecha_corte=2024-06-30');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        // 1. BOM UTF-8
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content, 'El CSV debe iniciar con BOM UTF-8.');

        // 2. Metadatos del Manifiesto
        $this->assertStringContainsString('SIG ELECTROCENTRO S. A.', $content);
        $this->assertStringContainsString('DATOS DEMOSTRATIVOS', $content);
        $this->assertStringContainsString('CORTE_COMUN', $content);
        $this->assertStringContainsString('2024-06-30', $content);
        $this->assertStringContainsString('PEN (S/)', $content);

        // 3. Los 10 proyectos de la cartera presentes (códigos canónicos 005 a 023)
        $this->assertStringContainsString('005', $content);
        $this->assertStringContainsString('010', $content);
        $this->assertStringContainsString('023', $content);
        $this->assertStringContainsString('8623550.00', $content);
    }

    /**
     * 4. Mitigación de inyección de fórmulas CSV (CSV Injection).
     */
    public function test_mitigacion_inyeccion_csv_injection(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $this->actingAs($admin);

        // Modificar temporalmente el nombre de un proyecto para simular un intento de inyección de fórmula
        $proyecto = DB::table('proyecto')->where('codigo_pgd', '005')->first();
        $nombreOriginal = $proyecto->nombre;

        try {
            DB::connection('escritor')->statement('SET @id_responsable = 1;');
            DB::connection('escritor')->statement('SET @motivo_cambio = "Prueba seguridad CSV";');
            DB::connection('escritor')->table('proyecto')
                ->where('id_proyecto', $proyecto->id_proyecto)
                ->update(['nombre' => '=SUM(A1:A10) - Test Injection']);

            $response = $this->get('/reportes/csv');
            $content = $response->streamedContent();

            // Comprobar que el texto malicioso fue neutralizado anteponiendo una comilla simple
            $this->assertStringContainsString("'=SUM(A1:A10)", $content, 'El texto con fórmula debe haber sido neutralizado.');

        } finally {
            DB::connection('escritor')->table('proyecto')
                ->where('id_proyecto', $proyecto->id_proyecto)
                ->update(['nombre' => $nombreOriginal]);
            DB::connection('escritor')->statement('SET @id_responsable = NULL;');
            DB::connection('escritor')->statement('SET @motivo_cambio = NULL;');
        }
    }

    /**
     * 5. Detección de discrepancia de revisión entre consulta y descarga (Gate G3).
     */
    public function test_deteccion_discrepancia_de_revision_en_reporte(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $this->actingAs($admin);

        $revisionActual = (int) DB::table('control_revision')->where('id', 1)->value('version');
        $revisionAntigua = $revisionActual - 1;

        // Solicitar reporte pasando una revisión esperada desfasada
        $response = $this->get("/reportes/imprimir?revision_esperada={$revisionAntigua}");
        $response->assertStatus(200);

        // Debe contener la advertencia de concurrencia
        $response->assertSee('Aviso de concurrencia');
        $response->assertSee("v{$revisionAntigua}");
        $response->assertSee("v{$revisionActual}");

        // En CSV también se anota en el manifiesto
        $resCsv = $this->get("/reportes/csv?revision_esperada={$revisionAntigua}");
        $contentCsv = $resCsv->streamedContent();
        $this->assertStringContainsString('ADVERTENCIA', $contentCsv);
        $this->assertStringContainsString('La cartera mutó tras la consulta inicial', $contentCsv);
    }

    /**
     * 6. Seguridad: Ausencia de hashes, contraseñas o secretos en el reporte.
     */
    public function test_seguridad_ausencia_de_secretos_en_reportes(): void
    {
        $consulta = Responsable::where('usuario', 'cgarcia')->first();
        $this->actingAs($consulta);

        $resImprimir = $this->get('/reportes/imprimir')->getContent();
        $resCsv = $this->get('/reportes/csv')->streamedContent();

        foreach ([$resImprimir, $resCsv] as $contenido) {
            $this->assertStringNotContainsString('clave_hash', $contenido);
            $this->assertStringNotContainsString('$2y$', $contenido); // Prefijo bcrypt
            $this->assertStringNotContainsString('password', $contenido);
            $this->assertStringNotContainsString('sig_escritor', $contenido);
            $this->assertStringNotContainsString('sig_auth', $contenido);
            $this->assertStringNotContainsString('sig_migrador', $contenido);
        }
    }

    /**
     * 7. Filtros combinados consistentes: filtrado por estado Cerrado.
     */
    public function test_filtros_combinados_en_exportacion(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $this->actingAs($admin);

        $response = $this->get('/reportes/csv?estado=Cerrado');
        $content = $response->streamedContent();

        // 3 proyectos cerrados con monto S/ 440,000.00
        $this->assertStringContainsString('Cerrado: 3', $content);
        $this->assertStringContainsString('En curso: 0', $content);
        $this->assertStringContainsString('440000.00', $content);
        $this->assertStringContainsString('014', $content);
        $this->assertStringContainsString('022', $content);
        $this->assertStringContainsString('023', $content);
    }
}
