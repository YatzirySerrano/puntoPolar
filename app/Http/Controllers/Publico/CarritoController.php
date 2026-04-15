<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
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
            return Inertia::render('Tienda/Carrito', [
                'items' => [],
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

        $subtotal = (float) $items->sum('subtotal');
        $descuentoOfertas = (float) $items->sum('descuento_oferta');
        $descuentoCupon = round((float) data_get(session('carrito_cupon', []), 'descuento_aplicado', 0), 2);
        $envio = 0.0;
        $descuento = round($descuentoOfertas + $descuentoCupon, 2);
        $total = round(max(0, $subtotal + $envio - $descuentoCupon), 2);

        return Inertia::render('Tienda/Carrito', [
            'items' => $items,
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

        return back()->with('success', $producto->nombre.' se quitó del carrito.');
    }

    public function vaciar(): RedirectResponse
    {
        session()->forget('carrito');

        return back()->with('success', 'Tu carrito se vació correctamente.');
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

        $precioFinal = $precioOriginal;

        if ($oferta->tipo === 'porcentaje') {
            $precioFinal = $precioOriginal - ($precioOriginal * ((float) $oferta->valor / 100));
        } elseif ($oferta->tipo === 'monto_fijo') {
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
            return $ofertaCategoria;
        }

        return Oferta::query()
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
