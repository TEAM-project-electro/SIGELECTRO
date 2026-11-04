<?php

namespace App\Http\Controllers;

use App\Http\Requests\Hito\StoreHitoRequest;
use App\Http\Requests\Hito\UpdateHitoRequest;
use App\Models\Hito;
use App\Models\Proyecto;
use App\Services\DatabaseTransactionService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class HitoController extends Controller
{
    /**
     * Muestra el formulario para crear un nuevo hito en el proyecto.
     */
    public function create(Proyecto $proyecto): View
    {
        Gate::authorize('create', [Hito::class, $proyecto]);

        if ($proyecto->estado === 'Cerrado') {
            abort(403, 'No se pueden agregar hitos a un proyecto cerrado.');
        }

        return view('hitos.create', compact('proyecto'));
    }

    /**
     * Almacena el nuevo hito con transacción y auditoría.
     */
    public function store(StoreHitoRequest $request, Proyecto $proyecto): mixed
    {
        $idResponsable = Auth::id();
        $motivo = mb_substr("Creación de hito en proyecto {$proyecto->codigo_pgd}: {$request->descripcion}", 0, 200);

        try {
            $idHito = DatabaseTransactionService::run($idResponsable, function () use ($request, $proyecto) {
                $padre = Proyecto::on('escritor')->findOrFail($proyecto->id_proyecto);
                abort_if($padre->estado === 'Cerrado', 403, 'El proyecto está cerrado.');
                Gate::authorize('create', [Hito::class, $padre]);

                $id = DB::connection('escritor')->table('hito')->insertGetId([
                    'id_proyecto' => $proyecto->id_proyecto,
                    'descripcion' => $request->descripcion,
                    'fecha_prevista' => $request->fecha_prevista,
                    'fecha_cierre' => $request->fecha_cierre,
                    'evidencia' => $request->evidencia,
                    'en_linea_base' => $request->boolean('en_linea_base') ? 1 : 0,
                ]);

                // A15: Refrescar actividad del proyecto padre
                DB::connection('escritor')->table('proyecto')
                    ->where('id_proyecto', $proyecto->id_proyecto)
                    ->update(['fecha_actualizacion' => now()]);

                return $id;
            }, $motivo, proyectosIds: [$proyecto->id_proyecto]);
        } catch (QueryException $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Error de motor: ' . \App\Services\ErroresNegocio::mensaje($e)], 422);
            }
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error de motor: ' . \App\Services\ErroresNegocio::mensaje($e));
        }

        if ($request->wantsJson()) {
            $hitoCreado = Hito::find($idHito);
            return response()->json($hitoCreado, 201);
        }

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', 'Hito registrado exitosamente.');
    }

    /**
     * Muestra el formulario de edición / cierre del hito.
     */
    public function edit(Hito $hito): View
    {
        Gate::authorize('update', $hito);

        $proyecto = $hito->proyecto;
        if (!$proyecto || $proyecto->estado === 'Cerrado') {
            abort(403, 'No se puede modificar un hito perteneciente a un proyecto cerrado.');
        }

        return view('hitos.edit', compact('hito', 'proyecto'));
    }

    /**
     * Actualiza el hito con control transaccional y auditoría.
     */
    public function update(UpdateHitoRequest $request, Hito $hito): mixed
    {
        $proyecto = $hito->proyecto;
        $idResponsable = Auth::id();

        $nuevaLb = $request->boolean('en_linea_base') ? 1 : 0;
        $cambioLb = (int) $hito->en_linea_base !== $nuevaLb;

        if ($cambioLb && $request->filled('motivo_cambio')) {
            $motivo = mb_substr(trim((string) $request->motivo_cambio), 0, 200);
        } else {
            $motivo = mb_substr("Actualización de hito #{$hito->id_hito} en proyecto {$proyecto->codigo_pgd}", 0, 200);
        }

        try {
            DatabaseTransactionService::run($idResponsable, function () use ($request, $hito, $proyecto) {
                $padre = Proyecto::on('escritor')->findOrFail($proyecto->id_proyecto);
                abort_if($padre->estado === 'Cerrado', 403, 'El proyecto está cerrado.');
                Gate::authorize('update', Hito::on('escritor')->findOrFail($hito->getKey()));
                DatabaseTransactionService::verificarRevision($request->integer('revision_esperada'));

                DB::connection('escritor')->table('hito')
                    ->where('id_hito', $hito->id_hito)
                    ->update([
                        'descripcion' => $request->descripcion,
                        'fecha_prevista' => $request->fecha_prevista,
                        'fecha_cierre' => $request->fecha_cierre,
                        'evidencia' => $request->evidencia,
                        'en_linea_base' => $request->boolean('en_linea_base') ? 1 : 0,
                    ]);

                // A15: Refrescar actividad del proyecto padre
                DB::connection('escritor')->table('proyecto')
                    ->where('id_proyecto', $proyecto->id_proyecto)
                    ->update(['fecha_actualizacion' => now()]);
            }, $motivo, proyectosIds: [$proyecto->id_proyecto]);
        } catch (QueryException $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Error al actualizar el hito: ' . \App\Services\ErroresNegocio::mensaje($e)], 422);
            }
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar el hito: ' . \App\Services\ErroresNegocio::mensaje($e));
        }

        if ($request->wantsJson()) {
            $hitoActualizado = Hito::find($hito->id_hito);
            return response()->json($hitoActualizado, 200);
        }

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', 'Hito actualizado exitosamente.');
    }

    /**
     * Elimina el hito (Solo Administrador en proyecto no cerrado).
     */
    public function destroy(Hito $hito): mixed
    {
        Gate::authorize('delete', $hito);

        $proyecto = $hito->proyecto;
        if ($proyecto && $proyecto->estado === 'Cerrado') {
            if (request()->wantsJson()) {
                return response()->json(['error' => 'No se pueden eliminar hitos de un proyecto cerrado.'], 403);
            }
            return redirect()->back()->with('error', 'No se pueden eliminar hitos de un proyecto cerrado.');
        }

        $idResponsable = Auth::id();
        $motivo = mb_substr("Eliminación de hito #{$hito->id_hito} en proyecto {$proyecto->codigo_pgd}", 0, 200);

        DatabaseTransactionService::run($idResponsable, function () use ($hito, $proyecto) {
                $padre = Proyecto::on('escritor')->findOrFail($proyecto->id_proyecto);
                abort_if($padre->estado === 'Cerrado', 403, 'El proyecto está cerrado.');
                Gate::authorize('delete', Hito::on('escritor')->findOrFail($hito->getKey()));

            DB::connection('escritor')->table('hito')
                ->where('id_hito', $hito->id_hito)
                ->delete();

            // A15: Refrescar actividad del proyecto padre
            if ($proyecto) {
                DB::connection('escritor')->table('proyecto')
                    ->where('id_proyecto', $proyecto->id_proyecto)
                    ->update(['fecha_actualizacion' => now()]);
            }
        }, $motivo, proyectosIds: [$proyecto->id_proyecto]);

        if (request()->wantsJson()) {
            return response()->json(['mensaje' => 'Hito eliminado correctamente.'], 200);
        }

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', 'Hito eliminado correctamente.');
    }
}
