<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Producto extends Model {

    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'marca_id',
        'sku',
        'nombre',
        'slug',
        'descripcion',
        'descripcion_larga',
        'precio',
        'precio_comparacion',
        'costo',
        'stock',
        'stock_minimo',
        'peso',
        'alto',
        'ancho',
        'largo',
        'imagen_principal',
        'destacado',
        'visible',
        'activo',
        'publicado_en',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'precio_comparacion' => 'decimal:2',
        'costo' => 'decimal:2',
        'destacado' => 'boolean',
        'visible' => 'boolean',
        'activo' => 'boolean',
        'publicado_en' => 'datetime',
    ];

    public function categoria() {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function marca(): BelongsTo {
        return $this->belongsTo(Marca::class, 'marca_id');
    }

    public function imagenes() {
        return $this->hasMany(ProductoImagen::class, 'producto_id');
    }

}
