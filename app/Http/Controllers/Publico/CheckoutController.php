<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Cupon;
use App\Models\Direccion;
use App\Models\Oferta;
use App\Models\Pedido;
use App\Models\PedidoEstatusHistorial;
use App\Models\PedidoItem;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Services\Payments\Data\PaymentChargeData;
use App\Services\Payments\PaymentService;

class CheckoutController extends Controller {

    public function index(Request $request): Response|RedirectResponse {
        $carrito = collect(session()->get('carrito', []));

        if ($carrito->isEmpty()) {
            return redirect()
                ->route('carrito.index')
                ->with('error', 'Tu carrito está vacío.');
        }

        $checkout = $this->buildCheckoutData($carrito);

        if ($checkout['items']->isEmpty()) {
            session()->forget('carrito');

            return redirect()
                ->route('carrito.index')
                ->with('error', 'Los productos del carrito ya no están disponibles.');
        }

        $user = $request->user();

        $direcciones = Direccion::query()
            ->where('user_id', $user->id)
            ->latest('predeterminada')
            ->latest('id')
            ->get()
            ->map(fn (Direccion $direccion) => [
                'id' => $direccion->id,
                'alias' => $direccion->alias,
                'nombre_receptor' => $direccion->nombre_receptor,
                'telefono' => $direccion->telefono,
                'calle' => $direccion->calle,
                'numero_exterior' => $direccion->numero_exterior,
                'numero_interior' => $direccion->numero_interior,
                'colonia' => $direccion->colonia,
                'municipio' => $direccion->municipio,
                'estado' => $direccion->estado,
                'pais' => $direccion->pais,
                'codigo_postal' => $direccion->codigo_postal,
                'referencias' => $direccion->referencias,
                'predeterminada' => (bool) $direccion->predeterminada,
            ]);

        return Inertia::render('Tienda/Checkout', [
            'items' => $checkout['items']->values(),
            'cupon' => $checkout['cupon'],
            'resumen' => $checkout['resumen'],
            'direcciones' => $direcciones,
            'cliente' => [
                'nombre' => $user->name,
                'correo' => $user->email,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse {
        $user = $request->user();

        $data = $request->validate([
            'nombre_cliente' => ['required', 'string', 'max:180'],
            'correo_cliente' => ['required', 'email', 'max:180'],
            'telefono_cliente' => ['required', 'string', 'max:30'],
            'notas_cliente' => ['nullable', 'string', 'max:1000'],

            'usar_direccion_existente' => ['required', 'boolean'],
            'direccion_id' => ['nullable', 'integer', 'exists:direcciones,id'],

            'alias' => ['nullable', 'string', 'max:80'],
            'nombre_receptor' => ['required_if:usar_direccion_existente,false', 'nullable', 'string', 'max:180'],
            'telefono' => ['required_if:usar_direccion_existente,false', 'nullable', 'string', 'max:30'],
            'calle' => ['required_if:usar_direccion_existente,false', 'nullable', 'string', 'max:180'],
            'numero_exterior' => ['required_if:usar_direccion_existente,false', 'nullable', 'string', 'max:50'],
            'numero_interior' => ['nullable', 'string', 'max:50'],
            'colonia' => ['required_if:usar_direccion_existente,false', 'nullable', 'string', 'max:120'],
            'municipio' => ['required_if:usar_direccion_existente,false', 'nullable', 'string', 'max:120'],
            'estado' => ['required_if:usar_direccion_existente,false', 'nullable', 'string', 'max:120'],
            'pais' => ['nullable', 'string', 'max:120'],
            'codigo_postal' => ['required_if:usar_direccion_existente,false', 'nullable', 'string', 'max:15'],
            'referencias' => ['nullable', 'string', 'max:1000'],
        ]);

        $carrito = collect(session()->get('carrito', []));

        if ($carrito->isEmpty()) {
            return redirect()
                ->route('carrito.index')
                ->with('error', 'Tu carrito está vacío.');
        }

        $checkout = $this->buildCheckoutData($carrito);

        if ($checkout['items']->isEmpty()) {
            session()->forget('carrito');

            return redirect()
                ->route('carrito.index')
                ->with('error', 'Los productos del carrito ya no están disponibles.');
        }

        $pedido = DB::transaction(function () use ($request, $user, $data, $checkout) {
            $direccion = $this->resolveDireccion($user->id, $data);

            $pedido = Pedido::create([
                'user_id' => $user->id,
                'direccion_id' => $direccion->id,
                'folio' => $this->generateFolio(),
                'estatus' => 'pendiente',
                'moneda' => 'MXN',
                'subtotal' => $checkout['resumen']['subtotal'],
                'descuento' => $checkout['resumen']['descuento'],
                'envio' => $checkout['resumen']['envio'],
                'impuesto' => 0,
                'total' => $checkout['resumen']['total'],
                'nombre_cliente' => $data['nombre_cliente'],
                'correo_cliente' => $data['correo_cliente'],
                'telefono_cliente' => $data['telefono_cliente'],
                'notas_cliente' => $data['notas_cliente'] ?? null,
            ]);

            foreach ($checkout['items'] as $item) {
                PedidoItem::create([
                    'pedido_id' => $pedido->id,
                    'producto_id' => $item['producto_id'],
                    'sku' => $item['sku'],
                    'nombre' => $item['nombre'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            $metodoPago = MetodoPago::query()->firstOrCreate(
                ['clave' => 'openpay'],
                [
                    'nombre' => 'Openpay',
                    'activo' => true,
                    'configuracion' => [],
                ]
            );

            Pago::create([
                'pedido_id' => $pedido->id,
                'metodo_pago_id' => $metodoPago->id,
                'estatus' => 'pendiente',
                'monto' => $checkout['resumen']['total'],
                'moneda' => 'MXN',
            ]);

            PedidoEstatusHistorial::create([
                'pedido_id' => $pedido->id,
                'user_id' => $request->user()?->id,
                'estatus' => 'pendiente',
                'comentario' => 'Pedido creado desde checkout. Pendiente de pago.',
            ]);

            return $pedido;
        });

        session()->forget('carrito');
        session()->forget('carrito_cupon');

        return redirect()
            ->route('checkout.gracias', $pedido)
            ->with('success', 'Tu pedido fue creado correctamente. El siguiente paso será integrar el pago.');
    }

    public function gracias(Pedido $pedido): Response {
        abort_unless($pedido->user_id === auth()->id(), 403);

        $pedido->load(['items', 'direccion', 'pagos']);

        return Inertia::render('Tienda/CheckoutGracias', [
            'pedido' => [
                'id' => $pedido->id,
                'folio' => $pedido->folio,
                'estatus' => $pedido->estatus,
                'subtotal' => (float) $pedido->subtotal,
                'descuento' => (float) $pedido->descuento,
                'envio' => (float) $pedido->envio,
                'total' => (float) $pedido->total,
                'moneda' => $pedido->moneda,
                'created_at' => $pedido->created_at?->toDateTimeString(),
                'items' => $pedido->items->map(fn (PedidoItem $item) => [
                    'id' => $item->id,
                    'nombre' => $item->nombre,
                    'sku' => $item->sku,
                    'cantidad' => (int) $item->cantidad,
                    'precio_unitario' => (float) $item->precio_unitario,
                    'subtotal' => (float) $item->subtotal,
                ]),
                'direccion' => $pedido->direccion ? [
                    'nombre_receptor' => $pedido->direccion->nombre_receptor,
                    'telefono' => $pedido->direccion->telefono,
                    'calle' => $pedido->direccion->calle,
                    'numero_exterior' => $pedido->direccion->numero_exterior,
                    'numero_interior' => $pedido->direccion->numero_interior,
                    'colonia' => $pedido->direccion->colonia,
                    'municipio' => $pedido->direccion->municipio,
                    'estado' => $pedido->direccion->estado,
                    'codigo_postal' => $pedido->direccion->codigo_postal,
                    'referencias' => $pedido->direccion->referencias,
                ] : null,
                'pago' => $pedido->pagos->sortByDesc('id')->first() ? [
                    'id' => $pedido->pagos->sortByDesc('id')->first()->id,
                    'estatus' => $pedido->pagos->sortByDesc('id')->first()->estatus,
                    'monto' => (float) $pedido->pagos->sortByDesc('id')->first()->monto,
                    'referencia_externa' => $pedido->pagos->sortByDesc('id')->first()->referencia_externa,
                    'autorizacion' => $pedido->pagos->sortByDesc('id')->first()->autorizacion,
                ] : null,
            ],
            'openpay' => [
                'merchant_id' => config('payments.gateways.openpay.merchant_id'),
                'public_key' => config('payments.gateways.openpay.public_key'),
                'sandbox' => (bool) config('payments.gateways.openpay.sandbox'),
            ],
        ]);
    }

    public function pagar(Request $request, Pedido $pedido, PaymentService $paymentService): RedirectResponse
    {
        abort_unless($pedido->user_id === auth()->id(), 403);

        $data = $request->validate([
            'token_id' => ['required', 'string', 'max:255'],
            'device_session_id' => ['required', 'string', 'max:255'],
        ]);

        $pedido->load(['pagos']);

        if ($pedido->estatus === 'pagado') {
            return back()->with('success', 'Este pedido ya se encuentra pagado.');
        }

        if ($pedido->estatus !== 'pendiente') {
            return back()->with('error', 'Este pedido ya no está disponible para pago.');
        }

        $metodoPago = MetodoPago::query()->firstOrCreate(
            ['clave' => 'openpay'],
            [
                'nombre' => 'Openpay',
                'activo' => true,
                'configuracion' => [],
            ]
        );

        $pago = $pedido->pagos()
            ->whereIn('estatus', ['pendiente', 'rechazado', 'error'])
            ->latest('id')
            ->first();

        if (! $pago) {
            $pago = Pago::create([
                'pedido_id' => $pedido->id,
                'metodo_pago_id' => $metodoPago->id,
                'estatus' => 'pendiente',
                'monto' => $pedido->total,
                'moneda' => $pedido->moneda ?: 'MXN',
            ]);
        }

        $result = $paymentService->charge(
            pedido: $pedido,
            pago: $pago,
            data: new PaymentChargeData(
                tokenId: $data['token_id'],
                deviceSessionId: $data['device_session_id'],
                amount: (float) $pedido->total,
                currency: $pedido->moneda ?: 'MXN',
                description: 'Pedido '.$pedido->folio,
                customerName: $pedido->nombre_cliente,
                customerEmail: $pedido->correo_cliente,
                customerPhone: $pedido->telefono_cliente,
            )
        );

        if ($result->successful && $result->status === 'aprobado') {
            return redirect()
                ->route('cliente.pedidos.show', $pedido)
                ->with('success', 'Pago aprobado correctamente.');
        }

        return back()->with('error', $result->message ?: 'No fue posible procesar el pago.');
    }

    private function resolveDireccion(int $userId, array $data): Direccion {
        if (
            (bool) $data['usar_direccion_existente'] === true &&
            ! empty($data['direccion_id'])
        ) {
            return Direccion::query()
                ->where('user_id', $userId)
                ->where('id', $data['direccion_id'])
                ->firstOrFail();
        }

        return Direccion::create([
            'user_id' => $userId,
            'alias' => $data['alias'] ?: 'Dirección de envío',
            'nombre_receptor' => $data['nombre_receptor'],
            'telefono' => $data['telefono'],
            'calle' => $data['calle'],
            'numero_exterior' => $data['numero_exterior'],
            'numero_interior' => $data['numero_interior'] ?? null,
            'colonia' => $data['colonia'],
            'municipio' => $data['municipio'],
            'estado' => $data['estado'],
            'pais' => $data['pais'] ?: 'México',
            'codigo_postal' => $data['codigo_postal'],
            'referencias' => $data['referencias'] ?? null,
            'predeterminada' => false,
        ]);
    }

    private function buildCheckoutData(Collection $carrito): array {
        $productoIds = $carrito
            ->pluck('producto_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        $productos = Producto::query()
            ->with([
                'imagenes:id,producto_id,ruta,orden',
                'ofertas:id,nombre,tipo,valor,aplica_a,categoria_id,marca_id,inicia_en,termina_en,activa',
            ])
            ->whereIn('id', $productoIds)
            ->where('activo', true)
            ->where('visible', true)
            ->get()
            ->keyBy('id');

        $items = $carrito
            ->map(function (array $item) use ($productos) {
                $productoId = (int) ($item['producto_id'] ?? 0);
                $cantidad = max(1, (int) ($item['cantidad'] ?? 1));

                /** @var Producto|null $producto */
                $producto = $productos->get($productoId);

                if (! $producto) {
                    return null;
                }

                $stock = max(0, (int) $producto->stock);

                if ($stock < 1) {
                    return null;
                }

                if ($cantidad > $stock) {
                    $cantidad = $stock;
                }

                $pricing = $this->resolveProductoPricing($producto);

                $precio = (float) $pricing['precio_final'];
                $precioOriginal = (float) $pricing['precio_original'];

                return [
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'slug' => $producto->slug,
                    'sku' => $producto->sku,
                    'imagen' => $this->resolveProductoImage($producto),
                    'precio' => round($precio, 2),
                    'precio_original' => round($precioOriginal, 2),
                    'cantidad' => $cantidad,
                    'stock' => $stock,
                    'subtotal_original' => round($precioOriginal * $cantidad, 2),
                    'subtotal' => round($precio * $cantidad, 2),
                    'descuento_oferta' => round(($precioOriginal - $precio) * $cantidad, 2),
                    'tiene_oferta' => (bool) $pricing['tiene_oferta'],
                    'oferta' => $pricing['oferta'],
                ];
            })
            ->filter()
            ->values();

        $subtotal = round((float) $items->sum('subtotal'), 2);
        $descuentoOfertas = round((float) $items->sum('descuento_oferta'), 2);
        $envio = 0.0;

        $cuponSession = session('carrito_cupon');
        $cuponAplicado = null;
        $descuentoCupon = 0.0;

        if ($cuponSession && ! empty($cuponSession['codigo'])) {
            $cupon = Cupon::query()
                ->where('codigo', mb_strtoupper(trim((string) $cuponSession['codigo'])))
                ->where('activo', true)
                ->first();

            if ($cupon) {
                $descuentoCupon = $this->calculateCuponDiscount($subtotal, $cupon);

                $cuponAplicado = [
                    'codigo' => $cupon->codigo,
                    'nombre' => $cupon->nombre,
                    'tipo' => $cupon->tipo,
                    'valor' => (float) $cupon->valor,
                    'descuento_aplicado' => $descuentoCupon,
                ];
            }
        }

        $descuento = round($descuentoOfertas + $descuentoCupon, 2);
        $total = round(max(0, $subtotal + $envio - $descuentoCupon), 2);

        return [
            'items' => $items,
            'cupon' => $cuponAplicado,
            'resumen' => [
                'subtotal' => round($subtotal, 2),
                'envio' => round($envio, 2),
                'descuento' => round($descuento, 2),
                'descuento_ofertas' => round($descuentoOfertas, 2),
                'descuento_cupon' => round($descuentoCupon, 2),
                'total' => round($total, 2),
                'total_productos' => (int) $items->sum('cantidad'),
            ],
        ];
    }

    private function generateFolio(): string {
        do {
            $folio = 'ML-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Pedido::query()->where('folio', $folio)->exists());

        return $folio;
    }

    private function resolveProductoPricing(Producto $producto): array {
        $precioOriginal = round((float) $producto->precio, 2);
        $oferta = $this->resolveOfertaForProducto($producto);

        if (! $oferta) {
            return [
                'precio_original' => $precioOriginal,
                'precio_final' => $precioOriginal,
                'descuento_oferta' => 0.0,
                'tiene_oferta' => false,
                'oferta' => null,
            ];
        }

        $tipo = trim(mb_strtolower((string) $oferta->tipo));
        $tipo = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', ' '],
            ['a', 'e', 'i', 'o', 'u', '_'],
            $tipo
        );

        $precioFinal = $precioOriginal;

        if ($tipo === 'porcentaje') {
            $precioFinal = $precioOriginal - ($precioOriginal * ((float) $oferta->valor / 100));
        } elseif (in_array($tipo, ['monto_fijo', 'montofijo'], true)) {
            $precioFinal = $precioOriginal - (float) $oferta->valor;
        }

        $precioFinal = max(0, round($precioFinal, 2));
        $descuentoOferta = round($precioOriginal - $precioFinal, 2);

        if ($descuentoOferta <= 0) {
            return [
                'precio_original' => $precioOriginal,
                'precio_final' => $precioOriginal,
                'descuento_oferta' => 0.0,
                'tiene_oferta' => false,
                'oferta' => null,
            ];
        }

        return [
            'precio_original' => $precioOriginal,
            'precio_final' => $precioFinal,
            'descuento_oferta' => $descuentoOferta,
            'tiene_oferta' => true,
            'oferta' => [
                'id' => $oferta->id,
                'nombre' => $oferta->nombre,
                'tipo' => $oferta->tipo,
                'valor' => (float) $oferta->valor,
            ],
        ];
    }

    private function resolveOfertaForProducto(Producto $producto): ?Oferta {
        $now = now();

        $ofertasProducto = $producto->ofertas
            ->filter(fn (Oferta $oferta) => $this->isOfertaActiva($oferta, $now))
            ->sortByDesc('id');

        if ($ofertasProducto->isNotEmpty()) {
            return $ofertasProducto->first();
        }

        $ofertas = collect();

        $ofertaCategoria = Oferta::query()
            ->where('activa', true)
            ->where('aplica_a', 'categoria')
            ->where('categoria_id', $producto->categoria_id)
            ->where(function ($query) use ($now) {
                $query->whereNull('inicia_en')->orWhere('inicia_en', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('termina_en')->orWhere('termina_en', '>=', $now);
            })
            ->latest('id')
            ->first();

        if ($ofertaCategoria) {
            $ofertas->push($ofertaCategoria);
        }

        $ofertaMarca = Oferta::query()
            ->where('activa', true)
            ->where('aplica_a', 'marca')
            ->where('marca_id', $producto->marca_id)
            ->where(function ($query) use ($now) {
                $query->whereNull('inicia_en')->orWhere('inicia_en', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('termina_en')->orWhere('termina_en', '>=', $now);
            })
            ->latest('id')
            ->first();

        if ($ofertaMarca) {
            $ofertas->push($ofertaMarca);
        }

        if ($ofertas->isEmpty()) {
            return null;
        }

        return $ofertas
            ->sortByDesc(fn (Oferta $oferta) => $this->calculateDiscountAmount($producto, $oferta))
            ->first();
    }

    private function calculateDiscountAmount(Producto $producto, Oferta $oferta): float
    {
        $precio = (float) $producto->precio;
        $tipo = trim(mb_strtolower((string) $oferta->tipo));
        $tipo = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', ' '],
            ['a', 'e', 'i', 'o', 'u', '_'],
            $tipo
        );

        if ($tipo === 'porcentaje') {
            return round($precio * ((float) $oferta->valor / 100), 2);
        }

        if (in_array($tipo, ['monto_fijo', 'montofijo'], true)) {
            return round(min($precio, (float) $oferta->valor), 2);
        }

        return 0;
    }

    private function calculateCuponDiscount(float $subtotal, Cupon $cupon): float {
        $tipo = trim(mb_strtolower((string) $cupon->tipo));
        $tipo = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', ' '],
            ['a', 'e', 'i', 'o', 'u', '_'],
            $tipo
        );

        $discount = 0.0;

        if ($tipo === 'porcentaje') {
            $discount = $subtotal * ((float) $cupon->valor / 100);
        } elseif (in_array($tipo, ['monto_fijo', 'montofijo'], true)) {
            $discount = (float) $cupon->valor;
        }

        return round(min($subtotal, max(0, $discount)), 2);
    }

    private function isOfertaActiva(Oferta $oferta, $now): bool {
        if (! $oferta->activa) {
            return false;
        }

        if ($oferta->inicia_en && $oferta->inicia_en->gt($now)) {
            return false;
        }

        if ($oferta->termina_en && $oferta->termina_en->lt($now)) {
            return false;
        }

        return true;
    }

    private function resolveProductoImage(Producto $producto): ?string {
        $primeraImagen = $producto->imagenes
            ->sortBy([
                ['orden', 'asc'],
                ['id', 'asc'],
            ])
            ->pluck('ruta')
            ->filter()
            ->first();

        return $this->resolveImageUrl($primeraImagen ?: $producto->imagen_principal);
    }

    private function resolveImageUrl(?string $path): ?string {
        if (! $path) {
            return null;
        }

        $path = trim($path);

        if ($path === '') {
            return null;
        }

        if (
            str_starts_with($path, 'http://') ||
            str_starts_with($path, 'https://')
        ) {
            return $path;
        }

        if (str_starts_with($path, '/storage/')) {
            return $path;
        }

        if (str_starts_with($path, 'storage/')) {
            return '/'.$path;
        }

        return Storage::url($path);
    }

}
