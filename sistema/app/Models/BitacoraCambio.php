<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BitacoraCambio extends Model
{
    protected $table = 'bitacora_cambio';
    protected $primaryKey = 'id_cambio';
    public $timestamps = false;

    protected $fillable = [
        'tabla_afectada',
        'id_registro',
        'campo',
        'valor_anterior',
        'valor_nuevo',
        'fecha_hora',
        'id_responsable',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'id_registro' => 'integer',
    ];

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Responsable::class, 'id_responsable', 'id_responsable');
    }

    public function motivoExtension(): HasOne
    {
        return $this->hasOne(BitacoraMotivo::class, 'id_cambio', 'id_cambio');
    }
}
