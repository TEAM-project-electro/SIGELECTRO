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

// Sonda pública mínima: nunca serializar diagnóstico o excepciones.
Route::get('/salud', function () {
    $disponible = false;
    try {
        $resultado = DB::connection('lector')->selectOne('SELECT VERSION() as v');
        $disponible = $resultado && str_contains((string) $resultado->v, 'MariaDB')
            && file_exists(public_path('build/manifest.json'));
    } catch (\Throwable $e) {
        report($e);
    }
    return response()->json(['estado' => $disponible ? 'OPERATIVO' : 'DEGRADADO'], $disponible ? 200 : 503);
})->name('salud');

// Rutas de invitados (Login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

// Rutas protegidas (Autenticación requerida)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Autoservicio de cambio de contraseña propia (todos los perfiles autenticados)
    Route::get('/perfil/clave', [\App\Http\Controllers\PerfilClaveController::class, 'edit'])->name('perfil.clave.edit');
    Route::put('/perfil/clave', [\App\Http\Controllers\PerfilClaveController::class, 'update'])->name('perfil.clave.update');

    Route::get('/tablero', [\App\Http\Controllers\TableroController::class, 'index'])->name('tablero');

    // Asistente RAG de consulta y Agente de Decisión Gerencial (Laudon Cap. 11 y 12) — solo lectura
    Route::get('/asistente', [\App\Http\Controllers\AsistenteController::class, 'index'])->name('asistente.index');
    Route::post('/asistente/preguntar', [\App\Http\Controllers\AsistenteController::class, 'preguntar'])
        ->middleware('throttle:'.config('rag.consultas_por_minuto', 10).',1')
        ->name('asistente.preguntar');
    Route::post('/asistente/decidir', [\App\Http\Controllers\AsistenteController::class, 'decidir'])
        ->middleware('throttle:'.config('rag.consultas_por_minuto', 10).',1')
        ->name('asistente.decidir');
    Route::post('/asistente/documentos', [\App\Http\Controllers\AsistenteController::class, 'subirDocumento'])
        ->middleware('throttle:'.config('rag.consultas_por_minuto', 10).',1')
        ->name('asistente.documentos.subir');

    // Motor de Indicadores y Consulta (RF04 - Etapa 10)
    Route::get('/indicadores/consulta', [\App\Http\Controllers\IndicadorController::class, 'consultar'])->name('indicadores.consulta');

    // Data Warehouse y ETL (Etapa 11)
    Route::get('/etl/estado', [\App\Http\Controllers\EtlController::class, 'estado'])->name('etl.estado');
    Route::post('/etl/ejecutar', [\App\Http\Controllers\EtlController::class, 'ejecutar'])->name('etl.ejecutar');

    // Reportes y Exportación (RF06 - Etapa 13 - Gate G3)
    Route::get('/reportes/imprimir', [\App\Http\Controllers\ReporteController::class, 'imprimir'])->name('reportes.imprimir');
    Route::get('/reportes/csv', [\App\Http\Controllers\ReporteController::class, 'exportarCsv'])->name('reportes.csv');

    // Módulo de Auditoría y Trazabilidad (RF05 - Solo Administrador)
    Route::get('/auditoria', [\App\Http\Controllers\AuditoriaController::class, 'index'])->name('auditoria.index');

    // Catálogo de Objetivos PGD
    Route::get('/objetivos', [ObjetivoGdController::class, 'index'])->name('objetivos.index');
    Route::post('/objetivos', [ObjetivoGdController::class, 'store'])->name('objetivos.store');
    Route::put('/objetivos/{objetivo}', [ObjetivoGdController::class, 'update'])->name('objetivos.update');

    // Administración de Usuarios y Responsables (Solo Administrador)
    Route::get('/usuarios', [\App\Http\Controllers\ResponsableController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios', [\App\Http\Controllers\ResponsableController::class, 'store'])->name('usuarios.store');
    Route::put('/usuarios/{responsable}', [\App\Http\Controllers\ResponsableController::class, 'update'])->name('usuarios.update');
    Route::patch('/usuarios/{responsable}/activo', [\App\Http\Controllers\ResponsableController::class, 'toggleActivo'])->name('usuarios.activo');

    // Gestión de Cartera de Proyectos PGD
    Route::get('/proyectos', [ProyectoController::class, 'index'])->name('proyectos.index');
    Route::get('/proyectos/crear', [ProyectoController::class, 'create'])->name('proyectos.create');
    Route::post('/proyectos', [ProyectoController::class, 'store'])->name('proyectos.store');
    Route::get('/proyectos/{proyecto}', [ProyectoController::class, 'show'])->name('proyectos.show');
    Route::get('/proyectos/{proyecto}/editar', [ProyectoController::class, 'edit'])->name('proyectos.edit');
    Route::put('/proyectos/{proyecto}', [ProyectoController::class, 'update'])->name('proyectos.update');
    Route::delete('/proyectos/{proyecto}', [ProyectoController::class, 'destroy'])->name('proyectos.destroy');

    // Cierre y Reapertura de Proyectos (Regla R3)
    Route::post('/proyectos/{proyecto}/cerrar', [ProyectoController::class, 'cerrar'])->name('proyectos.cerrar');
    Route::post('/proyectos/{proyecto}/reabrir', [ProyectoController::class, 'reabrir'])->name('proyectos.reabrir');

    // Gestión de Hitos (RF02)
    Route::get('/proyectos/{proyecto}/hitos/crear', [\App\Http\Controllers\HitoController::class, 'create'])->name('proyectos.hitos.create');
    Route::post('/proyectos/{proyecto}/hitos', [\App\Http\Controllers\HitoController::class, 'store'])->name('proyectos.hitos.store');
    Route::get('/hitos/{hito}/editar', [\App\Http\Controllers\HitoController::class, 'edit'])->name('hitos.edit');
    Route::put('/hitos/{hito}', [\App\Http\Controllers\HitoController::class, 'update'])->name('hitos.update');
    Route::delete('/hitos/{hito}', [\App\Http\Controllers\HitoController::class, 'destroy'])->name('hitos.destroy');

    // Gestión de Avances Presupuestales y Cortes (RF04 - Reglas R1 y R2)
    Route::get('/proyectos/{proyecto}/avances/crear', [\App\Http\Controllers\AvancePresupuestalController::class, 'create'])->name('proyectos.avances.create');
    Route::post('/proyectos/{proyecto}/avances', [\App\Http\Controllers\AvancePresupuestalController::class, 'store'])->name('proyectos.avances.store');
    Route::get('/avances/{avance}/editar', [\App\Http\Controllers\AvancePresupuestalController::class, 'edit'])->name('avances.edit');
    Route::put('/avances/{avance}', [\App\Http\Controllers\AvancePresupuestalController::class, 'update'])->name('avances.update');
    Route::delete('/avances/{avance}', [\App\Http\Controllers\AvancePresupuestalController::class, 'destroy'])->name('avances.destroy');

    // Gestión gerencial de la cartera (prioridad 2 de la auditoría)
    Route::get('/priorizacion', [\App\Http\Controllers\PriorizacionController::class, 'index'])->name('priorizacion.index');
    Route::put('/priorizacion/pesos', [\App\Http\Controllers\PriorizacionController::class, 'guardarPesos'])->name('priorizacion.pesos');
    Route::post('/priorizacion/proyectos/{proyecto}', [\App\Http\Controllers\PriorizacionController::class, 'evaluar'])->name('priorizacion.evaluar');

    Route::get('/riesgos', [\App\Http\Controllers\RiesgoController::class, 'index'])->name('riesgos.index');
    Route::post('/proyectos/{proyecto}/riesgos', [\App\Http\Controllers\RiesgoController::class, 'store'])->name('proyectos.riesgos.store');
    Route::put('/riesgos/{riesgo}', [\App\Http\Controllers\RiesgoController::class, 'update'])->whereNumber('riesgo')->name('riesgos.update');

    Route::get('/alertas', [\App\Http\Controllers\AlertaController::class, 'index'])->name('alertas.index');
    Route::put('/alertas/parametros', [\App\Http\Controllers\AlertaController::class, 'actualizarParametros'])->name('alertas.parametros');

    Route::put('/proyectos/{proyecto}/pesos-hitos', [\App\Http\Controllers\GestionProyectoController::class, 'ponderarHitos'])->name('proyectos.pesos');
    Route::post('/proyectos/{proyecto}/compromisos', [\App\Http\Controllers\GestionProyectoController::class, 'registrarCompromiso'])->name('proyectos.compromisos.store');
    Route::patch('/compromisos/{compromiso}/anular', [\App\Http\Controllers\GestionProyectoController::class, 'anularCompromiso'])->whereNumber('compromiso')->name('compromisos.anular');
    Route::post('/proyectos/{proyecto}/documentos', [\App\Http\Controllers\GestionProyectoController::class, 'subirDocumento'])->name('proyectos.documentos.store');
    Route::get('/documentos/{documento}', [\App\Http\Controllers\GestionProyectoController::class, 'descargarDocumento'])->whereNumber('documento')->name('documentos.descargar');
    Route::post('/proyectos/{proyecto}/beneficios', [\App\Http\Controllers\GestionProyectoController::class, 'registrarBeneficio'])->name('proyectos.beneficios.store');
    Route::patch('/beneficios/{beneficio}/medicion', [\App\Http\Controllers\GestionProyectoController::class, 'medirBeneficio'])->whereNumber('beneficio')->name('beneficios.medir');

    // Integración de Compras y Recepciones Odoo (CSV controlado)
    Route::get('/abastecimiento/importar', [\App\Http\Controllers\AbastecimientoController::class, 'importar'])->name('abastecimiento.importar');
    Route::get('/abastecimiento/plantilla', [\App\Http\Controllers\AbastecimientoController::class, 'descargarPlantilla'])->name('abastecimiento.plantilla');
    Route::post('/abastecimiento/previsualizar', [\App\Http\Controllers\AbastecimientoController::class, 'previsualizar'])->name('abastecimiento.previsualizar');
    Route::post('/abastecimiento/confirmar', [\App\Http\Controllers\AbastecimientoController::class, 'confirmar'])->name('abastecimiento.confirmar');
});
