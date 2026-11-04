@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center space-x-2">
                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-blue-100 text-blue-800">
                    PGD {{ $proyecto->codigo_pgd }}
                </span>
                <h1 class="text-2xl font-bold text-slate-900">Registrar Corte Presupuestal</h1>
            </div>
            <p class="text-sm text-slate-500 mt-1">
                Proyecto: <span class="font-semibold text-slate-700">{{ $proyecto->nombre }}</span>
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('proyectos.show', $proyecto) }}" class="text-sm font-medium text-blue-900 hover:underline">
                &larr; Volver al proyecto
            </a>
        </div>
    </div>

    <!-- Resumen de Reglas de Negocio -->
    <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl text-xs text-blue-900 space-y-1">
        <p class="font-bold flex items-center">
            <svg class="w-4 h-4 mr-1 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Reglas Financieras Obligatorias:
        </p>
        <p>&bull; <strong>Regla R1:</strong> Ni el gasto acumulado ni el planificado pueden superar el monto total del proyecto: <strong>S/ {{ number_format($proyecto->monto, 2) }}</strong>.</p>
        <p>&bull; <strong>Regla R2:</strong> El gasto acumulado es monótono no decreciente (debe ser mayor o igual al gasto del corte cronológico previo).</p>
        @if($ultimoCorte)
            <p class="mt-2 text-[11px] font-semibold text-blue-800 pt-1 border-t border-blue-200">
                Último corte registrado: {{ $ultimoCorte->fecha_reporte }} con Gasto Acumulado de S/ {{ number_format($ultimoCorte->gasto_acumulado, 2) }}.
            </p>
        @endif
    </div>

    @if ($errors->any())
        <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r text-rose-800 text-sm">
            <p class="font-bold">Corrige los siguientes errores:</p>
            <ul class="list-disc list-inside mt-1 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <form method="POST" action="{{ route('proyectos.avances.store', $proyecto) }}" class="space-y-5">
            @csrf

            <div>
                <label for="fecha_reporte" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                    Fecha de Corte / Reporte *
                </label>
                <input type="date" name="fecha_reporte" id="fecha_reporte" value="{{ old('fecha_reporte') }}" required
                    class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="presupuesto_planificado" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                        Presupuesto Planificado Acumulado (S/) *
                    </label>
                    <input type="number" step="0.01" min="0" max="{{ $proyecto->monto }}"
                        name="presupuesto_planificado" id="presupuesto_planificado"
                        value="{{ old('presupuesto_planificado') }}" required placeholder="0.00"
                        class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
                </div>

                <div>
                    <label for="gasto_acumulado" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                        Gasto Real Acumulado (S/) *
                    </label>
                    <input type="number" step="0.01" min="0" max="{{ $proyecto->monto }}"
                        name="gasto_acumulado" id="gasto_acumulado"
                        value="{{ old('gasto_acumulado') }}" required placeholder="0.00"
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
                    Guardar Corte Presupuestal
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
