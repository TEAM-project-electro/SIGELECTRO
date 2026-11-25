@extends('layouts.app')

@section('title', 'Priorización de la cartera')

@section('content')
@php
    $colores = [
        'Estratégico' => ['bg-brand-600', 'bg-brand-50 text-brand-800 border-brand-200'],
        'Servicio eléctrico' => ['bg-energy-500', 'bg-energy-50 text-energy-700 border-energy-100'],
        'Económico' => ['bg-amber-500', 'bg-amber-50 text-amber-800 border-amber-200'],
        'Ejecución' => ['bg-slate-500', 'bg-slate-100 text-slate-700 border-slate-200'],
    ];
    $grupoDe = $criterios->pluck('grupo', 'id_criterio');
    $completos = $filas->where('completo', true)->values();
    $incompletos = $filas->where('completo', false)->values();
    $seleccion = $proyectoSeleccionado ? $filas->firstWhere('id_proyecto', $proyectoSeleccionado) : null;
@endphp

<div class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Priorización multicriterio de la cartera</h1>
            <p class="text-sm text-slate-600 mt-1">Cada proyecto se califica de 1 a 5 en cada criterio, con sustento. El puntaje (0–100) pondera las calificaciones con los pesos vigentes.</p>
        </div>
    </div>

    @if ($pesos_iguales)
        <x-alert type="warning">
            <strong>Pesos iniciales iguales.</strong> Los {{ $vigentes->count() }} criterios tienen el mismo peso hasta que el Comité de Gobierno Digital apruebe los definitivos. El ranking es válido como ejercicio, no como decisión.
        </x-alert>
    @endif

    {{-- Resumen --}}
    <section class="grid grid-cols-1 sm:grid-cols-3 gap-4" aria-label="Resumen de la priorización">
        <div class="sig-panel p-5">
            <p class="text-xs font-semibold text-slate-600">Proyectos evaluados</p>
            <p class="text-[30px] leading-tight font-bold text-slate-900 mt-1.5 tabular-nums">{{ $evaluados }} <span class="text-base font-semibold text-slate-400">de {{ $filas->count() }}</span></p>
            <p class="text-xs text-slate-500 mt-2">Solo los proyectos con todas las calificaciones entran al ranking.</p>
        </div>
        <div class="sig-panel p-5">
            <p class="text-xs font-semibold text-slate-600">Criterios vigentes</p>
            <p class="text-[30px] leading-tight font-bold text-slate-900 mt-1.5 tabular-nums">{{ $vigentes->count() }}</p>
            <p class="text-xs text-slate-500 mt-2">Suma de pesos: <span class="tabular-nums font-semibold">{{ number_format($suma_pesos, 2) }}</span></p>
        </div>
        <div class="sig-panel p-5 {{ $sensibilidad['aplica'] && !$sensibilidad['estable'] ? 'bg-amber-50/40' : '' }}">
            <p class="text-xs font-semibold text-slate-600">Sensibilidad del primer lugar (pesos ±10 %)</p>
            @if (!$sensibilidad['aplica'])
                <p class="text-lg font-bold text-slate-500 mt-2">Sin datos</p>
                <p class="text-xs text-slate-500 mt-1">Se necesitan al menos 2 proyectos evaluados.</p>
            @elseif ($sensibilidad['estable'])
                <p class="text-lg font-bold text-energy-700 mt-2">Estable</p>
                <p class="text-xs text-slate-500 mt-1">El primer lugar no cambia al variar cualquier peso.</p>
            @else
                <p class="text-lg font-bold text-amber-800 mt-2">Sensible</p>
                <p class="text-xs text-slate-600 mt-1">Cambia con: {{ implode(', ', $sensibilidad['criterios']) }}.</p>
            @endif
        </div>
    </section>

    {{-- Ranking --}}
    <section class="sig-panel overflow-hidden" aria-labelledby="titulo-ranking">
        <div class="px-5 py-3.5 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-3">
            <h2 id="titulo-ranking" class="text-sm font-bold text-slate-900">Ranking de prioridad</h2>
            <div class="flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-slate-600">
                @foreach ($colores as $grupo => [$barra])
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm {{ $barra }}" aria-hidden="true"></span>{{ $grupo }}</span>
                @endforeach
            </div>
        </div>
        @if ($completos->isEmpty())
            <div class="px-5 py-10 text-center text-sm text-slate-500">
                Aún no hay proyectos con todas las calificaciones. {{ $esAdmin ? 'Califique un proyecto en la sección inferior.' : 'El Administrador debe calificarlos.' }}
            </div>
        @else
            <ol class="divide-y divide-slate-100">
                @foreach ($completos as $f)
                    @php
                        $porGrupo = collect($f['aportes'])->groupBy(fn ($v, $id) => $grupoDe[$id] ?? 'Ejecución', true)->map(fn ($g) => $g->sum());
                    @endphp
                    <li class="px-5 py-3.5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="shrink-0 w-7 h-7 rounded-lg bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center tabular-nums">{{ $loop->iteration }}</span>
                                <div class="min-w-0">
                                    <a href="{{ route('proyectos.show', $f['id_proyecto']) }}" class="text-sm font-semibold text-slate-900 hover:text-brand-800 hover:underline">
                                        <span class="font-mono text-brand-800">{{ $f['codigo_pgd'] }}</span> · {{ \Illuminate\Support\Str::limit($f['nombre'], 70) }}
                                    </a>
                                    <p class="text-[11px] text-slate-500 tabular-nums">{{ $f['responsable'] }} · S/ {{ number_format($f['monto'], 2) }} · {{ $f['estado'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <span class="text-lg font-bold text-slate-900 tabular-nums">{{ number_format($f['puntaje'], 1) }}</span>
                                @if ($esAdmin)
                                    <a href="{{ route('priorizacion.index', ['proyecto' => $f['id_proyecto']]) }}#evaluar" class="text-xs font-semibold text-brand-700 hover:underline">Recalificar</a>
                                @endif
                            </div>
                        </div>
                        <div class="mt-2.5 pl-10">
                            <div class="h-2.5 w-full rounded-full bg-slate-100 overflow-hidden flex" role="img"
                                 aria-label="Puntaje {{ number_format($f['puntaje'], 1) }} de 100: {{ $porGrupo->map(fn ($v, $g) => $g.' '.number_format($v, 1))->implode(', ') }}">
                                @foreach ($colores as $grupo => [$barra])
                                    @if (($porGrupo[$grupo] ?? 0) > 0)
                                        <div class="h-full {{ $barra }}" style="width: {{ $porGrupo[$grupo] }}%" title="{{ $grupo }}: {{ number_format($porGrupo[$grupo], 1) }} puntos"></div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
        @if ($incompletos->isNotEmpty())
            <div class="px-5 py-3 border-t border-slate-200/80 bg-slate-50/70 text-xs text-slate-600">
                <span class="font-semibold text-slate-700">Sin evaluación completa ({{ $incompletos->count() }}):</span>
                @foreach ($incompletos as $f)
                    <span class="inline-block mr-2">
                        @if ($esAdmin)
                            <a href="{{ route('priorizacion.index', ['proyecto' => $f['id_proyecto']]) }}#evaluar" class="font-mono text-brand-800 hover:underline">{{ $f['codigo_pgd'] }}</a>
                        @else
                            <span class="font-mono">{{ $f['codigo_pgd'] }}</span>
                        @endif
                        <span class="text-slate-400">(faltan {{ $f['faltantes'] }})</span>
                    </span>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Criterios y pesos --}}
    <section class="sig-panel overflow-hidden" aria-labelledby="titulo-criterios">
        <div class="px-5 py-3.5 border-b border-slate-200/80">
            <h2 id="titulo-criterios" class="text-sm font-bold text-slate-900">Criterios y pesos</h2>
            <p class="text-xs text-slate-500 mt-0.5">Escala de cada criterio: 1 = muy bajo, 5 = muy alto. Los cambios de peso quedan en la bitácora con su motivo.</p>
        </div>
        <form method="POST" action="{{ route('priorizacion.pesos') }}">
            @csrf
            @method('PUT')
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50/90 text-slate-600 text-left">
                        <tr>
                            <th class="px-5 py-2.5 font-semibold">Criterio</th>
                            <th class="px-4 py-2.5 font-semibold">Grupo</th>
                            <th class="px-4 py-2.5 font-semibold text-right">Peso</th>
                            <th class="px-4 py-2.5 font-semibold text-right">% del total</th>
                            <th class="px-5 py-2.5 font-semibold text-center">Activo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($criterios as $c)
                            <tr class="{{ $c->activo ? '' : 'opacity-60' }}">
                                <td class="px-5 py-2.5">
                                    <p class="font-semibold text-slate-800">{{ $c->nombre }}</p>
                                    <p class="text-[11px] text-slate-500">{{ $c->descripcion }}</p>
                                </td>
                                <td class="px-4 py-2.5"><span class="px-2 py-0.5 rounded-full border text-[11px] font-medium {{ $colores[$c->grupo][1] ?? '' }}">{{ $c->grupo }}</span></td>
                                <td class="px-4 py-2.5 text-right">
                                    @if ($esAdmin)
                                        <label class="sr-only" for="peso-{{ $c->id_criterio }}">Peso de {{ $c->nombre }}</label>
                                        <input id="peso-{{ $c->id_criterio }}" type="number" name="pesos[{{ $c->id_criterio }}]" value="{{ old('pesos.'.$c->id_criterio, (float) $c->peso) }}" min="0" max="100" step="0.5"
                                               class="sig-input w-20 text-right text-xs py-1.5 tabular-nums">
                                    @else
                                        <span class="tabular-nums">{{ number_format((float) $c->peso, 2) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-right tabular-nums text-slate-600">
                                    {{ $c->activo && $suma_pesos > 0 ? number_format((float) $c->peso / $suma_pesos * 100, 1).' %' : '—' }}
                                </td>
                                <td class="px-5 py-2.5 text-center">
                                    @if ($esAdmin)
                                        <input type="checkbox" name="activos[{{ $c->id_criterio }}]" value="1" @checked($c->activo) aria-label="Criterio {{ $c->nombre }} activo"
                                               class="rounded border-slate-300 text-brand-700 focus:ring-brand-600">
                                    @else
                                        {{ $c->activo ? 'Sí' : 'No' }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($esAdmin)
                <div class="px-5 py-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-end gap-3">
                    <div class="flex-1">
                        <label for="motivo-pesos" class="block text-xs font-semibold text-slate-700 mb-1.5">Motivo del cambio de pesos</label>
                        <input id="motivo-pesos" name="motivo" type="text" required minlength="5" maxlength="200" value="{{ old('motivo') }}"
                               placeholder="Ej.: Pesos aprobados en el acta 03-2026 del Comité de Gobierno Digital" class="sig-input">
                    </div>
                    <button type="submit" class="sig-btn-primary">Guardar pesos</button>
                </div>
            @endif
        </form>
    </section>

    {{-- Calificación de un proyecto --}}
    @if ($esAdmin)
        <section id="evaluar" class="sig-panel overflow-hidden" aria-labelledby="titulo-evaluar">
            <div class="px-5 py-3.5 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-end justify-between gap-3">
                <div>
                    <h2 id="titulo-evaluar" class="text-sm font-bold text-slate-900">Calificar un proyecto</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Cada calificación exige un sustento verificable (documento, dato o acuerdo).</p>
                </div>
                <form method="GET" action="{{ route('priorizacion.index') }}" class="flex gap-2 items-end">
                    <div>
                        <label for="proyecto-evaluar" class="sr-only">Proyecto</label>
                        <select id="proyecto-evaluar" name="proyecto" class="sig-input text-xs py-2" onchange="this.form.submit()">
                            <option value="">Seleccione un proyecto…</option>
                            @foreach ($filas as $f)
                                <option value="{{ $f['id_proyecto'] }}" @selected($proyectoSeleccionado === $f['id_proyecto'])>{{ $f['codigo_pgd'] }} — {{ \Illuminate\Support\Str::limit($f['nombre'], 50) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <noscript><button type="submit" class="sig-btn-secondary py-2">Ver</button></noscript>
                </form>
            </div>

            @if ($seleccion)
                <form method="POST" action="{{ route('priorizacion.evaluar', $seleccion['id_proyecto']) }}" class="divide-y divide-slate-100">
                    @csrf
                    @foreach ($vigentes as $c)
                        @php $previa = $seleccion['evaluaciones']->get($c->id_criterio); @endphp
                        <fieldset class="px-5 py-3.5 grid grid-cols-1 lg:grid-cols-12 gap-3 items-start">
                            <legend class="sr-only">{{ $c->nombre }}</legend>
                            <div class="lg:col-span-4">
                                <p class="text-xs font-semibold text-slate-800">{{ $c->nombre }}</p>
                                <p class="text-[11px] text-slate-500">{{ $c->descripcion }}</p>
                                @if ($previa)
                                    <p class="text-[11px] text-slate-400 mt-1">Última: {{ $previa->evaluador }}, {{ $previa->fecha_hora }}</p>
                                @endif
                            </div>
                            <div class="lg:col-span-3 flex gap-1.5" role="radiogroup" aria-label="Calificación de {{ $c->nombre }}">
                                @foreach ([1, 2, 3, 4, 5] as $n)
                                    <label class="cursor-pointer">
                                        <input type="radio" class="sr-only peer" name="puntajes[{{ $c->id_criterio }}]" value="{{ $n }}" required
                                               @checked((int) old('puntajes.'.$c->id_criterio, $previa->puntaje ?? 0) === $n)>
                                        <span class="w-9 h-9 rounded-lg border border-slate-300 flex items-center justify-center text-xs font-semibold text-slate-600 peer-checked:bg-brand-800 peer-checked:text-white peer-checked:border-brand-800 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600 hover:border-brand-400 transition-colors">{{ $n }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="lg:col-span-5">
                                <label for="sustento-{{ $c->id_criterio }}" class="sr-only">Sustento de {{ $c->nombre }}</label>
                                <input id="sustento-{{ $c->id_criterio }}" type="text" name="sustentos[{{ $c->id_criterio }}]" required minlength="10" maxlength="300"
                                       value="{{ old('sustentos.'.$c->id_criterio, $previa->sustento ?? '') }}" placeholder="Sustento (mín. 10 caracteres)…" class="sig-input text-xs">
                            </div>
                        </fieldset>
                    @endforeach
                    <div class="px-5 py-4 flex justify-end">
                        <button type="submit" class="sig-btn-primary">Guardar calificación de {{ $seleccion['codigo_pgd'] }}</button>
                    </div>
                </form>
            @else
                <p class="px-5 py-8 text-center text-sm text-slate-500">Seleccione un proyecto para calificarlo.</p>
            @endif
        </section>
    @endif
</div>
@endsection
