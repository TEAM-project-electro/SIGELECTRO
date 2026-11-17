@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto space-y-5" x-data="{ enviando: false }">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Editar corte presupuestal</h1>
            <p class="text-xs text-slate-500 mt-0.5">PGD {{ $proyecto->codigo_pgd }} · Corte {{ $avance->fecha_reporte }} · Monto estimado: S/ {{ number_format($proyecto->monto, 2) }}</p>
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

    <form method="POST" action="{{ route('avances.update', $avance) }}" @submit="enviando = true"
          class="bg-white p-6 rounded-xl border border-slate-200 space-y-4 text-sm">
        @csrf
        @method('PUT')
        <input type="hidden" name="revision_esperada" value="{{ old('revision_esperada', \Illuminate\Support\Facades\DB::table('control_revision')->where('id', 1)->value('version')) }}">

        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Fecha de corte</label>
            <input type="date" value="{{ $avance->fecha_reporte }}" disabled
                   class="w-full rounded-lg border-slate-200 bg-slate-100 text-slate-600 text-sm font-mono">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="presupuesto_planificado" class="block text-xs font-medium text-slate-700 mb-1">Presupuesto planificado acumulado (S/)</label>
                <input type="number" step="0.01" min="0" max="{{ $proyecto->monto }}" name="presupuesto_planificado" id="presupuesto_planificado"
                       value="{{ old('presupuesto_planificado', $avance->presupuesto_planificado) }}" required
                       class="w-full rounded-lg border-slate-300 text-sm tabular-nums">
            </div>
            <div>
                <label for="gasto_acumulado" class="block text-xs font-medium text-slate-700 mb-1">Gasto acumulado (S/)</label>
                <input type="number" step="0.01" min="0" max="{{ $proyecto->monto }}" name="gasto_acumulado" id="gasto_acumulado"
                       value="{{ old('gasto_acumulado', $avance->gasto_acumulado) }}" required
                       class="w-full rounded-lg border-slate-300 text-sm tabular-nums">
            </div>
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
