<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MisPedidosController extends Controller
{
    public function index(Request $request): Response
    {
        $pedidos = Pedido::query()
            ->with('items')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10)
            ->through(function (Pedido $pedido) {
                return [
                    'id' => $pedido->id,
                    'folio' => $pedido->folio,
                    'estatus' => $pedido->estatus,
                    'tipo_entrega' => $pedido->tipo_entrega,
                    'codigo_recoleccion' => $pedido->codigo_recoleccion,
                    'listo_para_recoger_en' => $pedido->listo_para_recoger_en?->toDateTimeString(),
                    'salio_a_entrega_en' => $pedido->salio_a_entrega_en?->toDateTimeString(),
                    'total' => (float) $pedido->total,
                    'created_at' => $pedido->created_at?->toDateTimeString(),
                    'items' => $pedido->items->map(fn ($item) => [
                        'id' => $item->id,
                        'nombre' => $item->nombre,
                        'cantidad' => (int) $item->cantidad,
                    ])->values(),
                ];
            });

        return Inertia::render('Cliente/Pedidos/Index', [
            'pedidos' => $pedidos,
        ]);
    }

    public function show(Request $request, Pedido $pedido): Response
    {
        abort_unless((int) $pedido->user_id === (int) $request->user()->id, 404);

        $pedido->load([
            'direccion',
            'items.producto:id,nombre,slug',
            'pagos.metodoPago:id,nombre,clave',
            'historial.user:id,name',
        ]);

        return Inertia::render('Cliente/Pedidos/Show', [
            'pedido' => [
                'id' => $pedido->id,
                'folio' => $pedido->folio,
                'estatus' => $pedido->estatus,
                'tipo_entrega' => $pedido->tipo_entrega,
                'codigo_recoleccion' => $pedido->codigo_recoleccion,
                'listo_para_recoger_en' => $pedido->listo_para_recoger_en?->toDateTimeString(),
                'fecha_entrega_programada' => $pedido->fecha_entrega_programada?->toDateTimeString(),
                'salio_a_entrega_en' => $pedido->salio_a_entrega_en?->toDateTimeString(),
                'zona_entrega' => $pedido->zona_entrega,
                'instrucciones_entrega' => $pedido->instrucciones_entrega,
                'moneda' => $pedido->moneda,
                'subtotal' => (float) $pedido->subtotal,
                'descuento' => (float) $pedido->descuento,
                'envio' => (float) $pedido->envio,
                'impuesto' => (float) $pedido->impuesto,
                'total' => (float) $pedido->total,
                'nombre_cliente' => $pedido->nombre_cliente,
                'correo_cliente' => $pedido->correo_cliente,
                'telefono_cliente' => $pedido->telefono_cliente,
                'notas_cliente' => $pedido->notas_cliente,
                'pagado_en' => $pedido->pagado_en?->toDateTimeString(),
                'created_at' => $pedido->created_at?->toDateTimeString(),
                'direccion' => $pedido->direccion ? [
                    'alias' => $pedido->direccion->alias,
                    'nombre_receptor' => $pedido->direccion->nombre_receptor,
                    'telefono' => $pedido->direccion->telefono,
                    'calle' => $pedido->direccion->calle,
                    'numero_exterior' => $pedido->direccion->numero_exterior,
                    'numero_interior' => $pedido->direccion->numero_interior,
                    'colonia' => $pedido->direccion->colonia,
                    'municipio' => $pedido->direccion->municipio,
                    'estado' => $pedido->direccion->estado,
                    'pais' => $pedido->direccion->pais,
                    'codigo_postal' => $pedido->direccion->codigo_postal,
                    'referencias' => $pedido->direccion->referencias,
                ] : null,
                'items' => $pedido->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'producto_nombre' => $item->producto?->nombre,
                        'producto_slug' => $item->producto?->slug,
                        'sku' => $item->sku,
                        'nombre' => $item->nombre,
                        'cantidad' => (int) $item->cantidad,
                        'precio_unitario' => (float) $item->precio_unitario,
                        'subtotal' => (float) $item->subtotal,
                    ];
                })->values(),
                'pagos' => $pedido->pagos->map(function ($pago) {
                    return [
                        'id' => $pago->id,
                        'estatus' => $pago->estatus,
                        'monto' => (float) $pago->monto,
                        'moneda' => $pago->moneda,
                        'referencia_externa' => $pago->referencia_externa,
                        'autorizacion' => $pago->autorizacion,
                        'pagado_en' => $pago->pagado_en?->toDateTimeString(),
                        'metodo_pago' => $pago->metodoPago ? [
                            'nombre' => $pago->metodoPago->nombre,
                            'clave' => $pago->metodoPago->clave,
                        ] : null,
                    ];
                })->values(),
                'historial' => $pedido->historial
                    ->sortByDesc('created_at')
                    ->values()
                    ->map(function ($row) {
                        return [
                            'id' => $row->id,
                            'estatus' => $row->estatus,
                            'comentario' => $row->comentario,
                            'created_at' => $row->created_at?->toDateTimeString(),
                            'user' => $row->user ? [
                                'id' => $row->user->id,
                                'name' => $row->user->name,
                            ] : null,
                        ];
                    }),
            ],
        ]);
    }
}