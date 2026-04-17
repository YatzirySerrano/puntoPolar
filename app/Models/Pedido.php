<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model {

    protected $table = 'pedidos';

    protected $fillable = [
        'user_id',
        'direccion_id',
        'folio',
        'estatus',
        'moneda',
        'subtotal',
        'descuento',
        'envio',
        'impuesto',
        'total',
        'nombre_cliente',
        'correo_cliente',
        'telefono_cliente',
        'notas_cliente',
        'paqueteria',
        'numero_guia',
        'preparando_en',
        'enviado_en',
        'entregado_en',
        'comentario_interno',
        'pagado_en',
        'cancelado_en',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'envio' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'total' => 'decimal:2',
        'preparando_en' => 'datetime',
        'enviado_en' => 'datetime',
        'entregado_en' => 'datetime',
        'pagado_en' => 'datetime',
        'cancelado_en' => 'datetime',
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function direccion(): BelongsTo {
        return $this->belongsTo(Direccion::class, 'direccion_id');
    }

    public function items(): HasMany {
        return $this->hasMany(PedidoItem::class, 'pedido_id');
    }

    public function pagos(): HasMany {
        return $this->hasMany(Pago::class, 'pedido_id');
    }

    public function historial(): HasMany {
        return $this->hasMany(PedidoEstatusHistorial::class, 'pedido_id');
    }

}
