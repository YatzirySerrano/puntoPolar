<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $table = 'banners';

    protected $fillable = [
        'titulo',
        'descripcion',
        'imagen',
        'url',
        'activo',
        'orden',
        'inicia_en',
        'termina_en',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
        'inicia_en' => 'datetime',
        'termina_en' => 'datetime',
    ];
}
