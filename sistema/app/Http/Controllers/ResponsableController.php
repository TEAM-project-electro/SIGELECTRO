<?php

namespace App\Http\Controllers;

use App\Models\Responsable;
use App\Services\UsuarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use RuntimeException;

class ResponsableController extends Controller
{
    /**
     * Listado administrativo de responsables y perfiles (Solo Administrador).
     */
    public function index(): mixed
    {
        /** @var Responsable $user */
        $user = Auth::user();
        if (!$user || !$user->esAdmin()) {
            abort(403, 'Solo el Administrador tiene acceso a la gestión de usuarios.');
        }

        $usuarios = Responsable::orderBy('id_responsable')->get();

        if (request()->wantsJson()) {
            return response()->json($usuarios);
        }

        return view('usuarios.index', compact('usuarios'));
    }

    /**
     * Alternar o fijar el estado activo de un usuario con protección concurrente del último administrador.
     */
    public function toggleActivo(Request $request, Responsable $responsable): mixed
    {
        /** @var Responsable $user */
        $user = Auth::user();
        if (!$user || !$user->esAdmin()) {
            abort(403, 'Solo el Administrador tiene autorización para cambiar el estado de los usuarios.');
        }

        $nuevoEstado = $request->has('activo') ? (int) $request->boolean('activo') : ($responsable->activo ? 0 : 1);
        $motivo = $request->input('motivo_cambio', "Modificación de estado activo para usuario {$responsable->usuario}");

        try {
            $actualizado = UsuarioService::cambiarEstado($responsable->id_responsable, $nuevoEstado, $motivo);
        } catch (RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'mensaje' => 'Estado de usuario actualizado correctamente',
                'usuario' => $actualizado,
            ]);
        }

        return redirect()->back()->with('success', "Estado del usuario {$responsable->nombre} actualizado exitosamente.");
    }
}
