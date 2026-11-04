@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{ submitting: false }">

    <!-- Encabezado de Navegación -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-brand-50 text-brand-800 border border-brand-200">
                    NUEVA INICIATIVA
                </span>
                <span class="text-xs text-slate-400">&bull; Portafolio PGD</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                Registrar Nuevo Proyecto de Gobierno Digital
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Incorporación de proyecto a la cartera institucional de Electrocentro S. A.
            </p>
        </div>
        <a href="{{ route('proyectos.index') }}"
           class="px-3.5 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 shadow-xs transition">
            &larr; Volver a Cartera
        </a>
    </div>

    <!-- Errores de Validación -->
    @if ($errors->any())
        <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl text-rose-900 text-xs shadow-xs" role="alert">
            <p class="font-bold flex items-center mb-1">
                <svg class="w-4 h-4 mr-1.5 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                Por favor revise los siguientes campos obligatorios:
            </p>
            <ul class="list-disc list-inside space-y-0.5 text-rose-800 pl-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
        <form method="POST" action="{{ route('proyectos.store') }}" class="p-6 sm:p-8 space-y-6" @submit="submitting = true">
            @csrf

            <!-- SECCIÓN 1: Identificación y Presupuesto -->
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-brand-600"></span>
                    1. Identificación y Presupuesto
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 mt-4">
                    <div class="sm:col-span-4">
                        <label for="codigo_pgd" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Código PGD <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="codigo_pgd" id="codigo_pgd" value="{{ old('codigo_pgd') }}" required
                            placeholder="p. ej. 011"
                            class="w-full text-xs sm:text-sm font-mono font-bold border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                        <p class="text-[11px] text-slate-400 mt-1">Identificador único en el portafolio PGD.</p>
                    </div>

                    <div class="sm:col-span-8">
                        <label for="monto" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Presupuesto Total Adjudicado (S/) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="0.01" min="0" name="monto" id="monto" value="{{ old('monto') }}" required
                            placeholder="0.00"
                            class="w-full text-xs sm:text-sm font-mono font-semibold border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500 tabular-nums">
                        <p class="text-[11px] text-slate-400 mt-1">Importe en moneda nacional (PEN). Límite de la Regla R1.</p>
                    </div>

                    <div class="sm:col-span-12">
                        <label for="nombre" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Nombre Descriptivo del Proyecto <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}" required
                            placeholder="p. ej. Modernización de la Plataforma de Ciberseguridad y SOC"
                            class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: Cronograma y Estado -->
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-brand-600"></span>
                    2. Periodo de Vigencia y Estado Inicial
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                    <div>
                        <label for="anio_inicio" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Año Inicio <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" min="2020" max="2035" name="anio_inicio" id="anio_inicio"
                            value="{{ old('anio_inicio', 2023) }}" required
                            class="w-full text-xs sm:text-sm font-mono border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                    </div>

                    <div>
                        <label for="anio_fin" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Año Fin <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" min="2020" max="2035" name="anio_fin" id="anio_fin"
                            value="{{ old('anio_fin', 2025) }}" required
                            class="w-full text-xs sm:text-sm font-mono border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                    </div>

                    <div>
                        <label for="estado" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Estado Inicial <span class="text-rose-500">*</span>
                        </label>
                        <select name="estado" id="estado" required
                            class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500 font-medium">
                            <option value="En curso" {{ old('estado', 'En curso') === 'En curso' ? 'selected' : '' }}>En curso</option>
                            <option value="Pendiente" {{ old('estado') === 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 3: Gobernanza y Asignación Institucional -->
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-brand-600"></span>
                    3. Asignación Institucional y Gobernanza
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label for="id_objetivo" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Objetivo de Gobierno Digital (OGD) <span class="text-rose-500">*</span>
                        </label>
                        <select name="id_objetivo" id="id_objetivo" required
                            class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                            <option value="">Seleccione un objetivo...</option>
                            @foreach($objetivos as $obj)
                                <option value="{{ $obj->id_objetivo }}" {{ old('id_objetivo') == $obj->id_objetivo ? 'selected' : '' }}>
                                    {{ $obj->codigo }} - {{ Str::limit($obj->descripcion, 45) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="id_responsable" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Responsable Titular Asignado <span class="text-rose-500">*</span>
                        </label>
                        <select name="id_responsable" id="id_responsable" required
                            class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                            <option value="">Seleccione un responsable...</option>
                            @foreach($responsables as $resp)
                                <option value="{{ $resp->id_responsable }}" {{ old('id_responsable') == $resp->id_responsable ? 'selected' : '' }}>
                                    {{ $resp->nombre }} ({{ $resp->area }} &bull; {{ $resp->perfil }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="fuente" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Fuente Oficial del Proyecto
                        </label>
                        <input type="text" name="fuente" id="fuente" value="{{ old('fuente', 'Plan de Gobierno Digital Electrocentro 2023–2026') }}"
                            class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('proyectos.index') }}"
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
                    <span x-text="submitting ? 'Guardando...' : 'Crear Proyecto PGD'">Crear Proyecto PGD</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
