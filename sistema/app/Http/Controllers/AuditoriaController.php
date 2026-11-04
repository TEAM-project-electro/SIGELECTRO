<?php

namespace App\Http\Controllers;

use App\Models\BitacoraCambio;
use App\Models\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AuditoriaController extends Controller
{
    /**
     * Muestra el registro histórico de la bitácora técnica con filtros avanzados.
     * Solo accesible para el perfil Administrador.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', BitacoraCambio::class);

        $query = BitacoraCambio::query()->with(['responsable', 'motivoExtension']);

        // Filtro por tabla afectada
        if ($request->filled('tabla_afectada')) {
            $query->where('tabla_afectada', $request->tabla_afectada);
        }

        // Filtro por ID de registro
        if ($request->filled('id_registro')) {
            $query->where('id_registro', $request->id_registro);
        }

        // Filtro por Responsable / Actor
        if ($request->filled('id_responsable')) {
            $query->where('id_responsable', $request->id_responsable);
        }

        // Filtro por Rango de Fechas
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_hora', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_hora', '<=', $request->fecha_hasta);
        }

        $cambios = $query->orderByDesc('id_cambio')->paginate(25)->withQueryString();

        $responsables = Responsable::orderBy('nombre')->get();

        $estadisticas = [
            'total_cambios' => BitacoraCambio::count(),
            'cambios_proyecto' => BitacoraCambio::where('tabla_afectada', 'proyecto')->count(),
            'cambios_hito' => BitacoraCambio::where('tabla_afectada', 'hito')->count(),
            'cambios_avance' => BitacoraCambio::where('tabla_afectada', 'avance_presupuestal')->count(),
        ];

        return view('auditoria.index', compact('cambios', 'responsables', 'estadisticas'));
    }
}
