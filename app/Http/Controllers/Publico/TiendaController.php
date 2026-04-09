<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Producto;
use Inertia\Inertia;
use Inertia\Response;

class TiendaController extends Controller
{
    public function index(): Response
    {
        $categorias = Categoria::query()
            ->where('activa', true)
            ->orderBy('orden')
            ->get([
                'id',
                'nombre',
                'slug',
                'imagen',
            ]);

        $destacados = Producto::query()
            ->with(['categoria:id,nombre,slug', 'marca:id,nombre,slug'])
            ->where('activo', true)
            ->where('visible', true)
            ->where('stock', '>', 0)
            ->where('destacado', true)
            ->latest()
            ->take(8)
            ->get([
                'id',
                'categoria_id',
                'marca_id',
                'sku',
                'nombre',
                'slug',
                'descripcion',
                'precio',
                'precio_comparacion',
                'stock',
                'imagen_principal',
                'destacado',
            ]);

        $productos = Producto::query()
            ->with(['categoria:id,nombre,slug', 'marca:id,nombre,slug'])
            ->where('activo', true)
            ->where('visible', true)
            ->where('stock', '>', 0)
            ->latest()
            ->take(12)
            ->get([
                'id',
                'categoria_id',
                'marca_id',
                'sku',
                'nombre',
                'slug',
                'descripcion',
                'precio',
                'precio_comparacion',
                'stock',
                'imagen_principal',
                'destacado',
            ]);

        return Inertia::render('Tienda/Index', [
            'categorias' => $categorias,
            'destacados' => $destacados,
            'productos' => $productos,
            'filtros' => [
                'buscar' => request('buscar'),
                'categoria' => request('categoria'),
            ],
            'bannerPrincipal' => [
                'titulo' => 'La excelencia del titanio en tu cocina',
                'subtitulo' => 'Durabilidad y precisión en cada preparación.',
                'boton' => 'Saber más',
            ],
        ]);
    }

    public function show(Producto $producto): Response
    {
        $producto->load([
            'categoria:id,nombre,slug',
            'marca:id,nombre,slug',
        ]);

        $relacionados = Producto::query()
            ->where('activo', true)
            ->where('visible', true)
            ->where('stock', '>', 0)
            ->where('id', '!=', $producto->id)
            ->where('categoria_id', $producto->categoria_id)
            ->take(4)
            ->get([
                'id',
                'categoria_id',
                'marca_id',
                'sku',
                'nombre',
                'slug',
                'descripcion',
                'precio',
                'precio_comparacion',
                'stock',
                'imagen_principal',
                'destacado',
            ]);

        return Inertia::render('Tienda/Show', [
            'producto' => $producto,
            'relacionados' => $relacionados,
        ]);
    }
}
