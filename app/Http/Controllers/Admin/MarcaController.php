<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marca;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MarcaController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Marcas/Index', [
            'marcas' => Marca::query()->latest()->paginate(12),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:180'],
            'logo' => ['nullable', 'string'],
            'activa' => ['boolean'],
        ]);

        $data['slug'] = Str::slug($data['nombre']).'-'.Str::lower(Str::random(4));
        $data['activa'] = $request->boolean('activa', true);
        Marca::create($data);

        return back()->with('success', 'Marca creada.');
    }

    public function show(Marca $marca): RedirectResponse
    {
        return redirect()->route('admin.marcas.index')->with('success', 'Detalle de marca: '.$marca->nombre);
    }

    public function update(Request $request, Marca $marca): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:180'],
            'logo' => ['nullable', 'string'],
            'activa' => ['boolean'],
        ]);

        $data['activa'] = $request->boolean('activa', true);
        $marca->update($data);

        return back()->with('success', 'Marca actualizada.');
    }

    public function destroy(Marca $marca): RedirectResponse
    {
        $marca->delete();

        return back()->with('success', 'Marca eliminada.');
    }
}
