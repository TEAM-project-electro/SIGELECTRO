@extends('layouts.app')

@section('title', 'Objetivos PGD')

@section('content')
<div class="space-y-5" x-data="{ modalNuevo: false, objetivoEdit: null }">

    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Catálogo de Objetivos de Gobierno Digital</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                {{ $objetivos->count() }} objetivos registrados &middot; {{ $objetivos->sum('proyectos_count') }} proyectos vinculados
            </p>
        </div>

        @can('create', \App\Models\ObjetivoGd::class)
            <button type="button" @click="modalNuevo = true"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold text-white bg-brand-800 hover:bg-brand-900 transition self-start sm:self-auto">
                + Nuevo objetivo
            </button>
        @endcan
    </div>

    @if ($errors->any())
        <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-lg text-rose-900 text-xs" role="alert">
            <p class="font-bold mb-1">Verifique los datos ingresados:</p>
            <ul class="list-disc list-inside space-y-0.5 text-rose-800">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Lista de Objetivos -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($objetivos as $obj)
            <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                        <span class="font-mono text-xs font-bold px-2.5 py-0.5 bg-brand-50 text-brand-900 border border-brand-200 rounded">
                            {{ $obj->codigo }}
                        </span>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-500">
                                {{ $obj->proyectos_count }} {{ Str::plural('proyecto', $obj->proyectos_count) }}
                            </span>
                            @can('update', $obj)
                                <button type="button"
                                    @click="objetivoEdit = {{ json_encode($obj) }}"
                                    class="text-xs font-medium text-brand-700 hover:text-brand-900">
                                    Editar
                                </button>
                            @endcan
                        </div>
                    </div>

                    <p class="text-xs font-semibold text-slate-800 mt-3 leading-relaxed">
                        {{ $obj->descripcion }}
                    </p>

                    <div class="mt-3 pt-2.5 border-t border-slate-100">
                        <ul class="space-y-1 text-xs">
                            @forelse($obj->proyectos as $p)
                                <li class="flex items-center justify-between py-1">
                                    <a href="{{ route('proyectos.show', $p->id_proyecto) }}"
                                       class="text-slate-700 hover:text-brand-800 truncate pr-2">
                                        <span class="font-mono font-bold text-brand-900 mr-1">{{ $p->codigo_pgd }}</span>
                                        {{ Str::limit($p->nombre, 34) }}
                                    </a>
                                    <span class="font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                        S/ {{ number_format($p->monto, 2) }}
                                    </span>
                                </li>
                            @empty
                                <li class="text-xs text-slate-400 py-1">Sin proyectos asignados.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="mt-3 pt-2.5 border-t border-slate-100 text-xs text-slate-500 flex justify-between items-center">
                    <span>Subtotal:</span>
                    <span class="font-bold font-mono text-slate-800">
                        S/ {{ number_format($obj->proyectos->sum('monto'), 2) }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Modal: Nuevo Objetivo -->
    <div x-show="modalNuevo" x-cloak
         class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-md w-full p-5 shadow-lg border border-slate-200 space-y-4"
             @click.away="modalNuevo = false">
            <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900">Nuevo Objetivo PGD</h3>
                <button type="button" @click="modalNuevo = false" class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
            </div>

            <form method="POST" action="{{ route('objetivos.store') }}" class="space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Código <span class="text-rose-500">*</span></label>
                    <input name="codigo" required maxlength="10" placeholder="OGD 07"
                        class="w-full text-xs font-mono border border-slate-300 rounded-lg px-3 py-2 focus:border-brand-600 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Descripción <span class="text-rose-500">*</span></label>
                    <textarea name="descripcion" rows="3" required maxlength="200"
                        class="w-full text-xs border border-slate-300 rounded-lg p-2.5 focus:border-brand-600 focus:outline-none"></textarea>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="modalNuevo = false"
                        class="px-3.5 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-4 py-1.5 bg-brand-800 hover:bg-brand-900 text-white rounded-lg text-xs font-semibold">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Editar Objetivo -->
    <div x-show="objetivoEdit !== null" x-cloak
         class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-md w-full p-5 shadow-lg border border-slate-200 space-y-4"
             @click.away="objetivoEdit = null">
            <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900">
                    Editar <span class="font-mono text-brand-800" x-text="objetivoEdit ? objetivoEdit.codigo : ''"></span>
                </h3>
                <button type="button" @click="objetivoEdit = null" class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
            </div>

            <template x-if="objetivoEdit">
                <form method="POST" :action="'/objetivos/' + objetivoEdit.id_objetivo" class="space-y-3.5">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Código <span class="text-rose-500">*</span></label>
                        <input name="codigo" :value="objetivoEdit.codigo" required maxlength="10"
                            class="w-full text-xs font-mono border border-slate-300 rounded-lg px-3 py-2 focus:border-brand-600 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Descripción <span class="text-rose-500">*</span></label>
                        <textarea name="descripcion" rows="3" required maxlength="200" :value="objetivoEdit.descripcion"
                            class="w-full text-xs border border-slate-300 rounded-lg p-2.5 focus:border-brand-600 focus:outline-none"></textarea>
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="objetivoEdit = null"
                            class="px-3.5 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-4 py-1.5 bg-brand-800 hover:bg-brand-900 text-white rounded-lg text-xs font-semibold">
                            Actualizar
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

</div>
@endsection
