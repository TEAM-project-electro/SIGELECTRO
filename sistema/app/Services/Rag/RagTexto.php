<?php

namespace App\Services\Rag;

/** Normalización y tokenización compartidas (sin dependencias externas). */
class RagTexto
{
    private const VACIAS = [
        'de','la','el','en','y','a','los','las','del','se','que','por','un','una','con','para','es','al',
        'lo','como','mas','o','su','sus','son','sin','sobre','este','esta','estos','estas','ser','no','si',
        'cual','cuales','cuantos','cuantas','que','qui','me','mi','le','les','ya','hay','fue','muy','tambien',
        'the','of','and','to','is',
    ];

    public static function normalizar(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        return strtr($texto, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n','à'=>'a','è'=>'e']);
    }

    /** @return array<string,int> término => frecuencia */
    public static function terminos(string $texto): array
    {
        $tokens = preg_split('/[^a-z0-9]+/', self::normalizar($texto), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $frecuencias = [];
        foreach ($tokens as $t) {
            if (strlen($t) < 2 || in_array($t, self::VACIAS, true)) {
                continue;
            }
            $frecuencias[$t] = ($frecuencias[$t] ?? 0) + 1;
        }
        return $frecuencias;
    }

    /** Neutraliza delimitadores para que un texto no pueda cerrar ni abrir etiquetas del prompt. */
    public static function neutralizarDelimitadores(string $texto): string
    {
        return str_replace(['<', '>'], ['‹', '›'], $texto);
    }
}
