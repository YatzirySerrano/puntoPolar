<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PagoController extends Controller {

    public function index(Request $request): Response {
        $search = trim((string) $request->string('search'));
        $status = (string) $request->input('status', 'all');
        $pagos = Pago::query()
            ->with([
                'pedido:id,folio',
                'metodoPago:id,nombre',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('estatus', 'like', "%{$search}%")
                        ->orWhere('referencia_externa', 'like', "%{$search}%")
                        ->orWhere('autorizacion', 'like', "%{$search}%")
                        ->orWhereHas('pedido', function ($pedidoQuery) use ($search) {
                            $pedidoQuery->where('folio', 'like', "%{$search}%");
                        })
                        ->orWhereHas('metodoPago', function ($metodoQuery) use ($search) {
                            $metodoQuery->where('nombre', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== 'all', fn ($query) => $query->where('estatus', $status))
            ->latest('id')
            ->paginate(12)
            ->through(function (Pago $pago) {
                return [
                    'id' => $pago->id,
                    'estatus' => $pago->estatus,
                    'monto' => $pago->monto,
                    'moneda' => $pago->moneda,
                    'referencia_externa' => $pago->referencia_externa,
                    'autorizacion' => $pago->autorizacion,
                    'pagado_en' => $pago->pagado_en?->toDateTimeString(),
                    'created_at' => $pago->created_at?->toDateTimeString(),
                    'pedido' => $pago->pedido
                        ? [
                            'folio' => $pago->pedido->folio,
                        ]
                        : null,
                    'metodo_pago' => $pago->metodoPago
                        ? [
                            'nombre' => $pago->metodoPago->nombre,
                        ]
                        : null,
                ];
            })
            ->withQueryString();
        return Inertia::render('Admin/Pagos/Index', [
            'pagos' => $pagos,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'endpoints' => [
                'index' => route('admin.pagos.index'),
                'updateBase' => url('/admin/pagos'),
            ],
            'estatusOptions' => [
                'pendiente',
                'aprobado',
                'rechazado',
                'cancelado',
                'reembolsado',
            ],
        ]);
    }

    public function show(Pago $pago): RedirectResponse {
        return redirect()
            ->route('admin.pagos.index')
            ->with('success', 'Detalle de pago: ' . $pago->id);
    }

    public function update(Request $request, Pago $pago): RedirectResponse {
        $data = $request->validate([
            'estatus' => ['required', 'string', 'max:50'],
            'referencia_externa' => ['nullable', 'string', 'max:180'],
            'autorizacion' => ['nullable', 'string', 'max:180'],
            'pagado_en' => ['nullable', 'date'],
        ]);
        $pago->update($data);
        return back()->with('success', 'Pago actualizado.');
    }

}
