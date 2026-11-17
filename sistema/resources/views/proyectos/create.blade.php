@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto space-y-5" x-data="{ enviando: false }">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Nuevo proyecto</h1>
            <p class="text-xs text-slate-500 mt-0.5">Registro en la cartera del Plan de Gobierno Digital</p>
        </div>
        <a href="{{ route('proyectos.index') }}" class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs text-slate-700 hover:bg-slate-50">Volver</a>
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

    <form method="POST" action="{{ route('proyectos.store') }}" @submit="enviando = true"
          class="bg-white p-6 rounded-xl border border-slate-200 space-y-4 text-sm">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="codigo_pgd" class="block text-xs font-medium text-slate-700 mb-1">Código PGD</label>
                <input type="text" name="codigo_pgd" id="codigo_pgd" value="{{ old('codigo_pgd') }}" required
                       class="w-full rounded-lg border-slate-300 text-sm font-mono">
            </div>
            <div class="sm:col-span-2">
                <label for="monto" class="block text-xs font-medium text-slate-700 mb-1">Monto estimado (S/)</label>
                <input type="number" step="0.01" min="0" name="monto" id="monto" value="{{ old('monto') }}" required
                       class="w-full rounded-lg border-slate-300 text-sm tabular-nums">
            </div>
        </div>

        <div>
            <label for="nombre" class="block text-xs font-medium text-slate-700 mb-1">Nombre del proyecto</label>
            <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}" required
                   class="w-full rounded-lg border-slate-300 text-sm">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="anio_inicio" class="block text-xs font-medium text-slate-700 mb-1">Año de inicio</label>
                <input type="number" min="2020" max="2035" name="anio_inicio" id="anio_inicio" value="{{ old('anio_inicio', 2023) }}" required
                       class="w-full rounded-lg border-slate-300 text-sm tabular-nums">
            </div>
            <div>
                <label for="anio_fin" class="block text-xs font-medium text-slate-700 mb-1">Año de fin</label>
                <input type="number" min="2020" max="2035" name="anio_fin" id="anio_fin" value="{{ old('anio_fin', 2025) }}" required
                       class="w-full rounded-lg border-slate-300 text-sm tabular-nums">
            </div>
            <div>
                <label for="estado" class="block text-xs font-medium text-slate-700 mb-1">Estado inicial</label>
                <select name="estado" id="estado" required class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="En curso" @selected(old('estado', 'En curso') === 'En curso')>En curso</option>
                    <option value="Pendiente" @selected(old('estado') === 'Pendiente')>Pendiente</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="id_objetivo" class="block text-xs font-medium text-slate-700 mb-1">Objetivo de Gobierno Digital</label>
                <select name="id_objetivo" id="id_objetivo" required class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="">Seleccione…</option>
                    @foreach($objetivos as $obj)
                        <option value="{{ $obj->id_objetivo }}" @selected(old('id_objetivo') == $obj->id_objetivo)>
                            {{ $obj->codigo }} · {{ Str::limit($obj->descripcion, 45) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="id_responsable" class="block text-xs font-medium text-slate-700 mb-1">Responsable</label>
                <select name="id_responsable" id="id_responsable" required class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="">Seleccione…</option>
                    @foreach($responsables as $resp)
                        <option value="{{ $resp->id_responsable }}" @selected(old('id_responsable') == $resp->id_responsable)>
                            {{ $resp->nombre }} ({{ $resp->area }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label for="fuente" class="block text-xs font-medium text-slate-700 mb-1">Fuente</label>
            <input type="text" name="fuente" id="fuente" value="{{ old('fuente', 'Plan de Gobierno Digital Electrocentro 2023–2026') }}"
                   class="w-full rounded-lg border-slate-300 text-sm">
        </div>

        <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
            <a href="{{ route('proyectos.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-xs text-slate-700 hover:bg-slate-50">Cancelar</a>
            <button type="submit" :disabled="enviando" class="px-4 py-2 bg-brand-800 hover:bg-brand-900 text-white rounded-lg text-xs font-semibold disabled:opacity-70">
                <span x-text="enviando ? 'Guardando…' : 'Guardar proyecto'">Guardar proyecto</span>
            </button>
        </div>
    </form>
</div>
@endsection
