<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlRevision extends Model
{
    protected $table = 'control_revision';
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'version',
        'fecha_hora',
    ];

    protected $casts = [
        'version' => 'integer',
        'fecha_hora' => 'datetime',
    ];

    public static function versionActual(): int
    {
        return (int) (static::where('id', 1)->value('version') ?? 1);
    }
}
