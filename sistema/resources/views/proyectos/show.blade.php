@extends('layouts.app')

@section('title', 'PGD ' . $proyecto->codigo_pgd)

@section('content')
@php
    $avancePct = ($indicador && $indicador->avance_hitos_pct !== null)
        ? min(100, max(0, (float) $indicador->avance_hitos_pct))
        : 0;
@endphp

<div class="space-y-6" x-data="{ modalReabrir: false }" @keydown.escape.window="modalReabrir = false">

    {{-- Encabezado ejecutivo del proyecto --}}
    <div class="sig-panel overflow-hidden">
        <div class="h-1 w-full electro-rail" aria-hidden="true"></div>
        <div class="p-5 sm:p-6">
            <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span translate="no" class="font-mono text-xs font-bold px-2.5 py-0.5 rounded bg-brand-900 text-white">PGD {{ $proyecto->codigo_pgd }}</span>
                        <x-badge size="sm" :variant="['En curso' => 'success', 'Pendiente' => 'warning'][$proyecto->estado] ?? 'neutral'">{{ $proyecto->estado }}</x-badge>
                        <span class="text-xs font-medium text-slate-500 tabular-nums">{{ $proyecto->anio_inicio }}–{{ $proyecto->anio_fin }}</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 mt-2">{{ $proyecto->nombre }}</h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Fuente: {{ $proyecto->fuente ?? 'PGD Electrocentro' }} | Actualizado: <span class="font-mono">{{ $proyecto->fecha_actualizacion }}</span>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <a href="{{ route('proyectos.index') }}" class="sig-btn-secondary">Volver</a>

                    @can('update', $proyecto)
                        <a href="{{ route('proyectos.edit', $proyecto) }}" class="sig-btn-secondary">Editar proyecto</a>
                    @endcan

                    @if($proyecto->estado !== 'Cerrado')
                        @can('cerrar', $proyecto)
                            <form method="POST" action="{{ route('proyectos.cerrar', $proyecto) }}" class="inline"
                                  onsubmit="return confirm('¿Cerrar formalmente el proyecto? Todos los hitos de línea base deben estar concluidos.');">
                                @csrf
                                <button type="submit" class="sig-btn-primary">Cerrar proyecto</button>
                            </form>
                        @endcan
                    @else
                        @can('reabrir', $proyecto)
                            <button type="button" @click="modalReabrir = true"
                                    class="inline-flex items-center justify-center rounded-lg bg-amber-600 hover:bg-amber-700 px-3.5 py-2 text-xs font-semibold text-white transition-colors duration-150">
                                Reabrir proyecto
                            </button>
                        @endcan
                    @endif
                </div>
            </div>

            <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-5 pt-4 border-t border-slate-100 text-xs">
                <div>
                    <dt class="text-slate-500 font-medium">Objetivo de Gobierno Digital</dt>
                    <dd class="font-semibold text-slate-900 mt-1 leading-snug">
                        <span class="font-mono text-brand-800">{{ $proyecto->objetivo->codigo ?? '—' }}</span>
                        <span class="font-normal text-slate-700">{{ $proyecto->objetivo->descripcion ?? '' }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500 font-medium">Responsable asignado</dt>
                    <dd class="font-semibold text-slate-900 mt-1">
                        {{ $proyecto->responsable->nombre ?? 'Sin asignar' }}
                        @if($proyecto->responsable?->area)
                            <span class="font-normal text-slate-500">({{ $proyecto->responsable->area }})</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500 font-medium">Monto adjudicado</dt>
                    <dd class="text-base font-bold text-brand-900 tabular-nums mt-0.5">S/ {{ number_format($proyecto->monto, 2) }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Franja unificada de indicadores del proyecto --}}
    @if($indicador)
        <div class="sig-panel overflow-hidden">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-slate-200/80">
                <div class="p-4 sm:p-5">
                    <p class="text-xs font-semibold text-slate-600">Avance de Hitos (LB)</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">{{ $indicador->avance_hitos_pct !== null ? $indicador->avance_hitos_pct . '%' : 'Sin hitos' }}</p>
                    <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden mt-2.5" aria-hidden="true">
                        <div class="h-full rounded-full bg-brand-600" style="width: {{ $avancePct }}%"></div>
                    </div>
                    <p class="text-xs text-slate-500 mt-1.5 tabular-nums">{{ $indicador->hitos_lb_concluidos }} de {{ $indicador->hitos_lb_total }} concluidos</p>
                </div>
                <div class="p-4 sm:p-5 {{ $indicador->tiene_retraso ? 'bg-rose-50/35' : '' }}">
                    <p class="text-xs font-semibold text-slate-600">Cronograma</p>
                    <p class="text-2xl font-bold mt-1 {{ $indicador->tiene_retraso ? 'text-rose-700' : 'text-energy-700' }}">
                        {{ $indicador->tiene_retraso ? 'Con retraso' : 'Al día' }}
                    </p>
                    <p class="text-xs text-slate-500 mt-3 tabular-nums">Hitos vencidos: {{ $indicador->hitos_lb_vencidos }}</p>
                </div>
                <div class="p-4 sm:p-5">
                    <p class="text-xs font-semibold text-slate-600">Gasto acumulado</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">S/ {{ number_format($indicador->gasto_acumulado ?? 0, 2) }}</p>
                    <p class="text-xs text-slate-500 mt-3 tabular-nums">Planificado: S/ {{ number_format($indicador->presupuesto_planificado ?? 0, 2) }}</p>
                </div>
                <div class="p-4 sm:p-5">
                    <p class="text-xs font-semibold text-slate-600">Desviación Presupuestal</p>
                    <p class="text-2xl font-bold mt-1 tabular-nums {{ ($indicador->desviacion_presupuesto_pct ?? 0) > 0 ? 'text-amber-700' : 'text-slate-900' }}">
                        {{ $indicador->desviacion_presupuesto_pct !== null ? ($indicador->desviacion_presupuesto_pct > 0 ? '+' : '') . $indicador->desviacion_presupuesto_pct . '%' : '0.0%' }}
                    </p>
                    <p class="text-xs text-slate-500 mt-3 font-mono">Corte: {{ $indicador->ultimo_reporte ?? 'Sin corte' }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Hitos del proyecto --}}
    <section id="hitos" class="sig-panel overflow-hidden scroll-mt-6">
        <div class="px-5 py-3.5 border-b border-slate-200/80 flex items-center justify-between gap-2">
            <h2 class="text-sm font-bold text-slate-900">Hitos del Proyecto ({{ $proyecto->hitos->count() }})</h2>
            @if($proyecto->estado !== 'Cerrado')
                @can('create', [App\Models\Hito::class, $proyecto])
                    <a href="{{ route('proyectos.hitos.create', $proyecto) }}" class="sig-btn-primary py-1.5">Registrar hito</a>
                @endcan
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead class="bg-slate-50/90 text-slate-600 text-left text-xs border-b border-slate-200/80">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Descripción</th>
                        <th class="px-4 py-3 font-semibold text-center">Línea base</th>
                        <th class="px-4 py-3 font-semibold text-center">Fecha prevista</th>
                        <th class="px-4 py-3 font-semibold text-center">Fecha cierre</th>
                        <th class="px-4 py-3 font-semibold">Evidencia</th>
                        <th class="px-4 py-3 font-semibold">Estado</th>
                        <th class="px-5 py-3"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($proyecto->hitos as $hito)
                        <tr class="hover:bg-brand-50/35 transition-colors duration-150">
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $hito->descripcion }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-medium {{ $hito->en_linea_base ? 'bg-brand-50 text-brand-800 border border-brand-200/70' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $hito->en_linea_base ? 'Sí' : 'No' }}
                                </span>
                            </td>
                            @php
                                $lb = $resumenLb->get($hito->id_hito);
                                $fechaPrevista = $hito->fecha_prevista?->format('Y-m-d');
                            @endphp
                            <td class="px-4 py-3 text-center font-mono text-xs text-slate-700">
                                {{ $fechaPrevista }}
                                @if($lb && $lb['reprogramaciones'] > 0)
                                    <span class="block mt-1 font-sans">
                                        <x-badge size="sm" variant="warning">Reprogramado {{ $lb['reprogramaciones'] }} {{ $lb['reprogramaciones'] === 1 ? 'vez' : 'veces' }}</x-badge>
                                    </span>
                                    @if($lb['fecha_original'] && $lb['fecha_original'] !== $fechaPrevista)
                                        <span class="block mt-0.5 font-sans text-[11px] text-slate-500">Original: <span class="font-mono">{{ $lb['fecha_original'] }}</span></span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-xs text-slate-700">{{ $hito->fecha_cierre ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600 max-w-xs break-words">{{ $hito->evidencia ?: '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($hito->fecha_cierre)
                                    <x-badge size="sm" variant="success">Concluido</x-badge>
                                @elseif($hito->fecha_prevista < now()->toDateString())
                                    <x-badge size="sm" variant="danger">Vencido</x-badge>
                                @else
                                    <x-badge size="sm" variant="neutral">Pendiente</x-badge>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap space-x-3 text-xs">
                                @if($proyecto->estado !== 'Cerrado')
                                    @can('update', $hito)
                                        <a href="{{ route('hitos.edit', $hito) }}" class="font-semibold text-brand-700 hover:underline">Editar</a>
                                    @endcan
                                    @can('delete', $hito)
                                        <form method="POST" action="{{ route('hitos.destroy', $hito) }}" class="inline" onsubmit="return confirm('¿Eliminar este hito?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-rose-700 hover:underline">Eliminar</button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">No hay hitos registrados para este proyecto.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($versionesLb->isNotEmpty())
            @php
                $cambiosLb = $versionesLb->where('accion', '!=', 'ALTA');
                $etiquetasLb = [
                    'ALTA' => ['Alta', 'neutral'],
                    'INCORPORACION' => ['Incorporación', 'info'],
                    'REPROGRAMACION' => ['Reprogramación', 'warning'],
                    'RETIRO' => ['Retiro', 'danger'],
                    'ELIMINACION' => ['Eliminación', 'danger'],
                ];
            @endphp
            <details class="group border-t border-slate-200/80" @if($cambiosLb->isNotEmpty()) open @endif>
                <summary class="cursor-pointer list-none px-5 py-3 flex items-center justify-between gap-3 text-xs hover:bg-slate-50/80">
                    <span class="font-semibold text-slate-800">
                        Historial de la línea base
                        <span class="font-normal text-slate-500">· {{ $cambiosLb->count() === 0 ? 'sin cambios desde su alta' : $cambiosLb->count().' '.($cambiosLb->count() === 1 ? 'cambio' : 'cambios').' desde su alta' }}</span>
                    </span>
                    <svg class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="overflow-x-auto border-t border-slate-100">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50/90 text-slate-600 text-left">
                            <tr>
                                <th class="px-5 py-2.5 font-semibold">Fecha y hora</th>
                                <th class="px-4 py-2.5 font-semibold">Hito</th>
                                <th class="px-4 py-2.5 font-semibold">Acción</th>
                                <th class="px-4 py-2.5 font-semibold text-center">Fecha prevista</th>
                                <th class="px-4 py-2.5 font-semibold">Motivo</th>
                                <th class="px-5 py-2.5 font-semibold">Registrado por</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($versionesLb->sortByDesc('id_version') as $v)
                                @php [$textoLb, $varianteLb] = $etiquetasLb[$v->accion] ?? [$v->accion, 'neutral']; @endphp
                                <tr>
                                    <td class="px-5 py-2.5 font-mono text-slate-600 whitespace-nowrap">{{ $v->fecha_hora }}</td>
                                    <td class="px-4 py-2.5 text-slate-800">{{ $v->hito ?? 'Hito #'.$v->id_hito.' (eliminado)' }}</td>
                                    <td class="px-4 py-2.5 whitespace-nowrap"><x-badge size="sm" :variant="$varianteLb">{{ $textoLb }}</x-badge></td>
                                    <td class="px-4 py-2.5 text-center font-mono text-slate-700 whitespace-nowrap">
                                        @if($v->fecha_prevista_anterior && $v->fecha_prevista && $v->fecha_prevista_anterior !== $v->fecha_prevista)
                                            {{ $v->fecha_prevista_anterior }} → {{ $v->fecha_prevista }}
                                        @else
                                            {{ $v->fecha_prevista ?? $v->fecha_prevista_anterior }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-slate-600 max-w-xs break-words">{{ $v->motivo ?: '—' }}</td>
                                    <td class="px-5 py-2.5 text-slate-600 whitespace-nowrap">{{ $v->responsable }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @endif
    </section>

    {{-- Cortes presupuestales --}}
    <section id="cortes" class="sig-panel overflow-hidden scroll-mt-6">
        <div class="px-5 py-3.5 border-b border-slate-200/80 flex items-center justify-between gap-2">
            <h2 class="text-sm font-bold text-slate-900">Historial de Cortes Presupuestales ({{ $proyecto->avances->count() }})</h2>
            @if($proyecto->estado !== 'Cerrado')
                @can('create', [App\Models\AvancePresupuestal::class, $proyecto])
                    <a href="{{ route('proyectos.avances.create', $proyecto) }}" class="sig-btn-primary py-1.5">Registrar corte</a>
                @endcan
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead class="bg-slate-50/90 text-slate-600 text-left text-xs border-b border-slate-200/80">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Fecha</th>
                        <th class="px-4 py-3 font-semibold text-right">Planificado (S/)</th>
                        <th class="px-4 py-3 font-semibold text-right">Gasto acumulado (S/)</th>
                        <th class="px-4 py-3 font-semibold text-right">Diferencia (S/)</th>
                        <th class="px-4 py-3 font-semibold text-right">Desviación</th>
                        <th class="px-5 py-3"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($proyecto->avances as $av)
                        @php
                            $dif = $av->gasto_acumulado - $av->presupuesto_planificado;
                            $pct = $av->presupuesto_planificado > 0 ? ($dif * 100 / $av->presupuesto_planificado) : 0;
                        @endphp
                        <tr class="hover:bg-brand-50/35 transition-colors duration-150">
                            <td class="px-5 py-3 font-mono text-xs font-semibold text-slate-900">{{ $av->fecha_reporte }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-600">{{ number_format($av->presupuesto_planificado, 2) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums font-semibold text-slate-900">{{ number_format($av->gasto_acumulado, 2) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums {{ $dif > 0 ? 'text-amber-700' : 'text-slate-700' }}">{{ ($dif > 0 ? '+' : '') . number_format($dif, 2) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums font-semibold {{ $pct > 0 ? 'text-amber-700' : 'text-slate-700' }}">{{ ($pct > 0 ? '+' : '') . number_format($pct, 1) }}%</td>
                            <td class="px-5 py-3 text-right whitespace-nowrap space-x-3 text-xs">
                                @if($proyecto->estado !== 'Cerrado')
                                    @can('update', $av)
                                        <a href="{{ route('avances.edit', $av) }}" class="font-semibold text-brand-700 hover:underline">Editar</a>
                                    @endcan
                                    @can('delete', $av)
                                        <form method="POST" action="{{ route('avances.destroy', $av) }}" class="inline" onsubmit="return confirm('¿Eliminar este corte presupuestal?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-rose-700 hover:underline">Eliminar</button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">No se registran cortes presupuestales para este proyecto.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Compras y recepciones (Odoo ERP/SCM) --}}
    <section class="sig-panel overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Compras y recepciones</h2>
                <p class="text-xs text-slate-500 mt-0.5">Abastecimiento vinculado desde Odoo (no altera los indicadores presupuestales del SIG).</p>
            </div>
            @can('import', \App\Models\AbastecimientoCompra::class)
                <a href="{{ route('abastecimiento.importar', ['proyecto' => $proyecto->id_proyecto]) }}" class="sig-btn-primary py-1.5">
                    + Importar CSV Odoo
                </a>
            @endcan
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead class="bg-slate-50/90 text-slate-600 text-left text-xs border-b border-slate-200/80">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Orden</th>
                        <th class="px-4 py-3 font-semibold">Producto</th>
                        <th class="px-4 py-3 font-semibold">Proveedor</th>
                        <th class="px-4 py-3 font-semibold text-right">Comprada</th>
                        <th class="px-4 py-3 font-semibold text-right">Recibida</th>
                        <th class="px-4 py-3 font-semibold text-right">Pendiente</th>
                        <th class="px-4 py-3 font-semibold text-right">Total (S/)</th>
                        <th class="px-4 py-3 font-semibold">ID externo</th>
                        <th class="px-5 py-3 font-semibold">Control de carga</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($proyecto->compras as $compra)
                        <tr class="hover:bg-brand-50/35 transition-colors duration-150">
                            <td class="px-5 py-3 font-mono text-xs font-bold text-brand-900 whitespace-nowrap">{{ $compra->orden_compra }}</td>
                            <td class="px-4 py-3 text-slate-900">
                                <span class="font-semibold block">{{ $compra->producto }}</span>
                                @if($compra->descripcion)
                                    <span class="text-xs text-slate-500">{{ $compra->descripcion }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-700 whitespace-nowrap">{{ $compra->proveedor }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-800">{{ number_format((float)$compra->cantidad_comprada, 2) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums font-semibold text-energy-700">{{ number_format((float)$compra->cantidad_recibida, 2) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold {{ (float)$compra->saldo_pendiente > 0 ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-energy-50 text-energy-700 border border-energy-100' }}">
                                    {{ number_format((float)$compra->saldo_pendiente, 2) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums font-medium text-slate-900">
                                {{ $compra->importe_total !== null ? number_format((float)$compra->importe_total, 2) : '—' }}
                            </td>
                            <td translate="no" class="px-4 py-3 font-mono text-[11px] text-slate-500">{{ $compra->id_externo_linea }}</td>
                            <td class="px-5 py-3 text-xs text-slate-600 whitespace-nowrap">
                                <span class="font-medium text-slate-800 block">{{ $compra->responsableCarga?->usuario ?? 'ID ' . $compra->id_responsable_carga }}</span>
                                <span class="font-mono text-[11px] text-slate-500">{{ $compra->fecha_carga?->format('Y-m-d H:i') }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-5 py-10 text-center text-sm text-slate-500">No se registran compras o recepciones importadas para este proyecto.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Gestión gerencial: presupuesto, valor ganado, riesgos, documentos, beneficios e historial --}}
    @if (($gestion['disponible'] ?? false))
        @include('proyectos.partials.gestion', ['gestion' => $gestion, 'proyecto' => $proyecto])
    @endif

    {{-- Modal de reapertura --}}
    <div x-show="modalReabrir" x-cloak
         class="fixed inset-0 bg-slate-950/60 backdrop-blur-[1px] z-50 flex items-center justify-center p-4 overscroll-contain"
         role="dialog" aria-modal="true" aria-labelledby="titulo-modal-reabrir">
        <div @click.outside="modalReabrir = false" class="bg-white rounded-xl max-w-md w-full p-6 border border-slate-200 shadow-elevated space-y-4">
            <h3 id="titulo-modal-reabrir" class="text-base font-bold text-slate-900">Reabrir proyecto PGD {{ $proyecto->codigo_pgd }}</h3>
            <p class="text-xs text-slate-600 leading-relaxed">El proyecto pasará al estado En curso. Indique el motivo del cambio (entre 5 y 200 caracteres).</p>
            <form method="POST" action="{{ route('proyectos.reabrir', $proyecto) }}" class="space-y-4">
                @csrf
                <div>
                    <label for="motivo" class="block text-xs font-semibold text-slate-700 mb-1.5">Motivo de reapertura</label>
                    <textarea name="motivo" id="motivo" rows="3" required minlength="5" maxlength="200"
                              placeholder="Describa la razón administrativa…"
                              class="sig-input"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="modalReabrir = false" class="sig-btn-secondary">Cancelar</button>
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-amber-600 hover:bg-amber-700 px-4 py-2 text-xs font-semibold text-white transition-colors duration-150">Confirmar reapertura</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
