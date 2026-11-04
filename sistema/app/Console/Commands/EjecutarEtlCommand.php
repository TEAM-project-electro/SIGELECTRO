<?php

namespace App\Console\Commands;

use App\Services\EtlService;
use Illuminate\Console\Command;

class EjecutarEtlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'etl:cargar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta el procedimiento sp_etl_cargar en dw_seguimiento_gd usando la cuenta sig_etl';

    /**
     * Execute the console command.
     */
    public function handle(EtlService $etlService): int
    {
        $this->info('Iniciando proceso ETL hacia dw_seguimiento_gd...');

        $resultado = $etlService->ejecutar();

        if ($resultado['exito']) {
            $this->info("ETL completado exitosamente (Carga #{$resultado['id_carga']}).");
            $this->line("- Filas avance cargadas: {$resultado['filas_avance']}");
            $this->line("- Filas hito cargadas: {$resultado['filas_hito']}");
            $this->line("- Revisión de origen sincronizada: {$resultado['revision_origen']}");
            return Command::SUCCESS;
        }

        $this->error("Fallo en la ejecución del ETL: {$resultado['mensaje']}");
        return Command::FAILURE;
    }
}
