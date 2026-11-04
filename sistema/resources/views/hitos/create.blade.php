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
                <span class="text-xs text-slate-400">&bull; Gestión de Entregables</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                Registrar Hito de Proyecto
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

    <!-- Regla de Negocio R3 -->
    <div class="p-4 bg-brand-50/60 border border-brand-200 rounded-xl text-xs text-brand-900 space-y-1 shadow-xs">
        <p class="font-bold flex items-center gap-1.5 text-brand-950">
            <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Regla de Negocio R3 (Cierre Formal de Proyecto)
        </p>
        <p class="text-brand-800 leading-relaxed">
            Para cerrar formalmente el proyecto, el <strong>100% de los hitos clasificados en Línea Base</strong> deben tener fecha de cierre registrada y evidencia documental.
        </p>
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
        <form method="POST" action="{{ route('proyectos.hitos.store', $proyecto) }}" class="space-y-5" @submit="submitting = true">
            @csrf

            <div>
                <label for="descripcion" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Descripción del Hito / Entregable <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="descripcion" id="descripcion" value="{{ old('descripcion') }}" required maxlength="150"
                    placeholder="p. ej. Aprobación y firma del plan de contingencia TIC"
                    class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="fecha_prevista" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Fecha Prevista de Término <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="fecha_prevista" id="fecha_prevista" value="{{ old('fecha_prevista') }}" required
                        class="w-full text-xs sm:text-sm font-mono border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                </div>

                <div>
                    <label for="fecha_cierre" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Fecha Real de Cierre (Opcional)
                    </label>
                    <input type="date" name="fecha_cierre" id="fecha_cierre" value="{{ old('fecha_cierre') }}"
                        class="w-full text-xs sm:text-sm font-mono border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                </div>
            </div>

            <div>
                <label for="evidencia" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Evidencia o Referencia Documental
                </label>
                <input type="text" name="evidencia" id="evidencia" value="{{ old('evidencia') }}" maxlength="200"
                    placeholder="p. ej. Acta de conformidad SIG-2024-001 o Resolución N° 045-2024"
                    class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
            </div>

            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                <input type="hidden" name="en_linea_base" value="0">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="en_linea_base" value="1" {{ old('en_linea_base', '1') == '1' ? 'checked' : '' }}
                        class="w-4 h-4 text-brand-700 border-slate-300 rounded mt-0.5 focus:ring-brand-500">
                    <div>
                        <span class="text-xs font-bold text-slate-900 block">Pertenece a la Línea Base del Proyecto</span>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Los hitos en línea base se computan obligatoriamente para el porcentaje de avance físico y la condición de retraso. Hitos fuera de línea base son complementarios.
                        </p>
                    </div>
                </label>
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
                    <span x-text="submitting ? 'Guardando...' : 'Guardar Hito'">Guardar Hito</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
