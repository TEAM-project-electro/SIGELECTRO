@extends('layouts.app')

@section('title', 'Editar corte presupuestal')

@section('content')
<div class="max-w-xl mx-auto space-y-6" x-data="{ enviando: false }">
    <div class="flex items-end justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Editar corte presupuestal</h1>
            <p class="text-sm text-slate-600 mt-1 tabular-nums">
                <span translate="no" class="font-mono font-semibold text-brand-800">PGD {{ $proyecto->codigo_pgd }}</span> — Corte <span class="font-mono">{{ $avance->fecha_reporte }}</span> (Monto S/&nbsp;{{ number_format($proyecto->monto, 2) }})
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

    <form method="POST" action="{{ route('avances.update', $avance) }}" @submit="enviando = true"
          class="sig-panel p-6 space-y-5">
        @csrf
        @method('PUT')
        <input type="hidden" name="revision_esperada" value="{{ old('revision_esperada', \Illuminate\Support\Facades\DB::table('control_revision')->where('id', 1)->value('version')) }}">

        <div>
            <label for="fecha_reporte_readonly" class="block text-xs font-semibold text-slate-600 mb-1.5">Fecha de corte</label>
            <input id="fecha_reporte_readonly" type="date" value="{{ $avance->fecha_reporte }}" disabled
                   class="w-full rounded-lg border-slate-200 bg-slate-100 text-slate-600 text-sm font-mono">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="presupuesto_planificado" class="block text-xs font-semibold text-slate-700 mb-1.5">Presupuesto planificado acumulado (S/)</label>
                <input type="number" inputmode="decimal" step="0.01" min="0" max="{{ $proyecto->monto }}" name="presupuesto_planificado" id="presupuesto_planificado"
                       value="{{ old('presupuesto_planificado', $avance->presupuesto_planificado) }}" required
                       class="sig-input tabular-nums">
            </div>
            <div>
                <label for="gasto_acumulado" class="block text-xs font-semibold text-slate-700 mb-1.5">Gasto acumulado (S/)</label>
                <input type="number" inputmode="decimal" step="0.01" min="0" max="{{ $proyecto->monto }}" name="gasto_acumulado" id="gasto_acumulado"
                       value="{{ old('gasto_acumulado', $avance->gasto_acumulado) }}" required
                       class="sig-input tabular-nums">
            </div>
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
