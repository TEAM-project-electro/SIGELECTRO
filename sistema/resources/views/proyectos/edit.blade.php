@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center space-x-2">
                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-blue-100 text-blue-800">
                    {{ $proyecto->codigo_pgd }}
                </span>
                <h1 class="text-2xl font-bold text-slate-900">Editar Proyecto</h1>
            </div>
            <p class="text-sm text-slate-500 mt-1">
                Actualización de metadatos con control de concurrencia optimista.
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('proyectos.show', $proyecto) }}" class="text-sm font-medium text-blue-900 hover:underline">
                &larr; Volver al detalle
            </a>
        </div>
    </div>

    @if ($errors->has('concurrencia'))
        <div class="p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r text-amber-900 text-sm">
            <p class="font-bold">Conflicto de Concurrencia:</p>
            <p class="mt-1">{{ $errors->first('concurrencia') }}</p>
        </div>
    @endif

    @if ($errors->any() && !$errors->has('concurrencia'))
        <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r text-rose-800 text-sm">
            <p class="font-bold">Corrige los siguientes errores en el formulario:</p>
            <ul class="list-disc list-inside mt-1 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <form method="POST" action="{{ route('proyectos.update', $proyecto) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- Control de Concurrencia Optimista -->
            <input type="hidden" name="fecha_actualizacion_esperada" value="{{ $proyecto->fecha_actualizacion }}">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">
                        Código PGD (No editable)
                    </label>
                    <input type="text" value="{{ $proyecto->codigo_pgd }}" disabled
                        class="w-full text-sm border-slate-200 rounded-lg px-3 py-2 bg-slate-100 text-slate-600 font-mono font-bold cursor-not-allowed">
                </div>

                <div class="sm:col-span-2">
                    <label for="monto" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                        Presupuesto Total (S/) *
                    </label>
                    <input type="number" step="0.01" min="0" name="monto" id="monto"
                        value="{{ old('monto', $proyecto->monto) }}" required
                        class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
                    <p class="text-[11px] text-slate-500 mt-1">
                        Nota: La Regla R1 impide reducir el monto por debajo del gasto acumulado ya reportado en los cortes.
                    </p>
                </div>
            </div>

            <div>
                <label for="nombre" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                    Nombre del Proyecto *
                </label>
                <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $proyecto->nombre) }}" required
                    class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="anio_inicio" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                        Año Inicio *
                    </label>
                    <input type="number" min="2020" max="2035" name="anio_inicio" id="anio_inicio"
                        value="{{ old('anio_inicio', $proyecto->anio_inicio) }}" required
                        class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
                </div>

                <div>
                    <label for="anio_fin" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                        Año Fin *
                    </label>
                    <input type="number" min="2020" max="2035" name="anio_fin" id="anio_fin"
                        value="{{ old('anio_fin', $proyecto->anio_fin) }}" required
                        class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="id_objetivo" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                        Objetivo de Gobierno Digital *
                    </label>
                    <select name="id_objetivo" id="id_objetivo" required
                        class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
                        @foreach($objetivos as $obj)
                            <option value="{{ $obj->id_objetivo }}" {{ old('id_objetivo', $proyecto->id_objetivo) == $obj->id_objetivo ? 'selected' : '' }}>
                                {{ $obj->codigo }} - {{ Str::limit($obj->descripcion, 40) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="id_responsable" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                        Responsable Titular *
                    </label>
                    @if(Auth::user()->esAdmin())
                        <select name="id_responsable" id="id_responsable" required
                            class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
                            @foreach($responsables as $resp)
                                <option value="{{ $resp->id_responsable }}" {{ old('id_responsable', $proyecto->id_responsable) == $resp->id_responsable ? 'selected' : '' }}>
                                    {{ $resp->nombre }} ({{ $resp->area }})
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="hidden" name="id_responsable" value="{{ $proyecto->id_responsable }}">
                        <input type="text" value="{{ $proyecto->responsable->nombre }} (Solo editable por Administrador)" disabled
                            class="w-full text-sm border-slate-200 rounded-lg px-3 py-2 bg-slate-100 text-slate-600 cursor-not-allowed">
                    @endif
                </div>
            </div>

            <div>
                <label for="fuente" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                    Fuente
                </label>
                <input type="text" name="fuente" id="fuente" value="{{ old('fuente', $proyecto->fuente) }}"
                    class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
            </div>

            <div>
                <label for="motivo_cambio" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                    Motivo de la Modificación (Auditoría)
                </label>
                <input type="text" name="motivo_cambio" id="motivo_cambio" value="{{ old('motivo_cambio') }}"
                    placeholder="Justificación del cambio para la bitácora..."
                    class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
            </div>

            <div class="pt-4 border-t border-slate-200 flex justify-end space-x-3">
                <a href="{{ route('proyectos.show', $proyecto) }}"
                    class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                    Cancelar
                </a>
                <button type="submit"
                    class="px-5 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-lg text-sm font-medium transition shadow">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
