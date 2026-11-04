<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PowerBiPreparacionTest extends TestCase
{
    /**
     * 1. Comprueba la existencia y validez de los archivos CSV y el manifiesto en docs/bi/datos/.
     */
    public function test_archivos_de_datos_bi_y_manifiesto_existen_y_tienen_bom(): void
    {
        $dir = base_path('../docs/bi/datos');
        $this->assertTrue(File::isDirectory($dir), 'El directorio docs/bi/datos debe existir.');

        $archivosRequeridos = [
            'dim_tiempo.csv',
            'dim_proyecto.csv',
            'dim_objetivo.csv',
            'dim_responsable.csv',
            'fact_avance_proyecto.csv',
            'fact_hito.csv',
            'v_alertas.csv',
            'v_calidad_datos.csv',
            'manifiesto_extraccion.json',
        ];

        foreach ($archivosRequeridos as $archivo) {
            $ruta = $dir . '/' . $archivo;
            $this->assertTrue(File::exists($ruta), "El archivo {$archivo} debe existir.");
            $this->assertGreaterThan(0, File::size($ruta), "El archivo {$archivo} no debe estar vacío.");

            if (str_ends_with($archivo, '.csv')) {
                $contenido = File::get($ruta);
                $this->assertStringStartsWith("\xEF\xBB\xBF", $contenido, "El archivo {$archivo} debe iniciar con BOM UTF-8.");
            }
        }

        // Validar estructura del manifiesto JSON
        $manifiesto = json_decode(File::get($dir . '/manifiesto_extraccion.json'), true);
        $this->assertEquals('SIG Electrocentro S. A.', $manifiesto['sistema']);
        $this->assertEquals('dw_seguimiento_gd', $manifiesto['base_datos']);
        $this->assertEquals(10, $manifiesto['tablas_exportadas']['dim_proyecto']);
        $this->assertEquals(24, $manifiesto['tablas_exportadas']['fact_avance_proyecto']);
        $this->assertEquals(26, $manifiesto['tablas_exportadas']['fact_hito']);
    }

    /**
     * 2. Comprueba la existencia de los documentos de arquitectura y DAX de Power BI.
     */
    public function test_documentos_de_modelo_dax_y_guia_power_bi_existen(): void
    {
        $baseBi = base_path('../docs/bi');

        $this->assertTrue(File::exists($baseBi . '/modelo_power_bi.md'), 'modelo_power_bi.md debe existir.');
        $this->assertTrue(File::exists($baseBi . '/medidas_DAX.txt'), 'medidas_DAX.txt debe existir.');
        $this->assertTrue(File::exists($baseBi . '/guia_power_bi.md'), 'guia_power_bi.md debe existir.');

        $dax = File::get($baseBi . '/medidas_DAX.txt');
        $this->assertStringContainsString('KPI 1 Avance Hitos LB %', $dax);
        $this->assertStringContainsString('KPI 2 Proyectos Retraso %', $dax);
        $this->assertStringContainsString('KPI 3 Desviación Presupuestal %', $dax);
        $this->assertStringContainsString('DIVIDE', $dax);

        $guia = File::get($baseBi . '/guia_power_bi.md');
        $this->assertStringContainsString('Tabla de Conciliación DAX vs SQL', $guia);
        $this->assertStringContainsString('CONCILIADO', $guia);
    }
}
