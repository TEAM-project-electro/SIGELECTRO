<?php

namespace App\Services\Rag;

use Illuminate\Support\Facades\Http;

/**
 * Cliente mínimo de Gemini API. La clave solo vive en el servidor (.env) y viaja en cabecera,
 * nunca en el cuerpo del prompt. Cualquier fallo devuelve null para degradar a modo extractivo.
 */
class RagGemini
{
    public function disponible(): bool
    {
        return filled(config('rag.gemini.api_key'));
    }

    public function generar(string $instruccionSistema, string $contenidoUsuario): ?string
    {
        if (!$this->disponible()) {
            return null;
        }
        try {
            $respuesta = Http::timeout((int) config('rag.gemini.timeout'))
                ->withHeaders(['x-goog-api-key' => config('rag.gemini.api_key')])
                ->post(config('rag.gemini.url').'/'.config('rag.gemini.modelo').':generateContent', [
                    'systemInstruction' => ['parts' => [['text' => $instruccionSistema]]],
                    'contents' => [['role' => 'user', 'parts' => [['text' => $contenidoUsuario]]]],
                    'generationConfig' => ['temperature' => 0.1, 'maxOutputTokens' => 800],
                ]);
            if (!$respuesta->successful()) {
                return null;
            }
            $texto = $respuesta->json('candidates.0.content.parts.0.text');
            return is_string($texto) && trim($texto) !== '' ? $texto : null;
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    /**
     * @param string[] $textos
     * @return array<int,float[]>|null
     */
    public function embeddings(array $textos, string $tarea): ?array
    {
        if (!$this->disponible() || $textos === []) {
            return null;
        }
        $modelo = config('rag.gemini.modelo_embedding');
        $vectores = [];
        try {
            foreach (array_chunk($textos, 25) as $lote) {
                $respuesta = Http::timeout(max(30, (int) config('rag.gemini.timeout')))
                    ->withHeaders(['x-goog-api-key' => config('rag.gemini.api_key')])
                    ->post(config('rag.gemini.url')."/{$modelo}:batchEmbedContents", [
                        'requests' => array_map(fn ($t) => [
                            'model' => "models/{$modelo}",
                            'content' => ['parts' => [['text' => mb_substr($t, 0, 6000)]]],
                            'taskType' => $tarea,
                            'outputDimensionality' => (int) config('rag.gemini.dimensiones'),
                        ], $lote),
                    ]);
                $lista = $respuesta->successful() ? $respuesta->json('embeddings') : null;
                if (!is_array($lista) || count($lista) !== count($lote)) {
                    return null;
                }
                foreach ($lista as $e) {
                    $vectores[] = array_map('floatval', $e['values'] ?? []);
                }
            }
            return $vectores;
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }
}
