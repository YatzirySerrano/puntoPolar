<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pago extends Model {

    protected $table = 'pagos';

    protected $fillable = [
        'pedido_id',
        'metodo_pago_id',
        'estatus',
        'monto',
        'moneda',
        'referencia_externa',
        'autorizacion',
        'respuesta_pasarela',
        'pagado_en',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'respuesta_pasarela' => 'array',
        'pagado_en' => 'datetime',
    ];

    public function pedido(): BelongsTo {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function metodoPago(): BelongsTo {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    public function transacciones(): HasMany {
        return $this->hasMany(TransaccionPasarela::class, 'pago_id');
    }

}
