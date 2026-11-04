<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvancePresupuestal extends Model
{
    protected $table = 'avance_presupuestal';
    protected $primaryKey = 'id_avance';
    public $timestamps = false;

    protected $fillable = [
        'id_proyecto',
        'fecha_reporte',
        'presupuesto_planificado',
        'gasto_acumulado',
    ];

    protected $casts = [
        'fecha_reporte' => 'date',
        'presupuesto_planificado' => 'decimal:2',
        'gasto_acumulado' => 'decimal:2',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'id_proyecto', 'id_proyecto');
    }
}
