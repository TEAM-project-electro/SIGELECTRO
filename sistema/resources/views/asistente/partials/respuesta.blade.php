{{-- Burbuja de respuesta del asistente RAG. Todo el texto pasa por RagFormato::htmlSeguro (escape previo). --}}
@php
    $modo = $resultado['modo'];
    $bloqueado = $modo === 'bloqueado';
    $etiquetasModo = [
        'generativo' => ['Redactada con IA a partir de las fuentes', 'info'],
        'extractivo' => ['Fragmentos de las fuentes', 'neutral'],
        'bloqueado' => ['Consulta rechazada', 'danger'],
    ];
    [$textoModo, $varianteModo] = $etiquetasModo[$modo] ?? ['Respuesta', 'neutral'];
    $motivosGuardia = [
        'anular_instrucciones' => 'Intento de anular las instrucciones del asistente',
        'prompt_sistema' => 'Solicitud del prompt o instrucciones internas',
        'cambio_rol' => 'Intento de cambiar el rol del asistente (jailbreak)',
        'etiquetas_rol' => 'Etiquetas de rol inyectadas en la pregunta',
        'revelar_configuracion' => 'Solicitud de configuración, claves o secretos',
        'secretos' => 'Referencia a secretos o credenciales del sistema',
        'credencial_ajena' => 'Solicitud de contraseñas de otros usuarios',
        'sql' => 'Instrucción SQL destructiva o de privilegios',
        'ofuscacion' => 'Texto ofuscado (base64, rot13, hexadecimal)',
        'caracteres_control' => 'Caracteres de control no permitidos',
        'longitud' => 'Longitud fuera del rango permitido',
    ];
    $motivo = $resultado['motivo'] ?? null;
@endphp

<div class="flex items-start gap-3 sig-burbuja-entrada" data-mensaje="asistente">
    <span class="shrink-0 w-8 h-8 rounded-xl flex items-center justify-center {{ $bloqueado ? 'bg-rose-100 text-rose-700' : 'bg-brand-900 text-white' }}" aria-hidden="true">
        @if ($bloqueado)
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l7 3v5c0 4.5-3 8.3-7 9.5-4-1.2-7-5-7-9.5V6l7-3zm-2.5 9.5l5-5m-5 0l5 5"/></svg>
        @else
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 3L4 14h7l-1 7 9-11h-7l1-7z"/></svg>
        @endif
    </span>

    <div class="min-w-0 flex-1 max-w-[46rem]">
        <div class="flex flex-wrap items-center gap-2 mb-1.5">
            <span class="text-xs font-semibold text-slate-800">Asistente SIGELECTRO</span>
            <x-badge :variant="$varianteModo" size="sm">{{ $textoModo }}</x-badge>
        </div>

        <div class="rounded-2xl rounded-tl-md border px-4 py-3 text-sm shadow-panel {{ $bloqueado ? 'border-rose-200 bg-rose-50/70' : 'border-slate-200 bg-white' }}">
            @if ($bloqueado)
                <div class="flex items-center gap-2 pb-2 mb-2 border-b border-rose-200/80">
                    <span class="text-[11px] font-bold uppercase tracking-wide text-rose-800">Guardia de seguridad · OWASP LLM01</span>
                </div>
                @if ($motivo)
                    <p class="text-xs text-rose-800 mb-1.5"><strong>Motivo detectado:</strong> {{ $motivosGuardia[$motivo] ?? $motivo }}</p>
                @endif
            @endif

            {{ \App\Services\Rag\RagFormato::htmlSeguro($resultado['respuesta']) }}

            @if ($bloqueado)
                <p class="text-[11px] text-rose-700/90 mt-2">La consulta no llegó al modelo de IA y quedó registrada en la bitácora del asistente.</p>
            @endif
        </div>

        @if (count($resultado['fuentes']))
            <div class="mt-2.5 space-y-1.5">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Fuentes consultadas ({{ count($resultado['fuentes']) }})</h3>
                    <span class="text-[11px] text-slate-400 hidden sm:inline">Abra una fuente para ver el fragmento original</span>
                </div>
                @foreach ($resultado['fuentes'] as $f)
                    @php
                        $esCartera = ($f['origen'] ?? '') === 'cartera';
                        $seccionLimpia = str_replace('`', '', $f['seccion']);
                    @endphp
                    <details class="group rounded-xl border border-slate-200 bg-slate-50/70 open:bg-white open:shadow-panel transition-colors">
                        <summary class="cursor-pointer list-none px-3 py-2 flex items-center justify-between gap-3 text-xs text-slate-700 hover:text-slate-900">
                            <span class="flex items-center gap-2 min-w-0">
                                <span class="px-1.5 py-0.5 rounded bg-brand-50 text-brand-800 border border-brand-200 font-mono text-[11px] font-semibold shrink-0">[{{ $f['id'] }}]</span>
                                <span class="font-medium text-slate-800 truncate">{{ $f['titulo'] }}</span>
                                <span class="text-slate-400 shrink-0 hidden sm:inline">—</span>
                                <span class="text-slate-600 truncate hidden sm:inline">{{ $seccionLimpia }}</span>
                            </span>
                            <span class="flex items-center gap-2 shrink-0">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium {{ $esCartera ? 'bg-energy-50 text-energy-700 border border-energy-100' : 'bg-white text-slate-600 border border-slate-200' }}">
                                    {{ $esCartera ? 'Dato en vivo' : 'Documento' }}
                                </span>
                                <svg class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </span>
                        </summary>
                        <div class="px-3.5 pb-3 pt-2 border-t border-slate-100 text-xs">
                            {{ \App\Services\Rag\RagFormato::htmlSeguro(\Illuminate\Support\Str::limit($f['texto'], 1100)) }}
                        </div>
                    </details>
                @endforeach
            </div>
        @endif
    </div>
</div>
