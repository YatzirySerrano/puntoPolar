<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Cupon;
use App\Models\Oferta;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CarritoController extends Controller
{
    public function index(): Response
    {
        $carrito = collect(session()->get('carrito', []));

        if ($carrito->isEmpty()) {
            session()->forget('carrito_cupon');

            return Inertia::render('Tienda/Carrito', [
                'items' => [],
                'cupon' => null,
                'resumen' => [
                    'subtotal' => 0,
                    'envio' => 0,
                    'descuento' => 0,
                    'descuento_ofertas' => 0,
                    'descuento_cupon' => 0,
                    'total' => 0,
                    'total_productos' => 0,
                ],
            ]);
        }

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
            ->get()
            ->keyBy('id');

        $items = $carrito
            ->map(function (array $item) use ($productos) {
                $productoId = (int) ($item['producto_id'] ?? 0);
                $cantidad = max(1, (int) ($item['cantidad'] ?? 1));

                /** @var \App\Models\Producto|null $producto */
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
                    'precio_comparacion' => $producto->precio_comparacion !== null
                        ? round((float) $producto->precio_comparacion, 2)
                        : null,
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

        $this->syncSessionFromItems($items);

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

                session()->put('carrito_cupon', $cuponAplicado);
            } else {
                session()->forget('carrito_cupon');
            }
        }

        $descuento = round($descuentoOfertas + $descuentoCupon, 2);
        $total = round(max(0, $subtotal + $envio - $descuentoCupon), 2);

        return Inertia::render('Tienda/Carrito', [
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
        ]);
    }

    public function agregar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'cantidad' => ['nullable', 'integer', 'min:1'],
        ]);

        $cantidad = max(1, (int) ($data['cantidad'] ?? 1));

        $producto = Producto::query()
            ->with([
                'imagenes:id,producto_id,ruta,orden',
                'ofertas:id,nombre,tipo,valor,aplica_a,categoria_id,marca_id,inicia_en,termina_en,activa',
            ])
            ->where('activo', true)
            ->where('visible', true)
            ->findOrFail($data['producto_id']);

        if ((int) $producto->stock < $cantidad) {
            return back()->with('error', 'No hay stock suficiente para '.$producto->nombre.'.');
        }

        $carrito = session()->get('carrito', []);
        $key = (string) $producto->id;

        if (isset($carrito[$key])) {
            $nuevaCantidad = ((int) $carrito[$key]['cantidad']) + $cantidad;

            if ($nuevaCantidad > (int) $producto->stock) {
                return back()->with('error', 'La cantidad solicitada excede el stock disponible.');
            }

            $cantidadFinal = $nuevaCantidad;
        } else {
            $cantidadFinal = $cantidad;
        }

        $pricing = $this->resolveProductoPricing($producto);
        $precio = (float) $pricing['precio_final'];
        $precioOriginal = (float) $pricing['precio_original'];

        $carrito[$key] = [
            'producto_id' => $producto->id,
            'nombre' => $producto->nombre,
            'slug' => $producto->slug,
            'sku' => $producto->sku,
            'imagen' => $this->resolveProductoImage($producto),
            'precio' => round($precio, 2),
            'precio_original' => round($precioOriginal, 2),
            'precio_comparacion' => $producto->precio_comparacion !== null
                ? round((float) $producto->precio_comparacion, 2)
                : null,
            'cantidad' => $cantidadFinal,
            'stock' => (int) $producto->stock,
            'subtotal_original' => round($precioOriginal * $cantidadFinal, 2),
            'subtotal' => round($precio * $cantidadFinal, 2),
            'descuento_oferta' => round(($precioOriginal - $precio) * $cantidadFinal, 2),
            'tiene_oferta' => (bool) $pricing['tiene_oferta'],
            'oferta' => $pricing['oferta'],
        ];

        session()->put('carrito', $carrito);

        return back()->with('success', $producto->nombre.' se agregó correctamente al carrito.');
    }

    public function actualizar(Request $request, Producto $producto): RedirectResponse
    {
        $data = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $producto->load([
            'imagenes:id,producto_id,ruta,orden',
            'ofertas:id,nombre,tipo,valor,aplica_a,categoria_id,marca_id,inicia_en,termina_en,activa',
        ]);

        $carrito = session()->get('carrito', []);
        $key = (string) $producto->id;

        if (! isset($carrito[$key])) {
            return back()->with('error', 'El producto no está en el carrito.');
        }

        $cantidad = (int) $data['cantidad'];

        if ($cantidad > (int) $producto->stock) {
            return back()->with('error', 'La cantidad solicitada excede el stock disponible.');
        }

        $pricing = $this->resolveProductoPricing($producto);
        $precio = (float) $pricing['precio_final'];
        $precioOriginal = (float) $pricing['precio_original'];

        $carrito[$key] = [
            'producto_id' => $producto->id,
            'nombre' => $producto->nombre,
            'slug' => $producto->slug,
            'sku' => $producto->sku,
            'imagen' => $this->resolveProductoImage($producto),
            'precio' => round($precio, 2),
            'precio_original' => round($precioOriginal, 2),
            'precio_comparacion' => $producto->precio_comparacion !== null
                ? round((float) $producto->precio_comparacion, 2)
                : null,
            'cantidad' => $cantidad,
            'stock' => (int) $producto->stock,
            'subtotal_original' => round($precioOriginal * $cantidad, 2),
            'subtotal' => round($precio * $cantidad, 2),
            'descuento_oferta' => round(($precioOriginal - $precio) * $cantidad, 2),
            'tiene_oferta' => (bool) $pricing['tiene_oferta'],
            'oferta' => $pricing['oferta'],
        ];

        session()->put('carrito', $carrito);

        return back()->with('success', 'Se actualizó la cantidad de '.$producto->nombre.'.');
    }

    public function eliminar(Producto $producto): RedirectResponse
    {
        $carrito = session()->get('carrito', []);
        unset($carrito[(string) $producto->id]);
        session()->put('carrito', $carrito);

        if (empty($carrito)) {
            session()->forget('carrito_cupon');
        }

        return back()->with('success', $producto->nombre.' se quitó del carrito.');
    }

    public function vaciar(): RedirectResponse
    {
        session()->forget('carrito');
        session()->forget('carrito_cupon');

        return back()->with('success', 'Tu carrito se vació correctamente.');
    }

    public function aplicarCupon(Request $request): RedirectResponse
    {
        $codigo = trim((string) $request->input('codigo', ''));

        if ($codigo === '') {
            return back()->with('error', 'Debes ingresar un código de cupón.');
        }

        $carrito = collect(session()->get('carrito', []));

        if ($carrito->isEmpty()) {
            return back()->with('error', 'Tu carrito está vacío.');
        }

        $cupon = Cupon::query()
            ->where('codigo', mb_strtoupper($codigo))
            ->where('activo', true)
            ->first();

        if (! $cupon) {
            return back()->with('error', 'El cupón no existe o no está disponible.');
        }

        $subtotal = round(
            (float) $carrito->sum(fn ($item) => (float) ($item['subtotal'] ?? 0)),
            2
        );

        if ($subtotal <= 0) {
            return back()->with('error', 'No se pudo aplicar el cupón al carrito actual.');
        }

        $descuentoAplicado = $this->calculateCuponDiscount($subtotal, $cupon);

        if ($descuentoAplicado <= 0) {
            return back()->with('error', 'El cupón no genera descuento para este carrito.');
        }

        session()->put('carrito_cupon', [
            'codigo' => $cupon->codigo,
            'nombre' => $cupon->nombre,
            'tipo' => $cupon->tipo,
            'valor' => (float) $cupon->valor,
            'descuento_aplicado' => $descuentoAplicado,
        ]);

        return back()->with('success', 'Cupón aplicado correctamente.');
    }

    public function quitarCupon(): RedirectResponse
    {
        session()->forget('carrito_cupon');

        return back()->with('success', 'Cupón eliminado del carrito.');
    }

    private function syncSessionFromItems(Collection $items): void
    {
        $carrito = $items
            ->mapWithKeys(fn (array $item) => [
                (string) $item['producto_id'] => $item,
            ])
            ->all();

        session()->put('carrito', $carrito);
    }

    private function resolveProductoPricing(Producto $producto): array
    {
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

    private function resolveOfertaForProducto(Producto $producto): ?Oferta
    {
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

    private function calculateCuponDiscount(float $subtotal, Cupon $cupon): float
    {
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

    private function isOfertaActiva(Oferta $oferta, $now): bool
    {
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

    private function resolveProductoImage(Producto $producto): ?string
    {
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

    private function resolveImageUrl(?string $path): ?string
    {
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
