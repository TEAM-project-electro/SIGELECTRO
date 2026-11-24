@extends('layouts.app')

@section('title', 'Objetivos PGD')

@section('content')
<div class="space-y-6" x-data="{ modalNuevo: false, objetivoEdit: null }" @keydown.escape.window="modalNuevo = false; objetivoEdit = null">

    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Catálogo de Objetivos de Gobierno Digital</h1>
            <p class="text-sm text-slate-600 mt-1 tabular-nums">
                {{ $objetivos->count() }} objetivos registrados | {{ $objetivos->sum('proyectos_count') }} proyectos vinculados
            </p>
        </div>

        @can('create', \App\Models\ObjetivoGd::class)
            <button type="button" @click="modalNuevo = true" class="sig-btn-primary self-start sm:self-auto">
                <svg class="w-4 h-4 text-brand-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Nuevo objetivo</span>
            </button>
        @endcan
    </div>

    @if ($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-900 text-xs" role="alert" aria-live="assertive">
            <p class="font-bold mb-1">Verifique los datos ingresados:</p>
            <ul class="list-disc list-inside space-y-0.5 text-rose-800">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($objetivos as $obj)
            <article class="sig-panel p-5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <span translate="no" class="font-mono text-xs font-bold px-2.5 py-0.5 bg-brand-50 text-brand-900 border border-brand-200/80 rounded">
                            {{ $obj->codigo }}
                        </span>
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-medium text-slate-500 tabular-nums">
                                {{ $obj->proyectos_count }} {{ Str::plural('proyecto', $obj->proyectos_count) }}
                            </span>
                            @can('update', $obj)
                                <button type="button"
                                        @click="objetivoEdit = {{ json_encode($obj) }}"
                                        class="text-xs font-semibold text-brand-700 hover:text-brand-900 hover:underline">
                                    Editar
                                </button>
                            @endcan
                        </div>
                    </div>

                    <p class="text-[13px] font-semibold text-slate-900 mt-3.5 leading-relaxed">
                        {{ $obj->descripcion }}
                    </p>

                    <div class="mt-4 pt-3 border-t border-slate-100">
                        <ul class="space-y-1.5 text-xs">
                            @forelse($obj->proyectos as $p)
                                <li class="flex items-center justify-between gap-2 py-0.5">
                                    <a href="{{ route('proyectos.show', $p->id_proyecto) }}"
                                       class="text-slate-700 hover:text-brand-800 hover:underline truncate min-w-0">
                                        <span translate="no" class="font-mono font-bold text-brand-800 mr-1">{{ $p->codigo_pgd }}</span>
                                        {{ Str::limit($p->nombre, 34) }}
                                    </a>
                                    <span class="font-mono text-[11px] text-slate-600 whitespace-nowrap tabular-nums shrink-0">
                                        S/ {{ number_format($p->monto, 2) }}
                                    </span>
                                </li>
                            @empty
                                <li class="text-xs text-slate-400 py-1">Sin proyectos asignados.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-500 flex justify-between items-center tabular-nums">
                    <span>Subtotal asignado</span>
                    <span class="font-bold text-slate-900">S/ {{ number_format($obj->proyectos->sum('monto'), 2) }}</span>
                </div>
            </article>
        @endforeach
    </div>

    {{-- Modal: Nuevo Objetivo --}}
    <div x-show="modalNuevo" x-cloak
         class="fixed inset-0 bg-slate-950/60 backdrop-blur-[1px] z-50 flex items-center justify-center p-4 overscroll-contain"
         role="dialog" aria-modal="true" aria-labelledby="titulo-modal-nuevo-ogd">
        <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-elevated border border-slate-200 space-y-4"
             @click.outside="modalNuevo = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 id="titulo-modal-nuevo-ogd" class="text-base font-bold text-slate-900">Nuevo Objetivo PGD</h3>
                <button type="button" @click="modalNuevo = false" aria-label="Cerrar ventana"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('objetivos.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="nuevo_ogd_codigo" class="block text-xs font-semibold text-slate-700 mb-1.5">Código <span class="text-rose-500">*</span></label>
                    <input id="nuevo_ogd_codigo" name="codigo" required maxlength="10" placeholder="Ej. OGD 07…" spellcheck="false" autocomplete="off"
                           class="sig-input font-mono">
                </div>

                <div>
                    <label for="nuevo_ogd_desc" class="block text-xs font-semibold text-slate-700 mb-1.5">Descripción <span class="text-rose-500">*</span></label>
                    <textarea id="nuevo_ogd_desc" name="descripcion" rows="3" required maxlength="200" placeholder="Descripción oficial del objetivo…"
                              class="sig-input"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="modalNuevo = false" class="sig-btn-secondary">Cancelar</button>
                    <button type="submit" class="sig-btn-primary">Guardar objetivo</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Editar Objetivo --}}
    <div x-show="objetivoEdit !== null" x-cloak
         class="fixed inset-0 bg-slate-950/60 backdrop-blur-[1px] z-50 flex items-center justify-center p-4 overscroll-contain"
         role="dialog" aria-modal="true" aria-labelledby="titulo-modal-edit-ogd">
        <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-elevated border border-slate-200 space-y-4"
             @click.outside="objetivoEdit = null">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 id="titulo-modal-edit-ogd" class="text-base font-bold text-slate-900">
                    Editar <span translate="no" class="font-mono text-brand-800" x-text="objetivoEdit ? objetivoEdit.codigo : ''"></span>
                </h3>
                <button type="button" @click="objetivoEdit = null" aria-label="Cerrar ventana"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <template x-if="objetivoEdit">
                <form method="POST" :action="'/objetivos/' + objetivoEdit.id_objetivo" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="edit_ogd_codigo" class="block text-xs font-semibold text-slate-700 mb-1.5">Código <span class="text-rose-500">*</span></label>
                        <input id="edit_ogd_codigo" name="codigo" :value="objetivoEdit.codigo" required maxlength="10" spellcheck="false" autocomplete="off"
                               class="sig-input font-mono">
                    </div>

                    <div>
                        <label for="edit_ogd_desc" class="block text-xs font-semibold text-slate-700 mb-1.5">Descripción <span class="text-rose-500">*</span></label>
                        <textarea id="edit_ogd_desc" name="descripcion" rows="3" required maxlength="200" :value="objetivoEdit.descripcion"
                                  class="sig-input"></textarea>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="objetivoEdit = null" class="sig-btn-secondary">Cancelar</button>
                        <button type="submit" class="sig-btn-primary">Guardar cambios</button>
                    </div>
                </form>
            </template>
        </div>
    </div>

</div>
@endsection
