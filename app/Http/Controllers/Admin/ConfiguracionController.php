<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConfiguracionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Configuraciones/Index', [
            'configuraciones' => Configuracion::query()->latest()->paginate(20),
        ]);
    }

    public function update(Request $request, Configuracion $configuracion): RedirectResponse
    {
        $data = $request->validate([
            'valor' => ['required', 'string'],
        ]);

        $configuracion->update($data);

        return back()->with('success', 'Configuración actualizada.');
    }
}
