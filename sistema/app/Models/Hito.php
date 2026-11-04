<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hito extends Model
{
    protected $table = 'hito';
    protected $primaryKey = 'id_hito';
    public $timestamps = false;

    protected $fillable = [
        'id_proyecto',
        'descripcion',
        'fecha_prevista',
        'fecha_cierre',
        'evidencia',
        'en_linea_base',
    ];

    protected $casts = [
        'fecha_prevista' => 'date',
        'fecha_cierre' => 'date',
        'en_linea_base' => 'boolean',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'id_proyecto', 'id_proyecto');
    }
}
