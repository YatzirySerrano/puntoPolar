<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductoController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $productos = Producto::query()
            ->with(['categoria:id,nombre', 'marca:id,nombre'])
            ->when($search, fn ($query) => $query
                ->where('nombre', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Admin/Productos/Index', [
            'productos' => $productos,
            'categorias' => Categoria::query()->where('activa', true)->get(['id', 'nombre']),
            'marcas' => Marca::query()->where('activa', true)->get(['id', 'nombre']),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:180'],
            'sku' => ['required', 'string', 'max:100', 'unique:productos,sku'],
            'precio' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'marca_id' => ['nullable', 'exists:marcas,id'],
            'destacado' => ['boolean'],
            'visible' => ['boolean'],
            'activo' => ['boolean'],
            'descripcion' => ['nullable', 'string'],
        ]);

        $data['slug'] = Str::slug($data['nombre']).'-'.Str::lower(Str::random(6));
        $data['destacado'] = $request->boolean('destacado');
        $data['visible'] = $request->boolean('visible', true);
        $data['activo'] = $request->boolean('activo', true);

        Producto::create($data);

        return back()->with('success', 'Producto creado correctamente.');
    }

    public function show(Producto $producto): RedirectResponse
    {
        return redirect()->route('admin.productos.index')->with('success', 'Detalle de producto: '.$producto->nombre);
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:180'],
            'sku' => ['required', 'string', 'max:100', 'unique:productos,sku,'.$producto->id],
            'precio' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'marca_id' => ['nullable', 'exists:marcas,id'],
            'destacado' => ['boolean'],
            'visible' => ['boolean'],
            'activo' => ['boolean'],
            'descripcion' => ['nullable', 'string'],
        ]);

        $data['destacado'] = $request->boolean('destacado');
        $data['visible'] = $request->boolean('visible', true);
        $data['activo'] = $request->boolean('activo', true);

        $producto->update($data);

        return back()->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        $producto->delete();

        return back()->with('success', 'Producto eliminado correctamente.');
    }
}
