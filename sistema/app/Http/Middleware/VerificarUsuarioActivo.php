<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class VerificarUsuarioActivo
{
    /**
     * Verifica en cada solicitud autenticada que el usuario continúe activo en la base de datos.
     * Si fue desactivado por un Administrador, invalida la sesión y lo desautentica de inmediato.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Consultar el estado activo en tiempo real mediante la conexión auth
            $activo = DB::connection('auth')
                ->table('responsable')
                ->where('id_responsable', $user->id_responsable)
                ->value('activo');

            if ((int) $activo !== 1) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->wantsJson()) {
                    return response()->json([
                        'message' => 'Su cuenta se encuentra inactiva. Contacte al administrador del sistema.'
                    ], 403);
                }

                return redirect()->route('login')
                    ->withErrors(['usuario' => 'Su cuenta ha sido desactivada por el administrador.']);
            }
        }

        return $next($request);
    }
}
