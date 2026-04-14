<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
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
            ->with(['imagenes:id,producto_id,ruta,orden'])
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

                $precio = (float) $producto->precio;
                $stock = max(0, (int) $producto->stock);

                if ($stock < 1) {
                    return null;
                }

                if ($cantidad > $stock) {
                    $cantidad = $stock;
                }

                return [
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'slug' => $producto->slug,
                    'sku' => $producto->sku,
                    'imagen' => $this->resolveProductoImage($producto),
                    'precio' => round($precio, 2),
                    'precio_comparacion' => $producto->precio_comparacion !== null
                        ? round((float) $producto->precio_comparacion, 2)
                        : null,
                    'cantidad' => $cantidad,
                    'stock' => $stock,
                    'subtotal' => round($precio * $cantidad, 2),
                ];
            })
            ->filter()
            ->values();

        $this->syncSessionFromItems($items);

        $subtotal = (float) $items->sum('subtotal');
        $envio = 0.0;
        $descuento = 0.0;
        $total = $subtotal + $envio - $descuento;

        return Inertia::render('Tienda/Carrito', [
            'items' => $items,
            'resumen' => [
                'subtotal' => round($subtotal, 2),
                'envio' => round($envio, 2),
                'descuento' => round($descuento, 2),
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
            ->with(['imagenes:id,producto_id,ruta,orden'])
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

        $precio = (float) $producto->precio;

        $carrito[$key] = [
            'producto_id' => $producto->id,
            'nombre' => $producto->nombre,
            'slug' => $producto->slug,
            'sku' => $producto->sku,
            'imagen' => $this->resolveProductoImage($producto),
            'precio' => round($precio, 2),
            'precio_comparacion' => $producto->precio_comparacion !== null
                ? round((float) $producto->precio_comparacion, 2)
                : null,
            'cantidad' => $cantidadFinal,
            'stock' => (int) $producto->stock,
            'subtotal' => round($precio * $cantidadFinal, 2),
        ];

        session()->put('carrito', $carrito);

        return back()->with('success', $producto->nombre.' se agregó correctamente al carrito.');
    }

    public function actualizar(Request $request, Producto $producto): RedirectResponse
    {
        $data = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $producto->load(['imagenes:id,producto_id,ruta,orden']);

        $carrito = session()->get('carrito', []);
        $key = (string) $producto->id;

        if (! isset($carrito[$key])) {
            return back()->with('error', 'El producto no está en el carrito.');
        }

        $cantidad = (int) $data['cantidad'];

        if ($cantidad > (int) $producto->stock) {
            return back()->with('error', 'La cantidad solicitada excede el stock disponible.');
        }

        $precio = (float) $producto->precio;

        $carrito[$key] = [
            'producto_id' => $producto->id,
            'nombre' => $producto->nombre,
            'slug' => $producto->slug,
            'sku' => $producto->sku,
            'imagen' => $this->resolveProductoImage($producto),
            'precio' => round($precio, 2),
            'precio_comparacion' => $producto->precio_comparacion !== null
                ? round((float) $producto->precio_comparacion, 2)
                : null,
            'cantidad' => $cantidad,
            'stock' => (int) $producto->stock,
            'subtotal' => round($precio * $cantidad, 2),
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
