<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MetodoPago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MetodoPagoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/MetodosPago/Index', [
            'metodos' => MetodoPago::query()->latest()->paginate(12),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:180'],
            'clave' => ['required', 'string', 'max:80', 'unique:metodos_pagos,clave'],
            'activo' => ['boolean'],
        ]);

        $data['activo'] = $request->boolean('activo', true);
        MetodoPago::create($data);

        return back()->with('success', 'Método de pago creado.');
    }

    public function show(MetodoPago $metodoPago): RedirectResponse
    {
        return redirect()->route('admin.metodos-pago.index')->with('success', 'Detalle de método: '.$metodoPago->nombre);
    }

    public function update(Request $request, MetodoPago $metodoPago): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:180'],
            'clave' => ['required', 'string', 'max:80', 'unique:metodos_pagos,clave,'.$metodoPago->id],
            'activo' => ['boolean'],
        ]);

        $data['activo'] = $request->boolean('activo', true);
        $metodoPago->update($data);

        return back()->with('success', 'Método de pago actualizado.');
    }

    public function destroy(MetodoPago $metodoPago): RedirectResponse
    {
        $metodoPago->delete();

        return back()->with('success', 'Método de pago eliminado.');
    }
}
