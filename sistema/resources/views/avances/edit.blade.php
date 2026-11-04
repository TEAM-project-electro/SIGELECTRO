@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center space-x-2">
                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-blue-100 text-blue-800">
                    PGD {{ $proyecto->codigo_pgd }}
                </span>
                <h1 class="text-2xl font-bold text-slate-900">Editar Corte Presupuestal</h1>
            </div>
            <p class="text-sm text-slate-500 mt-1">
                Fecha del Corte: <span class="font-mono font-bold text-slate-800">{{ $avance->fecha_reporte }}</span>
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('proyectos.show', $proyecto) }}" class="text-sm font-medium text-blue-900 hover:underline">
                &larr; Volver al proyecto
            </a>
        </div>
    </div>

    <!-- Resumen de Reglas Financieras -->
    <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl text-xs text-blue-900 space-y-1">
        <p class="font-bold">Reglas Financieras Obligatorias:</p>
        <p>&bull; <strong>Regla R1:</strong> Límite máximo presupuestal del proyecto: <strong>S/ {{ number_format($proyecto->monto, 2) }}</strong>.</p>
        <p>&bull; <strong>Regla R2:</strong> El gasto acumulado debe ser consistente con la cronología previa y posterior.</p>
    </div>

    @if ($errors->any())
        <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r text-rose-800 text-sm">
            <p class="font-bold">Corrige los errores:</p>
            <ul class="list-disc list-inside mt-1 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <form method="POST" action="{{ route('avances.update', $avance) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">
                    Fecha de Reporte (Inmutable)
                </label>
                <input type="date" value="{{ $avance->fecha_reporte }}" disabled
                    class="w-full text-sm border-slate-200 rounded-lg px-3 py-2 bg-slate-100 text-slate-600 font-mono font-bold cursor-not-allowed">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="presupuesto_planificado" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                        Presupuesto Planificado (S/) *
                    </label>
                    <input type="number" step="0.01" min="0" max="{{ $proyecto->monto }}"
                        name="presupuesto_planificado" id="presupuesto_planificado"
                        value="{{ old('presupuesto_planificado', $avance->presupuesto_planificado) }}" required
                        class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
                </div>

                <div>
                    <label for="gasto_acumulado" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                        Gasto Acumulado (S/) *
                    </label>
                    <input type="number" step="0.01" min="0" max="{{ $proyecto->monto }}"
                        name="gasto_acumulado" id="gasto_acumulado"
                        value="{{ old('gasto_acumulado', $avance->gasto_acumulado) }}" required
                        class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
                </div>
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
