<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoriaController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Categorias/Index', [
            'categorias' => Categoria::query()->latest()->paginate(12),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:180'],
            'descripcion' => ['nullable', 'string'],
            'activa' => ['boolean'],
            'orden' => ['nullable', 'integer'],
        ]);

        $data['slug'] = Str::slug($data['nombre']).'-'.Str::lower(Str::random(4));
        $data['activa'] = $request->boolean('activa', true);
        Categoria::create($data);

        return back()->with('success', 'Categoría creada.');
    }

    public function show(Categoria $categoria): RedirectResponse
    {
        return redirect()->route('admin.categorias.index')->with('success', 'Detalle de categoría: '.$categoria->nombre);
    }

    public function update(Request $request, Categoria $categoria): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:180'],
            'descripcion' => ['nullable', 'string'],
            'activa' => ['boolean'],
            'orden' => ['nullable', 'integer'],
        ]);

        $data['activa'] = $request->boolean('activa', true);
        $categoria->update($data);

        return back()->with('success', 'Categoría actualizada.');
    }

    public function destroy(Categoria $categoria): RedirectResponse
    {
        $categoria->delete();

        return back()->with('success', 'Categoría eliminada.');
    }
}
