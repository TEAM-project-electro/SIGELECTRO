<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Muestra la vista de formulario de inicio de sesión.
     */
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended('/tablero');
        }

        return view('auth.login');
    }

    /**
     * Procesa la solicitud de autenticación con rate limiting.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended('/tablero');
    }

    /**
     * Destruye la sesión autenticada.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
