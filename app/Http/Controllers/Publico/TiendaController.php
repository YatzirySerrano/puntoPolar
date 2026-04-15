<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Categoria;
use App\Models\Oferta;
use App\Models\Producto;
use Illuminate\Http\Request;
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
            ->get(['id', 'nombre', 'slug', 'imagen'])
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
                'ofertas:id,nombre,tipo,valor,aplica_a,categoria_id,marca_id,inicia_en,termina_en,activa',
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
            ->get(['id', 'titulo', 'descripcion', 'imagen', 'url', 'orden'])
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
            'banners' => $banners,
        ]);
    }

    public function catalogo(Request $request): Response
    {
        $categorias = Categoria::query()
            ->where('activa', true)
            ->orderBy('orden')
            ->get(['id', 'nombre', 'slug', 'imagen'])
            ->map(fn (Categoria $categoria) => [
                'id' => $categoria->id,
                'nombre' => $categoria->nombre,
                'slug' => $categoria->slug,
                'imagen' => $this->resolveImageUrl($categoria->imagen),
            ])
            ->values();

        $productos = Producto::query()
            ->with([
                'categoria:id,nombre,slug',
                'marca:id,nombre,slug',
                'imagenes:id,producto_id,ruta,orden',
                'ofertas:id,nombre,tipo,valor,aplica_a,categoria_id,marca_id,inicia_en,termina_en,activa',
            ])
            ->where('activo', true)
            ->where('visible', true)
            ->where('stock', '>', 0)
            ->latest()
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

        return Inertia::render('Tienda/Catalogo', [
            'categorias' => $categorias,
            'productos' => $productos,
            'filtros' => [
                'buscar' => $request->input('buscar'),
                'categoria' => $request->input('categoria'),
            ],
        ]);
    }

    public function show(Producto $producto): Response
    {
        $producto->load([
            'categoria:id,nombre,slug',
            'marca:id,nombre,slug',
            'imagenes:id,producto_id,ruta,orden',
            'ofertas:id,nombre,tipo,valor,aplica_a,categoria_id,marca_id,inicia_en,termina_en,activa',
        ]);

        $relacionados = Producto::query()
            ->with([
                'categoria:id,nombre,slug',
                'marca:id,nombre,slug',
                'imagenes:id,producto_id,ruta,orden',
                'ofertas:id,nombre,tipo,valor,aplica_a,categoria_id,marca_id,inicia_en,termina_en,activa',
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
        $pricing = $this->resolveProductoPricing($producto);

        return [
            'id' => $producto->id,
            'categoria_id' => $producto->categoria_id,
            'marca_id' => $producto->marca_id,
            'sku' => $producto->sku,
            'nombre' => $producto->nombre,
            'slug' => $producto->slug,
            'descripcion' => $producto->descripcion,
            'precio' => $pricing['precio_final'],
            'precio_original' => $pricing['precio_original'],
            'precio_final' => $pricing['precio_final'],
            'descuento_oferta' => $pricing['descuento_oferta'],
            'tiene_oferta' => $pricing['tiene_oferta'],
            'oferta' => $pricing['oferta'],
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
