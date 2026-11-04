<?php

namespace App\Http\Controllers;

use App\Http\Requests\Avance\StoreAvanceRequest;
use App\Http\Requests\Avance\UpdateAvanceRequest;
use App\Models\AvancePresupuestal;
use App\Models\Proyecto;
use App\Services\DatabaseTransactionService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AvancePresupuestalController extends Controller
{
    /**
     * Muestra el formulario para registrar un nuevo corte presupuestal.
     */
    public function create(Proyecto $proyecto): View
    {
        Gate::authorize('create', [AvancePresupuestal::class, $proyecto]);

        if ($proyecto->estado === 'Cerrado') {
            abort(403, 'No se pueden registrar avances en un proyecto cerrado.');
        }

        // Último corte registrado para referencia
        $ultimoCorte = $proyecto->avances()->orderByDesc('fecha_reporte')->first();

        return view('avances.create', compact('proyecto', 'ultimoCorte'));
    }

    /**
     * Almacena el corte presupuestal validando las Reglas R1 y R2.
     */
    public function store(StoreAvanceRequest $request, Proyecto $proyecto): RedirectResponse
    {
        $idResponsable = Auth::id();
        $motivo = "Registro de corte presupuestal al {$request->fecha_reporte} en proyecto {$proyecto->codigo_pgd}";

        try {
            DatabaseTransactionService::run($idResponsable, function () use ($request, $proyecto) {
                DB::connection('escritor')->table('avance_presupuestal')->insert([
                    'id_proyecto' => $proyecto->id_proyecto,
                    'fecha_reporte' => $request->fecha_reporte,
                    'presupuesto_planificado' => $request->presupuesto_planificado,
                    'gasto_acumulado' => $request->gasto_acumulado,
                ]);
            }, $motivo);
        } catch (QueryException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, '1062') || str_contains($msg, 'uq_avance_proyecto_fecha')) {
                $errorMsg = "Ya existe un corte presupuestal registrado para la fecha {$request->fecha_reporte}.";
            } elseif (str_contains($msg, 'menor que el de un reporte anterior')) {
                $errorMsg = 'Regla R2: El gasto acumulado no puede ser menor que el de un reporte cronológico anterior.';
            } elseif (str_contains($msg, 'supera al de un reporte posterior')) {
                $errorMsg = 'Regla R2: El gasto acumulado no puede superar al de un reporte cronológico posterior.';
            } elseif (str_contains($msg, 'supera el monto')) {
                $errorMsg = 'Regla R1: El monto supera el presupuesto total del proyecto.';
            } else {
                $errorMsg = 'Error al registrar el corte presupuestal: ' . $msg;
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $errorMsg);
        }

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', 'Corte presupuestal registrado exitosamente.');
    }

    /**
     * Muestra el formulario de edición de un corte presupuestal.
     */
    public function edit(AvancePresupuestal $avance): View
    {
        Gate::authorize('update', $avance);

        $proyecto = $avance->proyecto;
        if (!$proyecto || $proyecto->estado === 'Cerrado') {
            abort(403, 'No se puede modificar un corte de un proyecto cerrado.');
        }

        return view('avances.edit', compact('avance', 'proyecto'));
    }

    /**
     * Actualiza el corte presupuestal validando R1 y R2.
     */
    public function update(UpdateAvanceRequest $request, AvancePresupuestal $avance): RedirectResponse
    {
        $proyecto = $avance->proyecto;
        $idResponsable = Auth::id();
        $motivo = "Actualización de corte presupuestal #{$avance->id_avance} ({$avance->fecha_reporte}) en proyecto {$proyecto->codigo_pgd}";

        try {
            DatabaseTransactionService::run($idResponsable, function () use ($request, $avance) {
                DB::connection('escritor')->table('avance_presupuestal')
                    ->where('id_avance', $avance->id_avance)
                    ->update([
                        'presupuesto_planificado' => $request->presupuesto_planificado,
                        'gasto_acumulado' => $request->gasto_acumulado,
                    ]);
            }, $motivo);
        } catch (QueryException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar el corte presupuestal: ' . $e->getMessage());
        }

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', 'Corte presupuestal actualizado exitosamente.');
    }

    /**
     * Elimina el corte presupuestal (Solo Administrador en proyecto no cerrado).
     */
    public function destroy(AvancePresupuestal $avance): RedirectResponse
    {
        Gate::authorize('delete', $avance);

        $proyecto = $avance->proyecto;
        if ($proyecto && $proyecto->estado === 'Cerrado') {
            return redirect()->back()->with('error', 'No se pueden eliminar cortes de un proyecto cerrado.');
        }

        $idResponsable = Auth::id();
        $motivo = "Eliminación de corte presupuestal #{$avance->id_avance} en proyecto {$proyecto->codigo_pgd}";

        DatabaseTransactionService::run($idResponsable, function () use ($avance) {
            DB::connection('escritor')->table('avance_presupuestal')
                ->where('id_avance', $avance->id_avance)
                ->delete();
        }, $motivo);

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', 'Corte presupuestal eliminado correctamente.');
    }
}
