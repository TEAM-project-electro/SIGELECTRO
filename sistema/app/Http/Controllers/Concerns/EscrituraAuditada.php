<?php

namespace App\Http\Controllers\Concerns;

use App\Services\DatabaseTransactionService;
use App\Services\ErroresNegocio;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** Escritura transaccional con actor y motivo (regla R4) y respuesta uniforme en HTML o JSON. */
trait EscrituraAuditada
{
    protected function escribir(Request $request, callable $operacion, string $motivo, array $proyectos, string $exito, int $estadoJson = 200)
    {
        try {
            $resultado = DatabaseTransactionService::run(
                Auth::id(),
                fn () => $operacion(DB::connection('escritor')),
                mb_substr($motivo, 0, 200),
                proyectosIds: $proyectos
            );
        } catch (QueryException $e) {
            $mensaje = ErroresNegocio::mensaje($e);
            if ($request->expectsJson()) {
                return response()->json(['error' => $mensaje], 422);
            }
            return back()->withInput()->with('error', $mensaje);
        }

        if ($request->expectsJson()) {
            return response()->json(['mensaje' => $exito, 'resultado' => $resultado], $estadoJson);
        }
        return back()->with('success', $exito);
    }
}
