<?php

namespace App\Http\Controllers;

use App\Models\ObjetivoGd;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ObjetivoGdController extends Controller
{
    public function store(\Illuminate\Http\Request $request): mixed
    {
        Gate::authorize('create', ObjetivoGd::class);
        return $this->guardar($request);
    }

    public function update(\Illuminate\Http\Request $request, ObjetivoGd $objetivo): mixed
    {
        Gate::authorize('update', $objetivo);
        return $this->guardar($request, $objetivo);
    }

    private function guardar(\Illuminate\Http\Request $request, ?ObjetivoGd $objetivo = null): mixed
    {
        $datos = $request->validate(['codigo'=>['required','string','max:10', \Illuminate\Validation\Rule::unique('objetivo_gd','codigo')->ignore($objetivo?->id_objetivo,'id_objetivo')], 'descripcion'=>['required','string','max:200']]);
        $id = \App\Services\DatabaseTransactionService::run(\Illuminate\Support\Facades\Auth::id(), function ($db) use ($datos, $objetivo) {
            Gate::authorize('create', ObjetivoGd::class);
            if ($objetivo) {
                $db->table('objetivo_gd')->where('id_objetivo',$objetivo->id_objetivo)->update($datos);
                return $objetivo->id_objetivo;
            }
            return $db->table('objetivo_gd')->insertGetId($datos);
        }, 'Administración del catálogo OGD');
        return $request->wantsJson() ? response()->json(ObjetivoGd::find($id), $objetivo ? 200 : 201) : redirect()->route('objetivos.index')->with('success','Objetivo guardado.');
    }
    /**
     * Muestra el catálogo de los 6 objetivos de gobierno digital y sus proyectos asociados.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', ObjetivoGd::class);

        $objetivos = ObjetivoGd::withCount('proyectos')
            ->with(['proyectos' => function ($q) {
                $q->select('id_proyecto', 'codigo_pgd', 'nombre', 'monto', 'estado', 'id_objetivo');
            }])
            ->orderBy('codigo')
            ->get();

        return view('objetivos.index', compact('objetivos'));
    }
}
