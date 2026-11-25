<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EscrituraAuditada;
use App\Services\Gestion\AlertasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** Alertas preventivas de la cartera activa y sus umbrales configurables. */
class AlertaController extends Controller
{
    use EscrituraAuditada;

    public function index(Request $request, AlertasService $servicio)
    {
        $datos = $request->validate([
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'alcance' => ['nullable', 'in:cartera,mios'],
            'severidad' => ['nullable', 'in:ALTA,MEDIA,BAJA'],
        ]);
        $usuario = Auth::user();
        $soloMios = ($datos['alcance'] ?? null) === 'mios' && $usuario->esResponsable();
        $resultado = $servicio->generar($datos['fecha'] ?? null, $soloMios ? $usuario->id_responsable : null);
        if (!empty($datos['severidad'])) {
            $resultado['alertas'] = $resultado['alertas']->where('severidad', $datos['severidad'])->values();
        }

        $parametros = DB::connection('lector')->table('parametro_alerta')->orderBy('clave')->get();

        return view('alertas.index', $resultado + [
            'filtros' => $datos + ['alcance' => $soloMios ? 'mios' : 'cartera'],
            'parametrosEditables' => $parametros,
            'esAdmin' => Gate::allows('administrar-cartera'),
            'esResponsable' => $usuario->esResponsable(),
        ]);
    }

    public function actualizarParametros(Request $request)
    {
        Gate::authorize('administrar-cartera');
        $datos = $request->validate([
            'valores' => ['required', 'array'],
            'valores.*' => ['required', 'numeric', 'between:-100,1000'],
            'motivo' => ['required', 'string', 'min:5', 'max:200'],
        ]);
        $claves = array_keys(AlertasService::PREDETERMINADOS);
        $valores = array_intersect_key($datos['valores'], array_flip($claves));
        if (($valores['exigir_acta_cierre'] ?? 0) != 0 && ($valores['exigir_acta_cierre'] ?? 0) != 1) {
            return back()->withErrors(['valores' => 'exigir_acta_cierre solo admite 0 o 1.']);
        }
        if (isset($valores['exposicion_critica'], $valores['exposicion_media']) && $valores['exposicion_media'] >= $valores['exposicion_critica']) {
            return back()->withErrors(['valores' => 'La exposición media debe ser menor que la crítica.']);
        }

        return $this->escribir($request, function ($db) use ($valores) {
            foreach ($valores as $clave => $valor) {
                $db->table('parametro_alerta')->where('clave', $clave)->update(['valor' => $valor]);
            }
        }, $datos['motivo'], [], 'Umbrales de alerta actualizados.');
    }
}
