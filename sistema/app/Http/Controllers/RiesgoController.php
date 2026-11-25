<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EscrituraAuditada;
use App\Models\Proyecto;
use App\Services\Gestion\AlertasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Riesgos e incidencias: matriz de la cartera (todos) y registro por proyecto (Administrador o titular). */
class RiesgoController extends Controller
{
    use EscrituraAuditada;

    public function index(Request $request)
    {
        $parametros = AlertasService::parametros();
        $estado = $request->query('estado', 'abiertos');
        $riesgos = DB::connection('lector')->table('riesgo_proyecto as g')
            ->join('proyecto as p', 'p.id_proyecto', '=', 'g.id_proyecto')
            ->join('responsable as r', 'r.id_responsable', '=', 'g.id_responsable')
            ->when($estado === 'abiertos', fn ($q) => $q->where('g.estado', '<>', 'Cerrado'))
            ->select('g.*', 'p.codigo_pgd', 'p.nombre as proyecto', 'r.nombre as dueno')
            ->orderByDesc('g.exposicion')->orderBy('p.codigo_pgd')
            ->get()
            ->map(function ($g) use ($parametros) {
                $g->nivel = AlertasService::nivelRiesgo((int) $g->exposicion, $parametros);
                return $g;
            });

        $matriz = [];
        foreach ($riesgos->where('estado', '<>', 'Cerrado') as $g) {
            $matriz[$g->probabilidad][$g->impacto] = ($matriz[$g->probabilidad][$g->impacto] ?? 0) + 1;
        }

        return view('riesgos.index', [
            'riesgos' => $riesgos,
            'matriz' => $matriz,
            'parametros' => $parametros,
            'estado' => $estado,
            'proyectosCriticos' => $riesgos->where('estado', '<>', 'Cerrado')->where('nivel', 'Crítico')->pluck('codigo_pgd')->unique()->values(),
        ]);
    }

    public function store(Request $request, Proyecto $proyecto)
    {
        Gate::authorize('gestionar-proyecto', $proyecto);
        $datos = $this->validar($request);

        return $this->escribir($request, fn ($db) => $db->table('riesgo_proyecto')->insertGetId($datos + [
            'id_proyecto' => $proyecto->id_proyecto,
            'fecha_identificacion' => $request->input('fecha_identificacion', now()->toDateString()),
        ]), "Registro de {$datos['tipo']} en el proyecto {$proyecto->codigo_pgd}", [$proyecto->id_proyecto],
            "{$datos['tipo']} registrado en el proyecto {$proyecto->codigo_pgd}.", 201);
    }

    public function update(Request $request, int $riesgo)
    {
        $registro = DB::connection('lector')->table('riesgo_proyecto')->where('id_riesgo', $riesgo)->first();
        abort_if($registro === null, 404);
        $proyecto = Proyecto::findOrFail($registro->id_proyecto);
        Gate::authorize('gestionar-proyecto', $proyecto);
        $datos = $this->validar($request) + $request->validate([
            'estado' => ['required', Rule::in(['Abierto', 'En tratamiento', 'Cerrado'])],
            'motivo' => ['required', 'string', 'min:5', 'max:200'],
        ]);
        $motivo = $datos['motivo'];
        unset($datos['motivo']);

        return $this->escribir($request, fn ($db) => $db->table('riesgo_proyecto')->where('id_riesgo', $riesgo)->update($datos),
            $motivo, [$proyecto->id_proyecto], 'Riesgo actualizado.');
    }

    private function validar(Request $request): array
    {
        $datos = $request->validate([
            'tipo' => ['required', Rule::in(['Riesgo', 'Incidencia'])],
            'descripcion' => ['required', 'string', 'min:5', 'max:250'],
            'probabilidad' => ['required', 'integer', 'between:1,5'],
            'impacto' => ['required', 'integer', 'between:1,5'],
            'id_responsable' => ['required', 'integer', 'exists:lector.responsable,id_responsable'],
            'plan_respuesta' => ['required', 'string', 'min:5', 'max:300'],
            'fecha_identificacion' => ['nullable', 'date'],
        ]);
        unset($datos['fecha_identificacion']);
        // Una incidencia ya ocurrió: su probabilidad es 5 por definición.
        if ($datos['tipo'] === 'Incidencia') {
            $datos['probabilidad'] = 5;
        }
        return $datos;
    }
}
