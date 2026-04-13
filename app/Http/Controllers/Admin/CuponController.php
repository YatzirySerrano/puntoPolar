<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CuponController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Cupones/Index', [
            'cupones' => Cupon::query()->latest()->paginate(12),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:60', 'unique:cupones,codigo'],
            'nombre' => ['required', 'string', 'max:160'],
            'tipo' => ['required', 'string', 'max:30'],
            'valor' => ['required', 'numeric', 'min:0'],
            'activo' => ['boolean'],
        ]);

        $data['activo'] = $request->boolean('activo', true);
        Cupon::create($data);

        return back()->with('success', 'Cupón creado.');
    }

    public function show(Cupon $cupon): RedirectResponse
    {
        return redirect()->route('admin.cupones.index')->with('success', 'Detalle de cupón: '.$cupon->codigo);
    }

    public function update(Request $request, Cupon $cupon): RedirectResponse
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:60', 'unique:cupones,codigo,'.$cupon->id],
            'nombre' => ['required', 'string', 'max:160'],
            'tipo' => ['required', 'string', 'max:30'],
            'valor' => ['required', 'numeric', 'min:0'],
            'activo' => ['boolean'],
        ]);

        $data['activo'] = $request->boolean('activo', true);
        $cupon->update($data);

        return back()->with('success', 'Cupón actualizado.');
    }

    public function destroy(Cupon $cupon): RedirectResponse
    {
        $cupon->delete();

        return back()->with('success', 'Cupón eliminado.');
    }
}
