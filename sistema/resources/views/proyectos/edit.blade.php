@extends('layouts.app')

@section('title', 'Editar PGD ' . $proyecto->codigo_pgd)

@section('content')
<div class="max-w-2xl mx-auto space-y-6" x-data="{ enviando: false }">
    <div class="flex items-end justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Editar proyecto <span translate="no" class="font-mono text-brand-800">PGD {{ $proyecto->codigo_pgd }}</span></h1>
            <p class="text-sm text-slate-600 mt-1 truncate">{{ $proyecto->nombre }}</p>
        </div>
        <a href="{{ route('proyectos.show', $proyecto) }}" class="sig-btn-secondary shrink-0">Volver</a>
    </div>

    @if ($errors->has('concurrencia'))
        <x-alert type="warning">
            {{ $errors->first('concurrencia') }} Recargue la página para ver los datos actualizados.
        </x-alert>
    @endif

    @if ($errors->any() && !$errors->has('concurrencia'))
        <x-alert type="error">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    <form method="POST" action="{{ route('proyectos.update', $proyecto) }}" @submit="enviando = true"
          class="sig-panel p-6 space-y-5">
        @csrf
        @method('PUT')
        <input type="hidden" name="revision_esperada" value="{{ old('revision_esperada', \Illuminate\Support\Facades\DB::table('control_revision')->where('id', 1)->value('version')) }}">
        <input type="hidden" name="fecha_actualizacion_esperada" value="{{ $proyecto->fecha_actualizacion }}">

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="codigo_pgd_readonly" class="block text-xs font-semibold text-slate-600 mb-1.5">Código PGD</label>
                <input id="codigo_pgd_readonly" type="text" value="{{ $proyecto->codigo_pgd }}" disabled
                       class="w-full rounded-lg border-slate-200 bg-slate-100 text-slate-600 text-sm font-mono">
            </div>
            <div class="sm:col-span-2">
                <label for="monto" class="block text-xs font-semibold text-slate-700 mb-1.5">Monto estimado (S/)</label>
                @if(Auth::user()->esAdmin())
                    <input type="number" inputmode="decimal" step="0.01" min="0" name="monto" id="monto" value="{{ old('monto', $proyecto->monto) }}" required
                           class="sig-input tabular-nums">
                @else
                    <input type="hidden" name="monto" value="{{ $proyecto->monto }}">
                    <input id="monto" type="text" value="S/ {{ number_format($proyecto->monto, 2) }}" disabled
                           class="w-full rounded-lg border-slate-200 bg-slate-100 text-slate-600 text-sm tabular-nums">
                @endif
            </div>
        </div>

        <div>
            <label for="nombre" class="block text-xs font-semibold text-slate-700 mb-1.5">Nombre del proyecto</label>
            <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $proyecto->nombre) }}" required autocomplete="off"
                   class="sig-input">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="anio_inicio" class="block text-xs font-semibold text-slate-700 mb-1.5">Año de inicio</label>
                @if(Auth::user()->esAdmin())
                    <input type="number" inputmode="numeric" min="2020" max="2035" name="anio_inicio" id="anio_inicio" value="{{ old('anio_inicio', $proyecto->anio_inicio) }}" required
                           class="sig-input tabular-nums">
                @else
                    <input type="hidden" name="anio_inicio" value="{{ $proyecto->anio_inicio }}">
                    <input id="anio_inicio" type="text" value="{{ $proyecto->anio_inicio }}" disabled
                           class="w-full rounded-lg border-slate-200 bg-slate-100 text-slate-600 text-sm tabular-nums">
                @endif
            </div>
            <div>
                <label for="anio_fin" class="block text-xs font-semibold text-slate-700 mb-1.5">Año de fin</label>
                @if(Auth::user()->esAdmin())
                    <input type="number" inputmode="numeric" min="2020" max="2035" name="anio_fin" id="anio_fin" value="{{ old('anio_fin', $proyecto->anio_fin) }}" required
                           class="sig-input tabular-nums">
                @else
                    <input type="hidden" name="anio_fin" value="{{ $proyecto->anio_fin }}">
                    <input id="anio_fin" type="text" value="{{ $proyecto->anio_fin }}" disabled
                           class="w-full rounded-lg border-slate-200 bg-slate-100 text-slate-600 text-sm tabular-nums">
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="id_objetivo" class="block text-xs font-semibold text-slate-700 mb-1.5">Objetivo de Gobierno Digital</label>
                @if(Auth::user()->esAdmin())
                    <select name="id_objetivo" id="id_objetivo" required class="sig-input">
                        @foreach($objetivos as $obj)
                            <option value="{{ $obj->id_objetivo }}" @selected(old('id_objetivo', $proyecto->id_objetivo) == $obj->id_objetivo)>
                                {{ $obj->codigo }} — {{ Str::limit($obj->descripcion, 40) }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="hidden" name="id_objetivo" value="{{ $proyecto->id_objetivo }}">
                    <input id="id_objetivo" type="text" value="{{ $proyecto->objetivo->codigo ?? '' }} — {{ $proyecto->objetivo->descripcion ?? '' }}" disabled
                           class="w-full rounded-lg border-slate-200 bg-slate-100 text-slate-600 text-sm">
                @endif
            </div>
            <div>
                <label for="id_responsable" class="block text-xs font-semibold text-slate-700 mb-1.5">Responsable</label>
                @if(Auth::user()->esAdmin())
                    <select name="id_responsable" id="id_responsable" required class="sig-input">
                        @foreach($responsables as $resp)
                            <option value="{{ $resp->id_responsable }}" @selected(old('id_responsable', $proyecto->id_responsable) == $resp->id_responsable)>
                                {{ $resp->nombre }} ({{ $resp->area }})
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="hidden" name="id_responsable" value="{{ $proyecto->id_responsable }}">
                    <input id="id_responsable" type="text" value="{{ $proyecto->responsable->nombre ?? 'Sin asignar' }}" disabled
                           class="w-full rounded-lg border-slate-200 bg-slate-100 text-slate-600 text-sm">
                @endif
            </div>
        </div>

        <div>
            <label for="fuente" class="block text-xs font-semibold text-slate-700 mb-1.5">Fuente</label>
            <input type="text" name="fuente" id="fuente" value="{{ old('fuente', $proyecto->fuente) }}" autocomplete="off"
                   class="sig-input">
        </div>

        <div>
            <label for="motivo_cambio" class="block text-xs font-semibold text-slate-700 mb-1.5">Motivo del cambio</label>
            <input type="text" name="motivo_cambio" id="motivo_cambio" value="{{ old('motivo_cambio') }}" maxlength="200" autocomplete="off"
                   placeholder="Obligatorio si reduce el monto estimado (5 a 200 caracteres)…"
                   class="sig-input">
        </div>

        <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
            <a href="{{ route('proyectos.show', $proyecto) }}" class="sig-btn-secondary">Cancelar</a>
            <button type="submit" :disabled="enviando" class="sig-btn-primary">
                <span x-text="enviando ? 'Guardando…' : 'Guardar cambios'">Guardar cambios</span>
            </button>
        </div>
    </form>
</div>
@endsection
