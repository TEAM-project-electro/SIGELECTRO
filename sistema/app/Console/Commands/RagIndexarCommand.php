<?php

namespace App\Console\Commands;

use App\Services\Rag\RagIndexador;
use Illuminate\Console\Command;

class RagIndexarCommand extends Command
{
    protected $signature = 'rag:indexar';

    protected $description = 'Reindexa la lista blanca de documentos del asistente RAG (operación local del operador).';

    public function handle(RagIndexador $indexador): int
    {
        $r = $indexador->indexar();
        $this->table(['Documento', 'Fragmentos', 'Bloqueados por escaneo'], $r['detalle']);
        $this->info("Documentos: {$r['documentos']} | Fragmentos: {$r['fragmentos']} | Bloqueados: {$r['bloqueados']} | Vectores: ".($r['embeddings'] ? 'sí (Gemini)' : 'no (solo BM25 local)'));
        return self::SUCCESS;
    }
}
