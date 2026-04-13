<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PedidoController extends Controller
{
    private const ESTATUS_OPERATIVOS = ['pagado', 'preparando', 'enviado', 'entregado'];

    public function index(): Response
    {
        $pedidos = Pedido::query()
            ->with(['user:id,name,email', 'items'])
            ->whereIn('estatus', self::ESTATUS_OPERATIVOS)
            ->latest()
            ->paginate(12);

        return Inertia::render('Vendedor/Pedidos/Index', [
            'pedidos' => $pedidos,
            'estatusDisponibles' => self::ESTATUS_OPERATIVOS,
        ]);
    }

    public function show(Pedido $pedido): RedirectResponse
    {
        return redirect()->route('vendedor.pedidos.index')->with('success', 'Detalle de pedido: '.$pedido->folio);
    }

    public function updateStatus(Request $request, Pedido $pedido): RedirectResponse
    {
        $data = $request->validate([
            'estatus' => ['required', Rule::in(self::ESTATUS_OPERATIVOS)],
        ]);

        $pedido->update(['estatus' => $data['estatus']]);

        return back()->with('success', 'Pedido actualizado por vendedor.');
    }
}
