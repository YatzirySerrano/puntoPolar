<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MisPedidosController extends Controller
{
    public function index(Request $request): Response
    {
        $pedidos = Pedido::query()
            ->with('items')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return Inertia::render('Cliente/Pedidos/Index', [
            'pedidos' => $pedidos,
        ]);
    }
}
