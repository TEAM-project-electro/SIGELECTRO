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

        Gate::authorize('update', $responsable);
        $validated = $request->validate(['activo'=>['required','boolean'], 'motivo_cambio'=>['nullable','string','max:200']]);
        $nuevoEstado = (int) $validated['activo'];
        $motivo = $validated['motivo_cambio'] ?? null;

        try {
            $actualizado = \App\Services\AdministrarUsuarios::guardar($responsable->id_responsable, ['activo'=>$nuevoEstado], $motivo);
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

    public function store(Request $request): mixed
    {
        Gate::authorize('create', Responsable::class);
        return $this->guardar($request);
    }

    public function update(Request $request, Responsable $responsable): mixed
    {
        Gate::authorize('update', $responsable);
        return $this->guardar($request, $responsable);
    }

    private function guardar(Request $request, ?Responsable $responsable = null): mixed
    {
        $datos = $request->validate([
            'nombre'=>['required','string','max:100'], 'area'=>['required','string','max:80'],
            'usuario'=>['required','string','max:30', \Illuminate\Validation\Rule::unique('responsable','usuario')->ignore($responsable?->id_responsable,'id_responsable')],
            'perfil'=>['required', \Illuminate\Validation\Rule::in(['Administrador','Responsable','Consulta'])],
            'activo'=>['required','boolean'], 'clave'=>[$responsable ? 'nullable' : 'required','string','min:10','max:128'],
            'motivo_cambio'=>['nullable','string','max:200'],
        ]);
        $datos['activo'] = (int) $datos['activo'];
        try {
            $usuario = \App\Services\AdministrarUsuarios::guardar($responsable?->id_responsable, $datos, $datos['motivo_cambio'] ?? null);
        } catch (RuntimeException $e) {
            throw \Illuminate\Validation\ValidationException::withMessages(['perfil'=>$e->getMessage()]);
        }
        return $request->wantsJson() ? response()->json($usuario, $responsable ? 200 : 201) : redirect()->route('usuarios.index')->with('success','Usuario guardado.');
    }
}
