@props([
    'title' => 'No se encontraron registros',
    'description' => 'Pruebe modificando los filtros o criterios de búsqueda seleccionados.',
    'action' => null,
])

<div {{ $attributes->merge(['class' => 'text-center py-12 px-4 bg-white rounded-xl border border-dashed border-slate-300']) }}>
    <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
    </div>
    <h4 class="text-sm font-bold text-slate-800">{{ $title }}</h4>
    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto leading-relaxed">{{ $description }}</p>
    @if($action)
        <div class="mt-4">
            {{ $action }}
        </div>
    @endif
</div>
