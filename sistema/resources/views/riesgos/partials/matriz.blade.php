{{-- Matriz probabilidad × impacto (riesgos abiertos). Parámetros: $matriz[prob][impacto] = cantidad, $parametros. --}}
@php
    $critica = (float) $parametros['exposicion_critica'];
    $media = (float) $parametros['exposicion_media'];
    $total = collect($matriz)->flatten()->sum();
@endphp
<figure class="space-y-2">
    <div class="flex gap-2">
        <div class="flex items-center">
            <span class="text-[11px] font-semibold text-slate-500 [writing-mode:vertical-rl] rotate-180">Probabilidad →</span>
        </div>
        <table class="border-separate border-spacing-1" aria-label="Matriz de riesgos abiertos: {{ $total }} en total">
            <tbody>
                @foreach ([5, 4, 3, 2, 1] as $p)
                    <tr>
                        <th scope="row" class="w-5 text-[11px] font-semibold text-slate-500 text-right pr-1 tabular-nums">{{ $p }}</th>
                        @foreach ([1, 2, 3, 4, 5] as $i)
                            @php
                                $exp = $p * $i;
                                $n = $matriz[$p][$i] ?? 0;
                                $color = $exp >= $critica ? 'bg-rose-100 text-rose-900 border-rose-200' : ($exp >= $media ? 'bg-amber-100 text-amber-900 border-amber-200' : 'bg-energy-50 text-energy-700 border-energy-100');
                            @endphp
                            <td class="w-11 h-11 sm:w-12 sm:h-12 rounded-md border text-center align-middle {{ $color }} {{ $n ? 'font-bold text-sm' : 'text-[10px] opacity-60' }}"
                                title="Probabilidad {{ $p }} × impacto {{ $i }} = {{ $exp }}: {{ $n }} riesgo(s)">
                                {{ $n ?: $exp }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                <tr>
                    <td></td>
                    @foreach ([1, 2, 3, 4, 5] as $i)
                        <th scope="col" class="text-[11px] font-semibold text-slate-500 tabular-nums pt-1">{{ $i }}</th>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
    <figcaption class="text-[11px] text-slate-500 pl-6">
        Impacto → · Las celdas con número en negrita tienen riesgos abiertos; las demás muestran su exposición.
        <span class="block mt-1">
            <span class="inline-flex items-center gap-1 mr-2"><span class="w-2.5 h-2.5 rounded-sm bg-rose-200" aria-hidden="true"></span>Crítico ≥ {{ $critica + 0 }}</span>
            <span class="inline-flex items-center gap-1 mr-2"><span class="w-2.5 h-2.5 rounded-sm bg-amber-200" aria-hidden="true"></span>Medio ≥ {{ $media + 0 }}</span>
            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-energy-100" aria-hidden="true"></span>Bajo</span>
        </span>
    </figcaption>
</figure>
