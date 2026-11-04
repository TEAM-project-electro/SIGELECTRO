<?php

namespace App\Http\Controllers;

use App\Http\Requests\Proyecto\CerrarProyectoRequest;
use App\Http\Requests\Proyecto\ReabrirProyectoRequest;
use App\Http\Requests\Proyecto\StoreProyectoRequest;
use App\Http\Requests\Proyecto\UpdateProyectoRequest;
use App\Models\ObjetivoGd;
use App\Models\Proyecto;
use App\Models\Responsable;
use App\Services\DatabaseTransactionService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProyectoController extends Controller
{
    /**
     * Muestra la cartera de proyectos con filtros avanzados y métricas rápidas.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Proyecto::class);

        $query = Proyecto::query()->with(['objetivo', 'responsable']);

        // Filtro por Estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtro por Objetivo GD
        if ($request->filled('id_objetivo')) {
            $query->where('id_objetivo', $request->id_objetivo);
        }

        // Filtro por Responsable
        if ($request->filled('id_responsable')) {
            $query->where('id_responsable', $request->id_responsable);
        }

        // Filtro por búsqueda de texto
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo_pgd', 'like', "%{$buscar}%")
                  ->orWhere('nombre', 'like', "%{$buscar}%");
            });
        }

        $proyectos = $query->orderBy('codigo_pgd')->get();

        // Métricas resumidas
        $metricas = [
            'total_proyectos' => $proyectos->count(),
            'monto_total' => $proyectos->sum('monto'),
            'en_curso' => $proyectos->where('estado', 'En curso')->count(),
            'cerrados' => $proyectos->where('estado', 'Cerrado')->count(),
            'pendientes' => $proyectos->where('estado', 'Pendiente')->count(),
        ];

        $objetivos = ObjetivoGd::orderBy('codigo')->get();
        $responsables = Responsable::where('activo', 1)->orderBy('nombre')->get();

        return view('proyectos.index', compact('proyectos', 'metricas', 'objetivos', 'responsables'));
    }

    /**
     * Muestra el formulario para crear un nuevo proyecto (Solo Administrador).
     */
    public function create(): View
    {
        Gate::authorize('create', Proyecto::class);

        $objetivos = ObjetivoGd::orderBy('codigo')->get();
        $responsables = Responsable::where('activo', 1)->orderBy('nombre')->get();

        return view('proyectos.create', compact('objetivos', 'responsables'));
    }

    /**
     * Almacena un nuevo proyecto con auditoría transaccional.
     */
    public function store(StoreProyectoRequest $request): mixed
    {
        $idResponsable = Auth::id();
        $motivo = mb_substr("Creación de proyecto PGD {$request->codigo_pgd}", 0, 200);

        $idProyecto = DatabaseTransactionService::run($idResponsable, function () use ($request) {
            return DB::connection('escritor')->table('proyecto')->insertGetId([
                'codigo_pgd' => $request->codigo_pgd,
                'nombre' => $request->nombre,
                'monto' => $request->monto,
                'anio_inicio' => $request->anio_inicio,
                'anio_fin' => $request->anio_fin,
                'estado' => $request->estado,
                'id_objetivo' => $request->id_objetivo,
                'id_responsable' => $request->id_responsable,
                'fuente' => $request->fuente ?? 'Cartera PGD 2023 a 2026',
                'fecha_actualizacion' => now(),
            ]);
        }, $motivo);

        if ($request->wantsJson()) {
            $proyectoCreado = Proyecto::with(['objetivo', 'responsable'])->find($idProyecto);
            return response()->json($proyectoCreado, 201);
        }

        return redirect()->route('proyectos.index')
            ->with('success', "Proyecto PGD {$request->codigo_pgd} registrado exitosamente.");
    }

    /**
     * Muestra el detalle integral del proyecto, hitos y ejecuciones.
     */
    public function show(Proyecto $proyecto): View
    {
        Gate::authorize('view', $proyecto);

        $proyecto->load(['objetivo', 'responsable', 'hitos' => function ($q) {
            $q->orderBy('fecha_prevista');
        }, 'avances' => function ($q) {
            $q->orderBy('fecha_reporte');
        }]);

        // Indicadores específicos del proyecto desde la vista analítica
        $indicador = DB::table('v_indicadores_proyecto')
            ->where('id_proyecto', $proyecto->id_proyecto)
            ->first();

        return view('proyectos.show', compact('proyecto', 'indicador'));
    }

    /**
     * Muestra el formulario de edición de proyecto.
     */
    public function edit(Proyecto $proyecto): View
    {
        Gate::authorize('update', $proyecto);

        $objetivos = ObjetivoGd::orderBy('codigo')->get();
        $responsables = Responsable::where('activo', 1)->orderBy('nombre')->get();

        return view('proyectos.edit', compact('proyecto', 'objetivos', 'responsables'));
    }

    /**
     * Actualiza el proyecto con control de concurrencia optimista y auditoría.
     */
    public function update(UpdateProyectoRequest $request, Proyecto $proyecto): mixed
    {
        // 1. Control de Concurrencia Optimista
        // Verificamos si la fecha_actualizacion en BD coincide con la esperada
        $proyectoActual = DB::connection('escritor')->table('proyecto')
            ->where('id_proyecto', $proyecto->id_proyecto)
            ->first();

        if (!$proyectoActual) {
            abort(404, 'Proyecto no encontrado.');
        }

        // A01: Comprobación estricta en servidor para rol Responsable
        $user = $request->user();
        if ($user && $user->esResponsable()) {
            if ((float) $request->monto !== (float) $proyectoActual->monto ||
                (int) $request->id_responsable !== (int) $proyectoActual->id_responsable ||
                (int) $request->id_objetivo !== (int) $proyectoActual->id_objetivo ||
                (int) $request->anio_inicio !== (int) $proyectoActual->anio_inicio ||
                (int) $request->anio_fin !== (int) $proyectoActual->anio_fin) {
                abort(403, 'Acción no autorizada: Solo el Administrador puede modificar presupuesto, asignación de responsable u objetivos del proyecto.');
            }
        }

        if ($proyectoActual->fecha_actualizacion !== $request->fecha_actualizacion_esperada) {
            throw ValidationException::withMessages([
                'concurrencia' => 'Conflicto de concurrencia: El proyecto fue modificado por otro usuario mientras lo editabas. Por favor recarga la página para verificar los últimos cambios.',
            ]);
        }

        $idResponsable = Auth::id();
        $esReduccionMonto = (float) $request->monto < (float) $proyectoActual->monto;

        if ($esReduccionMonto) {
            $motivo = mb_substr(trim((string) $request->motivo_cambio), 0, 200);
            if (mb_strlen($motivo) < 5) {
                throw ValidationException::withMessages([
                    'motivo_cambio' => 'Al reducir el presupuesto del proyecto es obligatorio registrar una justificación detallada de al menos 5 caracteres.',
                ]);
            }
        } else {
            $motivo = $request->filled('motivo_cambio')
                ? mb_substr(trim((string) $request->motivo_cambio), 0, 200)
                : mb_substr("Actualización de datos del proyecto PGD {$proyecto->codigo_pgd}", 0, 200);
        }

        DatabaseTransactionService::run($idResponsable, function () use ($request, $proyecto) {
            DB::connection('escritor')->table('proyecto')
                ->where('id_proyecto', $proyecto->id_proyecto)
                ->update([
                    'nombre' => $request->nombre,
                    'monto' => $request->monto,
                    'anio_inicio' => $request->anio_inicio,
                    'anio_fin' => $request->anio_fin,
                    'id_objetivo' => $request->id_objetivo,
                    'id_responsable' => $request->id_responsable,
                    'fuente' => $request->fuente ?? 'Cartera PGD 2023 a 2026',
                    'fecha_actualizacion' => now(),
                ]);
        }, $motivo);

        if ($request->wantsJson()) {
            $proyectoActualizado = Proyecto::with(['objetivo', 'responsable'])->find($proyecto->id_proyecto);
            return response()->json($proyectoActualizado, 200);
        }

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', "Proyecto PGD {$proyecto->codigo_pgd} actualizado exitosamente.");
    }

    /**
     * Elimina un proyecto verificando integridad referencial (Solo Administrador).
     */
    public function destroy(Proyecto $proyecto): mixed
    {
        Gate::authorize('delete', $proyecto);

        $idResponsable = Auth::id();
        $codigoPgd = $proyecto->codigo_pgd;
        $motivo = mb_substr("Eliminación del proyecto PGD {$codigoPgd}", 0, 200);

        try {
            DatabaseTransactionService::run($idResponsable, function () use ($proyecto) {
                DB::connection('escritor')->table('proyecto')
                    ->where('id_proyecto', $proyecto->id_proyecto)
                    ->delete();
            }, $motivo);
        } catch (QueryException $e) {
            // Manejo de restricción de clave foránea (ON DELETE RESTRICT)
            if (str_contains($e->getMessage(), '1451') || str_contains($e->getMessage(), 'foreign key constraint fails')) {
                if (request()->wantsJson()) {
                    return response()->json(['error' => "No se puede eliminar el proyecto {$codigoPgd} porque tiene hitos o avances presupuestales asociados. Debe desvincularlos primero."], 422);
                }
                return redirect()->route('proyectos.index')
                    ->with('error', "No se puede eliminar el proyecto {$codigoPgd} porque tiene hitos o avances presupuestales asociados. Debe desvincularlos primero.");
            }
            throw $e;
        }

        if (request()->wantsJson()) {
            return response()->json(['mensaje' => "Proyecto {$codigoPgd} eliminado correctamente."], 200);
        }

        return redirect()->route('proyectos.index')
            ->with('success', "Proyecto {$codigoPgd} eliminado correctamente.");
    }

    /**
     * Realiza el cierre formal del proyecto verificando la Regla R3.
     */
    public function cerrar(CerrarProyectoRequest $request, Proyecto $proyecto): mixed
    {
        $idResponsable = Auth::id();
        $motivo = "Cierre formal de proyecto PGD {$proyecto->codigo_pgd}";

        try {
            DatabaseTransactionService::run($idResponsable, function () use ($proyecto) {
                DB::connection('escritor')->table('proyecto')
                    ->where('id_proyecto', $proyecto->id_proyecto)
                    ->update([
                        'estado' => 'Cerrado',
                        'fecha_actualizacion' => now(),
                    ]);
            }, $motivo);
        } catch (QueryException $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
            return redirect()->route('proyectos.show', $proyecto)
                ->with('error', 'No se pudo cerrar el proyecto: ' . $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json(['mensaje' => 'Proyecto cerrado exitosamente']);
        }

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', "El proyecto {$proyecto->codigo_pgd} ha sido cerrado formalmente tras verificar el 100% de hitos de línea base concluidos.");
    }

    /**
     * Reabre un proyecto cerrado requiriendo motivo obligatorio (Solo Administrador).
     */
    public function reabrir(ReabrirProyectoRequest $request, Proyecto $proyecto): mixed
    {
        $idResponsable = Auth::id();
        $motivo = $request->motivo;

        try {
            DatabaseTransactionService::run($idResponsable, function () use ($proyecto) {
                DB::connection('escritor')->table('proyecto')
                    ->where('id_proyecto', $proyecto->id_proyecto)
                    ->update([
                        'estado' => 'En curso',
                        'fecha_actualizacion' => now(),
                    ]);
            }, $motivo);
        } catch (QueryException $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
            return redirect()->route('proyectos.show', $proyecto)
                ->with('error', 'Error al reabrir el proyecto: ' . $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json(['mensaje' => 'Proyecto reabierto exitosamente']);
        }

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', "El proyecto {$proyecto->codigo_pgd} ha sido reabierto exitosamente. Motivo registrado en la bitácora.");
    }
}
