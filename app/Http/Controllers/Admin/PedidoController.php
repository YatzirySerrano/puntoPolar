<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PedidoController extends Controller
{
    private const ESTATUS = ['pendiente', 'pagado', 'preparando', 'enviado', 'entregado', 'cancelado', 'reembolsado'];

    public function index(Request $request): Response
    {
        $estatus = $request->string('estatus')->toString();

        $pedidos = Pedido::query()
            ->with(['user:id,name,email', 'items'])
            ->when($estatus, fn ($query) => $query->where('estatus', $estatus))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Admin/Pedidos/Index', [
            'pedidos' => $pedidos,
            'estatusDisponibles' => self::ESTATUS,
            'filters' => [
                'estatus' => $estatus,
            ],
        ]);
    }

    public function updateStatus(Request $request, Pedido $pedido): RedirectResponse
    {
        $data = $request->validate([
            'estatus' => ['required', Rule::in(self::ESTATUS)],
        ]);

        $pedido->update(['estatus' => $data['estatus']]);

        return back()->with('success', 'Estatus del pedido actualizado.');
    }
}
