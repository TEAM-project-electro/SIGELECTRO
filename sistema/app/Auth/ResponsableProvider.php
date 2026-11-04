<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;

/** La conexión con hashes queda limitada al proveedor de identidad. */
class ResponsableProvider extends EloquentUserProvider
{
    public function createModel()
    {
        return parent::createModel()->setConnection('auth');
    }

    protected function newModelQuery($model = null)
    {
        return parent::newModelQuery($model)->select([
            'id_responsable', 'usuario', 'clave_hash', 'activo', 'perfil', 'nombre',
        ]);
    }

    public function rehashPasswordIfRequired(\Illuminate\Contracts\Auth\Authenticatable $user, array $credentials, bool $force = false)
    {
        // El proveedor es SELECT-only. Las credenciales se gestionan y auditan con UsuarioService.
    }
}
