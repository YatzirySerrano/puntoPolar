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
            ])
            ->map(fn (Categoria $categoria) => [
                'id' => $categoria->id,
                'nombre' => $categoria->nombre,
                'slug' => $categoria->slug,
                'imagen' => $this->resolveImageUrl($categoria->imagen),
            ])
            ->values();

        $destacados = Producto::query()
            ->with([
                'categoria:id,nombre,slug',
                'marca:id,nombre,slug',
                'imagenes:id,producto_id,ruta,orden',
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
            ])
            ->map(fn (Producto $producto) => $this->mapProductoPublico($producto))
            ->values();

        $productos = Producto::query()
            ->with([
                'categoria:id,nombre,slug',
                'marca:id,nombre,slug',
                'imagenes:id,producto_id,ruta,orden',
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
            ])
            ->map(fn (Producto $producto) => $this->mapProductoPublico($producto))
            ->values();

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
                    'imagen' => $this->resolveImageUrl($banner->imagen),
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
            'imagenes:id,producto_id,ruta,orden',
        ]);

        $relacionados = Producto::query()
            ->with([
                'categoria:id,nombre,slug',
                'marca:id,nombre,slug',
                'imagenes:id,producto_id,ruta,orden',
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
            ])
            ->map(fn (Producto $item) => $this->mapProductoPublico($item))
            ->values();

        return Inertia::render('Tienda/Show', [
            'producto' => $this->mapProductoPublico($producto),
            'relacionados' => $relacionados,
        ]);
    }

    private function mapProductoPublico(Producto $producto): array
    {
        $imagenes = $producto->imagenes
            ->sortBy([
                ['orden', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        $primeraImagen = $imagenes->first()?->ruta;

        return [
            'id' => $producto->id,
            'categoria_id' => $producto->categoria_id,
            'marca_id' => $producto->marca_id,
            'sku' => $producto->sku,
            'nombre' => $producto->nombre,
            'slug' => $producto->slug,
            'descripcion' => $producto->descripcion,
            'precio' => (float) $producto->precio,
            'precio_comparacion' => $producto->precio_comparacion !== null
                ? (float) $producto->precio_comparacion
                : null,
            'stock' => (int) $producto->stock,
            'destacado' => (bool) $producto->destacado,
            'imagen_principal' => $this->resolveImageUrl($primeraImagen ?: $producto->imagen_principal),
            'categoria' => $producto->categoria
                ? [
                    'id' => $producto->categoria->id,
                    'nombre' => $producto->categoria->nombre,
                    'slug' => $producto->categoria->slug,
                ]
                : null,
            'marca' => $producto->marca
                ? [
                    'id' => $producto->marca->id,
                    'nombre' => $producto->marca->nombre,
                    'slug' => $producto->marca->slug,
                ]
                : null,
        ];
    }

    private function resolveImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (
            str_starts_with($path, 'http://') ||
            str_starts_with($path, 'https://') ||
            str_starts_with($path, '/storage/')
        ) {
            return $path;
        }

        return Storage::url($path);
    }
}
