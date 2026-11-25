<?php

namespace App\Services\Rag;

use Illuminate\Support\Facades\DB;

/** Etapas "adquirir" y "almacenar" de la cadena de valor del conocimiento (Laudon Cap. 11). */
class RagIndexador
{
    public function __construct(private RagGuardia $guardia, private RagGemini $gemini)
    {
    }

    /** @return array{documentos:int,fragmentos:int,bloqueados:int,embeddings:bool,detalle:array} */
    public function indexar(): array
    {
        $raiz = realpath(base_path('..'));
        $archivos = [];
        foreach (config('rag.fuentes') as $patron) {
            foreach (glob($raiz.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $patron)) ?: [] as $ruta) {
                $real = realpath($ruta);
                // Solo archivos .md dentro de docs/ del proyecto (evita rutas fuera de la lista blanca).
                if ($real && is_file($real) && str_starts_with($real, $raiz.DIRECTORY_SEPARATOR.'docs')
                    && str_ends_with(strtolower($real), '.md')) {
                    $archivos[$real] = str_replace('\\', '/', substr($real, strlen($raiz) + 1));
                }
            }
        }

        $documentos = [];
        foreach ($archivos as $real => $relativa) {
            $contenido = (string) file_get_contents($real);
            $titulo = preg_match('/^#\s+(.+)$/m', $contenido, $m) ? trim($m[1]) : basename($relativa);
            $fragmentos = [];
            foreach ($this->fragmentar($contenido, $titulo) as $i => [$seccion, $texto]) {
                $fragmentos[] = [
                    'orden' => $i + 1,
                    'seccion' => mb_substr($seccion, 0, 250),
                    'texto' => $texto,
                    'terminos' => RagTexto::terminos($seccion.' '.$texto),
                    'bloqueado' => $this->guardia->evaluarFragmento($texto) !== null,
                ];
            }
            $documentos[] = ['ruta' => $relativa, 'titulo' => mb_substr($titulo, 0, 195), 'hash' => hash('sha256', $contenido), 'fragmentos' => $fragmentos];
        }

        $todos = [];
        foreach ($documentos as $d => $doc) {
            foreach ($doc['fragmentos'] as $f => $frag) {
                if (!$frag['bloqueado']) {
                    $todos[] = [$d, $f, $doc['titulo'].' > '.$frag['seccion']."\n".$frag['texto']];
                }
            }
        }
        $vectores = $this->gemini->embeddings(array_column($todos, 2), 'RETRIEVAL_DOCUMENT');
        if ($vectores !== null) {
            foreach ($todos as $i => [$d, $f]) {
                $documentos[$d]['fragmentos'][$f]['embedding'] = $vectores[$i];
            }
        }

        $resumen = ['documentos' => 0, 'fragmentos' => 0, 'bloqueados' => 0, 'embeddings' => $vectores !== null, 'detalle' => []];
        DB::connection('mysql')->transaction(function () use ($documentos, &$resumen) {
            $db = DB::connection('mysql');
            $db->table('rag_fragmento')->delete();
            $db->table('rag_documento')->delete();
            foreach ($documentos as $doc) {
                $bloqueados = count(array_filter($doc['fragmentos'], fn ($f) => $f['bloqueado']));
                $id = $db->table('rag_documento')->insertGetId([
                    'ruta' => $doc['ruta'], 'titulo' => $doc['titulo'], 'hash_contenido' => $doc['hash'],
                    'fragmentos' => count($doc['fragmentos']), 'fragmentos_bloqueados' => $bloqueados,
                ]);
                foreach ($doc['fragmentos'] as $f) {
                    $db->table('rag_fragmento')->insert([
                        'id_documento' => $id, 'orden' => $f['orden'], 'seccion' => $f['seccion'], 'texto' => $f['texto'],
                        'terminos' => json_encode($f['terminos']), 'longitud' => array_sum($f['terminos']),
                        'embedding' => isset($f['embedding']) ? json_encode($f['embedding']) : null,
                        'bloqueado' => $f['bloqueado'] ? 1 : 0,
                    ]);
                }
                $resumen['documentos']++;
                $resumen['fragmentos'] += count($doc['fragmentos']);
                $resumen['bloqueados'] += $bloqueados;
                $resumen['detalle'][] = [$doc['ruta'], count($doc['fragmentos']), $bloqueados];
            }
        });
        return $resumen;
    }

    /**
     * Divide por encabezados Markdown y luego por párrafos (con solape de un párrafo).
     * @return array<int,array{0:string,1:string}>
     */
    public function fragmentar(string $contenido, string $titulo): array
    {
        $max = (int) config('rag.fragmento_max_caracteres');
        $contenido = str_replace("\r\n", "\n", $contenido);
        $contenido = preg_replace('/```mermaid.*?```/s', '', $contenido) ?? $contenido;
        $secciones = [];
        $actual = $titulo;
        $buffer = '';
        foreach (explode("\n", $contenido) as $linea) {
            if (preg_match('/^#{1,4}\s+(.+)$/', $linea, $m)) {
                $secciones[] = [$actual, $buffer];
                $actual = trim($m[1]);
                $buffer = '';
                continue;
            }
            $buffer .= $linea."\n";
        }
        $secciones[] = [$actual, $buffer];

        $resultado = [];
        foreach ($secciones as [$seccion, $texto]) {
            $parrafos = array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/', $texto) ?: []), fn ($p) => $p !== ''));
            $bloque = '';
            $ultimo = '';
            foreach ($parrafos as $p) {
                if ($bloque !== '' && mb_strlen($bloque) + mb_strlen($p) > $max) {
                    $resultado[] = [$seccion, $bloque];
                    $bloque = mb_strlen($ultimo) < $max / 3 ? $ultimo."\n\n" : '';
                }
                $bloque .= mb_substr($p, 0, $max)."\n\n";
                $ultimo = $p;
            }
            if (trim($bloque) !== '' && mb_strlen(trim($bloque)) > 40) {
                $resultado[] = [$seccion, trim($bloque)];
            }
        }
        return $resultado;
    }
}
