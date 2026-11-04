<?php

namespace App\Http\Controllers;

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
    public function store(StoreProyectoRequest $request): RedirectResponse
    {
        $idResponsable = Auth::id();
        $motivo = "Creación de proyecto PGD {$request->codigo_pgd}";

        DatabaseTransactionService::run($idResponsable, function () use ($request) {
            DB::connection('escritor')->table('proyecto')->insert([
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
    public function update(UpdateProyectoRequest $request, Proyecto $proyecto): RedirectResponse
    {
        // 1. Control de Concurrencia Optimista
        // Verificamos si la fecha_actualizacion en BD coincide con la esperada
        $proyectoActual = DB::connection('escritor')->table('proyecto')
            ->where('id_proyecto', $proyecto->id_proyecto)
            ->first();

        if (!$proyectoActual) {
            abort(404, 'Proyecto no encontrado.');
        }

        if ($proyectoActual->fecha_actualizacion !== $request->fecha_actualizacion_esperada) {
            throw ValidationException::withMessages([
                'concurrencia' => 'Conflicto de concurrencia: El proyecto fue modificado por otro usuario mientras lo editabas. Por favor recarga la página para verificar los últimos cambios.',
            ]);
        }

        $idResponsable = Auth::id();
        $motivo = $request->motivo_cambio ?: "Actualización de datos del proyecto PGD {$proyecto->codigo_pgd}";

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

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', "Proyecto PGD {$proyecto->codigo_pgd} actualizado exitosamente.");
    }

    /**
     * Elimina un proyecto verificando integridad referencial (Solo Administrador).
     */
    public function destroy(Proyecto $proyecto): RedirectResponse
    {
        Gate::authorize('delete', $proyecto);

        $idResponsable = Auth::id();
        $codigoPgd = $proyecto->codigo_pgd;
        $motivo = "Eliminación del proyecto PGD {$codigoPgd}";

        try {
            DatabaseTransactionService::run($idResponsable, function () use ($proyecto) {
                DB::connection('escritor')->table('proyecto')
                    ->where('id_proyecto', $proyecto->id_proyecto)
                    ->delete();
            }, $motivo);
        } catch (QueryException $e) {
            // Manejo de restricción de clave foránea (ON DELETE RESTRICT)
            if (str_contains($e->getMessage(), '1451') || str_contains($e->getMessage(), 'foreign key constraint fails')) {
                return redirect()->route('proyectos.index')
                    ->with('error', "No se puede eliminar el proyecto {$codigoPgd} porque tiene hitos o avances presupuestales asociados. Debe desvincularlos primero.");
            }
            throw $e;
        }

        return redirect()->route('proyectos.index')
            ->with('success', "Proyecto {$codigoPgd} eliminado correctamente.");
    }
}
