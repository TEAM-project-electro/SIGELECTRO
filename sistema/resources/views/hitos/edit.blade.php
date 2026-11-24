@extends('layouts.app')

@section('title', 'Editar hito')

@section('content')
<div class="max-w-xl mx-auto space-y-6" x-data="{ enviando: false }">
    <div class="flex items-end justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Editar hito</h1>
            <p class="text-sm text-slate-600 mt-1 truncate">
                <span translate="no" class="font-mono font-semibold text-brand-800">PGD {{ $proyecto->codigo_pgd }}</span> — {{ $proyecto->nombre }}
            </p>
        </div>
        <a href="{{ route('proyectos.show', $proyecto) }}" class="sig-btn-secondary shrink-0">Volver</a>
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

    <form method="POST" action="{{ route('hitos.update', $hito) }}" @submit="enviando = true"
          class="sig-panel p-6 space-y-5">
        @csrf
        @method('PUT')
        <input type="hidden" name="revision_esperada" value="{{ old('revision_esperada', \Illuminate\Support\Facades\DB::table('control_revision')->where('id', 1)->value('version')) }}">

        <div>
            <label for="descripcion" class="block text-xs font-semibold text-slate-700 mb-1.5">Descripción del hito</label>
            <input type="text" name="descripcion" id="descripcion" value="{{ old('descripcion', $hito->descripcion) }}" required maxlength="150" autocomplete="off"
                   class="sig-input">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="fecha_prevista" class="block text-xs font-semibold text-slate-700 mb-1.5">Fecha prevista</label>
                <input type="date" name="fecha_prevista" id="fecha_prevista" value="{{ old('fecha_prevista', $hito->fecha_prevista) }}" required
                       class="sig-input font-mono">
            </div>
            <div>
                <label for="fecha_cierre" class="block text-xs font-semibold text-slate-700 mb-1.5">Fecha de cierre</label>
                <input type="date" name="fecha_cierre" id="fecha_cierre" value="{{ old('fecha_cierre', $hito->fecha_cierre) }}"
                       class="sig-input font-mono">
            </div>
        </div>

        <div>
            <label for="evidencia" class="block text-xs font-semibold text-slate-700 mb-1.5">Evidencia o documento de referencia</label>
            <input type="text" name="evidencia" id="evidencia" value="{{ old('evidencia', $hito->evidencia) }}" maxlength="200" autocomplete="off"
                   class="sig-input">
        </div>

        <div>
            <input type="hidden" name="en_linea_base" value="0">
            <label for="en_linea_base" class="inline-flex items-center gap-2.5 text-xs font-medium text-slate-700 cursor-pointer select-none">
                <input id="en_linea_base" type="checkbox" name="en_linea_base" value="1" @checked(old('en_linea_base', $hito->en_linea_base))
                       class="rounded border-slate-300 text-brand-700 focus:ring-brand-600">
                <span>Forma parte de la línea base del proyecto</span>
            </label>
        </div>

        <div>
            <label for="motivo_cambio" class="block text-xs font-semibold text-slate-700 mb-1.5">Motivo del cambio</label>
            <input type="text" name="motivo_cambio" id="motivo_cambio" value="{{ old('motivo_cambio') }}" maxlength="200" autocomplete="off"
                   placeholder="Obligatorio si modifica la pertenencia a la línea base (5 a 200 caracteres)…"
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
