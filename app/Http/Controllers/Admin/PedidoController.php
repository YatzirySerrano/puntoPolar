<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PedidoEstadoMail;
use App\Models\Pedido;
use App\Models\PedidoEstatusHistorial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PedidoController extends Controller
{
    private const ESTATUS = [
        'pendiente',
        'pagado',
        'preparando',
        'listo_para_recoger',
        'salio_a_entrega',
        'entregado',
        'cancelado',
        'reembolsado',
    ];

    private const TRANSICIONES_RECOLECCION = [
        'pendiente' => ['pagado', 'cancelado'],
        'pagado' => ['preparando', 'reembolsado'],
        'preparando' => ['listo_para_recoger', 'cancelado'],
        'listo_para_recoger' => ['entregado', 'cancelado'],
        'salio_a_entrega' => ['entregado'],
        'entregado' => [],
        'cancelado' => [],
        'reembolsado' => [],
    ];

    private const TRANSICIONES_ENTREGA_LOCAL = [
        'pendiente' => ['pagado', 'cancelado'],
        'pagado' => ['preparando', 'reembolsado'],
        'preparando' => ['salio_a_entrega', 'cancelado'],
        'listo_para_recoger' => ['entregado'],
        'salio_a_entrega' => ['entregado', 'cancelado'],
        'entregado' => [],
        'cancelado' => [],
        'reembolsado' => [],
    ];

    public function index(Request $request): Response
    {
        $estatus = $request->string('estatus')->toString();
        $pago = $request->string('pago')->toString();
        $buscar = trim($request->string('buscar')->toString());
        $fechaDesde = $request->string('fecha_desde')->toString();
        $fechaHasta = $request->string('fecha_hasta')->toString();

        $query = Pedido::query()
            ->with([
                'user:id,name,email',
                'items.producto:id,nombre,slug,imagen_principal',
                'items.producto.imagenes:id,producto_id,ruta,principal,orden',
                'pagos.metodoPago:id,nombre,clave',
            ])
            ->when($estatus, fn ($query) => $query->where('estatus', $estatus))
            ->when($pago, function ($query) use ($pago) {
                $query->whereHas('pagos', fn ($subQuery) => $subQuery->where('estatus', $pago));
            })
            ->when($buscar, function ($query) use ($buscar) {
                $query->where(function ($subQuery) use ($buscar) {
                    $subQuery
                        ->where('folio', 'like', "%{$buscar}%")
                        ->orWhere('nombre_cliente', 'like', "%{$buscar}%")
                        ->orWhere('correo_cliente', 'like', "%{$buscar}%")
                        ->orWhere('telefono_cliente', 'like', "%{$buscar}%")
                        ->orWhere('codigo_recoleccion', 'like', "%{$buscar}%");
                });
            })
            ->when($fechaDesde, fn ($query) => $query->whereDate('created_at', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($query) => $query->whereDate('created_at', '<=', $fechaHasta));

        $statsBase = clone $query;

        $stats = [
            'total_pedidos' => (clone $statsBase)->count(),
            'monto_visible' => (float) (clone $statsBase)->sum('total'),
            'pendientes' => (clone $statsBase)->where('estatus', 'pendiente')->count(),
            'en_operacion' => (clone $statsBase)
                ->whereIn('estatus', ['pagado', 'preparando', 'listo_para_recoger', 'salio_a_entrega'])
                ->count(),
            'entregados' => (clone $statsBase)->where('estatus', 'entregado')->count(),
        ];

        $pedidos = $query
            ->latest()
            ->paginate(12)
            ->through(function (Pedido $pedido) {
                return [
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
                    'total' => (float) $pedido->total,
                    'nombre_cliente' => $pedido->nombre_cliente,
                    'correo_cliente' => $pedido->correo_cliente,
                    'telefono_cliente' => $pedido->telefono_cliente,
                    'comentario_interno' => $pedido->comentario_interno,
                    'created_at' => $pedido->created_at?->toDateTimeString(),
                    'items' => $pedido->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'producto_id' => $item->producto_id,
                            'nombre' => $item->nombre,
                            'sku' => $item->sku,
                            'cantidad' => (int) $item->cantidad,
                            'precio_unitario' => (float) $item->precio_unitario,
                            'subtotal' => (float) $item->subtotal,
                            'imagen_url' => $this->productoImagenUrl($item->producto),
                        ];
                    })->values(),
                    'pagos' => $pedido->pagos->map(fn ($pago) => [
                        'id' => $pago->id,
                        'estatus' => $pago->estatus,
                        'monto' => (float) $pago->monto,
                        'referencia_externa' => $pago->referencia_externa,
                        'autorizacion' => $pago->autorizacion,
                        'pagado_en' => $pago->pagado_en?->toDateTimeString(),
                        'metodo_pago' => $pago->metodoPago ? [
                            'nombre' => $pago->metodoPago->nombre,
                            'clave' => $pago->metodoPago->clave,
                        ] : null,
                    ])->values(),
                ];
            })
            ->withQueryString();

        return Inertia::render('Admin/Pedidos/Index', [
            'pedidos' => $pedidos,
            'stats' => $stats,
            'estatusDisponibles' => self::ESTATUS,
            'pagosDisponibles' => [
                'pendiente',
                'procesando',
                'aprobado',
                'rechazado',
                'error',
            ],
            'filters' => [
                'estatus' => $estatus,
                'pago' => $pago,
                'buscar' => $buscar,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
            ],
        ]);
    }

    public function show(Pedido $pedido): Response
    {
        $pedido->load([
            'user:id,name,email',
            'direccion',
            'items.producto:id,nombre,slug,imagen_principal',
            'items.producto.imagenes:id,producto_id,ruta,principal,orden',
            'pagos.metodoPago:id,nombre,clave',
            'historial.user:id,name',
        ]);

        return Inertia::render('Admin/Pedidos/Show', [
            'pedido' => [
                'id' => $pedido->id,
                'folio' => $pedido->folio,
                'estatus' => $pedido->estatus,
                'estatus_siguientes' => $this->estatusSiguientes($pedido),
                'tipo_entrega' => $pedido->tipo_entrega,
                'codigo_recoleccion' => $pedido->codigo_recoleccion,
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
                'comentario_interno' => $pedido->comentario_interno,
                'preparando_en' => $pedido->preparando_en?->toDateTimeString(),
                'listo_para_recoger_en' => $pedido->listo_para_recoger_en?->toDateTimeString(),
                'fecha_entrega_programada' => $pedido->fecha_entrega_programada?->toDateTimeString(),
                'salio_a_entrega_en' => $pedido->salio_a_entrega_en?->toDateTimeString(),
                'zona_entrega' => $pedido->zona_entrega,
                'instrucciones_entrega' => $pedido->instrucciones_entrega,
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
                        'imagen_url' => $this->productoImagenUrl($item->producto),
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
            'comentario_interno' => ['nullable', 'string', 'max:5000'],
            'fecha_entrega_programada' => ['nullable', 'date'],
            'zona_entrega' => ['nullable', 'string', 'max:120'],
            'instrucciones_entrega' => ['nullable', 'string', 'max:1000'],
        ]);

        $estatusAnterior = $pedido->estatus;
        $nuevoEstatus = $data['estatus'];

        $permitidos = $this->estatusSiguientes($pedido);

        if ($pedido->estatus !== $nuevoEstatus && ! in_array($nuevoEstatus, $permitidos, true)) {
            return back()->with('error', 'No puedes cambiar el pedido a ese estado desde el estado actual.');
        }

        $pedido->comentario_interno = $data['comentario_interno'] ?? $pedido->comentario_interno;

        if ($pedido->tipo_entrega === 'entrega_local') {
            $pedido->fecha_entrega_programada = $data['fecha_entrega_programada'] ?? $pedido->fecha_entrega_programada;
            $pedido->zona_entrega = $data['zona_entrega'] ?? $pedido->zona_entrega;
            $pedido->instrucciones_entrega = $data['instrucciones_entrega'] ?? $pedido->instrucciones_entrega;
        }

        if ($pedido->estatus !== $nuevoEstatus) {
            $pedido->estatus = $nuevoEstatus;

            if ($nuevoEstatus === 'pagado' && ! $pedido->pagado_en) {
                $pedido->pagado_en = now();
            }

            if ($nuevoEstatus === 'preparando' && ! $pedido->preparando_en) {
                $pedido->preparando_en = now();
            }

            if ($nuevoEstatus === 'listo_para_recoger' && ! $pedido->listo_para_recoger_en) {
                $pedido->listo_para_recoger_en = now();
            }

            if ($nuevoEstatus === 'salio_a_entrega' && ! $pedido->salio_a_entrega_en) {
                $pedido->salio_a_entrega_en = now();
                $pedido->enviado_en = $pedido->enviado_en ?: now();
            }

            if ($nuevoEstatus === 'entregado' && ! $pedido->entregado_en) {
                $pedido->entregado_en = now();
            }

            if ($nuevoEstatus === 'cancelado' && ! $pedido->cancelado_en) {
                $pedido->cancelado_en = now();
            }

            PedidoEstatusHistorial::create([
                'pedido_id' => $pedido->id,
                'user_id' => $request->user()?->id,
                'estatus' => $nuevoEstatus,
                'comentario' => $data['comentario'] ?? null,
            ]);
        }

        $pedido->save();

        if ($estatusAnterior !== $nuevoEstatus) {
            $this->enviarCorreoPorEstatus($pedido, $nuevoEstatus);
        }

        return back()->with('success', 'Pedido actualizado correctamente.');
    }

    private function estatusSiguientes(Pedido $pedido): array
    {
        $transiciones = $pedido->tipo_entrega === 'entrega_local'
            ? self::TRANSICIONES_ENTREGA_LOCAL
            : self::TRANSICIONES_RECOLECCION;

        return $transiciones[$pedido->estatus] ?? [];
    }

    private function enviarCorreoPorEstatus(Pedido $pedido, string $estatus): void
    {
        if (! $pedido->correo_cliente) {
            return;
        }

        if (! in_array($estatus, ['pagado', 'preparando', 'listo_para_recoger', 'salio_a_entrega', 'entregado'], true)) {
            return;
        }

        try {
            Mail::to($pedido->correo_cliente)->send(
                new PedidoEstadoMail($pedido->fresh(['items', 'direccion', 'pagos.metodoPago']), $estatus)
            );
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function productoImagenUrl($producto): ?string
    {
        if (! $producto) {
            return null;
        }

        $ruta = $producto->imagenes
            ?->sortBy([
                ['principal', 'desc'],
                ['orden', 'asc'],
                ['id', 'asc'],
            ])
            ->pluck('ruta')
            ->filter()
            ->first();

        $ruta = $ruta ?: $producto->imagen_principal;

        if (! $ruta) {
            return null;
        }

        if (str_starts_with($ruta, 'http://') || str_starts_with($ruta, 'https://')) {
            return $ruta;
        }

        if (str_starts_with($ruta, '/storage/')) {
            return $ruta;
        }

        if (str_starts_with($ruta, 'storage/')) {
            return '/'.$ruta;
        }

        return Storage::url($ruta);
    }
}