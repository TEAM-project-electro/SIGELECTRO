<?php

namespace App\Services\Rag;

use Illuminate\Support\HtmlString;

/**
 * Formatea respuestas y fragmentos de forma segura para Blade.
 * Escapa todo HTML primero con e() y solo transforma negritas, código en línea,
 * listas simples, tablas Markdown de fragmentos y citas [F#].
 */
class RagFormato
{
    public static function htmlSeguro(string $texto): HtmlString
    {
        $lineas = preg_split("/\r\n|\n|\r/", trim($texto)) ?: [];
        $html = [];
        $enLista = false;
        $bufferTabla = [];

        $cerrarLista = function () use (&$html, &$enLista): void {
            if ($enLista) {
                $html[] = '</ul>';
                $enLista = false;
            }
        };

        $volcarTabla = function () use (&$html, &$bufferTabla): void {
            if ($bufferTabla === []) {
                return;
            }
            $filas = array_values(array_filter($bufferTabla, fn ($l) => !preg_match('/^\|\s*[:\-| ]+\|$/', trim($l))));
            if ($filas === []) {
                $bufferTabla = [];
                return;
            }
            $html[] = '<div class="overflow-x-auto my-2 rounded-lg border border-slate-200"><table class="min-w-full text-xs divide-y divide-slate-200">';
            foreach ($filas as $idx => $fila) {
                $celdas = array_map('trim', explode('|', trim($fila, " \t|")));
                $tag = $idx === 0 ? 'th' : 'td';
                $rowCls = $idx === 0 ? 'bg-slate-100 text-slate-700 font-semibold' : 'bg-white text-slate-700';
                $html[] = "<tr class=\"{$rowCls}\">";
                foreach ($celdas as $c) {
                    $cellCls = $idx === 0 ? 'px-2.5 py-1.5 text-left' : 'px-2.5 py-1.5 border-t border-slate-100';
                    $html[] = "<{$tag} class=\"{$cellCls}\">".self::inline($c)."</{$tag}>";
                }
                $html[] = '</tr>';
            }
            $html[] = '</table></div>';
            $bufferTabla = [];
        };

        foreach ($lineas as $linea) {
            $trim = trim($linea);
            if (str_starts_with($trim, '|') && str_ends_with($trim, '|')) {
                $cerrarLista();
                $bufferTabla[] = $trim;
                continue;
            }
            $volcarTabla();

            if ($trim === '' || $trim === '---') {
                $cerrarLista();
                continue;
            }
            if (preg_match('/^(?:\*|-|\d+\.)\s+(.+)$/', $trim, $m)) {
                if (!$enLista) {
                    $html[] = '<ul class="list-disc pl-5 space-y-1.5 my-2 text-slate-700 marker:text-brand-600">';
                    $enLista = true;
                }
                $html[] = '<li>'.self::inline($m[1]).'</li>';
                continue;
            }
            $cerrarLista();
            $html[] = '<p class="leading-relaxed text-slate-800 my-1.5">'.self::inline($trim).'</p>';
        }
        $cerrarLista();
        $volcarTabla();

        return new HtmlString(implode("\n", $html));
    }

    private static function inline(string $texto): string
    {
        $seguro = e($texto);
        $seguro = preg_replace('/\*\*(.+?)\*\*/s', '<strong class="font-semibold text-slate-900">$1</strong>', $seguro) ?? $seguro;
        $seguro = preg_replace('/`([^`]+)`/', '<code class="px-1.5 py-0.5 rounded bg-slate-100 text-brand-800 font-mono text-[12px]">$1</code>', $seguro) ?? $seguro;
        return preg_replace(
            '/\[(F\d+)\]/',
            '<span class="inline-flex items-center px-1.5 py-0.5 mx-0.5 rounded bg-brand-50 text-brand-800 border border-brand-200 font-mono text-[11px] font-semibold">[$1]</span>',
            $seguro
        ) ?? $seguro;
    }
}
