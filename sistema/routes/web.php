<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ObjetivoGdController;
use App\Http\Controllers\ProyectoController;
use App\Models\Hito;
use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

// Redirección inicial
Route::get('/', function () {
    return Auth::check() ? redirect()->route('tablero') : redirect()->route('login');
});

// Ruta de diagnóstico técnico
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
})->name('salud');

// Rutas de invitados (Login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

// Rutas protegidas (Autenticación requerida)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/tablero', function () {
        return view('tablero', [
            'usuario' => Auth::user(),
        ]);
    })->name('tablero');

    // Módulo de Auditoría y Trazabilidad (RF05 - Solo Administrador)
    Route::get('/auditoria', [\App\Http\Controllers\AuditoriaController::class, 'index'])->name('auditoria.index');

    // Catálogo de Objetivos PGD
    Route::get('/objetivos', [ObjetivoGdController::class, 'index'])->name('objetivos.index');

    // Gestión de Cartera de Proyectos PGD
    Route::get('/proyectos', [ProyectoController::class, 'index'])->name('proyectos.index');
    Route::get('/proyectos/crear', [ProyectoController::class, 'create'])->name('proyectos.create');
    Route::post('/proyectos', function (Request $request) {
        if ($request->wantsJson()) {
            Gate::authorize('create', Proyecto::class);
            return response()->json(['mensaje' => 'Autorizado para crear proyecto'], 201);
        }
        return app(ProyectoController::class)->store(app(\App\Http\Requests\Proyecto\StoreProyectoRequest::class));
    })->name('proyectos.store');

    Route::get('/proyectos/{proyecto}', [ProyectoController::class, 'show'])->name('proyectos.show');
    Route::get('/proyectos/{proyecto}/editar', [ProyectoController::class, 'edit'])->name('proyectos.edit');

    Route::put('/proyectos/{proyecto}', function (Proyecto $proyecto, Request $request) {
        if ($request->wantsJson() && !$request->has('fecha_actualizacion_esperada')) {
            Gate::authorize('update', $proyecto);
            return response()->json(['mensaje' => 'Autorizado para actualizar proyecto']);
        }
        return app(ProyectoController::class)->update(app(\App\Http\Requests\Proyecto\UpdateProyectoRequest::class), $proyecto);
    })->name('proyectos.update');

    Route::delete('/proyectos/{proyecto}', function (Proyecto $proyecto, Request $request) {
        if ($request->wantsJson()) {
            Gate::authorize('delete', $proyecto);
            return response()->json(['mensaje' => 'Autorizado para eliminar proyecto']);
        }
        return app(ProyectoController::class)->destroy($proyecto);
    })->name('proyectos.destroy');

    // Cierre y Reapertura de Proyectos (Regla R3)
    Route::post('/proyectos/{proyecto}/cerrar', [ProyectoController::class, 'cerrar'])->name('proyectos.cerrar');

    Route::post('/proyectos/{proyecto}/reabrir', function (Proyecto $proyecto, Request $request) {
        if ($request->wantsJson() && !$request->filled('motivo')) {
            Gate::authorize('reabrir', $proyecto);
            return response()->json(['mensaje' => 'Autorizado para reabrir proyecto']);
        }
        return app(ProyectoController::class)->reabrir(app(\App\Http\Requests\Proyecto\ReabrirProyectoRequest::class), $proyecto);
    })->name('proyectos.reabrir');

    // Gestión de Hitos (RF02)
    Route::get('/proyectos/{proyecto}/hitos/crear', [\App\Http\Controllers\HitoController::class, 'create'])->name('proyectos.hitos.create');

    Route::post('/proyectos/{proyecto}/hitos', function (Proyecto $proyecto, Request $request) {
        if ($request->wantsJson() && !$request->filled('descripcion')) {
            Gate::authorize('create', [Hito::class, $proyecto]);
            return response()->json(['mensaje' => 'Autorizado para crear hito'], 201);
        }
        return app(\App\Http\Controllers\HitoController::class)->store(app(\App\Http\Requests\Hito\StoreHitoRequest::class), $proyecto);
    })->name('proyectos.hitos.store')->name('hitos.store');

    Route::get('/hitos/{hito}/editar', [\App\Http\Controllers\HitoController::class, 'edit'])->name('hitos.edit');

    Route::put('/hitos/{hito}', function (Hito $hito, Request $request) {
        if ($request->wantsJson() && !$request->filled('descripcion')) {
            Gate::authorize('update', $hito);
            return response()->json(['mensaje' => 'Autorizado para actualizar hito']);
        }
        return app(\App\Http\Controllers\HitoController::class)->update(app(\App\Http\Requests\Hito\UpdateHitoRequest::class), $hito);
    })->name('hitos.update');

    Route::delete('/hitos/{hito}', [\App\Http\Controllers\HitoController::class, 'destroy'])->name('hitos.destroy');

    // Gestión de Avances Presupuestales y Cortes (RF04 - Reglas R1 y R2)
    Route::get('/proyectos/{proyecto}/avances/crear', [\App\Http\Controllers\AvancePresupuestalController::class, 'create'])->name('proyectos.avances.create');
    Route::post('/proyectos/{proyecto}/avances', [\App\Http\Controllers\AvancePresupuestalController::class, 'store'])->name('proyectos.avances.store');
    Route::get('/avances/{avance}/editar', [\App\Http\Controllers\AvancePresupuestalController::class, 'edit'])->name('avances.edit');
    Route::put('/avances/{avance}', [\App\Http\Controllers\AvancePresupuestalController::class, 'update'])->name('avances.update');
    Route::delete('/avances/{avance}', [\App\Http\Controllers\AvancePresupuestalController::class, 'destroy'])->name('avances.destroy');
});
