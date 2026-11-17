@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto space-y-5" x-data="{ enviando: false }">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Editar hito</h1>
            <p class="text-xs text-slate-500 mt-0.5">PGD {{ $proyecto->codigo_pgd }} · {{ $proyecto->nombre }}</p>
        </div>
        <a href="{{ route('proyectos.show', $proyecto) }}" class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs text-slate-700 hover:bg-slate-50">Volver</a>
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
          class="bg-white p-6 rounded-xl border border-slate-200 space-y-4 text-sm">
        @csrf
        @method('PUT')
        <input type="hidden" name="revision_esperada" value="{{ old('revision_esperada', \Illuminate\Support\Facades\DB::table('control_revision')->where('id', 1)->value('version')) }}">

        <div>
            <label for="descripcion" class="block text-xs font-medium text-slate-700 mb-1">Descripción del hito</label>
            <input type="text" name="descripcion" id="descripcion" value="{{ old('descripcion', $hito->descripcion) }}" required maxlength="150"
                   class="w-full rounded-lg border-slate-300 text-sm">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="fecha_prevista" class="block text-xs font-medium text-slate-700 mb-1">Fecha prevista</label>
                <input type="date" name="fecha_prevista" id="fecha_prevista" value="{{ old('fecha_prevista', $hito->fecha_prevista) }}" required
                       class="w-full rounded-lg border-slate-300 text-sm font-mono">
            </div>
            <div>
                <label for="fecha_cierre" class="block text-xs font-medium text-slate-700 mb-1">Fecha de cierre</label>
                <input type="date" name="fecha_cierre" id="fecha_cierre" value="{{ old('fecha_cierre', $hito->fecha_cierre) }}"
                       class="w-full rounded-lg border-slate-300 text-sm font-mono">
            </div>
        </div>

        <div>
            <label for="evidencia" class="block text-xs font-medium text-slate-700 mb-1">Evidencia o documento de referencia</label>
            <input type="text" name="evidencia" id="evidencia" value="{{ old('evidencia', $hito->evidencia) }}" maxlength="200"
                   class="w-full rounded-lg border-slate-300 text-sm">
        </div>

        <div>
            <input type="hidden" name="en_linea_base" value="0">
            <label class="inline-flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                <input type="checkbox" name="en_linea_base" value="1" @checked(old('en_linea_base', $hito->en_linea_base))
                       class="rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                <span>Forma parte de la línea base del proyecto</span>
            </label>
        </div>

        <div>
            <label for="motivo_cambio" class="block text-xs font-medium text-slate-700 mb-1">Motivo del cambio</label>
            <input type="text" name="motivo_cambio" id="motivo_cambio" value="{{ old('motivo_cambio') }}" maxlength="200"
                   placeholder="Obligatorio si modifica la pertenencia a la línea base (5 a 200 caracteres)"
                   class="w-full rounded-lg border-slate-300 text-sm">
        </div>

        <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
            <a href="{{ route('proyectos.show', $proyecto) }}" class="px-4 py-2 border border-slate-300 rounded-lg text-xs text-slate-700 hover:bg-slate-50">Cancelar</a>
            <button type="submit" :disabled="enviando" class="px-4 py-2 bg-brand-800 hover:bg-brand-900 text-white rounded-lg text-xs font-semibold disabled:opacity-70">
                <span x-text="enviando ? 'Guardando…' : 'Guardar cambios'">Guardar cambios</span>
            </button>
        </div>
    </form>
</div>
@endsection
