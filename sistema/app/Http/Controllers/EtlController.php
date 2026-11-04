<?php

namespace App\Http\Controllers;

use App\Services\EtlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EtlController extends Controller
{
    protected EtlService $etlService;

    public function __construct(EtlService $etlService)
    {
        $this->etlService = $etlService;
    }

    /**
     * Consulta el estado de sincronización del Data Warehouse.
     */
    public function estado(): JsonResponse
    {
        $estado = $this->etlService->estaDesactualizado();
        return response()->json($estado);
    }

    /**
     * Ejecuta manualmente la carga ETL (solo Administrador).
     */
    public function ejecutar(Request $request): JsonResponse
    {
        abort_if(
            Auth::user()->perfil !== 'Administrador',
            403,
            'Acceso denegado: Solo el Administrador tiene autorización para ejecutar el proceso ETL.'
        );

        $resultado = $this->etlService->ejecutar();

        $statusCode = $resultado['exito'] ? 200 : 500;
        return response()->json($resultado, $statusCode);
    }
}
