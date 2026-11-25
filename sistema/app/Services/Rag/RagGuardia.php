<?php

namespace App\Services\Rag;

/**
 * Capas 2 y 3 contra prompt injection (OWASP LLM01): escaneo de fragmentos al indexar
 * y guardia de entrada de preguntas. Heurística: complementa, no reemplaza, las demás capas.
 */
class RagGuardia
{
    /** Patrones de instrucción inyectada (texto normalizado sin tildes). */
    private const INYECCION = [
        'anular_instrucciones' => '/\b(ignora|ignorar|ignore|olvida|olvidar|forget|omite|omitir|desobedece|disregard|override)\b.{0,40}\b(instruccion\w*|reglas|indicaciones|instructions?|rules|prompt|anterior\w*|previous|above|todo lo)\b/u',
        'prompt_sistema' => '/(system prompt|prompt del sistema|prompt de sistema|instrucciones del sistema|instrucciones internas|system instruction|mensaje del sistema)/u',
        'cambio_rol' => '/\b(actua como|actuar como|eres ahora|ahora eres|finge ser|finge que|pretend|you are now|act as|roleplay|modo desarrollador|developer mode|jailbreak|dan mode|sin restricciones|without restrictions)\b/u',
        'etiquetas_rol' => '/[<‹]\s*\/?\s*(system|sistema|fuente|pregunta|instrucciones|assistant|user|model)\b/u',
    ];

    /** Patrones adicionales solo para preguntas de usuarios. */
    private const SOLO_PREGUNTA = [
        'revelar_configuracion' => '/\b(revela\w*|muestra\w*|mostrar|imprime|imprimir|dime|dame|repite|reveal|show|print|leak|output)\b.{0,40}\b(prompt|instrucciones|configuracion|api ?key|clave api|token|secreto|secret|credencial\w*)\b/u',
        'secretos' => '/(clave_hash|gemini_api_key|app_key|db_password|db_\w*_password|\.env\b|\$2y\$|password_hash)/u',
        'credencial_ajena' => '/\b(contrasena|password|clave)s?\b.{0,30}\b(msalas|aquispe|lrojas|cgarcia|administrador|otros usuarios|todos los usuarios|otro usuario)\b/u',
        'sql' => '/\b(drop\s+table|truncate|delete\s+from|update\s+\w+\s+set|insert\s+into|grant\s+\w+|revoke\s+\w+|union\s+select)\b/u',
        'ofuscacion' => '/\b(base64|rot13|hexadecimal)\b/u',
    ];

    /** @return string|null motivo del bloqueo, o null si la pregunta es aceptable */
    public function evaluarPregunta(string $pregunta): ?string
    {
        if (preg_match('/[\x{0000}-\x{0008}\x{000B}\x{000C}\x{000E}-\x{001F}\x{007F}]/u', $pregunta)) {
            return 'caracteres_control';
        }
        $texto = $this->limpiar($pregunta);
        $longitud = mb_strlen($texto);
        if ($longitud < config('rag.pregunta_min') || $longitud > config('rag.pregunta_max')) {
            return 'longitud';
        }
        foreach (self::INYECCION + self::SOLO_PREGUNTA as $motivo => $patron) {
            if (preg_match($patron, $texto)) {
                return $motivo;
            }
        }
        return null;
    }

    /** Escaneo al indexar: devuelve el motivo si el fragmento contiene instrucciones inyectadas. */
    public function evaluarFragmento(string $texto): ?string
    {
        $texto = $this->limpiar($texto);
        foreach (self::INYECCION as $motivo => $patron) {
            if (preg_match($patron, $texto)) {
                return $motivo;
            }
        }
        if (preg_match('/!\[[^\]]*\]\(\s*https?:/u', $texto)) {
            return 'imagen_remota';
        }
        return null;
    }

    /** Elimina caracteres invisibles de formato (p. ej. de ancho cero) y normaliza. */
    private function limpiar(string $texto): string
    {
        $texto = preg_replace('/\p{Cf}+/u', '', $texto) ?? '';
        $texto = RagTexto::normalizar($texto);
        return trim(preg_replace('/\s+/u', ' ', $texto) ?? '');
    }
}
