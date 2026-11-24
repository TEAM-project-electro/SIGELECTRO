<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbastecimientoCompra extends Model
{
    protected $table = 'abastecimiento_compra';
    protected $primaryKey = 'id_abastecimiento';
    public $timestamps = false;

    protected $fillable = [
        'id_proyecto',
        'id_externo_linea',
        'orden_compra',
        'fecha_orden',
        'proveedor',
        'producto',
        'descripcion',
        'cantidad_comprada',
        'cantidad_recibida',
        'saldo_pendiente',
        'importe_total',
        'archivo_origen',
        'id_responsable_carga',
        'fecha_carga',
    ];

    protected $casts = [
        'fecha_orden' => 'date',
        'cantidad_comprada' => 'decimal:2',
        'cantidad_recibida' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
        'importe_total' => 'decimal:2',
        'fecha_carga' => 'datetime',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'id_proyecto', 'id_proyecto');
    }

    public function responsableCarga(): BelongsTo
    {
        return $this->belongsTo(Responsable::class, 'id_responsable_carga', 'id_responsable');
    }
}
