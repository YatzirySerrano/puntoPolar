<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Support\Facades\Storage;
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
            ->with([
                'categoria:id,nombre,slug',
                'marca:id,nombre,slug',
            ])
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
            ->with([
                'categoria:id,nombre,slug',
                'marca:id,nombre,slug',
            ])
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

        $banners = Banner::query()
            ->where('activo', true)
            ->where(function ($query) {
                $query->whereNull('inicia_en')
                    ->orWhere('inicia_en', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('termina_en')
                    ->orWhere('termina_en', '>=', now());
            })
            ->orderBy('orden')
            ->orderBy('id')
            ->get([
                'id',
                'titulo',
                'descripcion',
                'imagen',
                'url',
                'orden',
            ])
            ->map(function (Banner $banner) {
                return [
                    'id' => $banner->id,
                    'titulo' => $banner->titulo,
                    'descripcion' => $banner->descripcion,
                    'imagen' => $banner->imagen
                        ? Storage::url($banner->imagen)
                        : null,
                    'url' => $banner->url,
                    'orden' => $banner->orden,
                ];
            })
            ->values();

        return Inertia::render('Tienda/Index', [
            'categorias' => $categorias,
            'destacados' => $destacados,
            'productos' => $productos,
            'banners' => $banners,
            'filtros' => [
                'buscar' => request('buscar'),
                'categoria' => request('categoria'),
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
            ->with([
                'categoria:id,nombre,slug',
                'marca:id,nombre,slug',
            ])
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
