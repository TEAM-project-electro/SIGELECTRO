<?php

namespace App\Services\Rag;

use Illuminate\Support\Facades\DB;

/** Recuperación híbrida: BM25 local + similitud de vectores (si existen), fusionadas con RRF. */
class RagRecuperador
{
    private const K1 = 1.2;
    private const B = 0.5;
    private const RRF_K = 30;

    public function __construct(private RagGemini $gemini)
    {
    }

    /**
     * @param array<int,array{origen:string,titulo:string,seccion:string,texto:string}> $extra fragmentos vivos de cartera
     * @return array<int,array{origen:string,titulo:string,seccion:string,texto:string,puntaje:float}>
     */
    public function buscar(string $pregunta, array $extra = [], ?int $limite = null): array
    {
        $limite ??= (int) config('rag.resultados');
        $corpus = [];
        $filas = DB::connection('lector')->table('rag_fragmento as f')
            ->join('rag_documento as d', 'd.id_documento', '=', 'f.id_documento')
            ->where('f.bloqueado', 0)
            ->select('f.seccion', 'f.texto', 'f.terminos', 'f.longitud', 'f.embedding', 'd.titulo', 'd.ruta')
            ->get();
        foreach ($filas as $f) {
            $corpus[] = [
                'origen' => $f->ruta, 'titulo' => $f->titulo, 'seccion' => $f->seccion, 'texto' => $f->texto,
                'terminos' => json_decode($f->terminos, true) ?: [], 'longitud' => (int) $f->longitud,
                'embedding' => $f->embedding ? json_decode($f->embedding, true) : null,
            ];
        }
        foreach ($extra as $e) {
            $terminos = RagTexto::terminos($e['seccion'].' '.$e['texto']);
            $corpus[] = $e + ['terminos' => $terminos, 'longitud' => array_sum($terminos), 'embedding' => null];
        }
        if ($corpus === []) {
            return [];
        }

        $consulta = array_keys(RagTexto::terminos($pregunta));
        $bm25 = $this->bm25($consulta, $corpus);
        $rankings = [$bm25];

        if (collect($corpus)->contains(fn ($c) => $c['embedding'] !== null)) {
            $vector = $this->gemini->embeddings([$pregunta], 'RETRIEVAL_QUERY')[0] ?? null;
            if ($vector) {
                $similitudes = [];
                foreach ($corpus as $i => $c) {
                    if ($c['embedding']) {
                        $similitudes[$i] = $this->coseno($vector, $c['embedding']);
                    }
                }
                arsort($similitudes);
                $rankings[] = array_slice($similitudes, 0, 30, true);
            }
        }

        $fusion = [];
        foreach ($rankings as $ranking) {
            $posicion = 0;
            foreach ($ranking as $i => $_) {
                $fusion[$i] = ($fusion[$i] ?? 0) + 1 / (self::RRF_K + ++$posicion);
            }
        }
        arsort($fusion);

        $resultado = [];
        foreach (array_slice($fusion, 0, $limite, true) as $i => $puntaje) {
            $c = $corpus[$i];
            $resultado[] = ['origen' => $c['origen'], 'titulo' => $c['titulo'], 'seccion' => $c['seccion'], 'texto' => $c['texto'], 'puntaje' => round($puntaje, 5)];
        }
        return $resultado;
    }

    /** @return array<int,float> índice => puntaje (solo > 0, ordenado desc) */
    private function bm25(array $consulta, array $corpus): array
    {
        $n = count($corpus);
        $promedio = max(1, array_sum(array_column($corpus, 'longitud')) / $n);
        $puntajes = [];
        foreach ($consulta as $t) {
            $df = 0;
            foreach ($corpus as $c) {
                if (isset($c['terminos'][$t])) {
                    $df++;
                }
            }
            if ($df === 0) {
                continue;
            }
            $idf = log(1 + ($n - $df + 0.5) / ($df + 0.5));
            foreach ($corpus as $i => $c) {
                $tf = $c['terminos'][$t] ?? 0;
                if ($tf > 0) {
                    $puntajes[$i] = ($puntajes[$i] ?? 0)
                        + $idf * ($tf * (self::K1 + 1)) / ($tf + self::K1 * (1 - self::B + self::B * $c['longitud'] / $promedio));
                }
            }
        }
        arsort($puntajes);
        return array_slice($puntajes, 0, 30, true);
    }

    private function coseno(array $a, array $b): float
    {
        $p = $na = $nb = 0.0;
        foreach ($a as $i => $v) {
            $w = $b[$i] ?? 0.0;
            $p += $v * $w;
            $na += $v * $v;
            $nb += $w * $w;
        }
        return ($na > 0 && $nb > 0) ? $p / (sqrt($na) * sqrt($nb)) : 0.0;
    }
}
