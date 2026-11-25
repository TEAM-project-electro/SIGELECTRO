@extends('layouts.app')

@section('title', 'Riesgos de la cartera')

@section('content')
@php
    $variante = ['Crítico' => 'danger', 'Medio' => 'warning', 'Bajo' => 'success'];
    $abiertos = $riesgos->where('estado', '<>', 'Cerrado');
@endphp
<div class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Riesgos e incidencias de la cartera</h1>
            <p class="text-sm text-slate-600 mt-1">Exposición = probabilidad × impacto (1 a 25). Los riesgos se registran desde la ficha de cada proyecto.</p>
        </div>
        <form method="GET" action="{{ route('riesgos.index') }}" class="flex items-end gap-2">
            <div>
                <label for="estado" class="block text-xs font-semibold text-slate-700 mb-1">Mostrar</label>
                <select id="estado" name="estado" class="sig-input text-xs py-2" onchange="this.form.submit()">
                    <option value="abiertos" @selected($estado === 'abiertos')>Abiertos y en tratamiento</option>
                    <option value="todos" @selected($estado === 'todos')>Todos, incluidos cerrados</option>
                </select>
            </div>
            <noscript><button class="sig-btn-secondary py-2">Aplicar</button></noscript>
        </form>
    </div>

    <section class="grid grid-cols-1 sm:grid-cols-3 gap-4" aria-label="Resumen de riesgos">
        <div class="sig-panel p-5">
            <p class="text-xs font-semibold text-slate-600">Riesgos e incidencias abiertos</p>
            <p class="text-[30px] leading-tight font-bold text-slate-900 mt-1.5 tabular-nums">{{ $abiertos->count() }}</p>
            <p class="text-xs text-slate-500 mt-2 tabular-nums">{{ $abiertos->where('tipo', 'Incidencia')->count() }} incidencias ya ocurridas</p>
        </div>
        <div class="sig-panel p-5 {{ $abiertos->where('nivel', 'Crítico')->count() ? 'bg-rose-50/35' : '' }}">
            <p class="text-xs font-semibold text-slate-600">Críticos abiertos</p>
            <p class="text-[30px] leading-tight font-bold mt-1.5 tabular-nums {{ $abiertos->where('nivel', 'Crítico')->count() ? 'text-rose-700' : 'text-energy-700' }}">{{ $abiertos->where('nivel', 'Crítico')->count() }}</p>
            <p class="text-xs text-slate-500 mt-2">Exposición ≥ {{ $parametros['exposicion_critica'] + 0 }}</p>
        </div>
        <div class="sig-panel p-5">
            <p class="text-xs font-semibold text-slate-600">Proyectos con riesgo crítico</p>
            <p class="text-[30px] leading-tight font-bold text-slate-900 mt-1.5 tabular-nums">{{ $proyectosCriticos->count() }}</p>
            <p class="text-xs text-slate-500 mt-2 font-mono">{{ $proyectosCriticos->implode(', ') ?: '—' }}</p>
        </div>
    </section>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <section class="xl:col-span-4 sig-panel p-5" aria-labelledby="titulo-matriz">
            <h2 id="titulo-matriz" class="text-sm font-bold text-slate-900 mb-3">Matriz de riesgos abiertos</h2>
            @include('riesgos.partials.matriz', ['matriz' => $matriz, 'parametros' => $parametros])
        </section>

        <section class="xl:col-span-8 sig-panel overflow-hidden" aria-labelledby="titulo-lista">
            <div class="px-5 py-3.5 border-b border-slate-200/80">
                <h2 id="titulo-lista" class="text-sm font-bold text-slate-900">Registro ({{ $riesgos->count() }})</h2>
            </div>
            @if ($riesgos->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-slate-500">No hay riesgos registrados. Regístrelos desde la ficha de cada proyecto.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50/90 text-slate-600 text-left">
                            <tr>
                                <th class="px-5 py-2.5 font-semibold">Proyecto</th>
                                <th class="px-4 py-2.5 font-semibold">Riesgo o incidencia</th>
                                <th class="px-4 py-2.5 font-semibold text-center">P × I</th>
                                <th class="px-4 py-2.5 font-semibold">Dueño</th>
                                <th class="px-5 py-2.5 font-semibold">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($riesgos as $g)
                                <tr class="align-top">
                                    <td class="px-5 py-2.5 whitespace-nowrap">
                                        <a href="{{ route('proyectos.show', $g->id_proyecto) }}#riesgos" class="font-mono font-semibold text-brand-800 hover:underline">{{ $g->codigo_pgd }}</a>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <p class="text-slate-800"><span class="text-[11px] font-semibold text-slate-500">{{ $g->tipo }}:</span> {{ $g->descripcion }}</p>
                                        <p class="text-[11px] text-slate-500 mt-0.5">Respuesta: {{ $g->plan_respuesta }}</p>
                                    </td>
                                    <td class="px-4 py-2.5 text-center whitespace-nowrap">
                                        <x-badge size="sm" :variant="$variante[$g->nivel]">{{ $g->probabilidad }}×{{ $g->impacto }} = {{ $g->exposicion }}</x-badge>
                                    </td>
                                    <td class="px-4 py-2.5 text-slate-600 whitespace-nowrap">{{ $g->dueno }}</td>
                                    <td class="px-5 py-2.5 whitespace-nowrap text-slate-700">{{ $g->estado }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
