<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CarritoController extends Controller
{
    public function index(): Response
    {
        $carrito = session()->get('carrito', []);

        $items = collect($carrito)->values();
        $subtotal = $items->sum('subtotal');
        $envio = 0;
        $descuento = 0;
        $total = $subtotal + $envio - $descuento;

        return Inertia::render('Tienda/Carrito', [
            'items' => $items,
            'resumen' => [
                'subtotal' => round($subtotal, 2),
                'envio' => round($envio, 2),
                'descuento' => round($descuento, 2),
                'total' => round($total, 2),
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
            return back()->with('error', 'No hay stock suficiente disponible.');
        }

        $carrito = session()->get('carrito', []);
        $key = (string) $producto->id;

        if (isset($carrito[$key])) {
            $nuevaCantidad = $carrito[$key]['cantidad'] + $cantidad;

            if ($nuevaCantidad > $producto->stock) {
                return back()->with('error', 'La cantidad solicitada excede el stock disponible.');
            }

            $carrito[$key]['cantidad'] = $nuevaCantidad;
            $carrito[$key]['subtotal'] = round($nuevaCantidad * $carrito[$key]['precio'], 2);
        } else {
            $carrito[$key] = [
                'producto_id' => $producto->id,
                'nombre' => $producto->nombre,
                'slug' => $producto->slug,
                'sku' => $producto->sku,
                'imagen' => $producto->imagen_principal,
                'precio' => (float) $producto->precio,
                'cantidad' => $cantidad,
                'stock' => (int) $producto->stock,
                'subtotal' => round($producto->precio * $cantidad, 2),
            ];
        }

        session()->put('carrito', $carrito);

        return back()->with('success', 'Producto agregado al carrito.');
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

        $carrito[$key]['cantidad'] = $data['cantidad'];
        $carrito[$key]['subtotal'] = round($carrito[$key]['precio'] * $data['cantidad'], 2);

        session()->put('carrito', $carrito);

        return back()->with('success', 'Cantidad actualizada.');
    }

    public function eliminar(Producto $producto): RedirectResponse
    {
        $carrito = session()->get('carrito', []);
        unset($carrito[(string) $producto->id]);
        session()->put('carrito', $carrito);

        return back()->with('success', 'Producto eliminado del carrito.');
    }

    public function vaciar(): RedirectResponse
    {
        session()->forget('carrito');

        return back()->with('success', 'Carrito vaciado.');
    }
}
