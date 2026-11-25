<?php

namespace App\Http\Controllers;

use App\Services\Rag\RagAgenteDecision;
use App\Services\Rag\RagAsistente;
use App\Services\Rag\RagGemini;
use App\Services\Rag\RagIndexador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        if ($this->esParcial($request)) {
            return view('asistente.partials.respuesta', ['resultado' => $resultado]);
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
        if ($this->esParcial($request)) {
            return view('asistente.partials.dictamen', ['decision' => $decision]);
        }
        return view('asistente.index', $this->datosVista('decision', [
            'decision' => $decision,
            'escenarioSeleccionado' => $datos['escenario'],
            'proyectoSeleccionado' => $datos['id_proyecto'] ?? null,
            'objetivoDecision' => $datos['objetivo'] ?? '',
        ]));
    }

    public function subirDocumento(Request $request, RagIndexador $indexador)
    {
        abort_unless(Auth::user()->esAdmin(), 403, 'Solo el perfil Administrador puede alimentar la base documental del RAG.');

        $datos = $request->validate([
            'titulo' => ['required', 'string', 'min:3', 'max:180'],
            'archivo' => ['required', 'file', 'max:512', 'extensions:md,txt'],
        ]);

        $archivo = $request->file('archivo');
        $contenido = (string) file_get_contents($archivo->getRealPath());
        $slug = Str::slug(pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME));
        if ($slug === '') {
            $slug = Str::slug($datos['titulo']) ?: 'documento-rag';
        }

        $resultadoCarga = $indexador->indexarDocumentoSubido($slug, $datos['titulo'], $contenido);

        if ($request->expectsJson()) {
            return response()->json($resultadoCarga, $resultadoCarga['aceptado'] ? 201 : 422);
        }
        return view('asistente.index', $this->datosVista('documentos', [
            'resultadoCarga' => $resultadoCarga,
        ]));
    }

    /** Petición del chat sin recarga: devuelve solo el fragmento HTML ya escapado en servidor. */
    private function esParcial(Request $request): bool
    {
        return $request->header('X-Asistente-Parcial') === '1';
    }

    private function datosVista(string $tab, array $extra = []): array
    {
        $proyectos = DB::connection('lector')->table('proyecto')
            ->select('id_proyecto', 'codigo_pgd', 'nombre', 'estado')
            ->orderBy('codigo_pgd')
            ->get();

        $documentosIndexados = DB::connection('lector')->table('rag_documento')
            ->select('ruta', 'titulo', 'fragmentos', 'fragmentos_bloqueados', 'fecha_indexacion')
            ->orderBy('ruta')
            ->get();

        return array_merge([
            'tabActiva' => $tab,
            'iaDisponible' => app(RagGemini::class)->disponible(),
            'sugerencias' => self::SUGERENCIAS,
            'escenarios' => RagAgenteDecision::ESCENARIOS,
            'proyectos' => $proyectos,
            'documentosIndexados' => $documentosIndexados,
            'resultado' => null,
            'pregunta' => '',
            'decision' => null,
            'escenarioSeleccionado' => 'priorizar_cartera',
            'proyectoSeleccionado' => null,
            'objetivoDecision' => '',
            'resultadoCarga' => null,
        ], $extra);
    }
}
