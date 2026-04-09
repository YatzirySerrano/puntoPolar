<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Oferta extends Model
{
    protected $table = 'ofertas';

    protected $fillable = [
        'nombre',
        'tipo',
        'valor',
        'inicia_en',
        'termina_en',
        'activa',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'inicia_en' => 'datetime',
        'termina_en' => 'datetime',
        'activa' => 'boolean',
    ];
}
