@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center space-x-2">
                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-blue-100 text-blue-800">
                    PGD {{ $proyecto->codigo_pgd }}
                </span>
                <h1 class="text-2xl font-bold text-slate-900">Registrar Hito</h1>
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

    @if ($errors->any())
        <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r text-rose-800 text-sm">
            <p class="font-bold">Corrige los errores antes de continuar:</p>
            <ul class="list-disc list-inside mt-1 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <form method="POST" action="{{ route('proyectos.hitos.store', $proyecto) }}" class="space-y-5">
            @csrf

            <div>
                <label for="descripcion" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                    Descripción del Hito / Entregable *
                </label>
                <input type="text" name="descripcion" id="descripcion" value="{{ old('descripcion') }}" required
                    placeholder="p. ej. Aprobación del plan de contingencia por gerencia"
                    class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="fecha_prevista" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                        Fecha Prevista de Término *
                    </label>
                    <input type="date" name="fecha_prevista" id="fecha_prevista" value="{{ old('fecha_prevista') }}" required
                        class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
                </div>

                <div>
                    <label for="fecha_cierre" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                        Fecha Real de Cierre (Opcional si ya concluyó)
                    </label>
                    <input type="date" name="fecha_cierre" id="fecha_cierre" value="{{ old('fecha_cierre') }}"
                        class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
                </div>
            </div>

            <div>
                <label for="evidencia" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                    Evidencia / Referencia Documental
                </label>
                <input type="text" name="evidencia" id="evidencia" value="{{ old('evidencia') }}"
                    placeholder="p. ej. Acta de conformidad SIG-2024-001 o Resolución N° 045-2024"
                    class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
            </div>

            <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
                <label class="flex items-start space-x-3 cursor-pointer">
                    <input type="checkbox" name="en_linea_base" value="1" {{ old('en_linea_base', '1') == '1' ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600 border-slate-300 rounded mt-0.5 focus:ring-blue-500">
                    <div>
                        <span class="text-sm font-bold text-slate-800">Pertenece a la Línea Base del Proyecto</span>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Los hitos de línea base son obligatorios para el cálculo de porcentaje de avance y condición de retraso. Los hitos extraordinarios o no previstos (fuera de línea base) no restringen el cierre ni penalizan el retraso.
                        </p>
                    </div>
                </label>
            </div>

            <div class="pt-4 border-t border-slate-200 flex justify-end space-x-3">
                <a href="{{ route('proyectos.show', $proyecto) }}"
                    class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                    Cancelar
                </a>
                <button type="submit"
                    class="px-5 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-lg text-sm font-medium transition shadow">
                    Guardar Hito
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
