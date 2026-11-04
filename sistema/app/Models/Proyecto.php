<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proyecto extends Model
{
    protected $table = 'proyecto';
    protected $primaryKey = 'id_proyecto';
    public $timestamps = false;

    protected $fillable = [
        'codigo_pgd',
        'nombre',
        'monto',
        'anio_inicio',
        'anio_fin',
        'estado',
        'id_objetivo',
        'id_responsable',
        'fuente',
        'fecha_actualizacion',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'anio_inicio' => 'integer',
        'anio_fin' => 'integer',
        'fecha_actualizacion' => 'datetime',
    ];

    public function objetivo(): BelongsTo
    {
        return $this->belongsTo(ObjetivoGd::class, 'id_objetivo', 'id_objetivo');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Responsable::class, 'id_responsable', 'id_responsable');
    }

    public function hitos(): HasMany
    {
        return $this->hasMany(Hito::class, 'id_proyecto', 'id_proyecto');
    }

    public function avances(): HasMany
    {
        return $this->hasMany(AvancePresupuestal::class, 'id_proyecto', 'id_proyecto');
    }
}
