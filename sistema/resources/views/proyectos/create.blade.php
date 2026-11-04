@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Registrar Nuevo Proyecto PGD</h1>
            <p class="text-sm text-slate-500 mt-1">
                Incorporación de proyecto a la cartera de Gobierno Digital de Electrocentro S. A.
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('proyectos.index') }}" class="text-sm font-medium text-blue-900 hover:underline">
                &larr; Volver a la cartera
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r text-rose-800 text-sm">
            <p class="font-bold">Corrige los siguientes errores en el formulario:</p>
            <ul class="list-disc list-inside mt-1 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <form method="POST" action="{{ route('proyectos.store') }}" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="codigo_pgd" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                        Código PGD *
                    </label>
                    <input type="text" name="codigo_pgd" id="codigo_pgd" value="{{ old('codigo_pgd') }}" required
                        placeholder="p. ej. 025"
                        class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
                </div>

                <div class="sm:col-span-2">
                    <label for="monto" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                        Presupuesto Total Asignado (S/) *
                    </label>
                    <input type="number" step="0.01" min="0" name="monto" id="monto" value="{{ old('monto') }}" required
                        placeholder="0.00"
                        class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
                </div>
            </div>

            <div>
                <label for="nombre" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                    Nombre Descriptivo del Proyecto *
                </label>
                <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}" required
                    placeholder="Descripción oficial del proyecto..."
                    class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="anio_inicio" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                        Año Inicio *
                    </label>
                    <input type="number" min="2020" max="2035" name="anio_inicio" id="anio_inicio"
                        value="{{ old('anio_inicio', 2023) }}" required
                        class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
                </div>

                <div>
                    <label for="anio_fin" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                        Año Fin *
                    </label>
                    <input type="number" min="2020" max="2035" name="anio_fin" id="anio_fin"
                        value="{{ old('anio_fin', 2025) }}" required
                        class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
                </div>

                <div>
                    <label for="estado" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                        Estado Inicial *
                    </label>
                    <select name="estado" id="estado" required
                        class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
                        <option value="Pendiente" {{ old('estado') === 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                        <option value="En curso" {{ old('estado', 'En curso') === 'En curso' ? 'selected' : '' }}>En curso</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="id_objetivo" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                        Objetivo de Gobierno Digital *
                    </label>
                    <select name="id_objetivo" id="id_objetivo" required
                        class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
                        <option value="">Seleccione objetivo...</option>
                        @foreach($objetivos as $obj)
                            <option value="{{ $obj->id_objetivo }}" {{ old('id_objetivo') == $obj->id_objetivo ? 'selected' : '' }}>
                                {{ $obj->codigo }} - {{ Str::limit($obj->descripcion, 40) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="id_responsable" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                        Responsable Titular Asignado *
                    </label>
                    <select name="id_responsable" id="id_responsable" required
                        class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
                        <option value="">Seleccione responsable...</option>
                        @foreach($responsables as $resp)
                            <option value="{{ $resp->id_responsable }}" {{ old('id_responsable') == $resp->id_responsable ? 'selected' : '' }}>
                                {{ $resp->nombre }} ({{ $resp->area }} - {{ $resp->perfil }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="fuente" class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                    Fuente de Financiamiento o Portafolio
                </label>
                <input type="text" name="fuente" id="fuente" value="{{ old('fuente', 'Cartera PGD 2023 a 2026') }}"
                    class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
            </div>

            <div class="pt-4 border-t border-slate-200 flex justify-end space-x-3">
                <a href="{{ route('proyectos.index') }}"
                    class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                    Cancelar
                </a>
                <button type="submit"
                    class="px-5 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-lg text-sm font-medium transition shadow">
                    Guardar Proyecto
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
