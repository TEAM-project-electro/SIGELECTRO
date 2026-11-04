<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BitacoraMotivo extends Model
{
    protected $table = 'bitacora_motivo';
    protected $primaryKey = 'id_cambio';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_cambio',
        'motivo',
    ];

    public function cambio(): BelongsTo
    {
        return $this->belongsTo(BitacoraCambio::class, 'id_cambio', 'id_cambio');
    }
}
