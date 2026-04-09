<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cupon extends Model
{
    protected $table = 'cupones';

    protected $fillable = [
        'codigo',
        'nombre',
        'tipo',
        'valor',
        'compra_minima',
        'usos_maximos',
        'usos_por_usuario',
        'usos_actuales',
        'inicia_en',
        'termina_en',
        'activo',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'compra_minima' => 'decimal:2',
        'activo' => 'boolean',
        'inicia_en' => 'datetime',
        'termina_en' => 'datetime',
    ];

    public function usos(): HasMany
    {
        return $this->hasMany(CuponUso::class, 'cupon_id');
    }
}
