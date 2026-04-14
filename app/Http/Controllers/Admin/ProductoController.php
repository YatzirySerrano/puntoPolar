<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductoController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $categoriaId = $request->input('categoria_id');
        $marcaId = $request->input('marca_id');
        $status = (string) $request->input('status', 'all');
        $destacado = (string) $request->input('destacado', 'all');

        $productos = Producto::query()
            ->with([
                'categoria:id,nombre',
                'marca:id,nombre',
                'imagenes:id,producto_id,ruta,orden',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('nombre', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%");
                });
            })
            ->when($categoriaId, fn ($query) => $query->where('categoria_id', $categoriaId))
            ->when($marcaId, fn ($query) => $query->where('marca_id', $marcaId))
            ->when($status === 'active', fn ($query) => $query->where('activo', true))
            ->when($status === 'inactive', fn ($query) => $query->where('activo', false))
            ->when($status === 'visible', fn ($query) => $query->where('visible', true))
            ->when($status === 'hidden', fn ($query) => $query->where('visible', false))
            ->when($destacado === 'yes', fn ($query) => $query->where('destacado', true))
            ->when($destacado === 'no', fn ($query) => $query->where('destacado', false))
            ->latest('id')
            ->paginate(12)
            ->through(function (Producto $producto) {
                $imagenes = $producto->imagenes
                    ->sortBy([
                        ['orden', 'asc'],
                        ['id', 'asc'],
                    ])
                    ->map(fn ($imagen) => [
                        'id' => $imagen->id,
                        'ruta' => $imagen->ruta ? Storage::url($imagen->ruta) : null,
                        'orden' => (int) ($imagen->orden ?? 0),
                    ])
                    ->values();

                return [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'slug' => $producto->slug,
                    'sku' => $producto->sku,
                    'precio' => (float) $producto->precio,
                    'stock' => (int) $producto->stock,
                    'categoria_id' => $producto->categoria_id,
                    'categoria_nombre' => $producto->categoria?->nombre,
                    'marca_id' => $producto->marca_id,
                    'marca_nombre' => $producto->marca?->nombre,
                    'destacado' => (bool) $producto->destacado,
                    'visible' => (bool) $producto->visible,
                    'activo' => (bool) $producto->activo,
                    'descripcion' => $producto->descripcion,
                    'imagenes' => $imagenes,
                    'imagen_principal' => $imagenes->first()['ruta'] ?? null,
                    'created_at' => $producto->created_at?->toDateTimeString(),
                ];
            })
            ->withQueryString();

        return Inertia::render('Admin/Productos/Index', [
            'productos' => $productos,
            'categorias' => Categoria::query()
                ->where('activa', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre']),
            'marcas' => Marca::query()
                ->where('activa', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre']),
            'filters' => [
                'search' => $search,
                'categoria_id' => $categoriaId ? (int) $categoriaId : null,
                'marca_id' => $marcaId ? (int) $marcaId : null,
                'status' => $status,
                'destacado' => $destacado,
            ],
            'endpoints' => [
                'index' => route('admin.productos.index'),
                'store' => route('admin.productos.store'),
                'updateBase' => url('/admin/productos'),
                'destroyBase' => url('/admin/productos'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:220', 'unique:productos,slug'],
            'sku' => ['required', 'string', 'max:100', 'unique:productos,sku'],
            'precio' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'marca_id' => ['nullable', 'exists:marcas,id'],
            'destacado' => ['nullable', 'boolean'],
            'visible' => ['nullable', 'boolean'],
            'activo' => ['nullable', 'boolean'],
            'descripcion' => ['nullable', 'string'],
            'imagenes_files' => ['nullable', 'array'],
            'imagenes_files.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
        ]);

        $producto = Producto::create([
            'nombre' => $data['nombre'],
            'slug' => $this->resolveSlug($data['slug'] ?? null, $data['nombre']),
            'sku' => $data['sku'],
            'precio' => $data['precio'],
            'stock' => $data['stock'],
            'categoria_id' => $data['categoria_id'] ?? null,
            'marca_id' => $data['marca_id'] ?? null,
            'destacado' => $request->boolean('destacado'),
            'visible' => $request->boolean('visible', true),
            'activo' => $request->boolean('activo', true),
            'descripcion' => $data['descripcion'] ?? null,
        ]);

        if ($request->hasFile('imagenes_files')) {
            foreach ($request->file('imagenes_files') as $index => $file) {
                $path = $file->store('productos', 'public');

                $producto->imagenes()->create([
                    'ruta' => $path,
                    'orden' => $index,
                ]);
            }
        }

        return back()->with('success', 'Producto creado correctamente.');
    }

    public function show(Producto $producto): RedirectResponse
    {
        return redirect()
            ->route('admin.productos.index')
            ->with('success', 'Detalle de producto: ' . $producto->nombre);
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:180'],
            'slug' => [
                'nullable',
                'string',
                'max:220',
                Rule::unique('productos', 'slug')->ignore($producto->id),
            ],
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('productos', 'sku')->ignore($producto->id),
            ],
            'precio' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'marca_id' => ['nullable', 'exists:marcas,id'],
            'destacado' => ['nullable', 'boolean'],
            'visible' => ['nullable', 'boolean'],
            'activo' => ['nullable', 'boolean'],
            'descripcion' => ['nullable', 'string'],
            'imagenes_files' => ['nullable', 'array'],
            'imagenes_files.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => ['integer'],
        ]);

        $producto->update([
            'nombre' => $data['nombre'],
            'slug' => $this->resolveSlug($data['slug'] ?? null, $data['nombre'], $producto->id),
            'sku' => $data['sku'],
            'precio' => $data['precio'],
            'stock' => $data['stock'],
            'categoria_id' => $data['categoria_id'] ?? null,
            'marca_id' => $data['marca_id'] ?? null,
            'destacado' => $request->boolean('destacado'),
            'visible' => $request->boolean('visible', true),
            'activo' => $request->boolean('activo', true),
            'descripcion' => $data['descripcion'] ?? null,
        ]);

        $removeIds = collect($data['remove_image_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($removeIds->isNotEmpty()) {
            $imagenes = $producto->imagenes()->whereIn('id', $removeIds)->get();

            foreach ($imagenes as $imagen) {
                if ($imagen->ruta && Storage::disk('public')->exists($imagen->ruta)) {
                    Storage::disk('public')->delete($imagen->ruta);
                }
                $imagen->delete();
            }
        }

        if ($request->hasFile('imagenes_files')) {
            $startOrder = ((int) $producto->imagenes()->max('orden')) + 1;

            foreach ($request->file('imagenes_files') as $index => $file) {
                $path = $file->store('productos', 'public');

                $producto->imagenes()->create([
                    'ruta' => $path,
                    'orden' => $startOrder + $index,
                ]);
            }
        }

        return back()->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        $imagenes = $producto->imagenes()->get();

        foreach ($imagenes as $imagen) {
            if ($imagen->ruta && Storage::disk('public')->exists($imagen->ruta)) {
                Storage::disk('public')->delete($imagen->ruta);
            }
        }

        $producto->imagenes()->delete();
        $producto->delete();

        return back()->with('success', 'Producto eliminado correctamente.');
    }

    private function resolveSlug(?string $slug, string $nombre, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $nombre);

        if ($base === '') {
            $base = 'producto';
        }

        $candidate = $base;
        $counter = 2;

        while (
            Producto::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = "{$base}-{$counter}";
            $counter++;
        }

        return $candidate;
    }
}
