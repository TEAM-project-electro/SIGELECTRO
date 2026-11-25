@extends('layouts.app')

@section('title', 'Asistente de consulta')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="{ tab: @js($tabActiva), enviando: false, enviandoAgente: false, enviandoDoc: false, pregunta: @js($pregunta) }">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Asistente de consulta y apoyo a decisiones</h1>
            <p class="text-sm text-slate-600 mt-1">
                Consulta documentos autorizados con citas verificables o ejecuta el Agente de Decisión Gerencial sobre la cartera vigente.
            </p>
        </div>

        <div class="inline-flex rounded-xl bg-slate-100 p-1 border border-slate-200" role="tablist" aria-label="Modos del asistente">
            <button type="button" role="tab" :aria-selected="tab === 'consulta'" @click="tab = 'consulta'"
                    :class="tab === 'consulta' ? 'bg-white text-brand-900 shadow-xs font-semibold' : 'text-slate-600 hover:text-slate-900'"
                    class="px-3.5 py-2 rounded-lg text-xs transition-all">
                Consulta RAG
            </button>
            <button type="button" role="tab" :aria-selected="tab === 'decision'" @click="tab = 'decision'"
                    :class="tab === 'decision' ? 'bg-white text-brand-900 shadow-xs font-semibold' : 'text-slate-600 hover:text-slate-900'"
                    class="px-3.5 py-2 rounded-lg text-xs transition-all flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-energy-500"></span>
                Agente de Decisión (DSS)
            </button>
            @if (Auth::user()->esAdmin())
                <button type="button" role="tab" :aria-selected="tab === 'documentos'" @click="tab = 'documentos'"
                        :class="tab === 'documentos' ? 'bg-white text-brand-900 shadow-xs font-semibold' : 'text-slate-600 hover:text-slate-900'"
                        class="px-3.5 py-2 rounded-lg text-xs transition-all">
                    Base documental ({{ count($documentosIndexados) }})
                </button>
            @endif
        </div>
    </div>

    @if ($errors->any())
        <x-alert type="error">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    {{-- ============================================================
         PESTAÑA 1: CONSULTA RAG DOCUMENTAL + CARTERA
         ============================================================ --}}
    <div x-show="tab === 'consulta'" x-cloak class="space-y-6">
        <form method="POST" action="{{ route('asistente.preguntar') }}" autocomplete="off" @submit="enviando = true" class="sig-panel p-5 space-y-3">
            @csrf
            <label for="pregunta" class="block text-xs font-semibold text-slate-700">Su pregunta</label>
            <textarea id="pregunta" name="pregunta" rows="3" required minlength="{{ config('rag.pregunta_min') }}" maxlength="{{ config('rag.pregunta_max') }}"
                      x-model="pregunta" placeholder="Ejemplo: ¿Qué proyectos presentan retraso?" class="sig-input resize-none"></textarea>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-[11px] text-slate-500"><span x-text="pregunta.length"></span> / {{ config('rag.pregunta_max') }} caracteres</p>
                <button type="submit" :disabled="enviando" class="sig-btn-primary">
                    <span x-text="enviando ? 'Consultando…' : 'Consultar'">Consultar</span>
                </button>
            </div>
            <div class="flex flex-wrap gap-2 pt-1">
                @foreach ($sugerencias as $s)
                    <button type="button" @click="pregunta = @js($s)"
                            class="text-xs px-3 py-1.5 rounded-full border border-slate-200 text-slate-700 hover:border-brand-400 hover:text-brand-800 transition-colors">
                        {{ $s }}
                    </button>
                @endforeach
            </div>
        </form>

        @if ($resultado)
            <section class="sig-panel p-6 space-y-5" aria-live="polite">
                <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-lg bg-brand-50 text-brand-700 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8M8 14h5m-9 6l2.5-3H18a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v14z"/>
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">Respuesta del asistente</h2>
                            @if ($pregunta !== '')
                                <p class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($pregunta, 120) }}</p>
                            @endif
                        </div>
                    </div>
                    @php
                        $etiquetas = ['generativo' => ['Redactada con IA a partir de las fuentes', 'info'], 'extractivo' => ['Fragmentos de las fuentes', 'neutral'], 'bloqueado' => ['Consulta rechazada', 'danger']];
                        [$texto, $variante] = $etiquetas[$resultado['modo']];
                    @endphp
                    <x-badge :variant="$variante">{{ $texto }}</x-badge>
                </div>

                <div class="text-sm">
                    {{ \App\Services\Rag\RagFormato::htmlSeguro($resultado['respuesta']) }}
                </div>

                @if (count($resultado['fuentes']))
                    <div class="pt-4 border-t border-slate-100 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fuentes consultadas ({{ count($resultado['fuentes']) }})</h3>
                            <span class="text-[11px] text-slate-400">Haga clic en una fuente para ver el fragmento original</span>
                        </div>
                        @foreach ($resultado['fuentes'] as $f)
                            @php
                                $esCartera = ($f['origen'] ?? '') === 'cartera';
                                $seccionLimpia = str_replace('`', '', $f['seccion']);
                            @endphp
                            <details class="group rounded-lg border border-slate-200 bg-slate-50/70 open:bg-white open:shadow-xs transition-colors" @if($resultado['modo'] === 'extractivo' && $loop->first) open @endif>
                                <summary class="cursor-pointer list-none px-3.5 py-2.5 flex items-center justify-between gap-3 text-xs text-slate-700 hover:text-slate-900">
                                    <span class="flex items-center gap-2 min-w-0">
                                        <span class="px-1.5 py-0.5 rounded bg-brand-50 text-brand-800 border border-brand-200 font-mono text-[11px] font-semibold shrink-0">[{{ $f['id'] }}]</span>
                                        <span class="font-medium text-slate-800 truncate">{{ $f['titulo'] }}</span>
                                        <span class="text-slate-400 shrink-0">—</span>
                                        <span class="text-slate-600 truncate">{{ $seccionLimpia }}</span>
                                    </span>
                                    <span class="flex items-center gap-2 shrink-0">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-medium {{ $esCartera ? 'bg-energy-50 text-energy-700 border border-energy-100' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                            {{ $esCartera ? 'Dato en vivo' : 'Documento' }}
                                        </span>
                                        <svg class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </span>
                                </summary>
                                <div class="px-4 pb-3.5 pt-2 border-t border-slate-100 text-xs">
                                    {{ \App\Services\Rag\RagFormato::htmlSeguro(\Illuminate\Support\Str::limit($f['texto'], 1100)) }}
                                </div>
                            </details>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    </div>

    {{-- ============================================================
         PESTAÑA 2: AGENTE DE DECISIÓN GERENCIAL (DSS)
         ============================================================ --}}
    <div x-show="tab === 'decision'" x-cloak class="space-y-6">
        <form method="POST" action="{{ route('asistente.decidir') }}" autocomplete="off" @submit="enviandoAgente = true" class="sig-panel p-5 space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="escenario" class="block text-xs font-semibold text-slate-700 mb-1.5">Escenario de decisión</label>
                    <select id="escenario" name="escenario" class="sig-input">
                        @foreach ($escenarios as $clave => $etiqueta)
                            <option value="{{ $clave }}" @selected($escenarioSeleccionado === $clave)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="id_proyecto" class="block text-xs font-semibold text-slate-700 mb-1.5">Alcance del análisis</label>
                    <select id="id_proyecto" name="id_proyecto" class="sig-input">
                        <option value="">Toda la cartera PGD (10 proyectos)</option>
                        @foreach ($proyectos as $p)
                            <option value="{{ $p->id_proyecto }}" @selected((string) $proyectoSeleccionado === (string) $p->id_proyecto)>
                                {{ $p->codigo_pgd }} — {{ \Illuminate\Support\Str::limit($p->nombre, 48) }} ({{ $p->estado }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="objetivo" class="block text-xs font-semibold text-slate-700 mb-1.5">Enfoque o restricción gerencial (opcional)</label>
                <input id="objetivo" name="objetivo" type="text" maxlength="{{ config('rag.pregunta_max') }}"
                       value="{{ $objetivoDecision }}"
                       placeholder="Ejemplo: Preparar acuerdos para la reunión mensual del Comité de Gobierno Digital…"
                       class="sig-input">
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                <p class="text-[11px] text-slate-500">
                    El agente evalúa hitos vencidos, desviación presupuestal, peso financiero y abastecimiento Odoo en solo lectura (Human-in-the-Loop).
                </p>
                <button type="submit" :disabled="enviandoAgente" class="sig-btn-primary">
                    <span x-text="enviandoAgente ? 'Analizando cartera…' : 'Ejecutar agente de decisión'">Ejecutar agente de decisión</span>
                </button>
            </div>
        </form>

        @if ($decision)
            <section class="sig-panel p-6 space-y-6" aria-live="polite">
                <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Dictamen del Agente de Decisión</h2>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $decision['escenario_titulo'] }}</p>
                    </div>
                    @php
                        $etiquetasAgente = [
                            'generativo' => ['Síntesis IA + Motor determinista', 'info'],
                            'extractivo' => ['Motor determinista local', 'success'],
                            'bloqueado' => ['Solicitud rechazada', 'danger'],
                        ];
                        [$textoAg, $varAg] = $etiquetasAgente[$decision['modo']];
                    @endphp
                    <x-badge :variant="$varAg">{{ $textoAg }}</x-badge>
                </div>

                {{-- Recomendación ejecutiva --}}
                <div class="rounded-xl border border-brand-200 bg-brand-50/40 p-4 text-sm">
                    {{ \App\Services\Rag\RagFormato::htmlSeguro($decision['recomendacion']) }}
                </div>

                {{-- Opciones de decisión comparadas --}}
                @if (count($decision['opciones']))
                    <div class="space-y-2.5">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Opciones de decisión evaluadas</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            @foreach ($decision['opciones'] as $op)
                                @php
                                    $borde = match ($op['tipo']) {
                                        'Recomendada' => 'border-energy-200 bg-energy-50/40',
                                        'Riesgo alto' => 'border-rose-200 bg-rose-50/40',
                                        default => 'border-slate-200 bg-slate-50/60',
                                    };
                                    $badgeTipo = match ($op['tipo']) {
                                        'Recomendada' => 'success',
                                        'Riesgo alto' => 'danger',
                                        default => 'neutral',
                                    };
                                @endphp
                                <div class="rounded-xl border p-4 space-y-2 {{ $borde }}">
                                    <div class="flex items-center justify-between gap-2">
                                        <x-badge :variant="$badgeTipo" size="sm">{{ $op['tipo'] }}</x-badge>
                                    </div>
                                    <h4 class="text-xs font-bold text-slate-900 leading-snug">{{ $op['titulo'] }}</h4>
                                    <p class="text-xs text-slate-700 leading-relaxed"><strong>Impacto:</strong> {{ $op['impacto'] }}</p>
                                    <p class="text-xs text-slate-600 leading-relaxed"><strong>Acción humana:</strong> {{ $op['accion'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Ranking de prioridad gerencial --}}
                @if (count($decision['ranking']))
                    <div class="space-y-2.5">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ranking de prioridad gerencial (0–100)</h3>
                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="min-w-full text-xs divide-y divide-slate-200">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="px-3 py-2.5 text-left font-semibold">Proyecto</th>
                                        <th class="px-3 py-2.5 text-left font-semibold">Prioridad</th>
                                        <th class="px-3 py-2.5 text-left font-semibold">Hitos / Desviación</th>
                                        <th class="px-3 py-2.5 text-left font-semibold">Factores detectados</th>
                                        <th class="px-3 py-2.5 text-right font-semibold">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($decision['ranking'] as $item)
                                        @php
                                            $varNivel = match ($item['nivel']) {
                                                'Crítico' => 'danger',
                                                'Alto' => 'warning',
                                                'Moderado' => 'info',
                                                default => 'success',
                                            };
                                        @endphp
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="px-3 py-2.5 align-top">
                                                <div class="font-semibold text-slate-900">
                                                    <span class="font-mono text-brand-800">{{ $item['codigo_pgd'] }}</span> · {{ \Illuminate\Support\Str::limit($item['nombre'], 40) }}
                                                </div>
                                                <div class="text-[11px] text-slate-500">{{ $item['responsable'] }} · S/ {{ number_format($item['monto'], 2) }}</div>
                                            </td>
                                            <td class="px-3 py-2.5 align-top whitespace-nowrap">
                                                <x-badge :variant="$varNivel" size="sm">{{ $item['nivel'] }} ({{ $item['puntaje'] }})</x-badge>
                                            </td>
                                            <td class="px-3 py-2.5 align-top whitespace-nowrap tabular-nums text-slate-700">
                                                <div>Hitos: {{ $item['avance_hitos'] }}</div>
                                                <div class="text-[11px] text-slate-500">Desv.: {{ $item['desviacion'] }}</div>
                                            </td>
                                            <td class="px-3 py-2.5 align-top text-slate-600">
                                                {{ implode(' · ', $item['factores']) }}
                                            </td>
                                            <td class="px-3 py-2.5 align-top text-right whitespace-nowrap">
                                                <a href="{{ route('proyectos.show', $item['id_proyecto']) }}" class="text-xs font-semibold text-brand-700 hover:text-brand-900 hover:underline">
                                                    Revisar ficha →
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Traza auditable de pasos del agente --}}
                @if (count($decision['pasos']))
                    <div class="pt-3 border-t border-slate-100 space-y-2">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Traza de ejecución del agente</h3>
                        <ol class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                            @foreach ($decision['pasos'] as $paso)
                                <li class="rounded-lg border border-slate-200 bg-slate-50/70 px-3 py-2">
                                    <div class="font-semibold text-slate-800">{{ $paso['paso'] }}</div>
                                    <div class="text-[11px] text-slate-600 mt-0.5">{{ $paso['detalle'] }}</div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif
            </section>
        @endif
    </div>

    {{-- ============================================================
         PESTAÑA 3: BASE DOCUMENTAL DEL RAG (SOLO ADMINISTRADOR)
         ============================================================ --}}
    @if (Auth::user()->esAdmin())
        <div x-show="tab === 'documentos'" x-cloak class="space-y-6">
            @if ($resultadoCarga)
                <x-alert :type="$resultadoCarga['aceptado'] ? 'success' : 'error'">
                    <p class="font-semibold">{{ $resultadoCarga['mensaje'] }}</p>
                    @if ($resultadoCarga['aceptado'])
                        <p class="text-xs mt-1">
                            Ruta: <span class="font-mono">{{ $resultadoCarga['ruta'] }}</span> ·
                            Vectores Gemini: {{ $resultadoCarga['embeddings'] ? 'generados' : 'pendientes (BM25 local activo)' }}
                        </p>
                    @endif
                </x-alert>
            @endif

            <form method="POST" action="{{ route('asistente.documentos.subir') }}" enctype="multipart/form-data"
                  @submit="enviandoDoc = true" class="sig-panel p-5 space-y-4">
                @csrf
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Alimentar base documental del RAG (Solo Administrador)</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Suba documentos institucionales en texto plano o Markdown (<code>.md</code>, <code>.txt</code>, máx. 500 KB). Cada fragmento pasa por el escáner anti-inyección indirecta (OWASP LLM01) antes de indexarse.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="titulo_doc" class="block text-xs font-semibold text-slate-700 mb-1.5">Título del documento</label>
                        <input id="titulo_doc" name="titulo" type="text" required minlength="3" maxlength="180"
                               placeholder="Ejemplo: Resumen PEI 2022–2026 Electrocentro" class="sig-input">
                    </div>
                    <div>
                        <label for="archivo_doc" class="block text-xs font-semibold text-slate-700 mb-1.5">Archivo (.md o .txt)</label>
                        <input id="archivo_doc" name="archivo" type="file" accept=".md,.txt,text/plain,text/markdown" required
                               class="block w-full text-xs text-slate-700 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-800 hover:file:bg-brand-100 border border-slate-300 rounded-lg p-1 bg-white">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" :disabled="enviandoDoc" class="sig-btn-primary">
                        <span x-text="enviandoDoc ? 'Escaneando e indexando…' : 'Escanear e indexar documento'">Escanear e indexar documento</span>
                    </button>
                </div>
            </form>

            <section class="sig-panel p-5 space-y-3">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Documentos actualmente indexados ({{ count($documentosIndexados) }})</h3>
                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="min-w-full text-xs divide-y divide-slate-200">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold">Documento</th>
                                <th class="px-3 py-2 text-left font-semibold">Ruta</th>
                                <th class="px-3 py-2 text-right font-semibold">Fragmentos</th>
                                <th class="px-3 py-2 text-right font-semibold">Bloqueados</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($documentosIndexados as $doc)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-slate-800">{{ $doc->titulo }}</td>
                                    <td class="px-3 py-2 font-mono text-[11px] text-slate-500">{{ $doc->ruta }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums text-slate-700">{{ $doc->fragmentos }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums {{ $doc->fragmentos_bloqueados > 0 ? 'text-rose-600 font-semibold' : 'text-slate-400' }}">
                                        {{ $doc->fragmentos_bloqueados }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    @endif

    <p class="text-[11px] text-slate-500">
        El asistente y el agente de decisión operan en modo solo lectura (Human-in-the-Loop): no modifican proyectos, hitos ni avances sin intervención del usuario autorizado.
    </p>
</div>
@endsection
