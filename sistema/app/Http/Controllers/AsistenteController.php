<?php

namespace App\Http\Controllers;

use App\Services\Rag\RagAsistente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AsistenteController extends Controller
{
    private const SUGERENCIAS = [
        '¿Cómo se calcula la desviación presupuestal?',
        '¿Cuál es el estado del proyecto 005?',
        '¿Qué diferencia hay entre corte común y último reporte?',
        '¿Qué requisitos debe cumplir un proyecto para cerrarse?',
        '¿Qué puede hacer el perfil Consulta?',
    ];

    public function index()
    {
        return view('asistente.index', ['sugerencias' => self::SUGERENCIAS, 'resultado' => null, 'pregunta' => '']);
    }

    public function preguntar(Request $request, RagAsistente $asistente)
    {
        $datos = $request->validate([
            'pregunta' => ['required', 'string', 'min:'.config('rag.pregunta_min'), 'max:'.config('rag.pregunta_max')],
        ]);
        $resultado = $asistente->responder(Auth::user(), $datos['pregunta']);

        if ($request->expectsJson()) {
            return response()->json($resultado);
        }
        return view('asistente.index', ['sugerencias' => self::SUGERENCIAS, 'resultado' => $resultado, 'pregunta' => $datos['pregunta']]);
    }
}
