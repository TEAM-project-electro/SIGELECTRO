@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{ modalNuevo: false, objetivoEdit: null }">

    <!-- Encabezado de Página -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-brand-50 text-brand-800 border border-brand-200">
                    ALINEACIÓN ESTRATÉGICA
                </span>
                <span class="text-xs text-slate-400">&bull; PGD 2023–2026</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                Catálogo de Objetivos de Gobierno Digital (OGD)
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Ejes estratégicos del Plan de Gobierno Digital de Electrocentro S. A. y distribución de cartera.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('proyectos.index') }}"
                class="px-3.5 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 shadow-xs transition">
                &larr; Volver a Cartera
            </a>

            @can('create', \App\Models\ObjetivoGd::class)
                <button type="button" @click="modalNuevo = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold text-white bg-brand-700 hover:bg-brand-800 shadow-xs transition">
                    <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Nuevo Objetivo</span>
                </button>
            @endcan
        </div>
    </div>

    <!-- Errores de Validación -->
    @if ($errors->any())
        <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl text-rose-900 text-xs shadow-xs" role="alert">
            <p class="font-bold flex items-center mb-1">
                <svg class="w-4 h-4 mr-1.5 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                Ocurrió un error en la operación:
            </p>
            <ul class="list-disc list-inside space-y-0.5 text-rose-800 pl-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Tarjetas de Resumen Estratégico -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4.5 rounded-xl border border-slate-200/90 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block">Objetivos Canónicos</span>
            <div class="text-2xl font-extrabold text-slate-900 mt-1 tabular-nums">{{ $objetivos->count() }} OGD</div>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Ejes estratégicos del PGD</span>
        </div>
        <div class="bg-white p-4.5 rounded-xl border border-slate-200/90 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block">Proyectos Asignados</span>
            <div class="text-2xl font-extrabold text-brand-900 mt-1 tabular-nums">{{ $objetivos->sum('proyectos_count') }} Proyectos</div>
            <span class="text-[11px] text-slate-400 mt-0.5 block">100% de cartera vinculada</span>
        </div>
        <div class="bg-white p-4.5 rounded-xl border border-slate-200/90 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block">Presupuesto Portafolio</span>
            <div class="text-2xl font-extrabold text-slate-900 mt-1 tabular-nums font-mono">
                S/ {{ number_format($objetivos->flatMap->proyectos->sum('monto'), 2) }}
            </div>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Monto total adjudicado</span>
        </div>
    </div>

    <!-- Cuadrícula de Objetivos -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($objetivos as $obj)
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-5 flex flex-col justify-between hover:shadow-sm transition">
                <div>
                    <!-- Header de Tarjeta -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <span class="font-mono text-xs font-extrabold px-2.5 py-1 bg-brand-50 text-brand-900 border border-brand-200 rounded-lg">
                            {{ $obj->codigo }}
                        </span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-semibold px-2.5 py-0.5 bg-slate-100 text-slate-700 rounded-full border border-slate-200">
                                {{ $obj->proyectos_count }} {{ Str::plural('proyecto', $obj->proyectos_count) }}
                            </span>
                            @can('update', $obj)
                                <button type="button"
                                    @click="objetivoEdit = {{ json_encode($obj) }}"
                                    class="p-1 text-slate-400 hover:text-brand-700 rounded transition" title="Editar objetivo">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                            @endcan
                        </div>
                    </div>

                    <!-- Descripción -->
                    <p class="text-xs font-semibold text-slate-800 mt-3 leading-relaxed">
                        {{ $obj->descripcion }}
                    </p>

                    <!-- Lista de Proyectos Vinculados -->
                    <div class="mt-4 pt-3 border-t border-slate-100">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-2">
                            Proyectos Asignados:
                        </span>
                        <ul class="space-y-1.5 text-xs">
                            @forelse($obj->proyectos as $p)
                                <li class="flex items-center justify-between p-2 rounded-lg bg-slate-50 hover:bg-brand-50/40 border border-slate-100 transition">
                                    <div class="min-w-0 pr-2">
                                        <a href="{{ route('proyectos.show', $p->id_proyecto) }}"
                                           class="font-semibold text-slate-800 hover:text-brand-700 truncate block">
                                            <span class="font-mono text-brand-900 font-bold mr-1">{{ $p->codigo_pgd }}</span>
                                            {{ Str::limit($p->nombre, 32) }}
                                        </a>
                                    </div>
                                    <span class="font-mono text-[11px] text-slate-600 whitespace-nowrap shrink-0">
                                        S/ {{ number_format($p->monto, 2) }}
                                    </span>
                                </li>
                            @empty
                                <li class="text-xs text-slate-400 italic py-1">
                                    Sin proyectos vinculados a este eje.
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] text-slate-500 flex justify-between items-center">
                    <span>Monto en este eje:</span>
                    <span class="font-bold font-mono text-slate-800">
                        S/ {{ number_format($obj->proyectos->sum('monto'), 2) }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    <!-- ============================================================ -->
    <!-- MODAL: NUEVO OBJETIVO                                        -->
    <!-- ============================================================ -->
    <div x-show="modalNuevo" x-cloak
         class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200/90 space-y-4"
             @click.away="modalNuevo = false">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-extrabold text-slate-900">Registrar Objetivo de Gobierno Digital</h3>
                <button type="button" @click="modalNuevo = false" class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
            </div>

            <form method="POST" action="{{ route('objetivos.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Código (p. ej. OGD 07) <span class="text-rose-500">*</span>
                    </label>
                    <input name="codigo" required maxlength="10" placeholder="OGD 07"
                        class="w-full text-xs sm:text-sm font-mono font-bold border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Descripción Estratégica <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="descripcion" rows="3" required maxlength="200" placeholder="Descripción oficial del objetivo estratégico..."
                        class="w-full text-xs sm:text-sm border-slate-300 rounded-lg p-3 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button" @click="modalNuevo = false"
                        class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-5 py-2 bg-brand-700 hover:bg-brand-800 text-white rounded-lg text-xs font-semibold transition shadow-xs">
                        Crear Objetivo
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL: EDITAR OBJETIVO                                       -->
    <!-- ============================================================ -->
    <div x-show="objetivoEdit !== null" x-cloak
         class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200/90 space-y-4"
             @click.away="objetivoEdit = null">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-extrabold text-slate-900">
                    Editar: <span class="font-mono text-brand-800" x-text="objetivoEdit ? objetivoEdit.codigo : ''"></span>
                </h3>
                <button type="button" @click="objetivoEdit = null" class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
            </div>

            <template x-if="objetivoEdit">
                <form method="POST" :action="'/objetivos/' + objetivoEdit.id_objetivo" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Código <span class="text-rose-500">*</span>
                        </label>
                        <input name="codigo" :value="objetivoEdit.codigo" required maxlength="10"
                            class="w-full text-xs sm:text-sm font-mono font-bold border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Descripción Estratégica <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="descripcion" rows="3" required maxlength="200" :value="objetivoEdit.descripcion"
                            class="w-full text-xs sm:text-sm border-slate-300 rounded-lg p-3 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500"></textarea>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button type="button" @click="objetivoEdit = null"
                            class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-brand-700 hover:bg-brand-800 text-white rounded-lg text-xs font-semibold transition shadow-xs">
                            Actualizar Objetivo
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

</div>
@endsection
