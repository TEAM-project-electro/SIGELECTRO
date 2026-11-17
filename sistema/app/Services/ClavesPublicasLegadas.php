<?php

namespace App\Services;

final class ClavesPublicasLegadas
{
    // Credenciales públicas anteriores: no deben volver a autorizar acceso.
    public const VALORES = ['Admin2026!', 'Resp2026!', 'Consulta2026!'];

    public static function contiene(string $clave): bool
    {
        return in_array($clave, self::VALORES, true);
    }
}
