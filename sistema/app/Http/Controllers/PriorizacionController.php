<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EscrituraAuditada;
use App\Models\Proyecto;
use App\Services\Gestion\PriorizacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Priorización multicriterio: ranking (todos los perfiles), pesos y calificaciones (Administrador). */
class PriorizacionController extends Controller
{
    use EscrituraAuditada;

    public function index(Request $request, PriorizacionService $servicio)
    {
        $datos = $servicio->ranking();
        $seleccionado = $request->integer('proyecto') ?: null;

        return view('priorizacion.index', $datos + [
            'proyectoSeleccionado' => $seleccionado,
            'esAdmin' => Gate::allows('administrar-cartera'),
        ]);
    }

    public function guardarPesos(Request $request)
    {
        Gate::authorize('administrar-cartera');
        $datos = $request->validate([
            'pesos' => ['required', 'array'],
            'pesos.*' => ['required', 'numeric', 'min:0', 'max:100'],
            'activos' => ['nullable', 'array'],
            'motivo' => ['required', 'string', 'min:5', 'max:200'],
        ]);
        abort_if(array_sum(array_map('floatval', $datos['pesos'])) <= 0, 422, 'Al menos un criterio debe tener peso mayor que cero.');

        return $this->escribir($request, function ($db) use ($datos) {
            foreach ($datos['pesos'] as $id => $peso) {
                $db->table('criterio_priorizacion')->where('id_criterio', (int) $id)->update([
                    'peso' => round((float) $peso, 2),
                    'activo' => isset($datos['activos'][$id]) ? 1 : 0,
                ]);
            }
        }, $datos['motivo'], [], 'Pesos de priorización actualizados.');
    }

    public function evaluar(Request $request, Proyecto $proyecto)
    {
        Gate::authorize('administrar-cartera');
        abort_if($proyecto->estado === 'Cerrado', 422, 'Un proyecto cerrado no se prioriza.');
        $datos = $request->validate([
            'puntajes' => ['required', 'array', 'min:1'],
            'puntajes.*' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5])],
            'sustentos' => ['required', 'array'],
            'sustentos.*' => ['required', 'string', 'min:10', 'max:300'],
        ], [
            'sustentos.*.min' => 'Cada calificación necesita un sustento de al menos 10 caracteres.',
            'sustentos.*.required' => 'Cada calificación necesita su sustento.',
        ]);

        return $this->escribir($request, function ($db) use ($datos, $proyecto) {
            foreach ($datos['puntajes'] as $idCriterio => $puntaje) {
                $db->table('evaluacion_priorizacion')->upsert([[
                    'id_proyecto' => $proyecto->id_proyecto,
                    'id_criterio' => (int) $idCriterio,
                    'puntaje' => (int) $puntaje,
                    'sustento' => trim((string) ($datos['sustentos'][$idCriterio] ?? '')),
                    'id_responsable' => auth()->id(),
                ]], ['id_proyecto', 'id_criterio'], ['puntaje', 'sustento']);
            }
        }, "Calificación de priorización del proyecto {$proyecto->codigo_pgd}", [$proyecto->id_proyecto],
            "Calificación del proyecto {$proyecto->codigo_pgd} guardada.");
    }
}
