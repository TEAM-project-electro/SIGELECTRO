<?php

namespace App\Services;

use App\Models\Responsable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AdministrarUsuarios
{
    public static function guardar(?int $id, array $datos, ?string $motivo = null): Responsable
    {
        abort_unless(Auth::check() && Auth::user()->esAdmin(), 403);
        if (isset($datos['clave']) && ClavesPublicasLegadas::contiene((string) $datos['clave'])) {
            throw ValidationException::withMessages(['clave'=>'La contraseña demostrativa anterior es pública. Elija una contraseña privada.']);
        }
        if (isset($datos['activo']) && !in_array($datos['activo'], [0,1], true)) {
            throw ValidationException::withMessages(['activo'=>'Estado inválido.']);
        }
        $motivo = trim((string) $motivo);
        if (($datos['activo'] ?? 1) === 0 && (mb_strlen($motivo) < 5 || mb_strlen($motivo) > 200)) {
            throw ValidationException::withMessages(['motivo_cambio'=>'Desactivar requiere una justificación de 5 a 200 caracteres.']);
        }
        return DatabaseTransactionService::run(Auth::id(), function ($db) use ($id, $datos) {
            abort_unless(Auth::user()->esAdmin(), 403);
            $admins = $db->table('responsable')->select('id_responsable')->where('perfil', 'Administrador')->where('activo', 1)
                ->orderBy('id_responsable')->lockForUpdate()->get();
            $actual = $id ? $db->table('responsable')->select('id_responsable','activo','perfil')->where('id_responsable', $id)->lockForUpdate()->first() : null;
            if ($id && !$actual) abort(404);
            if ($actual && $actual->perfil === 'Administrador' && (int) $actual->activo === 1 &&
                (($datos['activo'] ?? 1) === 0 || ($datos['perfil'] ?? $actual->perfil) !== 'Administrador') && $admins->count() <= 1) {
                throw new RuntimeException('No se puede desactivar al único Administrador activo ni degradar su perfil.');
            }
            $valores = array_intersect_key($datos, array_flip(['nombre','area','usuario','perfil','activo']));
            if (!empty($datos['clave'])) $valores['clave_hash'] = Hash::make($datos['clave']);
            if ($id) $db->table('responsable')->where('id_responsable', $id)->update($valores);
            else $id = $db->table('responsable')->insertGetId($valores);
            $fila = $db->table('responsable')->select('id_responsable','nombre','area','usuario','perfil','activo')->where('id_responsable', $id)->first();
            return (new Responsable)->newFromBuilder((array) $fila, 'lector');
        }, $motivo !== '' ? $motivo : 'Administración de usuario');
    }
}
