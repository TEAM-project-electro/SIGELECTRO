<?php

namespace App\Http\Controllers;

use App\Models\ObjetivoGd;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ObjetivoGdController extends Controller
{
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
