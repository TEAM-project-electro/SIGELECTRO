<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Responsable extends Authenticatable
{
    protected $table = 'responsable';
    protected $primaryKey = 'id_responsable';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'area',
        'perfil',
        'usuario',
        'clave_hash',
        'activo',
    ];

    protected $hidden = [
        'clave_hash',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Nombre de la columna de contraseña para autenticación Laravel.
     */
    public function getAuthPasswordName(): string
    {
        return 'clave_hash';
    }

    /**
     * Relación con proyectos a su cargo.
     */
    public function proyectos(): HasMany
    {
        return $this->hasMany(Proyecto::class, 'id_responsable', 'id_responsable');
    }

    /**
     * Relación con cambios registrados en la bitácora.
     */
    public function cambios(): HasMany
    {
        return $this->hasMany(BitacoraCambio::class, 'id_responsable', 'id_responsable');
    }

    public function esAdmin(): bool
    {
        return $this->perfil === 'Administrador';
    }

    public function esResponsable(): bool
    {
        return $this->perfil === 'Responsable';
    }

    public function esConsulta(): bool
    {
        return $this->perfil === 'Consulta';
    }
}
