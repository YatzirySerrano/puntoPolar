<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PagoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Pagos/Index', [
            'pagos' => Pago::query()->with(['pedido:id,folio', 'metodoPago:id,nombre'])->latest()->paginate(12),
        ]);
    }

    public function show(Pago $pago): RedirectResponse
    {
        return redirect()->route('admin.pagos.index')->with('success', 'Detalle de pago: '.$pago->id);
    }

    public function update(Request $request, Pago $pago): RedirectResponse
    {
        $data = $request->validate([
            'estatus' => ['required', 'string', 'max:50'],
            'referencia_externa' => ['nullable', 'string', 'max:180'],
        ]);

        $pago->update($data);

        return back()->with('success', 'Pago actualizado.');
    }
}
