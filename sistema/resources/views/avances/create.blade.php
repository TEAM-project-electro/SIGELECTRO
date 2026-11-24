@extends('layouts.app')

@section('title', 'Registrar corte presupuestal')

@section('content')
<div class="max-w-xl mx-auto space-y-6" x-data="{ enviando: false }">
    <div class="flex items-end justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Registrar corte presupuestal</h1>
            <p class="text-sm text-slate-600 mt-1 tabular-nums">
                <span translate="no" class="font-mono font-semibold text-brand-800">PGD {{ $proyecto->codigo_pgd }}</span> — Monto estimado: S/&nbsp;{{ number_format($proyecto->monto, 2) }}
            </p>
            @if($ultimoCorte)
                <p class="text-xs text-slate-500 mt-0.5 tabular-nums">
                    Último corte registrado: <span class="font-mono">{{ $ultimoCorte->fecha_reporte }}</span> (Gasto S/&nbsp;{{ number_format($ultimoCorte->gasto_acumulado, 2) }})
                </p>
            @endif
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

    <form method="POST" action="{{ route('proyectos.avances.store', $proyecto) }}" @submit="enviando = true"
          class="sig-panel p-6 space-y-5">
        @csrf

        <div>
            <label for="fecha_reporte" class="block text-xs font-semibold text-slate-700 mb-1.5">Fecha de corte</label>
            <input type="date" name="fecha_reporte" id="fecha_reporte" value="{{ old('fecha_reporte') }}" required
                   class="sig-input font-mono">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="presupuesto_planificado" class="block text-xs font-semibold text-slate-700 mb-1.5">Presupuesto planificado acumulado (S/)</label>
                <input type="number" inputmode="decimal" step="0.01" min="0" max="{{ $proyecto->monto }}" name="presupuesto_planificado" id="presupuesto_planificado"
                       value="{{ old('presupuesto_planificado') }}" required placeholder="0.00…"
                       class="sig-input tabular-nums">
            </div>
            <div>
                <label for="gasto_acumulado" class="block text-xs font-semibold text-slate-700 mb-1.5">Gasto acumulado (S/)</label>
                <input type="number" inputmode="decimal" step="0.01" min="0" max="{{ $proyecto->monto }}" name="gasto_acumulado" id="gasto_acumulado"
                       value="{{ old('gasto_acumulado') }}" required placeholder="0.00…"
                       class="sig-input tabular-nums">
            </div>
        </div>

        <p class="text-xs text-slate-500">Los importes no deben superar el monto del proyecto y el gasto acumulado no puede ser menor al del corte anterior.</p>

        <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
            <a href="{{ route('proyectos.show', $proyecto) }}" class="sig-btn-secondary">Cancelar</a>
            <button type="submit" :disabled="enviando" class="sig-btn-primary">
                <span x-text="enviando ? 'Guardando…' : 'Guardar corte'">Guardar corte</span>
            </button>
        </div>
    </form>
</div>
@endsection
