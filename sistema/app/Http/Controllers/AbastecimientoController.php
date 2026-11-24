<?php

namespace App\Http\Controllers;

use App\Models\AbastecimientoCompra;
use App\Models\Proyecto;
use App\Services\ImportadorOdooService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AbastecimientoController extends Controller
{
    public function __construct(private readonly ImportadorOdooService $importador)
    {
    }

    public function importar(Request $request): View
    {
        Gate::authorize('import', AbastecimientoCompra::class);

        $idProyecto = $request->integer('proyecto');
        $proyecto = $idProyecto ? Proyecto::find($idProyecto) : null;

        return view('abastecimiento.importar', [
            'proyecto' => $proyecto,
            'previsualizacion' => null,
            'contenidoCsvB64' => null,
            'archivoNombre' => null,
        ]);
    }

    public function previsualizar(Request $request): View
    {
        Gate::authorize('import', AbastecimientoCompra::class);

        $request->validate([
            'archivo_csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            'id_proyecto' => ['nullable', 'integer'],
        ]);

        $archivo = $request->file('archivo_csv');
        $contenido = (string) file_get_contents($archivo->getRealPath());
        $previsualizacion = $this->importador->previsualizarDesdeCsv($contenido);

        $idProyecto = $request->integer('id_proyecto');
        $proyecto = $idProyecto ? Proyecto::find($idProyecto) : null;

        return view('abastecimiento.importar', [
            'proyecto' => $proyecto,
            'previsualizacion' => $previsualizacion,
            'contenidoCsvB64' => base64_encode($contenido),
            'archivoNombre' => $archivo->getClientOriginalName(),
        ]);
    }

    public function confirmar(Request $request): RedirectResponse
    {
        Gate::authorize('import', AbastecimientoCompra::class);

        $contenido = '';
        $archivoNombre = $request->input('archivo_nombre');

        if ($request->hasFile('archivo_csv')) {
            $request->validate([
                'archivo_csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            ]);
            $archivo = $request->file('archivo_csv');
            $contenido = (string) file_get_contents($archivo->getRealPath());
            $archivoNombre = $archivo->getClientOriginalName();
        } else {
            $request->validate([
                'contenido_csv_b64' => ['required', 'string'],
            ]);
            $decodificado = base64_decode((string) $request->input('contenido_csv_b64'), true);
            $contenido = $decodificado !== false ? $decodificado : '';
        }

        $resumen = $this->importador->confirmarDesdeCsv(
            $contenido,
            (int) Auth::id(),
            $archivoNombre ? mb_substr((string) $archivoNombre, 0, 150) : null
        );

        $destino = $request->integer('id_proyecto')
            ? route('proyectos.show', $request->integer('id_proyecto'))
            : route('proyectos.index');

        return redirect($destino)->with(
            'success',
            "Carga de Odoo confirmada: {$resumen['insertadas']} nuevas, {$resumen['actualizadas']} actualizadas."
        );
    }
}
