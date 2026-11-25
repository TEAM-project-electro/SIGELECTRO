@extends('layouts.app')

@section('title', 'Importar Compras Odoo')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">

    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Importar Compras y Recepciones desde Odoo</h1>
            <p class="text-sm text-slate-600 mt-1">
                @if($proyecto)
                    <span translate="no" class="font-mono font-semibold text-brand-800">PGD {{ $proyecto->codigo_pgd }}</span> — {{ $proyecto->nombre }}
                @else
                    Carga controlada por CSV con vista previa y actualización idempotente por ID externo
                @endif
            </p>
        </div>

        <div class="flex items-center gap-2 self-start">
            <a href="{{ route('abastecimiento.plantilla') }}" class="sig-btn-secondary">
                Descargar plantilla CSV
            </a>
            <a href="{{ $proyecto ? route('proyectos.show', $proyecto) : route('proyectos.index') }}" class="sig-btn-secondary">
                Volver
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-900 text-xs" role="alert" aria-live="assertive">
            <p class="font-bold mb-1">No se pudo completar la carga:</p>
            <ul class="list-disc list-inside space-y-0.5 text-rose-800">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Paso 1: Selección del archivo CSV --}}
    <div class="sig-panel p-5 sm:p-6" x-data="{ nombreArchivo: '' }">
        <div class="flex items-center gap-2.5 mb-4">
            <span class="w-6 h-6 rounded-full bg-brand-900 text-white text-xs font-bold inline-flex items-center justify-center shrink-0">1</span>
            <h2 class="text-sm font-bold text-slate-900">Seleccionar archivo CSV exportado desde Odoo</h2>
        </div>

        <form method="POST" action="{{ route('abastecimiento.previsualizar') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @if($proyecto)
                <input type="hidden" name="id_proyecto" value="{{ $proyecto->id_proyecto }}">
            @endif

            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <label for="archivo_csv"
                       class="flex-1 flex items-center justify-between gap-3 px-4 py-3 rounded-xl border border-dashed border-slate-300 bg-slate-50/70 hover:bg-brand-50/40 hover:border-brand-400 cursor-pointer transition-colors duration-150 focus-within:ring-2 focus-within:ring-brand-600">
                    <div class="flex items-center gap-3 min-w-0">
                        <svg class="w-5 h-5 text-brand-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <div class="min-w-0">
                            <span class="block text-xs font-semibold text-slate-800 truncate" x-text="nombreArchivo || 'Elegir archivo CSV…'">Elegir archivo CSV…</span>
                            <span class="block text-[11px] text-slate-500">Formato CSV UTF-8 con ID externo, orden, cantidades y código PROYGD-005</span>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-300 text-xs font-semibold text-slate-700 shrink-0">Examinar</span>
                    <input id="archivo_csv" name="archivo_csv" type="file" accept=".csv,.txt" required class="sr-only"
                           @change="nombreArchivo = $event.target.files[0]?.name || ''">
                </label>

                <button type="submit" class="sig-btn-primary py-3 px-5 shrink-0">
                    Previsualizar archivo
                </button>
            </div>
        </form>
    </div>

    {{-- Paso 2: Vista previa y confirmación --}}
    @if($previsualizacion !== null)
        <div class="sig-panel overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-3 bg-slate-50/60">
                <div class="flex items-center gap-2.5">
                    <span class="w-6 h-6 rounded-full bg-brand-900 text-white text-xs font-bold inline-flex items-center justify-center shrink-0">2</span>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Vista previa de validación</h2>
                        <p class="text-xs text-slate-500">Archivo: <span class="font-mono text-slate-700">{{ $archivoNombre }}</span></p>
                    </div>
                </div>
                @if($previsualizacion['valido'])
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-energy-50 text-energy-700 border border-energy-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-energy-500" aria-hidden="true"></span>
                        Archivo válido listo para confirmar
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-600" aria-hidden="true"></span>
                        Contiene observaciones (no publicable)
                    </span>
                @endif
            </div>

            @if(!empty($previsualizacion['errores_generales']))
                <div class="p-4 bg-rose-50 text-rose-800 text-xs border-b border-rose-200">
                    @foreach($previsualizacion['errores_generales'] as $err)
                        <p>{{ $err }}</p>
                    @endforeach
                </div>
            @endif

            @if(!empty($previsualizacion['filas']))
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-[13px]">
                        <thead class="bg-slate-50/90 text-slate-600 text-xs">
                            <tr>
                                <th class="px-4 py-3 text-center font-semibold">Fila</th>
                                <th class="px-4 py-3 text-left font-semibold">Estado</th>
                                <th class="px-4 py-3 text-left font-semibold">Proyecto</th>
                                <th class="px-4 py-3 text-left font-semibold">Orden</th>
                                <th class="px-4 py-3 text-left font-semibold">Producto</th>
                                <th class="px-4 py-3 text-right font-semibold">Comprada</th>
                                <th class="px-4 py-3 text-right font-semibold">Recibida</th>
                                <th class="px-4 py-3 text-right font-semibold">Pendiente</th>
                                <th class="px-4 py-3 text-left font-semibold">ID externo / Observación</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($previsualizacion['filas'] as $fila)
                                <tr class="{{ $fila['estado_validacion'] === 'error' ? 'bg-rose-50/50' : '' }}">
                                    <td class="px-4 py-3 text-center font-mono text-xs text-slate-500">{{ $fila['numero_fila'] }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if($fila['estado_validacion'] === 'nueva')
                                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-energy-50 text-energy-700 border border-energy-100">Nueva</span>
                                        @elseif($fila['estado_validacion'] === 'actualizacion')
                                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-brand-50 text-brand-800 border border-brand-200">Actualización</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200">Error</span>
                                        @endif
                                    </td>
                                    <td translate="no" class="px-4 py-3 font-mono font-bold text-slate-900">{{ $fila['codigo_pgd'] ?? '—' }}</td>
                                    <td translate="no" class="px-4 py-3 font-mono text-slate-800">{{ $fila['orden_compra'] ?: '—' }}</td>
                                    <td class="px-4 py-3 text-slate-800">{{ $fila['producto'] ?: '—' }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ number_format($fila['cantidad_comprada'], 2) }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ number_format($fila['cantidad_recibida'], 2) }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums font-bold text-slate-900">{{ number_format($fila['saldo_pendiente'], 2) }}</td>
                                    <td class="px-4 py-3 text-xs">
                                        @if(!empty($fila['errores']))
                                            <span class="text-rose-700 font-semibold">{{ implode(' ', $fila['errores']) }}</span>
                                        @else
                                            <span translate="no" class="font-mono text-slate-500">{{ $fila['id_externo_linea'] }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($previsualizacion['valido'])
                <div class="px-5 py-4 border-t border-slate-200/80 bg-slate-50/70 flex justify-end">
                    <form method="POST" action="{{ route('abastecimiento.confirmar') }}">
                        @csrf
                        @if($proyecto)
                            <input type="hidden" name="id_proyecto" value="{{ $proyecto->id_proyecto }}">
                        @endif
                        <input type="hidden" name="contenido_csv_b64" value="{{ $contenidoCsvB64 }}">
                        <input type="hidden" name="archivo_nombre" value="{{ $archivoNombre }}">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-energy-600 hover:bg-energy-700 text-white rounded-lg text-xs font-semibold shadow-panel transition-colors duration-150">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Confirmar carga en el SIG</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>
    @endif

</div>
@endsection
