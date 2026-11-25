{{-- Gestión gerencial del proyecto. Recibe $gestion (FichaGerencialService) y $proyecto. --}}
@php
    $puede = Gate::allows('gestionar-proyecto', $proyecto);
    $esAdmin = Gate::allows('administrar-cartera');
    $abierto = $proyecto->estado !== 'Cerrado';
    $pp = $gestion['presupuesto'];
    $vg = $gestion['valor_ganado'];
    $soles = fn ($v) => $v === null ? '—' : 'S/ '.number_format((float) $v, 2);
    $variante = ['Crítico' => 'danger', 'Medio' => 'warning', 'Bajo' => 'success'];
    $responsables = \App\Models\Responsable::where('activo', 1)->orderBy('nombre')->get(['id_responsable', 'nombre']);
    $pestanas = [
        'presupuesto' => 'Presupuesto',
        'avance' => 'Avance y valor ganado',
        'riesgos' => 'Riesgos ('.$gestion['riesgos']->where('estado', '<>', 'Cerrado')->count().')',
        'documentos' => 'Documentos ('.$gestion['documentos']->count().')',
        'beneficios' => 'Beneficios ('.$gestion['beneficios']->count().')',
        'historial' => 'Historial',
    ];
    $tablaLegible = [
        'proyecto' => 'Proyecto', 'hito' => 'Hito', 'avance_presupuestal' => 'Corte', 'riesgo_proyecto' => 'Riesgo',
        'compromiso_presupuestal' => 'Compromiso', 'documento_proyecto' => 'Documento', 'beneficio_proyecto' => 'Beneficio',
        'evaluacion_priorizacion' => 'Priorización', 'hito_peso' => 'Peso de hito',
    ];
@endphp

<section id="gestion" class="sig-panel overflow-hidden scroll-mt-6" aria-labelledby="titulo-gestion"
         x-data="{ pestana: (['presupuesto','avance','riesgos','documentos','beneficios','historial'].includes(location.hash.slice(1)) ? location.hash.slice(1) : 'presupuesto') }">
    <span id="presupuesto" class="block scroll-mt-6"></span><span id="avance" class="block"></span><span id="riesgos" class="block"></span>
    <span id="documentos" class="block"></span><span id="beneficios" class="block"></span><span id="historial" class="block"></span>

    <div class="px-5 pt-4 border-b border-slate-200/80">
        <h2 id="titulo-gestion" class="text-sm font-bold text-slate-900">Gestión gerencial del proyecto</h2>
        <div class="flex gap-1 overflow-x-auto mt-3 -mb-px" role="tablist" aria-label="Secciones de gestión">
            @foreach ($pestanas as $clave => $texto)
                <button type="button" role="tab" :aria-selected="pestana === '{{ $clave }}'" @click="pestana = '{{ $clave }}'; history.replaceState(null, '', '#{{ $clave }}')"
                        :class="pestana === '{{ $clave }}' ? 'border-brand-700 text-brand-900 font-semibold' : 'border-transparent text-slate-600 hover:text-slate-900'"
                        class="px-3 py-2 text-xs whitespace-nowrap border-b-2 transition-colors">{{ $texto }}</button>
            @endforeach
        </div>
    </div>

    {{-- ===================== PRESUPUESTO ===================== --}}
    <div x-show="pestana === 'presupuesto'" x-cloak class="p-5 space-y-5" role="tabpanel">
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            @foreach ([
                ['Aprobado inicial', $pp['inicial'], 'Primera versión registrada'],
                ['Vigente', $pp['vigente'], count($pp['versiones']) > 1 ? (count($pp['versiones']) - 1).' modificación(es)' : 'Sin modificaciones'],
                ['Comprometido', $pp['comprometido'], $pp['compromisos']->where('estado', 'Vigente')->count().' compromiso(s) vigente(s)'],
                ['Devengado', $pp['devengado'], $pp['fecha_devengado'] ? 'Corte del '.$pp['fecha_devengado'] : 'Sin cortes'],
                ['Por comprometer', $pp['por_comprometer'], 'Vigente − comprometido'],
            ] as [$titulo, $valor, $nota])
                <div class="rounded-xl border border-slate-200 p-3.5">
                    <p class="text-[11px] font-semibold text-slate-500">{{ $titulo }}</p>
                    <p class="text-base font-bold text-slate-900 tabular-nums mt-1">{{ $soles($valor) }}</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">{{ $nota }}</p>
                </div>
            @endforeach
        </div>

        @if ($pp['vigente'] > 0)
            @php
                $pctComp = min(100, $pp['comprometido'] / $pp['vigente'] * 100);
                $pctDev = min(100, $pp['devengado'] / $pp['vigente'] * 100);
            @endphp
            <div>
                <div class="relative h-3 w-full rounded-full bg-slate-100 overflow-hidden" role="img"
                     aria-label="Del presupuesto vigente: {{ number_format($pctComp, 1) }} % comprometido y {{ number_format($pctDev, 1) }} % devengado">
                    <div class="absolute inset-y-0 left-0 bg-brand-200" style="width: {{ $pctComp }}%"></div>
                    <div class="absolute inset-y-0 left-0 bg-brand-700" style="width: {{ $pctDev }}%"></div>
                </div>
                <div class="flex flex-wrap gap-x-4 mt-1.5 text-[11px] text-slate-600">
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-brand-700" aria-hidden="true"></span>Devengado {{ number_format($pctDev, 1) }} %</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-brand-200" aria-hidden="true"></span>Comprometido {{ number_format($pctComp, 1) }} %</span>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
            <div class="space-y-2">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Compromisos (órdenes, contratos)</h3>
                @if ($pp['compromisos']->isEmpty())
                    <p class="text-xs text-slate-500">Sin compromisos registrados.</p>
                @else
                    <ul class="divide-y divide-slate-100 rounded-xl border border-slate-200">
                        @foreach ($pp['compromisos'] as $c)
                            <li class="px-3.5 py-2.5 text-xs {{ $c->estado === 'Anulado' ? 'opacity-60' : '' }}">
                                <div class="flex flex-wrap justify-between gap-2">
                                    <span><span class="font-mono font-semibold text-slate-800">{{ $c->documento }}</span> · {{ $c->descripcion }}</span>
                                    <span class="tabular-nums font-semibold {{ $c->estado === 'Anulado' ? 'line-through' : '' }}">{{ $soles($c->monto) }}</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5">{{ $c->fecha }} · {{ $c->estado }} · {{ $c->responsable }}</p>
                                @if ($esAdmin && $c->estado === 'Vigente')
                                    <details class="mt-1.5">
                                        <summary class="cursor-pointer text-[11px] font-semibold text-rose-700">Anular</summary>
                                        <form method="POST" action="{{ route('compromisos.anular', $c->id_compromiso) }}" class="flex gap-2 mt-1.5">
                                            @csrf @method('PATCH')
                                            <label class="sr-only" for="motivo-anular-{{ $c->id_compromiso }}">Motivo de anulación</label>
                                            <input id="motivo-anular-{{ $c->id_compromiso }}" name="motivo" required minlength="5" maxlength="200" class="sig-input text-xs py-1.5" placeholder="Motivo de la anulación…">
                                            <button class="sig-btn-secondary py-1.5 text-rose-700">Anular</button>
                                        </form>
                                    </details>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
                @if ($esAdmin)
                    <details class="rounded-xl border border-dashed border-slate-300 p-3.5">
                        <summary class="cursor-pointer text-xs font-semibold text-brand-800">Registrar compromiso</summary>
                        <form method="POST" action="{{ route('proyectos.compromisos.store', $proyecto) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                            @csrf
                            <div><label class="block text-[11px] font-semibold text-slate-700 mb-1" for="comp-doc">Documento</label><input id="comp-doc" name="documento" required maxlength="60" class="sig-input text-xs" placeholder="OC-2026-0045"></div>
                            <div><label class="block text-[11px] font-semibold text-slate-700 mb-1" for="comp-monto">Monto (S/)</label><input id="comp-monto" name="monto" type="number" step="0.01" min="0.01" required class="sig-input text-xs tabular-nums"></div>
                            <div class="sm:col-span-2"><label class="block text-[11px] font-semibold text-slate-700 mb-1" for="comp-desc">Descripción</label><input id="comp-desc" name="descripcion" required minlength="5" maxlength="200" class="sig-input text-xs"></div>
                            <div><label class="block text-[11px] font-semibold text-slate-700 mb-1" for="comp-fecha">Fecha</label><input id="comp-fecha" name="fecha" type="date" required value="{{ now()->toDateString() }}" class="sig-input text-xs font-mono"></div>
                            <div class="flex items-end"><button class="sig-btn-primary w-full">Registrar</button></div>
                        </form>
                    </details>
                @endif
            </div>

            <div class="space-y-2">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Versiones del presupuesto</h3>
                <ol class="divide-y divide-slate-100 rounded-xl border border-slate-200">
                    @foreach ($pp['versiones']->sortByDesc('version') as $v)
                        <li class="px-3.5 py-2.5 text-xs">
                            <div class="flex justify-between gap-2">
                                <span class="font-semibold text-slate-800">v{{ $v->version }}
                                    @if ($v->monto_anterior !== null)
                                        <span class="font-normal text-slate-500 tabular-nums">{{ $soles($v->monto_anterior) }} →</span>
                                    @endif
                                </span>
                                <span class="tabular-nums font-semibold">{{ $soles($v->monto) }}</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">{{ $v->fecha_hora }} · {{ $v->responsable }}{{ $v->motivo ? ' · '.$v->motivo : '' }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>

    {{-- ===================== AVANCE Y VALOR GANADO ===================== --}}
    <div x-show="pestana === 'avance'" x-cloak class="p-5 space-y-5" role="tabpanel">
        <p class="text-xs text-slate-600">
            Evaluado al <span class="font-mono font-semibold">{{ $vg['fecha'] }}</span>{{ $vg['fecha_corte'] ? ' (último corte del proyecto)' : '' }}.
            El avance físico pondera los hitos de línea base cerrados{{ $vg['ponderado'] ? ' con los pesos aprobados' : '; sin pesos aprobados usa igual peso para todos' }}.
        </p>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach ([
                ['Avance físico', $vg['avance_fisico'] !== null ? number_format($vg['avance_fisico'], 2).' %' : '—', $vg['ponderado'] ? 'Ponderado' : 'Sin ponderar'],
                ['Valor ganado (EV)', $soles($vg['ev']), 'Avance × presupuesto vigente'],
                ['Valor planificado (PV)', $soles($vg['pv']), 'Plan acumulado del corte'],
                ['Costo real (AC)', $soles($vg['ac']), 'Gasto acumulado del corte'],
            ] as [$titulo, $valor, $nota])
                <div class="rounded-xl border border-slate-200 p-3.5">
                    <p class="text-[11px] font-semibold text-slate-500">{{ $titulo }}</p>
                    <p class="text-base font-bold text-slate-900 tabular-nums mt-1">{{ $valor }}</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">{{ $nota }}</p>
                </div>
            @endforeach
        </div>

        @if ($vg['motivo_sin_indices'])
            <x-alert type="info">SPI, CPI y EAC no se calculan: {{ $vg['motivo_sin_indices'] }} Sin esa base, un índice parecería exacto sin serlo.</x-alert>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach ([
                    ['SPI (cronograma)', $vg['spi'], $vg['spi'] >= 1 ? 'Avanza igual o mejor que lo planificado.' : 'Avanza más lento que lo planificado.'],
                    ['CPI (costo)', $vg['cpi'], $vg['cpi'] >= 1 ? 'Cada sol gastado rinde lo previsto o más.' : 'Cada sol gastado rinde menos de lo previsto.'],
                ] as [$titulo, $valor, $lectura])
                    <div class="rounded-xl border p-3.5 {{ $valor >= 1 ? 'border-energy-100 bg-energy-50/50' : ($valor >= 0.9 ? 'border-amber-200 bg-amber-50/50' : 'border-rose-200 bg-rose-50/50') }}">
                        <p class="text-[11px] font-semibold text-slate-500">{{ $titulo }}</p>
                        <p class="text-2xl font-bold text-slate-900 tabular-nums mt-0.5">{{ number_format($valor, 2) }}</p>
                        <p class="text-[11px] text-slate-600 mt-0.5">{{ $lectura }}</p>
                    </div>
                @endforeach
                <div class="rounded-xl border border-slate-200 p-3.5">
                    <p class="text-[11px] font-semibold text-slate-500">EAC (costo estimado al terminar)</p>
                    <p class="text-base font-bold text-slate-900 tabular-nums mt-1">{{ $soles($vg['eac']) }}</p>
                    <p class="text-[11px] {{ $vg['vac'] < 0 ? 'text-rose-700' : 'text-slate-600' }} mt-0.5">Variación al terminar: {{ $soles($vg['vac']) }}</p>
                </div>
            </div>
        @endif

        <div class="space-y-2">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ponderación de hitos de línea base</h3>
            @if ($vg['hitos']->isEmpty())
                <p class="text-xs text-slate-500">El proyecto no tiene hitos de línea base.</p>
            @elseif ($esAdmin && $abierto)
                <form method="POST" action="{{ route('proyectos.pesos', $proyecto) }}"
                      x-data="{ pesos: @js($vg['hitos']->mapWithKeys(fn ($h) => [$h->id_hito => $h->peso !== null ? (float) $h->peso : round(100 / max(1, $vg['hitos']->count()), 2)])) }"
                      class="rounded-xl border border-slate-200 divide-y divide-slate-100">
                    @csrf @method('PUT')
                    @foreach ($vg['hitos'] as $h)
                        <div class="px-3.5 py-2 flex items-center justify-between gap-3 text-xs">
                            <label for="peso-hito-{{ $h->id_hito }}" class="min-w-0">
                                <span class="text-slate-800">{{ $h->descripcion }}</span>
                                <span class="block text-[11px] text-slate-500 font-mono">{{ $h->fecha_prevista }} · {{ $h->fecha_cierre ? 'cerrado '.$h->fecha_cierre : 'abierto' }}</span>
                            </label>
                            <input id="peso-hito-{{ $h->id_hito }}" type="number" step="0.5" min="0.5" max="100" required name="pesos[{{ $h->id_hito }}]"
                                   x-model.number="pesos[{{ $h->id_hito }}]" class="sig-input w-24 text-right text-xs py-1.5 tabular-nums">
                        </div>
                    @endforeach
                    <div class="px-3.5 py-3 flex flex-col sm:flex-row sm:items-end gap-3">
                        <p class="text-xs tabular-nums" :class="Math.abs(Object.values(pesos).reduce((a, b) => a + (+b || 0), 0) - 100) < 0.01 ? 'text-energy-700' : 'text-rose-700'">
                            Suma: <span class="font-bold" x-text="Object.values(pesos).reduce((a, b) => a + (+b || 0), 0).toFixed(2)"></span> de 100
                        </p>
                        <div class="flex-1"><label class="sr-only" for="motivo-pesos-hitos">Motivo</label><input id="motivo-pesos-hitos" name="motivo" required minlength="5" maxlength="200" class="sig-input text-xs" placeholder="Motivo (ej.: pesos acordados con el responsable según esfuerzo)"></div>
                        <button class="sig-btn-primary">Guardar pesos</button>
                    </div>
                </form>
            @else
                <ul class="rounded-xl border border-slate-200 divide-y divide-slate-100 text-xs">
                    @foreach ($vg['hitos'] as $h)
                        <li class="px-3.5 py-2 flex justify-between gap-3"><span>{{ $h->descripcion }}</span><span class="tabular-nums font-semibold">{{ $h->peso !== null ? number_format((float) $h->peso, 2) : 'sin peso' }}</span></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- ===================== RIESGOS ===================== --}}
    <div x-show="pestana === 'riesgos'" x-cloak class="p-5 space-y-5" role="tabpanel">
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-5">
            <div class="xl:col-span-4">@include('riesgos.partials.matriz', ['matriz' => $gestion['matriz'], 'parametros' => $gestion['parametros']])</div>
            <div class="xl:col-span-8 space-y-2">
                @forelse ($gestion['riesgos'] as $g)
                    <div class="rounded-xl border border-slate-200 p-3.5 text-xs {{ $g->estado === 'Cerrado' ? 'opacity-60' : '' }}">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <p class="text-slate-800"><span class="font-semibold text-slate-500">{{ $g->tipo }}:</span> {{ $g->descripcion }}</p>
                            <x-badge size="sm" :variant="$variante[$g->nivel]">{{ $g->nivel }} · {{ $g->probabilidad }}×{{ $g->impacto }}</x-badge>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-1">Respuesta: {{ $g->plan_respuesta }}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">{{ $g->estado }} · Dueño: {{ $g->dueno }} · Identificado {{ $g->fecha_identificacion }}{{ $g->fecha_cierre ? ' · Cerrado '.$g->fecha_cierre : '' }}</p>
                        @if ($puede)
                            <details class="mt-2">
                                <summary class="cursor-pointer text-[11px] font-semibold text-brand-800">Actualizar</summary>
                                <form method="POST" action="{{ route('riesgos.update', $g->id_riesgo) }}" class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-2">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="tipo" value="{{ $g->tipo }}">
                                    <div class="col-span-2 sm:col-span-4"><label class="sr-only" for="desc-{{ $g->id_riesgo }}">Descripción</label><input id="desc-{{ $g->id_riesgo }}" name="descripcion" value="{{ $g->descripcion }}" required minlength="5" maxlength="250" class="sig-input text-xs"></div>
                                    <div><label class="block text-[11px] text-slate-600" for="prob-{{ $g->id_riesgo }}">Probabilidad</label><select id="prob-{{ $g->id_riesgo }}" name="probabilidad" class="sig-input text-xs py-1.5">@foreach ([1,2,3,4,5] as $n)<option @selected($g->probabilidad == $n)>{{ $n }}</option>@endforeach</select></div>
                                    <div><label class="block text-[11px] text-slate-600" for="imp-{{ $g->id_riesgo }}">Impacto</label><select id="imp-{{ $g->id_riesgo }}" name="impacto" class="sig-input text-xs py-1.5">@foreach ([1,2,3,4,5] as $n)<option @selected($g->impacto == $n)>{{ $n }}</option>@endforeach</select></div>
                                    <div><label class="block text-[11px] text-slate-600" for="est-{{ $g->id_riesgo }}">Estado</label><select id="est-{{ $g->id_riesgo }}" name="estado" class="sig-input text-xs py-1.5">@foreach (['Abierto','En tratamiento','Cerrado'] as $e)<option @selected($g->estado === $e)>{{ $e }}</option>@endforeach</select></div>
                                    <div><label class="block text-[11px] text-slate-600" for="dueno-{{ $g->id_riesgo }}">Dueño</label><select id="dueno-{{ $g->id_riesgo }}" name="id_responsable" class="sig-input text-xs py-1.5">@foreach ($responsables as $r)<option value="{{ $r->id_responsable }}" @selected($g->id_responsable == $r->id_responsable)>{{ $r->nombre }}</option>@endforeach</select></div>
                                    <div class="col-span-2 sm:col-span-4"><label class="sr-only" for="plan-{{ $g->id_riesgo }}">Plan de respuesta</label><input id="plan-{{ $g->id_riesgo }}" name="plan_respuesta" value="{{ $g->plan_respuesta }}" required minlength="5" maxlength="300" class="sig-input text-xs"></div>
                                    <div class="col-span-2 sm:col-span-3"><label class="sr-only" for="motivo-r-{{ $g->id_riesgo }}">Motivo</label><input id="motivo-r-{{ $g->id_riesgo }}" name="motivo" required minlength="5" maxlength="200" class="sig-input text-xs" placeholder="Motivo de la actualización…"></div>
                                    <button class="sig-btn-primary">Guardar</button>
                                </form>
                            </details>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-slate-500">Sin riesgos ni incidencias registrados.</p>
                @endforelse

                @if ($puede)
                    <details class="rounded-xl border border-dashed border-slate-300 p-3.5" @if($errors->has('plan_respuesta') || $errors->has('probabilidad')) open @endif>
                        <summary class="cursor-pointer text-xs font-semibold text-brand-800">Registrar riesgo o incidencia</summary>
                        <form method="POST" action="{{ route('proyectos.riesgos.store', $proyecto) }}" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3">
                            @csrf
                            <div><label class="block text-[11px] font-semibold text-slate-700 mb-1" for="nr-tipo">Tipo</label><select id="nr-tipo" name="tipo" class="sig-input text-xs py-1.5"><option>Riesgo</option><option>Incidencia</option></select></div>
                            <div><label class="block text-[11px] font-semibold text-slate-700 mb-1" for="nr-prob">Probabilidad (1–5)</label><select id="nr-prob" name="probabilidad" class="sig-input text-xs py-1.5">@foreach ([1,2,3,4,5] as $n)<option @selected($n === 3)>{{ $n }}</option>@endforeach</select></div>
                            <div><label class="block text-[11px] font-semibold text-slate-700 mb-1" for="nr-imp">Impacto (1–5)</label><select id="nr-imp" name="impacto" class="sig-input text-xs py-1.5">@foreach ([1,2,3,4,5] as $n)<option @selected($n === 3)>{{ $n }}</option>@endforeach</select></div>
                            <div><label class="block text-[11px] font-semibold text-slate-700 mb-1" for="nr-dueno">Dueño</label><select id="nr-dueno" name="id_responsable" class="sig-input text-xs py-1.5">@foreach ($responsables as $r)<option value="{{ $r->id_responsable }}" @selected($proyecto->id_responsable == $r->id_responsable)>{{ $r->nombre }}</option>@endforeach</select></div>
                            <div class="col-span-2 sm:col-span-4"><label class="block text-[11px] font-semibold text-slate-700 mb-1" for="nr-desc">Descripción</label><input id="nr-desc" name="descripcion" required minlength="5" maxlength="250" class="sig-input text-xs" placeholder="Ej.: Demora del proveedor en la entrega de equipos"></div>
                            <div class="col-span-2 sm:col-span-3"><label class="block text-[11px] font-semibold text-slate-700 mb-1" for="nr-plan">Plan de respuesta</label><input id="nr-plan" name="plan_respuesta" required minlength="5" maxlength="300" class="sig-input text-xs" placeholder="Ej.: Penalidad contractual y proveedor alterno"></div>
                            <div class="flex items-end"><button class="sig-btn-primary w-full">Registrar</button></div>
                            <p class="col-span-2 sm:col-span-4 text-[11px] text-slate-500">Una incidencia ya ocurrió: se registra con probabilidad 5.</p>
                        </form>
                    </details>
                @endif
            </div>
        </div>
    </div>

    {{-- ===================== DOCUMENTOS ===================== --}}
    <div x-show="pestana === 'documentos'" x-cloak class="p-5 space-y-4" role="tabpanel">
        @if ($gestion['documentos']->isEmpty())
            <p class="text-xs text-slate-500">Sin documentos registrados.</p>
        @else
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50/90 text-slate-600 text-left">
                        <tr><th class="px-3.5 py-2 font-semibold">Documento</th><th class="px-3 py-2 font-semibold">Tipo</th><th class="px-3 py-2 font-semibold">Hito</th><th class="px-3 py-2 font-semibold text-right">Tamaño</th><th class="px-3.5 py-2 font-semibold">Registro</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($gestion['documentos'] as $d)
                            <tr>
                                <td class="px-3.5 py-2">
                                    <a href="{{ route('documentos.descargar', $d->id_documento) }}" class="font-semibold text-brand-800 hover:underline">{{ $d->titulo }}</a>
                                    <span class="block text-[11px] text-slate-500 font-mono" title="SHA-256 {{ $d->hash_sha256 }}">{{ $d->nombre_original }} · {{ substr($d->hash_sha256, 0, 12) }}…</span>
                                </td>
                                <td class="px-3 py-2">{{ $d->tipo }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $d->hito ?? '—' }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ number_format($d->tamano / 1024, 0) }} KB</td>
                                <td class="px-3.5 py-2 text-slate-600 whitespace-nowrap">{{ $d->responsable }}<span class="block text-[11px] text-slate-500 font-mono">{{ $d->fecha_hora }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        @if ($puede)
            <form method="POST" action="{{ route('proyectos.documentos.store', $proyecto) }}" enctype="multipart/form-data" class="rounded-xl border border-dashed border-slate-300 p-3.5 grid grid-cols-1 sm:grid-cols-4 gap-3">
                @csrf
                <div><label class="block text-[11px] font-semibold text-slate-700 mb-1" for="doc-tipo">Tipo</label><select id="doc-tipo" name="tipo" class="sig-input text-xs py-1.5">@foreach (\App\Http\Controllers\GestionProyectoController::TIPOS_DOCUMENTO as $t)<option>{{ $t }}</option>@endforeach</select></div>
                <div class="sm:col-span-3"><label class="block text-[11px] font-semibold text-slate-700 mb-1" for="doc-titulo">Título</label><input id="doc-titulo" name="titulo" required minlength="3" maxlength="150" class="sig-input text-xs" placeholder="Ej.: Acta de conformidad de la etapa 1"></div>
                <div class="sm:col-span-2"><label class="block text-[11px] font-semibold text-slate-700 mb-1" for="doc-hito">Hito relacionado (opcional)</label><select id="doc-hito" name="id_hito" class="sig-input text-xs py-1.5"><option value="">—</option>@foreach ($proyecto->hitos as $h)<option value="{{ $h->id_hito }}">{{ \Illuminate\Support\Str::limit($h->descripcion, 60) }}</option>@endforeach</select></div>
                <div class="sm:col-span-2"><label class="block text-[11px] font-semibold text-slate-700 mb-1" for="doc-archivo">Archivo (PDF, Office, imagen, TXT/CSV; máx. 10 MB)</label><input id="doc-archivo" name="archivo" type="file" required accept=".pdf,.docx,.xlsx,.pptx,.png,.jpg,.jpeg,.txt,.csv" class="block w-full text-xs text-slate-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-800 border border-slate-300 rounded-lg p-1 bg-white"></div>
                <div class="sm:col-span-4 flex justify-end"><button class="sig-btn-primary">Registrar documento</button></div>
                <p class="sm:col-span-4 text-[11px] text-slate-500">Los documentos no se modifican ni se borran; cada archivo queda con su huella SHA-256.</p>
            </form>
        @endif
    </div>

    {{-- ===================== BENEFICIOS ===================== --}}
    <div x-show="pestana === 'beneficios'" x-cloak class="p-5 space-y-4" role="tabpanel">
        @forelse ($gestion['beneficios'] as $b)
            <div class="rounded-xl border border-slate-200 p-3.5 text-xs space-y-1.5">
                <div class="flex flex-wrap justify-between gap-2">
                    <p class="font-semibold text-slate-800">{{ $b->indicador }}</p>
                    @if ($b->logro !== null)
                        <x-badge size="sm" :variant="$b->logro >= 100 ? 'success' : ($b->logro >= 70 ? 'warning' : 'danger')">Logro {{ number_format($b->logro, 1) }} %</x-badge>
                    @else
                        <x-badge size="sm" variant="neutral">Sin medir</x-badge>
                    @endif
                </div>
                <p class="text-slate-600">{{ $b->descripcion }}</p>
                <p class="text-[11px] text-slate-500 tabular-nums">Línea base {{ (float) $b->linea_base }} → meta {{ (float) $b->meta }} {{ $b->unidad }}{{ $b->valor_logrado !== null ? ' · logrado '.(float) $b->valor_logrado.' el '.$b->fecha_medicion : '' }}</p>
                @if ($b->logro !== null)
                    <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden"><div class="h-full rounded-full {{ $b->logro >= 100 ? 'bg-energy-500' : ($b->logro >= 70 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ max(0, min(100, $b->logro)) }}%"></div></div>
                @endif
                @if ($puede)
                    <details>
                        <summary class="cursor-pointer text-[11px] font-semibold text-brand-800">Registrar medición</summary>
                        <form method="POST" action="{{ route('beneficios.medir', $b->id_beneficio) }}" class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-2">
                            @csrf @method('PATCH')
                            <div><label class="block text-[11px] text-slate-600" for="logrado-{{ $b->id_beneficio }}">Valor logrado</label><input id="logrado-{{ $b->id_beneficio }}" name="valor_logrado" type="number" step="0.01" required class="sig-input text-xs py-1.5 tabular-nums"></div>
                            <div><label class="block text-[11px] text-slate-600" for="fmed-{{ $b->id_beneficio }}">Fecha</label><input id="fmed-{{ $b->id_beneficio }}" name="fecha_medicion" type="date" required max="{{ now()->toDateString() }}" class="sig-input text-xs py-1.5 font-mono"></div>
                            <div class="col-span-2 sm:col-span-1"><label class="block text-[11px] text-slate-600" for="obs-{{ $b->id_beneficio }}">Observación</label><input id="obs-{{ $b->id_beneficio }}" name="observacion" maxlength="250" class="sig-input text-xs py-1.5"></div>
                            <div class="flex items-end"><button class="sig-btn-primary w-full">Guardar</button></div>
                        </form>
                    </details>
                @endif
            </div>
        @empty
            <p class="text-xs text-slate-500">Sin beneficios planificados.</p>
        @endforelse
        @if ($puede)
            <details class="rounded-xl border border-dashed border-slate-300 p-3.5">
                <summary class="cursor-pointer text-xs font-semibold text-brand-800">Planificar beneficio</summary>
                <form method="POST" action="{{ route('proyectos.beneficios.store', $proyecto) }}" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3">
                    @csrf
                    <div class="col-span-2"><label class="block text-[11px] font-semibold text-slate-700 mb-1" for="ben-ind">Indicador</label><input id="ben-ind" name="indicador" required minlength="3" maxlength="120" class="sig-input text-xs" placeholder="Ej.: Tiempo de atención de trámites"></div>
                    <div><label class="block text-[11px] font-semibold text-slate-700 mb-1" for="ben-uni">Unidad</label><input id="ben-uni" name="unidad" required maxlength="30" class="sig-input text-xs" placeholder="días"></div>
                    <div class="col-span-2 sm:col-span-4"><label class="block text-[11px] font-semibold text-slate-700 mb-1" for="ben-desc">Descripción</label><input id="ben-desc" name="descripcion" required minlength="5" maxlength="200" class="sig-input text-xs"></div>
                    <div><label class="block text-[11px] font-semibold text-slate-700 mb-1" for="ben-base">Línea base</label><input id="ben-base" name="linea_base" type="number" step="0.01" required class="sig-input text-xs tabular-nums"></div>
                    <div><label class="block text-[11px] font-semibold text-slate-700 mb-1" for="ben-meta">Meta</label><input id="ben-meta" name="meta" type="number" step="0.01" required class="sig-input text-xs tabular-nums"></div>
                    <div class="col-span-2 flex items-end"><button class="sig-btn-primary w-full">Registrar</button></div>
                </form>
            </details>
        @endif
        @if ((int) $gestion['parametros']['exigir_acta_cierre'] === 1 && $abierto)
            <p class="text-[11px] text-slate-500">Regla activa: el proyecto no se cierra sin un acta adjunta y al menos un beneficio planificado.</p>
        @endif
    </div>

    {{-- ===================== HISTORIAL ===================== --}}
    <div x-show="pestana === 'historial'" x-cloak class="p-5" role="tabpanel">
        @if ($gestion['historial']->isEmpty())
            <p class="text-xs text-slate-500">Sin cambios registrados.</p>
        @else
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50/90 text-slate-600 text-left">
                        <tr><th class="px-3.5 py-2 font-semibold">Fecha y hora</th><th class="px-3 py-2 font-semibold">Elemento</th><th class="px-3 py-2 font-semibold">Cambio</th><th class="px-3 py-2 font-semibold">Motivo</th><th class="px-3.5 py-2 font-semibold">Usuario</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($gestion['historial'] as $h)
                            <tr class="align-top">
                                <td class="px-3.5 py-2 font-mono text-slate-600 whitespace-nowrap">{{ $h->fecha_hora }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ $tablaLegible[$h->tabla_afectada] ?? $h->tabla_afectada }} #{{ $h->id_registro }}</td>
                                <td class="px-3 py-2 text-slate-700">
                                    <span class="font-semibold">{{ $h->campo }}</span>:
                                    @if ($h->valor_anterior !== null)<span class="text-slate-500 line-through">{{ \Illuminate\Support\Str::limit($h->valor_anterior, 60) }}</span> → @endif
                                    {{ \Illuminate\Support\Str::limit($h->valor_nuevo ?? '—', 80) }}
                                </td>
                                <td class="px-3 py-2 text-slate-600">{{ $h->motivo ?? '—' }}</td>
                                <td class="px-3.5 py-2 text-slate-600 whitespace-nowrap">{{ $h->responsable }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-[11px] text-slate-500 mt-2">Últimos {{ $gestion['historial']->count() }} cambios auditados del proyecto, sus hitos, cortes, riesgos, compromisos, documentos y beneficios.</p>
        @endif
    </div>
</section>
