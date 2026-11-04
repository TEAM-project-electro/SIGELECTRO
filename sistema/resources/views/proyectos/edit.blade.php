@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{ submitting: false }">

    <!-- Encabezado de Edición -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="font-mono text-xs font-bold px-2.5 py-0.5 rounded bg-brand-50 text-brand-800 border border-brand-200">
                    PGD {{ $proyecto->codigo_pgd }}
                </span>
                <span class="text-xs text-slate-400">&bull; Control de Concurrencia Optimista</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                Editar Proyecto de Gobierno Digital
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Actualización de metadatos del proyecto con trazabilidad en bitácora R4.
            </p>
        </div>
        <a href="{{ route('proyectos.show', $proyecto) }}"
           class="px-3.5 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 shadow-xs transition">
            &larr; Volver al Detalle
        </a>
    </div>

    <!-- Alerta de Conflicto de Concurrencia Optimista -->
    @if ($errors->has('concurrencia'))
        <div class="p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-xl text-amber-950 text-xs shadow-xs" role="alert">
            <div class="flex items-start gap-2.5">
                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <p class="font-bold text-sm">Conflicto de Edición Concurrente Detectado:</p>
                    <p class="mt-1 leading-relaxed">{{ $errors->first('concurrencia') }}</p>
                    <p class="mt-1 text-[11px] text-amber-800">Recargue la página para obtener la última versión actualizada de los datos.</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Errores Generales de Validación -->
    @if ($errors->any() && !$errors->has('concurrencia'))
        <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl text-rose-900 text-xs shadow-xs" role="alert">
            <p class="font-bold flex items-center mb-1">
                <svg class="w-4 h-4 mr-1.5 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                Por favor revise los siguientes errores:
            </p>
            <ul class="list-disc list-inside space-y-0.5 text-rose-800 pl-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
        <form method="POST" action="{{ route('proyectos.update', $proyecto) }}" class="p-6 sm:p-8 space-y-6" @submit="submitting = true">
            @csrf
            @method('PUT')

            <!-- Campos Ocultos de Control de Concurrencia y Versión Global -->
            <input type="hidden" name="revision_esperada" value="{{ old('revision_esperada', \Illuminate\Support\Facades\DB::table('control_revision')->where('id', 1)->value('version')) }}">
            <input type="hidden" name="fecha_actualizacion_esperada" value="{{ $proyecto->fecha_actualizacion }}">

            <!-- SECCIÓN 1: Identificación y Presupuesto -->
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-brand-600"></span>
                    1. Identificación y Presupuesto
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 mt-4">
                    <div class="sm:col-span-4">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Código PGD (Inmutable)
                        </label>
                        <input type="text" value="{{ $proyecto->codigo_pgd }}" disabled
                            class="w-full text-xs sm:text-sm border-slate-200 rounded-lg px-3 py-2 bg-slate-100 text-slate-600 font-mono font-bold cursor-not-allowed">
                        <p class="text-[11px] text-slate-400 mt-1">Clave canónica del proyecto.</p>
                    </div>

                    <div class="sm:col-span-8">
                        <label for="monto" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Presupuesto Total Adjudicado (S/) *
                            @if(!Auth::user()->esAdmin())
                                <span class="text-slate-400 font-normal text-[11px] normal-case">(Solo editable por Administrador)</span>
                            @endif
                        </label>
                        @if(Auth::user()->esAdmin())
                            <input type="number" step="0.01" min="0" name="monto" id="monto"
                                value="{{ old('monto', $proyecto->monto) }}" required
                                class="w-full text-xs sm:text-sm font-mono font-semibold border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500 tabular-nums">
                        @else
                            <input type="hidden" name="monto" value="{{ $proyecto->monto }}">
                            <input type="text" value="S/ {{ number_format($proyecto->monto, 2) }}" disabled
                                class="w-full text-xs sm:text-sm font-mono border-slate-200 rounded-lg px-3 py-2 bg-slate-100 text-slate-600 cursor-not-allowed tabular-nums">
                        @endif
                        <p class="text-[11px] text-slate-400 mt-1">
                            Regla R1: El monto total no puede reducirse por debajo del gasto acumulado reportado en los cortes existentes.
                        </p>
                    </div>

                    <div class="sm:col-span-12">
                        <label for="nombre" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Nombre del Proyecto <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $proyecto->nombre) }}" required
                            class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: Cronograma y Vigencia -->
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-brand-600"></span>
                    2. Periodo de Vigencia
                    @if(!Auth::user()->esAdmin())
                        <span class="text-slate-400 font-normal text-[11px] normal-case">(Solo editable por Administrador)</span>
                    @endif
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label for="anio_inicio" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Año Inicio *
                        </label>
                        @if(Auth::user()->esAdmin())
                            <input type="number" min="2020" max="2035" name="anio_inicio" id="anio_inicio"
                                value="{{ old('anio_inicio', $proyecto->anio_inicio) }}" required
                                class="w-full text-xs sm:text-sm font-mono border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                        @else
                            <input type="hidden" name="anio_inicio" value="{{ $proyecto->anio_inicio }}">
                            <input type="text" value="{{ $proyecto->anio_inicio }}" disabled
                                class="w-full text-xs sm:text-sm font-mono border-slate-200 rounded-lg px-3 py-2 bg-slate-100 text-slate-600 cursor-not-allowed">
                        @endif
                    </div>

                    <div>
                        <label for="anio_fin" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Año Fin *
                        </label>
                        @if(Auth::user()->esAdmin())
                            <input type="number" min="2020" max="2035" name="anio_fin" id="anio_fin"
                                value="{{ old('anio_fin', $proyecto->anio_fin) }}" required
                                class="w-full text-xs sm:text-sm font-mono border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                        @else
                            <input type="hidden" name="anio_fin" value="{{ $proyecto->anio_fin }}">
                            <input type="text" value="{{ $proyecto->anio_fin }}" disabled
                                class="w-full text-xs sm:text-sm font-mono border-slate-200 rounded-lg px-3 py-2 bg-slate-100 text-slate-600 cursor-not-allowed">
                        @endif
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
                            Objetivo de Gobierno Digital *
                            @if(!Auth::user()->esAdmin())
                                <span class="text-slate-400 font-normal text-[11px] normal-case">(Solo Administrador)</span>
                            @endif
                        </label>
                        @if(Auth::user()->esAdmin())
                            <select name="id_objetivo" id="id_objetivo" required
                                class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                                @foreach($objetivos as $obj)
                                    <option value="{{ $obj->id_objetivo }}" {{ old('id_objetivo', $proyecto->id_objetivo) == $obj->id_objetivo ? 'selected' : '' }}>
                                        {{ $obj->codigo }} - {{ Str::limit($obj->descripcion, 40) }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="id_objetivo" value="{{ $proyecto->id_objetivo }}">
                            <input type="text" value="{{ $proyecto->objetivo->codigo ?? '' }} - {{ $proyecto->objetivo->descripcion ?? '' }}" disabled
                                class="w-full text-xs sm:text-sm border-slate-200 rounded-lg px-3 py-2 bg-slate-100 text-slate-600 cursor-not-allowed truncate">
                        @endif
                    </div>

                    <div>
                        <label for="id_responsable" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Responsable Titular *
                            @if(!Auth::user()->esAdmin())
                                <span class="text-slate-400 font-normal text-[11px] normal-case">(Solo Administrador)</span>
                            @endif
                        </label>
                        @if(Auth::user()->esAdmin())
                            <select name="id_responsable" id="id_responsable" required
                                class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                                @foreach($responsables as $resp)
                                    <option value="{{ $resp->id_responsable }}" {{ old('id_responsable', $proyecto->id_responsable) == $resp->id_responsable ? 'selected' : '' }}>
                                        {{ $resp->nombre }} ({{ $resp->area }})
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="id_responsable" value="{{ $proyecto->id_responsable }}">
                            <input type="text" value="{{ $proyecto->responsable->nombre ?? 'Sin asignar' }}" disabled
                                class="w-full text-xs sm:text-sm border-slate-200 rounded-lg px-3 py-2 bg-slate-100 text-slate-600 cursor-not-allowed">
                        @endif
                    </div>

                    <div class="sm:col-span-2">
                        <label for="fuente" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Fuente Oficial
                        </label>
                        <input type="text" name="fuente" id="fuente" value="{{ old('fuente', $proyecto->fuente) }}"
                            class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 4: Auditoría y Motivo de Cambio -->
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-brand-600"></span>
                    4. Justificación y Trazabilidad de Auditoría
                </h3>
                <div class="mt-4">
                    <label for="motivo_cambio" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Motivo de la Modificación (Bitácora R4)
                    </label>
                    <input type="text" name="motivo_cambio" id="motivo_cambio" value="{{ old('motivo_cambio') }}"
                        maxlength="200"
                        placeholder="Justificación del cambio para la bitácora de auditoría (5 a 200 caracteres si reduce presupuesto)..."
                        class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2.5 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                    <p class="text-[11px] text-slate-400 mt-1">
                        Requerido si se modifica el presupuesto del proyecto o se ajustan líneas base.
                    </p>
                </div>
            </div>

            <!-- Botones de Acción -->
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
                    <span x-text="submitting ? 'Guardando...' : 'Guardar Modificaciones'">Guardar Modificaciones</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
