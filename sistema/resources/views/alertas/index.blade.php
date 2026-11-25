@extends('layouts.app')

@section('title', 'Alertas de la cartera')

@section('content')
@php
    $variante = ['ALTA' => 'danger', 'MEDIA' => 'warning', 'BAJA' => 'neutral'];
    $etiqueta = ['ALTA' => 'Alta', 'MEDIA' => 'Media', 'BAJA' => 'Baja'];
@endphp
<div class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Alertas preventivas de la cartera</h1>
            <p class="text-sm text-slate-600 mt-1">Proyectos activos a la fecha de referencia <span class="font-mono font-semibold">{{ $fecha }}</span>. Cada alerta lleva a la sección de la ficha que la origina.</p>
        </div>
        <form method="GET" action="{{ route('alertas.index') }}" class="flex flex-wrap items-end gap-2">
            <div>
                <label for="fecha" class="block text-xs font-semibold text-slate-700 mb-1">Fecha de referencia</label>
                <input id="fecha" type="date" name="fecha" value="{{ $filtros['fecha'] ?? $fecha }}" class="sig-input text-xs py-2 font-mono">
            </div>
            <div>
                <label for="severidad" class="block text-xs font-semibold text-slate-700 mb-1">Severidad</label>
                <select id="severidad" name="severidad" class="sig-input text-xs py-2">
                    <option value="">Todas</option>
                    @foreach ($etiqueta as $k => $v)
                        <option value="{{ $k }}" @selected(($filtros['severidad'] ?? '') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            @if ($esResponsable)
                <div>
                    <label for="alcance" class="block text-xs font-semibold text-slate-700 mb-1">Alcance</label>
                    <select id="alcance" name="alcance" class="sig-input text-xs py-2">
                        <option value="cartera" @selected($filtros['alcance'] === 'cartera')>Toda la cartera</option>
                        <option value="mios" @selected($filtros['alcance'] === 'mios')>Mis proyectos</option>
                    </select>
                </div>
            @endif
            <button type="submit" class="sig-btn-primary py-2">Aplicar</button>
        </form>
    </div>

    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4" aria-label="Resumen de alertas">
        <div class="sig-panel p-5">
            <p class="text-xs font-semibold text-slate-600">Alertas</p>
            <p class="text-[30px] leading-tight font-bold text-slate-900 mt-1.5 tabular-nums">{{ $resumen['total'] }}</p>
            <p class="text-xs text-slate-500 mt-2 tabular-nums">en {{ $resumen['proyectos'] }} proyecto(s)</p>
        </div>
        @foreach (['ALTA' => 'text-rose-700', 'MEDIA' => 'text-amber-800', 'BAJA' => 'text-slate-700'] as $sev => $color)
            <div class="sig-panel p-5">
                <p class="text-xs font-semibold text-slate-600">Severidad {{ strtolower($etiqueta[$sev]) }}</p>
                <p class="text-[30px] leading-tight font-bold mt-1.5 tabular-nums {{ $color }}">{{ $resumen['por_severidad'][$sev] }}</p>
            </div>
        @endforeach
    </section>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <section class="xl:col-span-8 sig-panel overflow-hidden" aria-labelledby="titulo-alertas">
            <div class="px-5 py-3.5 border-b border-slate-200/80 flex flex-wrap gap-2 items-center justify-between">
                <h2 id="titulo-alertas" class="text-sm font-bold text-slate-900">Detalle ({{ $alertas->count() }})</h2>
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($resumen['por_tipo'] as $tipo => $n)
                        <span class="text-[11px] px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200 text-slate-600">{{ $tipo }}: <span class="tabular-nums font-semibold">{{ $n }}</span></span>
                    @endforeach
                </div>
            </div>
            @if ($alertas->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-slate-500">No hay alertas con estos filtros.</p>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($alertas as $a)
                        <li>
                            <a href="{{ $a->url }}" class="flex items-start gap-3 px-5 py-3 hover:bg-slate-50/80 transition-colors">
                                <x-badge size="sm" :variant="$variante[$a->severidad]" class="shrink-0 mt-0.5">{{ $etiqueta[$a->severidad] }}</x-badge>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-xs font-semibold text-slate-900">
                                        <span class="font-mono text-brand-800">{{ $a->codigo_pgd }}</span> · {{ $a->tipo }}
                                    </span>
                                    <span class="block text-xs text-slate-600 mt-0.5">{{ $a->detalle }}</span>
                                </span>
                                <span class="text-[11px] text-slate-500 whitespace-nowrap shrink-0">{{ $a->responsable }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="xl:col-span-4 sig-panel overflow-hidden" aria-labelledby="titulo-umbrales">
            <div class="px-5 py-3.5 border-b border-slate-200/80">
                <h2 id="titulo-umbrales" class="text-sm font-bold text-slate-900">Umbrales configurables</h2>
                <p class="text-xs text-slate-500 mt-0.5">{{ $esAdmin ? 'Cada cambio queda en la bitácora con su motivo.' : 'Los define el Administrador.' }}</p>
            </div>
            <form method="POST" action="{{ route('alertas.parametros') }}" class="divide-y divide-slate-100">
                @csrf
                @method('PUT')
                @foreach ($parametrosEditables as $pa)
                    <div class="px-5 py-3 flex items-start justify-between gap-3">
                        <label for="param-{{ $pa->clave }}" class="text-xs text-slate-700 min-w-0">
                            <span class="block font-mono text-[11px] text-slate-500">{{ $pa->clave }}</span>
                            {{ $pa->descripcion }}
                        </label>
                        @if ($esAdmin)
                            <span class="flex items-center gap-1 shrink-0">
                                <input id="param-{{ $pa->clave }}" type="number" step="0.5" name="valores[{{ $pa->clave }}]" value="{{ old('valores.'.$pa->clave, (float) $pa->valor) }}"
                                       class="sig-input w-20 text-right text-xs py-1.5 tabular-nums">
                                <span class="text-[11px] text-slate-500 w-10">{{ $pa->unidad }}</span>
                            </span>
                        @else
                            <span class="text-xs font-semibold tabular-nums shrink-0">{{ (float) $pa->valor }} {{ $pa->unidad }}</span>
                        @endif
                    </div>
                @endforeach
                @if ($esAdmin)
                    <div class="px-5 py-4 space-y-2">
                        <label for="motivo-umbrales" class="block text-xs font-semibold text-slate-700">Motivo del cambio</label>
                        <input id="motivo-umbrales" name="motivo" type="text" required minlength="5" maxlength="200" class="sig-input" placeholder="Ej.: Acuerdo de la PMO sobre plazos de aviso">
                        <button type="submit" class="sig-btn-primary w-full">Guardar umbrales</button>
                    </div>
                @endif
            </form>
        </section>
    </div>
</div>
@endsection
