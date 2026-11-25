{{-- Dictamen del Agente de Decisión Gerencial (DSS). Cifras del motor determinista; texto escapado con RagFormato. --}}
@php
    $ranking = $decision['ranking'];
    $bloqueado = $decision['modo'] === 'bloqueado';
    $etiquetasAgente = [
        'generativo' => ['Síntesis IA + Motor determinista', 'info'],
        'extractivo' => ['Motor determinista local', 'success'],
        'bloqueado' => ['Solicitud rechazada', 'danger'],
    ];
    [$textoAg, $varAg] = $etiquetasAgente[$decision['modo']] ?? ['Dictamen', 'neutral'];

    $colorNivel = [
        'Crítico' => ['barra' => 'bg-rose-600', 'hex' => '#e11d48', 'badge' => 'danger'],
        'Alto' => ['barra' => 'bg-amber-500', 'hex' => '#f59e0b', 'badge' => 'warning'],
        'Moderado' => ['barra' => 'bg-brand-500', 'hex' => '#1672c2', 'badge' => 'info'],
        'Controlado' => ['barra' => 'bg-energy-500', 'hex' => '#3ba62d', 'badge' => 'success'],
    ];
    $prioritarios = array_values(array_filter($ranking, fn ($r) => in_array($r['nivel'], ['Crítico', 'Alto'], true)));
    $exposicion = array_sum(array_column($prioritarios, 'monto'));
    $puntajeMax = $ranking ? max(array_column($ranking, 'puntaje')) : 0;
    $nivelMax = $ranking ? $ranking[0]['nivel'] : 'Controlado';

    $datosGrafico = array_map(fn ($r) => [
        'codigo' => $r['codigo_pgd'],
        'nombre' => \Illuminate\Support\Str::limit($r['nombre'], 60),
        'puntaje' => $r['puntaje'],
        'nivel' => $r['nivel'],
        'color' => $colorNivel[$r['nivel']]['hex'] ?? '#64748b',
    ], $ranking);
@endphp

<section class="sig-panel overflow-hidden sig-burbuja-entrada" aria-labelledby="titulo-dictamen" data-dictamen>
    {{-- Cabecera --}}
    <div class="px-5 sm:px-6 py-4 bg-brand-950 text-white blueprint-grid flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-brand-200">Dictamen del Agente de Decisión</p>
            <h2 id="titulo-dictamen" class="text-base sm:text-lg font-bold tracking-tight mt-0.5">{{ $decision['escenario_titulo'] }}</h2>
        </div>
        <x-badge :variant="$varAg">{{ $textoAg }}</x-badge>
    </div>

    <div class="p-5 sm:p-6 space-y-6">
        @if ($bloqueado)
            <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-4 text-sm">
                <p class="text-[11px] font-bold uppercase tracking-wide text-rose-800 mb-1.5">Guardia de seguridad · OWASP LLM01</p>
                {{ \App\Services\Rag\RagFormato::htmlSeguro($decision['recomendacion']) }}
            </div>
        @else
            {{-- Franja de indicadores del dictamen --}}
            @if (count($ranking))
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="rounded-xl border border-slate-200 p-3.5">
                        <p class="text-[11px] font-semibold text-slate-500">Proyectos priorizados</p>
                        <p class="text-2xl font-bold text-slate-900 tabular-nums mt-0.5">{{ count($ranking) }}</p>
                    </div>
                    <div class="rounded-xl border p-3.5 {{ count($prioritarios) ? 'border-rose-200 bg-rose-50/50' : 'border-energy-100 bg-energy-50/50' }}">
                        <p class="text-[11px] font-semibold text-slate-500">Críticos o altos</p>
                        <p class="text-2xl font-bold tabular-nums mt-0.5 {{ count($prioritarios) ? 'text-rose-700' : 'text-energy-700' }}">{{ count($prioritarios) }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-3.5">
                        <p class="text-[11px] font-semibold text-slate-500">Monto expuesto (crítico/alto)</p>
                        <p class="text-lg font-bold text-slate-900 tabular-nums mt-1">S/ {{ number_format($exposicion, 2) }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-3.5">
                        <p class="text-[11px] font-semibold text-slate-500">Riesgo máximo</p>
                        <div class="flex items-baseline gap-2 mt-0.5">
                            <p class="text-2xl font-bold text-slate-900 tabular-nums">{{ $puntajeMax }}<span class="text-sm font-semibold text-slate-400">/100</span></p>
                            <x-badge :variant="$colorNivel[$nivelMax]['badge'] ?? 'neutral'" size="sm">{{ $nivelMax }}</x-badge>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Recomendación + gráfico --}}
            <div class="grid grid-cols-1 {{ count($ranking) ? 'lg:grid-cols-5' : '' }} gap-4">
                <div class="{{ count($ranking) ? 'lg:col-span-3' : '' }} rounded-xl border border-brand-200 bg-brand-50/40 p-4 text-sm">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-brand-800 mb-1.5">Recomendación ejecutiva</p>
                    {{ \App\Services\Rag\RagFormato::htmlSeguro($decision['recomendacion']) }}
                </div>
                @if (count($ranking))
                    <figure class="lg:col-span-2 rounded-xl border border-slate-200 p-4 flex flex-col">
                        <figcaption class="flex items-center justify-between gap-2 mb-2">
                            <span class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Puntaje de prioridad</span>
                            <span class="text-[11px] text-slate-400">0–100</span>
                        </figcaption>
                        <div class="relative flex-1 min-h-[180px]" style="height: {{ 60 + count($ranking) * 38 }}px">
                            <canvas data-grafico-ranking='@json($datosGrafico)'
                                    role="img"
                                    aria-label="Gráfico de barras del puntaje de prioridad por proyecto: {{ collect($datosGrafico)->map(fn ($d) => $d['codigo'].' '.$d['puntaje'])->implode(', ') }}"></canvas>
                        </div>
                        <div class="flex flex-wrap gap-x-3 gap-y-1 mt-3 text-[11px] text-slate-600">
                            @foreach ($colorNivel as $nivel => $c)
                                <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm {{ $c['barra'] }}" aria-hidden="true"></span>{{ $nivel }}</span>
                            @endforeach
                        </div>
                    </figure>
                @endif
            </div>
        @endif

        {{-- Opciones de decisión comparadas --}}
        @if (count($decision['opciones']))
            <div class="space-y-2.5">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Opciones de decisión evaluadas</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @foreach ($decision['opciones'] as $op)
                        @php
                            $estiloOp = match ($op['tipo']) {
                                'Recomendada' => ['border-energy-200 bg-energy-50/40 ring-1 ring-energy-200', 'success', 'border-t-energy-500'],
                                'Riesgo alto' => ['border-rose-200 bg-rose-50/40', 'danger', 'border-t-rose-500'],
                                default => ['border-slate-200 bg-slate-50/60', 'neutral', 'border-t-slate-400'],
                            };
                        @endphp
                        <div class="rounded-xl border border-t-4 p-4 space-y-2 {{ $estiloOp[0] }} {{ $estiloOp[2] }}">
                            <x-badge :variant="$estiloOp[1]" size="sm">{{ $op['tipo'] }}</x-badge>
                            <h4 class="text-xs font-bold text-slate-900 leading-snug">{{ $op['titulo'] }}</h4>
                            <p class="text-xs text-slate-700 leading-relaxed"><strong>Impacto:</strong> {{ $op['impacto'] }}</p>
                            <p class="text-xs text-slate-600 leading-relaxed"><strong>Acción humana:</strong> {{ $op['accion'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Ranking con medidor de riesgo por proyecto --}}
        @if (count($ranking))
            <div class="space-y-2.5">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ranking de prioridad gerencial</h3>
                <ol class="space-y-2">
                    @foreach ($ranking as $item)
                        @php $c = $colorNivel[$item['nivel']] ?? $colorNivel['Controlado']; @endphp
                        <li class="rounded-xl border border-slate-200 bg-white p-3.5 hover:border-brand-300 transition-colors">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="flex items-start gap-3 min-w-0">
                                    <span class="shrink-0 w-7 h-7 rounded-lg bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center tabular-nums">{{ $loop->iteration }}</span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-900">
                                            <span class="font-mono text-brand-800">{{ $item['codigo_pgd'] }}</span> · {{ \Illuminate\Support\Str::limit($item['nombre'], 70) }}
                                        </p>
                                        <p class="text-[11px] text-slate-500 mt-0.5 tabular-nums">{{ $item['responsable'] }} · S/ {{ number_format($item['monto'], 2) }} · Hitos {{ $item['avance_hitos'] }} · Desv. {{ $item['desviacion'] }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <x-badge :variant="$c['badge']" size="sm">{{ $item['nivel'] }} · {{ $item['puntaje'] }}</x-badge>
                                    <a href="{{ route('proyectos.show', $item['id_proyecto']) }}" class="text-xs font-semibold text-brand-700 hover:text-brand-900 hover:underline">Revisar ficha →</a>
                                </div>
                            </div>
                            <div class="mt-2.5 pl-10">
                                <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden" role="meter" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $item['puntaje'] }}" aria-label="Puntaje de riesgo {{ $item['codigo_pgd'] }}">
                                    <div class="h-full rounded-full {{ $c['barra'] }} sig-medidor" style="--medidor: {{ $item['puntaje'] }}%; width: {{ $item['puntaje'] }}%"></div>
                                </div>
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    @foreach ($item['factores'] as $factor)
                                        <span class="text-[11px] px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200 text-slate-600">{{ $factor }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        {{-- Traza auditable del agente --}}
        @if (count($decision['pasos']))
            <div class="pt-4 border-t border-slate-100 space-y-3">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Traza de ejecución del agente</h3>
                @php
                    $columnasTraza = match (count($decision['pasos'])) {
                        1 => 'md:grid-cols-1',
                        2 => 'md:grid-cols-2',
                        3 => 'md:grid-cols-3',
                        default => 'md:grid-cols-4',
                    };
                @endphp
                <ol class="relative grid grid-cols-1 {{ $columnasTraza }} gap-3">
                    @foreach ($decision['pasos'] as $paso)
                        <li class="relative rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-3">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full {{ $bloqueado ? 'bg-rose-600' : 'bg-energy-500' }} text-white flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="{{ $bloqueado ? 'M6 18L18 6M6 6l12 12' : 'M5 13l4 4L19 7' }}"/></svg>
                                </span>
                                <span class="text-xs font-semibold text-slate-800">{{ $paso['paso'] }}</span>
                            </div>
                            <p class="text-[11px] text-slate-600 mt-1.5 leading-relaxed">{{ $paso['detalle'] }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif
    </div>
</section>
