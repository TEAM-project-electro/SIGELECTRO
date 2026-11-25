<?php

namespace App\Http\Controllers;

use App\Services\Rag\RagAgenteDecision;
use App\Services\Rag\RagAsistente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AsistenteController extends Controller
{
    private const SUGERENCIAS = [
        '¿Cómo se calcula la desviación presupuestal?',
        '¿Cuál es el estado del proyecto 005?',
        '¿Qué diferencia hay entre corte común y último reporte?',
        '¿Qué requisitos debe cumplir un proyecto para cerrarse?',
        '¿Qué puede hacer el perfil Consulta?',
    ];

    public function index()
    {
        return view('asistente.index', $this->datosVista('consulta'));
    }

    public function preguntar(Request $request, RagAsistente $asistente)
    {
        $datos = $request->validate([
            'pregunta' => ['required', 'string', 'min:'.config('rag.pregunta_min'), 'max:'.config('rag.pregunta_max')],
        ]);
        $resultado = $asistente->responder(Auth::user(), $datos['pregunta']);

        if ($request->expectsJson()) {
            return response()->json($resultado);
        }
        return view('asistente.index', $this->datosVista('consulta', [
            'resultado' => $resultado,
            'pregunta' => $datos['pregunta'],
        ]));
    }

    public function decidir(Request $request, RagAgenteDecision $agente)
    {
        $datos = $request->validate([
            'escenario' => ['required', 'string', Rule::in(array_keys(RagAgenteDecision::ESCENARIOS))],
            'id_proyecto' => ['nullable', 'integer', 'exists:lector.proyecto,id_proyecto'],
            'objetivo' => ['nullable', 'string', 'max:'.config('rag.pregunta_max')],
        ]);

        $decision = $agente->analizar(
            Auth::user(),
            $datos['escenario'],
            isset($datos['id_proyecto']) && $datos['id_proyecto'] !== '' ? (int) $datos['id_proyecto'] : null,
            $datos['objetivo'] ?? null
        );

        if ($request->expectsJson()) {
            return response()->json($decision);
        }
        return view('asistente.index', $this->datosVista('decision', [
            'decision' => $decision,
            'escenarioSeleccionado' => $datos['escenario'],
            'proyectoSeleccionado' => $datos['id_proyecto'] ?? null,
            'objetivoDecision' => $datos['objetivo'] ?? '',
        ]));
    }

    private function datosVista(string $tab, array $extra = []): array
    {
        $proyectos = DB::connection('lector')->table('proyecto')
            ->select('id_proyecto', 'codigo_pgd', 'nombre', 'estado')
            ->orderBy('codigo_pgd')
            ->get();

        return array_merge([
            'tabActiva' => $tab,
            'sugerencias' => self::SUGERENCIAS,
            'escenarios' => RagAgenteDecision::ESCENARIOS,
            'proyectos' => $proyectos,
            'resultado' => null,
            'pregunta' => '',
            'decision' => null,
            'escenarioSeleccionado' => 'priorizar_cartera',
            'proyectoSeleccionado' => null,
            'objetivoDecision' => '',
        ], $extra);
    }
}
