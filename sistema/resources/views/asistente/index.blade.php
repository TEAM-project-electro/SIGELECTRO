@extends('layouts.app')

@section('title', 'Asistente de consulta')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{ enviando: false, pregunta: @js($pregunta) }">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Asistente de consulta</h1>
        <p class="text-sm text-slate-600 mt-1">
            Responde con base en los documentos autorizados del SIG y en los datos vigentes de la cartera. Cada respuesta indica sus fuentes.
        </p>
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

    <p class="text-[11px] text-slate-500">
        El asistente solo consulta información: no modifica proyectos, hitos ni avances. Las respuestas deben contrastarse con las fuentes citadas.
    </p>
</div>
@endsection
