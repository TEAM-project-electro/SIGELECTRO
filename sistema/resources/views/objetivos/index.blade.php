@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Catálogo de Objetivos de Gobierno Digital</h1>
            <p class="text-sm text-slate-500 mt-1">
                Ejes estratégicos del PGD 2023–2026 de Electrocentro S. A. y distribución de proyectos.
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('proyectos.index') }}" class="text-sm font-medium text-blue-900 hover:underline">
                &larr; Volver a la Cartera
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($objetivos as $obj)
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <span class="font-mono text-sm font-bold px-3 py-1 bg-blue-50 text-blue-900 rounded-lg">
                        {{ $obj->codigo }}
                    </span>
                    <span class="text-xs font-semibold px-2.5 py-1 bg-slate-100 text-slate-700 rounded-md">
                        {{ $obj->proyectos_count }} {{ Str::plural('proyecto', $obj->proyectos_count) }}
                    </span>
                </div>

                <p class="text-sm text-slate-800 font-medium">
                    {{ $obj->descripcion }}
                </p>

                <div class="pt-3 border-t border-slate-100">
                    <span class="text-xs font-semibold uppercase text-slate-400 block mb-2">Proyectos Asociados:</span>
                    <ul class="space-y-1.5 text-xs">
                        @forelse($obj->proyectos as $p)
                            <li class="flex items-center justify-between text-slate-700 hover:bg-slate-50 p-1.5 rounded transition">
                                <span class="font-medium">
                                    <span class="font-mono font-bold text-blue-900 mr-1">{{ $p->codigo_pgd }}</span>
                                    {{ Str::limit($p->nombre, 38) }}
                                </span>
                                <span class="font-mono text-slate-500">
                                    S/ {{ number_format($p->monto, 2) }}
                                </span>
                            </li>
                        @empty
                            <li class="text-slate-400 italic">Sin proyectos asignados en este objetivo.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
