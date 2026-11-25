<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EscrituraAuditada;
use App\Models\Proyecto;
use App\Services\DatabaseTransactionService;
use App\Services\ErroresNegocio;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Gestión gerencial desde la ficha del proyecto: ponderación de hitos (avance físico y valor ganado),
 * compromisos presupuestales, documentos y beneficios. Las reglas de quién puede escribir se validan
 * también en la base de datos.
 */
class GestionProyectoController extends Controller
{
    use EscrituraAuditada;

    public const TIPOS_DOCUMENTO = ['Acta', 'Informe', 'Evidencia', 'Contrato', 'Otro'];
    public const EXTENSIONES_DOCUMENTO = ['pdf', 'docx', 'xlsx', 'pptx', 'png', 'jpg', 'jpeg', 'txt', 'csv'];

    /** Pesos de los hitos de línea base; deben sumar 100. */
    public function ponderarHitos(Request $request, Proyecto $proyecto)
    {
        Gate::authorize('administrar-cartera');
        $datos = $request->validate([
            'pesos' => ['required', 'array', 'min:1'],
            'pesos.*' => ['required', 'numeric', 'gt:0', 'max:100'],
            'motivo' => ['required', 'string', 'min:5', 'max:200'],
        ]);
        $lb = DB::connection('lector')->table('hito')->where('id_proyecto', $proyecto->id_proyecto)->where('en_linea_base', 1)
            ->pluck('id_hito')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $enviados = collect(array_keys($datos['pesos']))->map(fn ($id) => (int) $id)->sort()->values()->all();
        if ($lb !== $enviados) {
            return $this->rechazar($request, 'pesos', 'Debe ponderar exactamente los hitos de línea base del proyecto.');
        }
        $suma = round(array_sum(array_map('floatval', $datos['pesos'])), 2);
        if (abs($suma - 100) > 0.01) {
            return $this->rechazar($request, 'pesos', "Los pesos deben sumar 100 (suman {$suma}).");
        }

        return $this->escribir($request, function ($db) use ($datos) {
            foreach ($datos['pesos'] as $id => $peso) {
                $db->table('hito_peso')->upsert([['id_hito' => (int) $id, 'peso' => round((float) $peso, 2)]], ['id_hito'], ['peso']);
            }
        }, $datos['motivo'], [$proyecto->id_proyecto], 'Pesos de los hitos guardados.');
    }

    public function registrarCompromiso(Request $request, Proyecto $proyecto)
    {
        Gate::authorize('administrar-cartera');
        $datos = $request->validate([
            'documento' => ['required', 'string', 'max:60'],
            'descripcion' => ['required', 'string', 'min:5', 'max:200'],
            'monto' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'fecha' => ['required', 'date'],
        ]);

        return $this->escribir($request, fn ($db) => $db->table('compromiso_presupuestal')->insertGetId($datos + [
            'id_proyecto' => $proyecto->id_proyecto, 'estado' => 'Vigente', 'id_responsable' => Auth::id(),
        ]), "Compromiso {$datos['documento']} en el proyecto {$proyecto->codigo_pgd}", [$proyecto->id_proyecto],
            "Compromiso {$datos['documento']} registrado.", 201);
    }

    public function anularCompromiso(Request $request, int $compromiso)
    {
        Gate::authorize('administrar-cartera');
        $registro = DB::connection('lector')->table('compromiso_presupuestal')->where('id_compromiso', $compromiso)->first();
        abort_if($registro === null, 404);
        $datos = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:200']]);

        return $this->escribir($request, fn ($db) => $db->table('compromiso_presupuestal')->where('id_compromiso', $compromiso)
            ->update(['estado' => 'Anulado']), $datos['motivo'], [(int) $registro->id_proyecto], "Compromiso {$registro->documento} anulado.");
    }

    public function subirDocumento(Request $request, Proyecto $proyecto)
    {
        Gate::authorize('gestionar-proyecto', $proyecto);
        $datos = $request->validate([
            'tipo' => ['required', Rule::in(self::TIPOS_DOCUMENTO)],
            'titulo' => ['required', 'string', 'min:3', 'max:150'],
            'id_hito' => ['nullable', 'integer', Rule::exists('lector.hito', 'id_hito')->where('id_proyecto', $proyecto->id_proyecto)],
            'archivo' => ['required', 'file', 'max:10240', 'extensions:'.implode(',', self::EXTENSIONES_DOCUMENTO)],
        ]);

        $archivo = $request->file('archivo');
        $extension = strtolower($archivo->getClientOriginalExtension());
        $ruta = "documentos/{$proyecto->id_proyecto}/".Str::uuid().".{$extension}";
        Storage::disk('local')->putFileAs(dirname($ruta), $archivo, basename($ruta));

        try {
            $id = DatabaseTransactionService::run(Auth::id(), fn () => DB::connection('escritor')->table('documento_proyecto')->insertGetId([
                'id_proyecto' => $proyecto->id_proyecto,
                'id_hito' => $datos['id_hito'] ?? null,
                'tipo' => $datos['tipo'],
                'titulo' => $datos['titulo'],
                'nombre_original' => mb_substr($archivo->getClientOriginalName(), 0, 200),
                'ruta' => $ruta,
                'mime' => mb_substr((string) $archivo->getMimeType(), 0, 100),
                'tamano' => $archivo->getSize(),
                'hash_sha256' => hash_file('sha256', Storage::disk('local')->path($ruta)),
                'id_responsable' => Auth::id(),
            ]), "Documento {$datos['tipo']} en el proyecto {$proyecto->codigo_pgd}", proyectosIds: [$proyecto->id_proyecto]);
        } catch (QueryException $e) {
            Storage::disk('local')->delete($ruta); // Sin registro no queda archivo huérfano.
            return $this->rechazar($request, 'archivo', ErroresNegocio::mensaje($e));
        }

        if ($request->expectsJson()) {
            return response()->json(['id_documento' => $id], 201);
        }
        return back()->with('success', "Documento «{$datos['titulo']}» registrado.");
    }

    public function descargarDocumento(int $documento)
    {
        $registro = DB::connection('lector')->table('documento_proyecto')->where('id_documento', $documento)->first();
        abort_if($registro === null || !Storage::disk('local')->exists($registro->ruta), 404);
        Gate::authorize('view', Proyecto::findOrFail($registro->id_proyecto));

        return Storage::disk('local')->download($registro->ruta, $registro->nombre_original, [
            'Content-Type' => $registro->mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function registrarBeneficio(Request $request, Proyecto $proyecto)
    {
        Gate::authorize('gestionar-proyecto', $proyecto);
        $datos = $request->validate([
            'descripcion' => ['required', 'string', 'min:5', 'max:200'],
            'indicador' => ['required', 'string', 'min:3', 'max:120'],
            'unidad' => ['required', 'string', 'max:30'],
            'linea_base' => ['required', 'numeric'],
            'meta' => ['required', 'numeric', 'different:linea_base'],
        ]);

        return $this->escribir($request, fn ($db) => $db->table('beneficio_proyecto')->insertGetId($datos + [
            'id_proyecto' => $proyecto->id_proyecto, 'id_responsable' => Auth::id(),
        ]), "Beneficio planificado en el proyecto {$proyecto->codigo_pgd}", [$proyecto->id_proyecto], 'Beneficio planificado registrado.', 201);
    }

    public function medirBeneficio(Request $request, int $beneficio)
    {
        $registro = DB::connection('lector')->table('beneficio_proyecto')->where('id_beneficio', $beneficio)->first();
        abort_if($registro === null, 404);
        $proyecto = Proyecto::findOrFail($registro->id_proyecto);
        Gate::authorize('gestionar-proyecto', $proyecto);
        $datos = $request->validate([
            'valor_logrado' => ['required', 'numeric'],
            'fecha_medicion' => ['required', 'date', 'before_or_equal:today'],
            'observacion' => ['nullable', 'string', 'max:250'],
        ]);

        return $this->escribir($request, fn ($db) => $db->table('beneficio_proyecto')->where('id_beneficio', $beneficio)->update($datos),
            "Medición del beneficio «{$registro->indicador}»", [$proyecto->id_proyecto], 'Medición del beneficio registrada.');
    }

    private function rechazar(Request $request, string $campo, string $mensaje)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $mensaje, 'errors' => [$campo => [$mensaje]]], 422);
        }
        return back()->withInput()->withErrors([$campo => $mensaje]);
    }
}
