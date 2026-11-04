@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto space-y-6" x-data="{ submitting: false }">

    <!-- Encabezado -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-brand-50 text-brand-800 border border-brand-200">
                    PGD {{ $proyecto->codigo_pgd }}
                </span>
                <span class="text-xs text-slate-400">&bull; Modificación de Corte</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                Editar Corte Presupuestal
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Fecha del Corte: <span class="font-mono font-bold text-slate-800">{{ $avance->fecha_reporte }}</span>
            </p>
        </div>
        <a href="{{ route('proyectos.show', $proyecto) }}"
           class="px-3.5 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 shadow-xs transition">
            &larr; Volver al Proyecto
        </a>
    </div>

    <!-- Resumen de Reglas Financieras -->
    <div class="p-4 bg-brand-50/60 border border-brand-200 rounded-xl text-xs text-brand-900 space-y-1.5 shadow-xs">
        <p class="font-bold flex items-center gap-1.5 text-brand-950">
            <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Reglas Financieras Obligatorias:
        </p>
        <p class="leading-relaxed">
            &bull; <strong>Regla R1:</strong> Límite máximo presupuestal del proyecto: <strong class="font-mono text-slate-900">S/ {{ number_format($proyecto->monto, 2) }}</strong>.
        </p>
        <p class="leading-relaxed">
            &bull; <strong>Regla R2:</strong> El gasto acumulado debe ser consistente con la cronología previa y posterior.
        </p>
    </div>

    <!-- Errores de Validación -->
    @if ($errors->any())
        <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl text-rose-900 text-xs shadow-xs" role="alert">
            <p class="font-bold flex items-center mb-1">
                <svg class="w-4 h-4 mr-1.5 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                Corrige los siguientes errores:
            </p>
            <ul class="list-disc list-inside space-y-0.5 text-rose-800 pl-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white p-6 sm:p-7 rounded-xl border border-slate-200/90 shadow-xs">
        <form method="POST" action="{{ route('avances.update', $avance) }}" class="space-y-5" @submit="submitting = true">
            @csrf
            @method('PUT')
            <input type="hidden" name="revision_esperada" value="{{ old('revision_esperada', \Illuminate\Support\Facades\DB::table('control_revision')->where('id', 1)->value('version')) }}">

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                    Fecha de Reporte (Inmutable)
                </label>
                <input type="date" value="{{ $avance->fecha_reporte }}" disabled
                    class="w-full text-xs sm:text-sm border-slate-200 rounded-lg px-3 py-2 bg-slate-100 text-slate-600 font-mono font-bold cursor-not-allowed">
                <p class="text-[11px] text-slate-400 mt-1">La fecha de corte es la clave cronológica del registro.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="presupuesto_planificado" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Presupuesto Planificado (S/) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0" max="{{ $proyecto->monto }}"
                        name="presupuesto_planificado" id="presupuesto_planificado"
                        value="{{ old('presupuesto_planificado', $avance->presupuesto_planificado) }}" required
                        class="w-full text-xs sm:text-sm font-mono border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500 tabular-nums">
                </div>

                <div>
                    <label for="gasto_acumulado" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Gasto Acumulado (S/) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0" max="{{ $proyecto->monto }}"
                        name="gasto_acumulado" id="gasto_acumulado"
                        value="{{ old('gasto_acumulado', $avance->gasto_acumulado) }}" required
                        class="w-full text-xs sm:text-sm font-mono border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500 tabular-nums">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('proyectos.show', $proyecto) }}"
                    class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Cancelar
                </a>
                <button type="submit" :disabled="submitting"
                    class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-brand-700 hover:bg-brand-800 text-white rounded-lg text-xs font-semibold transition shadow-xs disabled:opacity-75">
                    <svg x-show="!submitting" class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg x-show="submitting" x-cloak class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span x-text="submitting ? 'Guardando...' : 'Guardar Cambios'">Guardar Cambios</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
