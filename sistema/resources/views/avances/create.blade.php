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
                <span class="text-xs text-slate-400">&bull; Ejecución Financiera</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                Registrar Corte Presupuestal
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Proyecto: <span class="font-semibold text-slate-700">{{ $proyecto->nombre }}</span>
            </p>
        </div>
        <a href="{{ route('proyectos.show', $proyecto) }}"
           class="px-3.5 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 shadow-xs transition">
            &larr; Volver al Proyecto
        </a>
    </div>

    <!-- Resumen de Reglas Financieras R1 y R2 -->
    <div class="p-4 bg-brand-50/60 border border-brand-200 rounded-xl text-xs text-brand-900 space-y-1.5 shadow-xs">
        <p class="font-bold flex items-center gap-1.5 text-brand-950">
            <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Reglas de Integridad Financiera MariaDB (R1 y R2):
        </p>
        <p class="leading-relaxed">
            &bull; <strong>Regla R1:</strong> Ni el gasto acumulado ni el planificado pueden superar el monto total del proyecto: <strong class="font-mono text-slate-900">S/ {{ number_format($proyecto->monto, 2) }}</strong>.
        </p>
        <p class="leading-relaxed">
            &bull; <strong>Regla R2:</strong> El gasto devengado acumulado es monótono no decreciente (debe ser mayor o igual al reporte cronológico anterior y menor o igual al posterior).
        </p>
        @if($ultimoCorte)
            <div class="mt-2 text-[11px] font-semibold text-brand-900 pt-1.5 border-t border-brand-200/60 flex items-center justify-between">
                <span>Último corte registrado: <strong class="font-mono">{{ $ultimoCorte->fecha_reporte }}</strong></span>
                <span class="font-mono">Gasto: S/ {{ number_format($ultimoCorte->gasto_acumulado, 2) }}</span>
            </div>
        @endif
    </div>

    <!-- Errores de Validación -->
    @if ($errors->any())
        <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl text-rose-900 text-xs shadow-xs" role="alert">
            <p class="font-bold flex items-center mb-1">
                <svg class="w-4 h-4 mr-1.5 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                Corrige los siguientes campos:
            </p>
            <ul class="list-disc list-inside space-y-0.5 text-rose-800 pl-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white p-6 sm:p-7 rounded-xl border border-slate-200/90 shadow-xs">
        <form method="POST" action="{{ route('proyectos.avances.store', $proyecto) }}" class="space-y-5" @submit="submitting = true">
            @csrf

            <div>
                <label for="fecha_reporte" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Fecha de Corte / Reporte <span class="text-rose-500">*</span>
                </label>
                <input type="date" name="fecha_reporte" id="fecha_reporte" value="{{ old('fecha_reporte') }}" required
                    class="w-full text-xs sm:text-sm font-mono border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                <p class="text-[11px] text-slate-400 mt-1">Solo se permite un corte presupuestal por fecha para este proyecto.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="presupuesto_planificado" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Presupuesto Planificado Acumulado (S/) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0" max="{{ $proyecto->monto }}"
                        name="presupuesto_planificado" id="presupuesto_planificado"
                        value="{{ old('presupuesto_planificado') }}" required placeholder="0.00"
                        class="w-full text-xs sm:text-sm font-mono border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500 tabular-nums">
                </div>

                <div>
                    <label for="gasto_acumulado" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Gasto Real Devengado Acumulado (S/) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0" max="{{ $proyecto->monto }}"
                        name="gasto_acumulado" id="gasto_acumulado"
                        value="{{ old('gasto_acumulado') }}" required placeholder="0.00"
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
                    <span x-text="submitting ? 'Guardando...' : 'Guardar Corte Presupuestal'">Guardar Corte Presupuestal</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
