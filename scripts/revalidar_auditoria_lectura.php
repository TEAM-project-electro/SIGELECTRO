<?php
// Evidencias de solo lectura para la segunda auditoría. No imprime secretos.
require __DIR__.'/../sistema/vendor/autoload.php';
$app = require __DIR__.'/../sistema/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
function emitir($dato): void { echo json_encode($dato, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL; }
emitir(['entorno'=>app()->environment(), 'default'=>DB::getDefaultConnection()]);
foreach (['mysql','lector','auth','escritor','dw_migrador','dw_etl','dw_lector'] as $nombre) {
    emitir(['conexion'=>$nombre, 'identidad'=>DB::connection($nombre)->selectOne('SELECT DATABASE() esquema, CURRENT_USER() cuenta, VERSION() motor')]);
}
$db = DB::connection('mysql');
try {
    foreach ($db->select("SHOW GRANTS FOR 'sig_definer'@'localhost'") as $fila) {
        foreach ((array)$fila as $grant) emitir(['grants'=>'definer','valor'=>preg_replace('/ IDENTIFIED .*/i', ' [omitido]', $grant)]);
    }
} catch (Throwable $e) {
    emitir(['grants'=>'definer','estado'=>'No accesible para la cuenta auditora','codigo'=>$e->getCode()]);
}
emitir(['triggers'=>$db->select('SELECT TRIGGER_SCHEMA, DEFINER, COUNT(*) cantidad FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA LIKE ? GROUP BY TRIGGER_SCHEMA, DEFINER', ['seguimiento_gd%'])]);
foreach (['dw_seguimiento_gd','dw_seguimiento_gd_test','dw_seguimiento_gd_restaurada'] as $esquema) {
    $rutina = $db->selectOne('SELECT ROUTINE_DEFINITION cuerpo, DEFINER FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = ? AND ROUTINE_NAME = ?', [$esquema,'sp_etl_cargar']);
    preg_match_all('/(?:FROM|JOIN)\s+`?(seguimiento_gd\w*)`?\./i', $rutina->cuerpo ?? '', $coincidencias);
    emitir(['rutina'=>$esquema, 'definer'=>$rutina->DEFINER ?? null, 'fuentes'=>array_values(array_unique($coincidencias[1]))]);
}
foreach (['auth','lector','escritor'] as $nombre) {
    foreach (DB::connection($nombre)->select('SHOW GRANTS') as $fila) {
        foreach ((array)$fila as $grant) emitir(['grants'=>$nombre,'valor'=>preg_replace('/ IDENTIFIED .*/i', ' [omitido]', $grant)]);
    }
}
emitir(['muestra'=>$db->selectOne('SELECT COUNT(*) proyectos, SUM(monto) monto FROM proyecto')]);
$fechas = DB::connection('lector')->table('avance_presupuestal')->distinct()->orderBy('fecha_reporte')->pluck('fecha_reporte')->all();
foreach ([App\Services\IndicadoresService::MODO_CORTE_COMUN, App\Services\IndicadoresService::MODO_ULTIMO_REPORTE] as $modo) {
    foreach (array_merge($fechas, ['2024-05-15']) as $fecha) {
        $filtros = ['modo'=>$modo, 'fecha_corte'=>$fecha];
        $op = app(App\Services\IndicadoresService::class)->calcularCartera($filtros);
        $dw = app(App\Services\EtlService::class)->consultarDw($filtros);
        $resumir = fn($datos) => ['n'=>$datos['resumen']['total_proyectos'],'avance'=>$datos['resumen']['avance_hitos']['valor'],'retraso'=>$datos['resumen']['proyectos_retraso']['valor'],'plan'=>$datos['resumen']['desviacion_presupuestal']['presupuesto_planificado'],'gasto'=>$datos['resumen']['desviacion_presupuestal']['gasto_acumulado']];
        emitir(['filtros'=>$filtros,'operativo'=>$resumir($op),'dw'=>$resumir($dw)]);
    }
}
