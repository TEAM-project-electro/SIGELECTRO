<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ObjetivoGd extends Model
{
    protected $table = 'objetivo_gd';
    protected $primaryKey = 'id_objetivo';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'descripcion',
    ];

    public function proyectos(): HasMany
    {
        return $this->hasMany(Proyecto::class, 'id_objetivo', 'id_objetivo');
    }
}
