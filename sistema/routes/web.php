<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/salud', function () {
    $dbStatus = 'desconectado';
    $dbVersion = null;
    $dbPort = null;
    $dbSchema = null;

    try {
        $result = DB::connection('mysql')->select('SELECT VERSION() as v, @@port as p, DATABASE() as d');
        if (!empty($result)) {
            $dbStatus = 'conectado';
            $dbVersion = $result[0]->v;
            $dbPort = (int) $result[0]->p;
            $dbSchema = $result[0]->d;
        }
    } catch (\Throwable $e) {
        $dbStatus = 'error: ' . $e->getMessage();
    }

    $manifestPath = public_path('build/manifest.json');
    $assetsCompiled = file_exists($manifestPath);

    $isReady = ($dbStatus === 'conectado' && $assetsCompiled && str_contains($dbVersion, 'MariaDB'));

    return response()->json([
        'estado' => $isReady ? 'OPERATIVO' : 'DEGRADADO',
        'sistema' => 'SIG Electrocentro S. A. - Prototipo Académico',
        'entorno' => config('app.env'),
        'timezone' => config('app.timezone'),
        'locale' => config('app.locale'),
        'php_version' => PHP_VERSION,
        'framework' => 'Laravel ' . app()->version(),
        'base_datos' => [
            'estado' => $dbStatus,
            'motor' => $dbVersion,
            'puerto' => $dbPort,
            'esquema' => $dbSchema,
            'referencia_10_11' => str_contains($dbVersion, '10.11'),
        ],
        'activos_compilados' => $assetsCompiled,
    ], $isReady ? 200 : 503);
});
