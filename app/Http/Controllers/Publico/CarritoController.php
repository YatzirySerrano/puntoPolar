<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CarritoController extends Controller
{
    public function index(): Response
    {
        $carrito = collect(session()->get('carrito', []))
            ->map(function (array $item) {
                $item['imagen'] = $this->resolveImageUrl($item['imagen'] ?? null);
                $item['precio'] = round((float) ($item['precio'] ?? 0), 2);
                $item['precio_comparacion'] = isset($item['precio_comparacion'])
                    ? round((float) $item['precio_comparacion'], 2)
                    : null;
                $item['subtotal'] = round((float) ($item['subtotal'] ?? 0), 2);
                $item['cantidad'] = (int) ($item['cantidad'] ?? 0);
                $item['stock'] = (int) ($item['stock'] ?? 0);

                return $item;
            })
            ->values();

        $subtotal = $carrito->sum('subtotal');
        $envio = 0;
        $descuento = 0;
        $total = $subtotal + $envio - $descuento;
        $totalProductos = $carrito->sum('cantidad');

        return Inertia::render('Tienda/Carrito', [
            'items' => $carrito,
            'resumen' => [
                'subtotal' => round($subtotal, 2),
                'envio' => round($envio, 2),
                'descuento' => round($descuento, 2),
                'total' => round($total, 2),
                'total_productos' => (int) $totalProductos,
            ],
        ]);
    }

    public function agregar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'cantidad' => ['nullable', 'integer', 'min:1'],
        ]);

        $cantidad = $data['cantidad'] ?? 1;

        $producto = Producto::query()
            ->where('activo', true)
            ->where('visible', true)
            ->findOrFail($data['producto_id']);

        if ($producto->stock < $cantidad) {
            return back()->with('error', 'No hay stock suficiente para '.$producto->nombre.'.');
        }

        $carrito = session()->get('carrito', []);
        $key = (string) $producto->id;

        if (isset($carrito[$key])) {
            $nuevaCantidad = $carrito[$key]['cantidad'] + $cantidad;

            if ($nuevaCantidad > $producto->stock) {
                return back()->with('error', 'La cantidad solicitada excede el stock disponible.');
            }

            $carrito[$key]['cantidad'] = $nuevaCantidad;
            $carrito[$key]['stock'] = (int) $producto->stock;
            $carrito[$key]['precio'] = (float) $producto->precio;
            $carrito[$key]['precio_comparacion'] = $producto->precio_comparacion !== null
                ? (float) $producto->precio_comparacion
                : null;
            $carrito[$key]['subtotal'] = round($nuevaCantidad * $carrito[$key]['precio'], 2);
        } else {
            $carrito[$key] = [
                'producto_id' => $producto->id,
                'nombre' => $producto->nombre,
                'slug' => $producto->slug,
                'sku' => $producto->sku,
                'imagen' => $producto->imagen_principal,
                'precio' => (float) $producto->precio,
                'precio_comparacion' => $producto->precio_comparacion !== null
                    ? (float) $producto->precio_comparacion
                    : null,
                'cantidad' => $cantidad,
                'stock' => (int) $producto->stock,
                'subtotal' => round($producto->precio * $cantidad, 2),
            ];
        }

        session()->put('carrito', $carrito);

        return back()->with('success', $producto->nombre.' se agregó correctamente al carrito.');
    }

    public function actualizar(Request $request, Producto $producto): RedirectResponse
    {
        $data = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $carrito = session()->get('carrito', []);
        $key = (string) $producto->id;

        if (! isset($carrito[$key])) {
            return back()->with('error', 'El producto no está en el carrito.');
        }

        if ($data['cantidad'] > $producto->stock) {
            return back()->with('error', 'La cantidad solicitada excede el stock disponible.');
        }

        $carrito[$key]['cantidad'] = (int) $data['cantidad'];
        $carrito[$key]['stock'] = (int) $producto->stock;
        $carrito[$key]['precio'] = (float) $producto->precio;
        $carrito[$key]['precio_comparacion'] = $producto->precio_comparacion !== null
            ? (float) $producto->precio_comparacion
            : null;
        $carrito[$key]['subtotal'] = round($carrito[$key]['precio'] * $data['cantidad'], 2);

        session()->put('carrito', $carrito);

        return back()->with('success', 'Actualizamos la cantidad de '.$producto->nombre.'.');
    }

    public function eliminar(Producto $producto): RedirectResponse
    {
        $carrito = session()->get('carrito', []);
        unset($carrito[(string) $producto->id]);
        session()->put('carrito', $carrito);

        return back()->with('success', $producto->nombre.' fue eliminado del carrito.');
    }

    public function vaciar(): RedirectResponse
    {
        session()->forget('carrito');

        return back()->with('success', 'Tu carrito se vació correctamente.');
    }

    private function resolveImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/storage/')) {
            return $path;
        }

        return Storage::url($path);
    }
}
