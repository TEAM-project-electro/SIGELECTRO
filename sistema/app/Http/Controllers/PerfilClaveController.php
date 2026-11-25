<?php

namespace App\Http\Controllers;

use App\Services\ClavesPublicasLegadas;
use App\Services\DatabaseTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PerfilClaveController extends Controller
{
    public function edit(): View
    {
        return view('auth.cambiar-clave');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'clave_actual' => ['required', 'string'],
            'clave_nueva' => ['required', 'string', 'confirmed'],
        ]);

        $idUsuario = (int) Auth::id();
        $claveActual = (string) $request->input('clave_actual');
        $claveNueva = (string) $request->input('clave_nueva');

        $filaAuth = DB::connection('auth')
            ->table('responsable')
            ->select('id_responsable', 'clave_hash', 'activo')
            ->where('id_responsable', $idUsuario)
            ->first();

        if (!$filaAuth || !(int) $filaAuth->activo || !Hash::check($claveActual, (string) $filaAuth->clave_hash)) {
            throw ValidationException::withMessages([
                'clave_actual' => 'La contraseña actual ingresada no es correcta.',
            ]);
        }

        if (
            strlen($claveNueva) < 12
            || strlen($claveNueva) > 72
            || trim($claveNueva) === ''
            || $claveNueva !== trim($claveNueva)
            || str_contains($claveNueva, "\0")
            || ClavesPublicasLegadas::contiene($claveNueva)
        ) {
            throw ValidationException::withMessages([
                'clave_nueva' => 'La nueva contraseña debe tener entre 12 y 72 caracteres, sin espacios al inicio o final, y no ser una clave demostrativa pública.',
            ]);
        }

        if (Hash::check($claveNueva, (string) $filaAuth->clave_hash)) {
            throw ValidationException::withMessages([
                'clave_nueva' => 'La nueva contraseña debe ser diferente de la contraseña actual.',
            ]);
        }

        DatabaseTransactionService::run($idUsuario, function ($db) use ($idUsuario, $claveNueva) {
            $cuenta = $db->table('responsable')
                ->select('id_responsable')
                ->where('id_responsable', $idUsuario)
                ->where('activo', 1)
                ->lockForUpdate()
                ->first();

            if (!$cuenta) {
                abort(403, 'La cuenta ya no se encuentra activa.');
            }

            $db->table('responsable')
                ->where('id_responsable', $idUsuario)
                ->update(['clave_hash' => Hash::make($claveNueva)]);
        }, 'Actualización de contraseña propia por autoservicio autenticado');

        $request->session()->regenerate();

        return redirect()->route('tablero')->with('success', 'Su contraseña ha sido actualizada correctamente.');
    }
}
