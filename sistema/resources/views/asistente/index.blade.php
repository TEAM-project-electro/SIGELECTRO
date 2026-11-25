@extends('layouts.app')

@section('title', 'Asistente de consulta')

@section('content')
@php
    $descripcionEscenarios = [
        'priorizar_cartera' => 'Ordena los proyectos por riesgo combinado: hitos vencidos, desviación, peso financiero y abastecimiento.',
        'desfase_presupuestal' => 'Pondera con más fuerza el desfase entre el gasto devengado y lo planificado.',
        'riesgo_proyecto' => 'Revisa los hitos de línea base pendientes que bloquean el cierre (regla R3).',
    ];
    $pasosAgente = [
        ['Lectura verificada', 'Cartera en solo lectura, filtrada por su perfil'],
        ['Motor de riesgo', 'Puntaje 0–100 por proyecto con reglas explícitas'],
        ['Síntesis', $iaDisponible ? 'Redacción con IA sobre cifras verificadas' : 'Recomendación del motor local'],
        ['Control humano', 'Ninguna acción se ejecuta sin el responsable'],
    ];
@endphp

<div class="max-w-6xl mx-auto space-y-5" x-data="{ tab: @js($tabActiva), enviandoDoc: false }">
    {{-- Encabezado --}}
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2 mb-1.5">
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-semibold border {{ $iaDisponible ? 'bg-brand-50 text-brand-800 border-brand-200' : 'bg-slate-100 text-slate-700 border-slate-200' }}">
                    <span class="relative flex w-1.5 h-1.5" aria-hidden="true">
                        @if ($iaDisponible)
                            <span class="absolute inline-flex h-full w-full rounded-full bg-brand-500 opacity-60 animate-ping"></span>
                        @endif
                        <span class="relative inline-flex w-1.5 h-1.5 rounded-full {{ $iaDisponible ? 'bg-brand-600' : 'bg-slate-500' }}"></span>
                    </span>
                    {{ $iaDisponible ? 'IA generativa activa' : 'Modo local sin internet' }}
                </span>
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-semibold bg-energy-50 text-energy-700 border border-energy-100">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M12 3l7 3v5c0 4.5-3 8.3-7 9.5-4-1.2-7-5-7-9.5V6l7-3z"/></svg>
                    Solo lectura · Human-in-the-Loop
                </span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Asistente de consulta y apoyo a decisiones</h1>
            <p class="text-sm text-slate-600 mt-1">
                Pregunte sobre la cartera y los procedimientos con citas verificables, o ejecute el Agente de Decisión Gerencial.
            </p>
        </div>

        <div class="inline-flex flex-wrap self-start lg:self-auto rounded-xl bg-white p-1 border border-slate-200 shadow-panel" role="tablist" aria-label="Modos del asistente">
            <button type="button" role="tab" id="tab-consulta" aria-controls="panel-consulta" :aria-selected="tab === 'consulta'" @click="tab = 'consulta'"
                    :class="tab === 'consulta' ? 'bg-brand-900 text-white shadow-panel' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                    class="px-3.5 py-2 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8M8 14h5m-9 6l2.5-3H18a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v14z"/></svg>
                Chat de consulta
            </button>
            <button type="button" role="tab" id="tab-decision" aria-controls="panel-decision" :aria-selected="tab === 'decision'" @click="tab = 'decision'"
                    :class="tab === 'decision' ? 'bg-brand-900 text-white shadow-panel' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                    class="px-3.5 py-2 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Agente de Decisión (DSS)
            </button>
            @if (Auth::user()->esAdmin())
                <button type="button" role="tab" id="tab-documentos" aria-controls="panel-documentos" :aria-selected="tab === 'documentos'" @click="tab = 'documentos'"
                        :class="tab === 'documentos' ? 'bg-brand-900 text-white shadow-panel' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                        class="px-3.5 py-2 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2zM12 11v6m-3-3h6"/></svg>
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
         PESTAÑA 1: CHAT DE CONSULTA RAG (documentos + cartera)
         ============================================================ --}}
    <div id="panel-consulta" role="tabpanel" aria-labelledby="tab-consulta" x-show="tab === 'consulta'" x-cloak>
        <div class="sig-panel overflow-hidden flex flex-col" data-chat
             data-limite-minuto="{{ config('rag.consultas_por_minuto') }}">
            {{-- Cabecera del chat --}}
            <div class="px-4 sm:px-5 py-3 border-b border-slate-200/80 flex items-center justify-between gap-3 bg-white">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="relative shrink-0 w-9 h-9 rounded-xl bg-brand-900 text-white flex items-center justify-center" aria-hidden="true">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 3L4 14h7l-1 7 9-11h-7l1-7z"/></svg>
                        <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-energy-500 ring-2 ring-white"></span>
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-900">Asistente SIGELECTRO</p>
                        <p class="text-[11px] text-slate-500 truncate">RAG con citas verificables · {{ $iaDisponible ? 'Gemini sobre fuentes autorizadas' : 'Fragmentos de fuentes autorizadas' }}</p>
                    </div>
                </div>
                <button type="button" data-chat-reiniciar class="sig-btn-secondary py-1.5 px-2.5 text-[11px]" title="Borrar la conversación de esta pantalla">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M5.1 15A7 7 0 0018.4 17M18.9 9A7 7 0 005.6 7"/></svg>
                    <span class="hidden sm:inline">Nueva conversación</span>
                </button>
            </div>

            {{-- Conversación --}}
            <div data-chat-log role="log" aria-live="polite" aria-label="Conversación con el asistente"
                 class="h-[min(60vh,36rem)] overflow-y-auto overscroll-contain px-4 sm:px-6 py-5 space-y-5 bg-gradient-to-b from-slate-50/80 to-white scroll-smooth">

                {{-- Mensaje de bienvenida (permanece al reiniciar) --}}
                <div class="flex items-start gap-3" data-chat-bienvenida>
                    <span class="shrink-0 w-8 h-8 rounded-xl bg-brand-900 text-white flex items-center justify-center" aria-hidden="true">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 3L4 14h7l-1 7 9-11h-7l1-7z"/></svg>
                    </span>
                    <div class="min-w-0 flex-1 max-w-[46rem]">
                        <p class="text-xs font-semibold text-slate-800 mb-1.5">Asistente SIGELECTRO</p>
                        <div class="rounded-2xl rounded-tl-md border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-panel">
                            <p class="leading-relaxed">Hola, {{ \Illuminate\Support\Str::of(Auth::user()->nombre)->before(' ') }}. Respondo con base en los documentos autorizados del SIG y en los datos de la cartera que su perfil puede ver. Cada afirmación lleva su cita <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-brand-50 text-brand-800 border border-brand-200 font-mono text-[11px] font-semibold">[F1]</span>.</p>
                            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-3">
                                @foreach ([
                                    ['Citas verificables', 'Cada fuente se puede abrir y contrastar.'],
                                    ['Datos en vivo', 'Indicadores de la cartera según su perfil.'],
                                    ['Guardia anti-inyección', 'Bloquea intentos de manipular a la IA.'],
                                    ['Sin cambios en datos', 'Solo consulta; nunca modifica registros.'],
                                ] as [$titulo, $detalle])
                                    <li class="flex items-start gap-2 rounded-lg bg-slate-50 border border-slate-100 px-2.5 py-2">
                                        <svg class="w-3.5 h-3.5 mt-0.5 text-energy-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        <span class="text-xs"><strong class="font-semibold text-slate-800">{{ $titulo }}.</strong> <span class="text-slate-600">{{ $detalle }}</span></span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Intercambio renderizado en servidor (envío sin JavaScript) --}}
                @if ($resultado)
                    <div class="flex justify-end" data-mensaje="usuario">
                        <div class="max-w-[36rem] rounded-2xl rounded-tr-md bg-brand-800 text-white px-4 py-2.5 text-sm leading-relaxed shadow-panel whitespace-pre-line break-words">{{ $pregunta }}</div>
                    </div>
                    @include('asistente.partials.respuesta', ['resultado' => $resultado])
                @endif
            </div>

            {{-- Compositor --}}
            <form method="POST" action="{{ route('asistente.preguntar') }}" autocomplete="off" data-chat-form
                  class="border-t border-slate-200/80 bg-white px-4 sm:px-5 pt-3 pb-4 space-y-2.5">
                @csrf
                <div class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1 [scrollbar-width:thin]" aria-label="Preguntas sugeridas">
                    @foreach ($sugerencias as $s)
                        <button type="button" data-sugerencia="{{ $s }}"
                                class="shrink-0 text-xs px-3 py-1.5 rounded-full border border-slate-200 bg-white text-slate-700 hover:border-brand-400 hover:text-brand-800 hover:bg-brand-50/50 transition-colors">
                            {{ $s }}
                        </button>
                    @endforeach
                </div>
                <label for="pregunta" class="sr-only">Su pregunta</label>
                <div class="flex items-end gap-2 rounded-2xl border border-slate-300 bg-white pl-3.5 pr-1.5 py-1.5 focus-within:border-brand-600 focus-within:ring-2 focus-within:ring-brand-600/20 transition-colors">
                    <textarea id="pregunta" name="pregunta" rows="1" required
                              minlength="{{ config('rag.pregunta_min') }}" maxlength="{{ config('rag.pregunta_max') }}"
                              placeholder="Escriba su pregunta… por ejemplo: ¿Qué proyectos presentan retraso?"
                              class="flex-1 resize-none border-0 bg-transparent p-0 py-1.5 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-0 max-h-40"></textarea>
                    <button type="submit" data-chat-enviar class="shrink-0 w-9 h-9 rounded-xl bg-brand-800 text-white flex items-center justify-center hover:bg-brand-900 active:bg-brand-950 disabled:opacity-50 disabled:cursor-not-allowed transition-colors" aria-label="Enviar pregunta">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </button>
                </div>
                <div class="flex items-center justify-between gap-3 text-[11px] text-slate-500">
                    <span class="hidden sm:inline">Enter para enviar · Shift + Enter para nueva línea</span>
                    <span class="tabular-nums"><span data-chat-contador>0</span> / {{ config('rag.pregunta_max') }}</span>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         PESTAÑA 2: AGENTE DE DECISIÓN GERENCIAL (DSS)
         ============================================================ --}}
    <div id="panel-decision" role="tabpanel" aria-labelledby="tab-decision" x-show="tab === 'decision'" x-cloak class="space-y-5">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">
            <form method="POST" action="{{ route('asistente.decidir') }}" autocomplete="off" data-agente-form
                  class="lg:col-span-3 sig-panel p-5 space-y-4">
                @csrf
                <fieldset>
                    <legend class="block text-xs font-semibold text-slate-700 mb-2">Escenario de decisión</legend>
                    <div class="grid grid-cols-1 gap-2">
                        @foreach ($escenarios as $clave => $etiqueta)
                            <label class="relative flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 cursor-pointer hover:border-brand-300 transition-colors has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50/50 has-[:checked]:ring-1 has-[:checked]:ring-brand-600">
                                <input type="radio" name="escenario" value="{{ $clave }}" @checked($escenarioSeleccionado === $clave)
                                       class="mt-0.5 text-brand-700 border-slate-300 focus:ring-brand-600">
                                <span class="min-w-0">
                                    <span class="block text-xs font-semibold text-slate-900">{{ $etiqueta }}</span>
                                    <span class="block text-[11px] text-slate-600 mt-0.5 leading-relaxed">{{ $descripcionEscenarios[$clave] ?? '' }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="id_proyecto" class="block text-xs font-semibold text-slate-700 mb-1.5">Alcance del análisis</label>
                        <select id="id_proyecto" name="id_proyecto" class="sig-input">
                            <option value="">Toda la cartera PGD ({{ count($proyectos) }} proyectos)</option>
                            @foreach ($proyectos as $p)
                                <option value="{{ $p->id_proyecto }}" @selected((string) $proyectoSeleccionado === (string) $p->id_proyecto)>
                                    {{ $p->codigo_pgd }} — {{ \Illuminate\Support\Str::limit($p->nombre, 40) }} ({{ $p->estado }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="objetivo" class="block text-xs font-semibold text-slate-700 mb-1.5">Enfoque gerencial <span class="font-normal text-slate-400">(opcional)</span></label>
                        <input id="objetivo" name="objetivo" type="text" maxlength="{{ config('rag.pregunta_max') }}"
                               value="{{ $objetivoDecision }}"
                               placeholder="Ej.: acuerdos para el Comité de Gobierno Digital…"
                               class="sig-input">
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                    <p class="text-[11px] text-slate-500 max-w-md">El agente no modifica proyectos, hitos ni avances: propone y el responsable decide.</p>
                    <button type="submit" data-agente-enviar class="sig-btn-primary px-4 py-2.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 3L4 14h7l-1 7 9-11h-7l1-7z"/></svg>
                        <span data-agente-texto>Ejecutar agente de decisión</span>
                    </button>
                </div>
            </form>

            {{-- Cómo decide el agente: se anima durante la ejecución --}}
            <aside class="lg:col-span-2 sig-panel p-5 bg-brand-950 text-white blueprint-grid border-brand-900" aria-labelledby="titulo-pipeline">
                <p id="titulo-pipeline" class="text-[11px] font-semibold uppercase tracking-widest text-brand-200">Cómo decide el agente</p>
                <ol class="mt-4 space-y-3" data-agente-pipeline>
                    @foreach ($pasosAgente as [$titulo, $detalle])
                        <li class="sig-paso flex items-start gap-3" data-estado="{{ $decision ? 'hecho' : 'pendiente' }}">
                            <span class="sig-paso-icono shrink-0 w-7 h-7 rounded-lg flex items-center justify-center text-[11px] font-bold tabular-nums" aria-hidden="true">
                                <span class="sig-paso-numero">{{ $loop->iteration }}</span>
                                <svg class="sig-paso-check w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-xs font-semibold text-white">{{ $titulo }}</span>
                                <span class="block text-[11px] text-slate-300 mt-0.5">{{ $detalle }}</span>
                            </span>
                        </li>
                    @endforeach
                </ol>
                <p class="mt-5 pt-4 border-t border-white/10 text-[11px] text-slate-300 leading-relaxed">
                    Las cifras salen del motor determinista; la IA solo redacta y nunca cambia los números.
                </p>
            </aside>
        </div>

        <div data-agente-resultado aria-live="polite">
            @if ($decision)
                @include('asistente.partials.dictamen', ['decision' => $decision])
            @endif
        </div>
    </div>

    {{-- ============================================================
         PESTAÑA 3: BASE DOCUMENTAL DEL RAG (SOLO ADMINISTRADOR)
         ============================================================ --}}
    @if (Auth::user()->esAdmin())
        <div id="panel-documentos" role="tabpanel" aria-labelledby="tab-documentos" x-show="tab === 'documentos'" x-cloak class="space-y-5">
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

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">
                <form method="POST" action="{{ route('asistente.documentos.subir') }}" enctype="multipart/form-data"
                      @submit="enviandoDoc = true" class="lg:col-span-3 sig-panel p-5 space-y-4">
                    @csrf
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Alimentar la base documental del RAG</h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Documentos institucionales en texto plano o Markdown (<code>.md</code>, <code>.txt</code>, máx. 500 KB).
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="titulo_doc" class="block text-xs font-semibold text-slate-700 mb-1.5">Título del documento</label>
                            <input id="titulo_doc" name="titulo" type="text" required minlength="3" maxlength="180"
                                   placeholder="Ej.: Resumen PEI 2022–2026 Electrocentro" class="sig-input">
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

                <aside class="lg:col-span-2 sig-panel p-5 bg-brand-950 text-white blueprint-grid border-brand-900">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-brand-200">Antes de indexar</p>
                    <ul class="mt-3 space-y-2.5 text-xs text-slate-200">
                        <li class="flex gap-2"><span class="text-energy-500 font-bold" aria-hidden="true">✓</span>Cada fragmento pasa por el escáner anti-inyección indirecta (OWASP LLM01).</li>
                        <li class="flex gap-2"><span class="text-energy-500 font-bold" aria-hidden="true">✓</span>Los fragmentos sospechosos se bloquean y no llegan al modelo.</li>
                        <li class="flex gap-2"><span class="text-energy-500 font-bold" aria-hidden="true">✓</span>Sin clave de Gemini, la búsqueda sigue funcionando con BM25 local.</li>
                    </ul>
                </aside>
            </div>

            <section class="sig-panel overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-200/80">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Documentos indexados ({{ count($documentosIndexados) }})</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs divide-y divide-slate-200">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-5 py-2.5 text-left font-semibold">Documento</th>
                                <th class="px-3 py-2.5 text-left font-semibold">Ruta</th>
                                <th class="px-3 py-2.5 text-right font-semibold">Fragmentos</th>
                                <th class="px-5 py-2.5 text-right font-semibold">Bloqueados</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($documentosIndexados as $doc)
                                <tr>
                                    <td class="px-5 py-2.5 font-medium text-slate-800">{{ $doc->titulo }}</td>
                                    <td class="px-3 py-2.5 font-mono text-[11px] text-slate-500">{{ $doc->ruta }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums text-slate-700">{{ $doc->fragmentos }}</td>
                                    <td class="px-5 py-2.5 text-right tabular-nums {{ $doc->fragmentos_bloqueados > 0 ? 'text-rose-600 font-semibold' : 'text-slate-400' }}">
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

{{-- Plantillas usadas por resources/js/asistente.js --}}
<template id="tpl-escribiendo">
    <div class="flex items-start gap-3 sig-burbuja-entrada" data-mensaje="escribiendo">
        <span class="shrink-0 w-8 h-8 rounded-xl bg-brand-900 text-white flex items-center justify-center" aria-hidden="true">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 3L4 14h7l-1 7 9-11h-7l1-7z"/></svg>
        </span>
        <div class="rounded-2xl rounded-tl-md border border-slate-200 bg-white px-4 py-3 shadow-panel flex items-center gap-3">
            <span class="flex items-center gap-1" aria-hidden="true">
                <span class="sig-punto"></span><span class="sig-punto"></span><span class="sig-punto"></span>
            </span>
            <span class="text-xs text-slate-500" data-escribiendo-texto>Buscando en las fuentes autorizadas…</span>
        </div>
    </div>
</template>

<template id="tpl-aviso">
    <div class="flex items-start gap-3 sig-burbuja-entrada" data-mensaje="aviso">
        <span class="shrink-0 w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center" aria-hidden="true">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
        </span>
        <div class="max-w-[40rem] rounded-2xl rounded-tl-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" data-aviso-texto></div>
    </div>
</template>
@endsection
