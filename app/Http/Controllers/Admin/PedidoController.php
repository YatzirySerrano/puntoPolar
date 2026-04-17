<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\PedidoEstatusHistorial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PedidoController extends Controller {

    private const ESTATUS = ['pendiente', 'pagado', 'preparando', 'enviado', 'entregado', 'cancelado', 'reembolsado'];

    public function index(Request $request): Response {
        $estatus = $request->string('estatus')->toString();
        $pedidos = Pedido::query()
            ->with(['user:id,name,email', 'items'])
            ->when($estatus, fn ($query) => $query->where('estatus', $estatus))
            ->latest()
            ->paginate(12)
            ->through(function (Pedido $pedido) {
                return [
                    'id' => $pedido->id,
                    'folio' => $pedido->folio,
                    'estatus' => $pedido->estatus,
                    'total' => (float) $pedido->total,
                    'nombre_cliente' => $pedido->nombre_cliente,
                    'correo_cliente' => $pedido->correo_cliente,
                    'items' => $pedido->items->map(fn ($item) => [
                        'id' => $item->id,
                        'cantidad' => (int) $item->cantidad,
                    ])->values(),
                    'created_at' => $pedido->created_at?->toDateTimeString(),
                ];
            })
            ->withQueryString();
        return Inertia::render('Admin/Pedidos/Index', [
            'pedidos' => $pedidos,
            'estatusDisponibles' => self::ESTATUS,
            'filters' => [
                'estatus' => $estatus,
            ],
        ]);
    }

    public function show(Pedido $pedido): Response {
        $pedido->load([
            'user:id,name,email',
            'direccion',
            'items.producto:id,nombre,slug',
            'pagos.metodoPago:id,nombre,clave',
            'historial.user:id,name',
        ]);
        return Inertia::render('Admin/Pedidos/Show', [
            'pedido' => [
                'id' => $pedido->id,
                'folio' => $pedido->folio,
                'estatus' => $pedido->estatus,
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
                'paqueteria' => $pedido->paqueteria,
                'numero_guia' => $pedido->numero_guia,
                'comentario_interno' => $pedido->comentario_interno,
                'preparando_en' => $pedido->preparando_en?->toDateTimeString(),
                'enviado_en' => $pedido->enviado_en?->toDateTimeString(),
                'entregado_en' => $pedido->entregado_en?->toDateTimeString(),
                'pagado_en' => $pedido->pagado_en?->toDateTimeString(),
                'cancelado_en' => $pedido->cancelado_en?->toDateTimeString(),
                'created_at' => $pedido->created_at?->toDateTimeString(),
                'user' => $pedido->user ? [
                    'id' => $pedido->user->id,
                    'name' => $pedido->user->name,
                    'email' => $pedido->user->email,
                ] : null,
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
                        'producto_id' => $item->producto_id,
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
                        'respuesta_pasarela' => $pago->respuesta_pasarela,
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
            'estatusDisponibles' => self::ESTATUS,
        ]);
    }

    public function updateStatus(Request $request, Pedido $pedido): RedirectResponse
    {
        $data = $request->validate([
            'estatus' => ['required', Rule::in(self::ESTATUS)],
            'comentario' => ['nullable', 'string', 'max:500'],
            'paqueteria' => ['nullable', 'string', 'max:120'],
            'numero_guia' => ['nullable', 'string', 'max:180'],
            'comentario_interno' => ['nullable', 'string', 'max:5000'],
        ]);
        $nuevoEstatus = $data['estatus'];
        $pedido->paqueteria = $data['paqueteria'] ?? null;
        $pedido->numero_guia = $data['numero_guia'] ?? null;
        $pedido->comentario_interno = $data['comentario_interno'] ?? null;
        if ($pedido->estatus !== $nuevoEstatus) {
            $pedido->estatus = $nuevoEstatus;
            if ($nuevoEstatus === 'pagado' && ! $pedido->pagado_en) {
                $pedido->pagado_en = now();
            }
            if ($nuevoEstatus === 'preparando' && ! $pedido->preparando_en) {
                $pedido->preparando_en = now();
            }
            if ($nuevoEstatus === 'enviado' && ! $pedido->enviado_en) {
                $pedido->enviado_en = now();
            }
            if ($nuevoEstatus === 'entregado' && ! $pedido->entregado_en) {
                $pedido->entregado_en = now();
            }
            if ($nuevoEstatus === 'cancelado' && ! $pedido->cancelado_en) {
                $pedido->cancelado_en = now();
            }
            if ($nuevoEstatus !== 'cancelado') {
                $pedido->cancelado_en = null;
            }
            PedidoEstatusHistorial::create([
                'pedido_id' => $pedido->id,
                'user_id' => $request->user()?->id,
                'estatus' => $nuevoEstatus,
                'comentario' => $data['comentario'] ?? null,
            ]);
        }
        $pedido->save();
        return back()->with('success', 'Pedido actualizado correctamente.');
    }

}
