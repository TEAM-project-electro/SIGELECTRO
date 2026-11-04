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
    public function store(StoreHitoRequest $request, Proyecto $proyecto): RedirectResponse
    {
        $idResponsable = Auth::id();
        $motivo = "Creación de hito en proyecto {$proyecto->codigo_pgd}: {$request->descripcion}";

        try {
            DatabaseTransactionService::run($idResponsable, function () use ($request, $proyecto) {
                DB::connection('escritor')->table('hito')->insert([
                    'id_proyecto' => $proyecto->id_proyecto,
                    'descripcion' => $request->descripcion,
                    'fecha_prevista' => $request->fecha_prevista,
                    'fecha_cierre' => $request->fecha_cierre,
                    'evidencia' => $request->evidencia,
                    'en_linea_base' => $request->boolean('en_linea_base') ? 1 : 0,
                ]);
            }, $motivo);
        } catch (QueryException $e) {
            // Manejo de error lanzado por triggers de MariaDB (ej. trg_hito_bi)
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error de motor: ' . $e->getMessage());
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
    public function update(UpdateHitoRequest $request, Hito $hito): RedirectResponse
    {
        $proyecto = $hito->proyecto;
        $idResponsable = Auth::id();
        $motivo = "Actualización de hito #{$hito->id_hito} en proyecto {$proyecto->codigo_pgd}";

        try {
            DatabaseTransactionService::run($idResponsable, function () use ($request, $hito) {
                DB::connection('escritor')->table('hito')
                    ->where('id_hito', $hito->id_hito)
                    ->update([
                        'descripcion' => $request->descripcion,
                        'fecha_prevista' => $request->fecha_prevista,
                        'fecha_cierre' => $request->fecha_cierre,
                        'evidencia' => $request->evidencia,
                        'en_linea_base' => $request->boolean('en_linea_base') ? 1 : 0,
                    ]);
            }, $motivo);
        } catch (QueryException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar el hito: ' . $e->getMessage());
        }

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', 'Hito actualizado exitosamente.');
    }

    /**
     * Elimina el hito (Solo Administrador en proyecto no cerrado).
     */
    public function destroy(Hito $hito): RedirectResponse
    {
        Gate::authorize('delete', $hito);

        $proyecto = $hito->proyecto;
        if ($proyecto && $proyecto->estado === 'Cerrado') {
            return redirect()->back()->with('error', 'No se pueden eliminar hitos de un proyecto cerrado.');
        }

        $idResponsable = Auth::id();
        $motivo = "Eliminación de hito #{$hito->id_hito} en proyecto {$proyecto->codigo_pgd}";

        DatabaseTransactionService::run($idResponsable, function () use ($hito) {
            DB::connection('escritor')->table('hito')
                ->where('id_hito', $hito->id_hito)
                ->delete();
        }, $motivo);

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', 'Hito eliminado correctamente.');
    }
}
