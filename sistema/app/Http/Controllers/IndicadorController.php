<?php

namespace App\Http\Controllers;

use App\Services\IndicadoresService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IndicadorController extends Controller
{
    protected IndicadoresService $indicadoresService;

    public function __construct(IndicadoresService $indicadoresService)
    {
        $this->indicadoresService = $indicadoresService;
    }

    /**
     * Endpoint de consulta parametrizada del motor de indicadores.
     */
    public function consultar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'modo' => ['nullable', 'string', Rule::in([IndicadoresService::MODO_CORTE_COMUN, IndicadoresService::MODO_ULTIMO_REPORTE])],
            'fecha_corte' => ['nullable', 'date_format:Y-m-d'],
            'id_objetivo' => ['nullable', 'integer', 'exists:objetivo_gd,id_objetivo'],
            'id_responsable' => ['nullable', 'integer', 'exists:responsable,id_responsable'],
            'estado' => ['nullable', 'string', Rule::in(['Pendiente', 'En curso', 'Cerrado'])],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $resultado = $this->indicadoresService->calcularCartera($validated);

        return response()->json($resultado);
    }
}
