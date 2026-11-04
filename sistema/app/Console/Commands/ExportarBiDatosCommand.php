<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ExportarBiDatosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bi:exportar-datos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exporta las tablas dimensionales y hechos de dw_seguimiento_gd a CSV para Power BI';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Iniciando exportación de datos de dw_seguimiento_gd para Power BI...');

        $destino = base_path('../docs/bi/datos');
        if (!File::exists($destino)) {
            File::makeDirectory($destino, 0755, true);
        }

        $tablas = [
            'dim_tiempo' => 'dim_tiempo.csv',
            'dim_proyecto' => 'dim_proyecto.csv',
            'dim_objetivo' => 'dim_objetivo.csv',
            'dim_responsable' => 'dim_responsable.csv',
            'fact_avance_proyecto' => 'fact_avance_proyecto.csv',
            'fact_hito' => 'fact_hito.csv',
            'v_alertas' => 'v_alertas.csv',
            'v_calidad_datos' => 'v_calidad_datos.csv',
        ];

        $conteos = [];

        foreach ($tablas as $tabla => $archivo) {
            $rutaCompleta = $destino . '/' . $archivo;
            $filas = DB::connection('dw_lector')->table($tabla)->get();
            $conteos[$tabla] = $filas->count();

            $fp = fopen($rutaCompleta, 'w');
            // BOM UTF-8 para apertura en Excel/Power BI
            fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

            if ($filas->isNotEmpty()) {
                // Cabecera
                $primera = (array) $filas->first();
                fputcsv($fp, array_keys($primera));

                // Filas
                foreach ($filas as $fila) {
                    fputcsv($fp, (array) $fila);
                }
            }

            fclose($fp);
            $this->line("- {$tabla}: {$conteos[$tabla]} filas -> {$archivo}");
        }

        // Obtener metadatos de la última carga ETL
        $ultimoEtl = DB::connection('dw_lector')
            ->table('etl_control')
            ->orderByDesc('id_carga')
            ->first();

        $manifiesto = [
            'sistema' => 'SIG Electrocentro S. A.',
            'base_datos' => 'dw_seguimiento_gd',
            'cuenta_lectura' => 'bi_lector',
            'fecha_extraccion' => Carbon::now('America/Lima')->format('Y-m-d H:i:s'),
            'id_carga_etl' => $ultimoEtl->id_carga ?? 1,
            'revision_origen' => $ultimoEtl->revision_origen ?? 1,
            'estado_carga_etl' => $ultimoEtl->estado ?? 'Desconocido',
            'tablas_exportadas' => $conteos,
            'compatibilidad_power_bi' => [
                'formato' => 'CSV UTF-8 con BOM',
                'separador' => 'Coma (,)',
                'lineas' => 'CRLF',
            ],
        ];

        File::put($destino . '/manifiesto_extraccion.json', json_encode($manifiesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("Manifiesto generado en {$destino}/manifiesto_extraccion.json");

        return Command::SUCCESS;
    }
}
