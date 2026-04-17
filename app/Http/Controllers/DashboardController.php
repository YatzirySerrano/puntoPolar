<?php

namespace App\Http\Controllers;

use App\Models\Direccion;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller {

    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $rol = $user?->rol ?? 'cliente';
        $stats = match ($rol) {
            'admin' => $this->buildAdminStats(),
            'vendedor' => $this->buildVendedorStats(),
            default => $this->buildClienteStats($user->id),
        };
        return Inertia::render('Dashboard', [
            'stats' => $stats,
        ]);
    }

    private function buildAdminStats(): array {
        $ventasPagadas = (float) Pedido::query()
            ->whereIn('estatus', ['pagado', 'preparando', 'enviado', 'entregado'])
            ->sum('total');
        return [
            'resumen' => [
                [
                    'label' => 'Productos activos',
                    'value' => Producto::query()->where('activo', true)->count(),
                    'hint' => 'Catálogo visible y administrable',
                ],
                [
                    'label' => 'Pedidos totales',
                    'value' => Pedido::query()->count(),
                    'hint' => 'Histórico general de pedidos',
                ],
                [
                    'label' => 'Ventas operativas',
                    'value' => '$' . number_format($ventasPagadas, 2),
                    'hint' => 'Pagado, preparando, enviado o entregado',
                ],
                [
                    'label' => 'Pagos registrados',
                    'value' => Pago::query()->count(),
                    'hint' => 'Módulo de conciliación y seguimiento',
                ],
            ],
            'quickActions' => [
                [
                    'title' => 'Productos',
                    'description' => 'Administra catálogo, stock, imágenes y visibilidad.',
                    'href' => '/admin/productos',
                ],
                [
                    'title' => 'Pedidos',
                    'description' => 'Supervisa pedidos, estatus, historial y operación.',
                    'href' => '/admin/pedidos',
                ],
                [
                    'title' => 'Pagos',
                    'description' => 'Consulta pagos, referencias y conciliación manual.',
                    'href' => '/admin/pagos',
                ],
                [
                    'title' => 'Métodos de pago',
                    'description' => 'Deja lista la base para integrar OpenPay después.',
                    'href' => '/admin/metodos-pago',
                ],
            ],
            'panels' => [
                [
                    'title' => 'Pedidos por atender',
                    'items' => [
                        [
                            'label' => 'Pendientes',
                            'value' => Pedido::query()->where('estatus', 'pendiente')->count(),
                        ],
                        [
                            'label' => 'Pagados',
                            'value' => Pedido::query()->where('estatus', 'pagado')->count(),
                        ],
                        [
                            'label' => 'Preparando',
                            'value' => Pedido::query()->where('estatus', 'preparando')->count(),
                        ],
                        [
                            'label' => 'Enviados',
                            'value' => Pedido::query()->where('estatus', 'enviado')->count(),
                        ],
                    ],
                ],
                [
                    'title' => 'Control comercial',
                    'items' => [
                        [
                            'label' => 'Entregados',
                            'value' => Pedido::query()->where('estatus', 'entregado')->count(),
                        ],
                        [
                            'label' => 'Cancelados',
                            'value' => Pedido::query()->where('estatus', 'cancelado')->count(),
                        ],
                        [
                            'label' => 'Reembolsados',
                            'value' => Pedido::query()->where('estatus', 'reembolsado')->count(),
                        ],
                        [
                            'label' => 'Métodos activos',
                            'value' => MetodoPago::query()->where('activo', true)->count(),
                        ],
                    ],
                ],
            ],
        ];
    }

    private function buildVendedorStats(): array {
        return [
            'resumen' => [
                [
                    'label' => 'Pedidos pagados',
                    'value' => Pedido::query()->where('estatus', 'pagado')->count(),
                    'hint' => 'Listos para iniciar operación',
                ],
                [
                    'label' => 'Preparando',
                    'value' => Pedido::query()->where('estatus', 'preparando')->count(),
                    'hint' => 'Pedidos en proceso interno',
                ],
                [
                    'label' => 'Enviados',
                    'value' => Pedido::query()->where('estatus', 'enviado')->count(),
                    'hint' => 'Con guía o salida registrada',
                ],
                [
                    'label' => 'Entregados',
                    'value' => Pedido::query()->where('estatus', 'entregado')->count(),
                    'hint' => 'Operación cerrada con éxito',
                ],
            ],
            'quickActions' => [
                [
                    'title' => 'Pedidos operativos',
                    'description' => 'Da seguimiento a pedidos pagados y actualiza estatus.',
                    'href' => '/vendedor/pedidos',
                ],
                [
                    'title' => 'Panel general de pedidos',
                    'description' => 'Si tu cuenta lo permite, revisa también la vista administrativa.',
                    'href' => '/admin/pedidos',
                ],
            ],
            'panels' => [
                [
                    'title' => 'Enfoque del día',
                    'items' => [
                        [
                            'label' => 'Prioridad alta',
                            'value' => Pedido::query()
                                ->whereIn('estatus', ['pagado', 'preparando'])
                                ->count(),
                        ],
                        [
                            'label' => 'Listos para embarque',
                            'value' => Pedido::query()->where('estatus', 'preparando')->count(),
                        ],
                        [
                            'label' => 'Con entrega pendiente',
                            'value' => Pedido::query()->where('estatus', 'enviado')->count(),
                        ],
                    ],
                ],
                [
                    'title' => 'Objetivo operativo',
                    'items' => [
                        [
                            'label' => 'Cerrar entregas',
                            'value' => Pedido::query()->where('estatus', 'enviado')->count(),
                        ],
                        [
                            'label' => 'Nuevos pagados',
                            'value' => Pedido::query()->where('estatus', 'pagado')->count(),
                        ],
                        [
                            'label' => 'Completados',
                            'value' => Pedido::query()->where('estatus', 'entregado')->count(),
                        ],
                    ],
                ],
            ],
        ];
    }

    private function buildClienteStats(int $userId): array
    {
        $pedidosBase = Pedido::query()->where('user_id', $userId);
        return [
            'resumen' => [
                [
                    'label' => 'Pedidos totales',
                    'value' => (clone $pedidosBase)->count(),
                    'hint' => 'Historial completo de tus compras',
                ],
                [
                    'label' => 'Pagados / en proceso',
                    'value' => (clone $pedidosBase)
                        ->whereIn('estatus', ['pagado', 'preparando', 'enviado'])
                        ->count(),
                    'hint' => 'Pedidos activos con seguimiento',
                ],
                [
                    'label' => 'Entregados',
                    'value' => (clone $pedidosBase)->where('estatus', 'entregado')->count(),
                    'hint' => 'Compras finalizadas',
                ],
                [
                    'label' => 'Direcciones',
                    'value' => Direccion::query()->where('user_id', $userId)->count(),
                    'hint' => 'Tus direcciones guardadas',
                ],
            ],
            'quickActions' => [
                [
                    'title' => 'Mis pedidos',
                    'description' => 'Consulta detalle, pagos y seguimiento de tus compras.',
                    'href' => '/mi-cuenta/pedidos',
                ],
                [
                    'title' => 'Mis direcciones',
                    'description' => 'Mantén actualizadas tus direcciones para compras futuras.',
                    'href' => '/mi-cuenta/direcciones',
                ],
                [
                    'title' => 'Ir al catálogo',
                    'description' => 'Explora productos y revisa novedades del catálogo.',
                    'href' => '/productos',
                ],
                [
                    'title' => 'Ver carrito',
                    'description' => 'Consulta los productos que tienes pendientes.',
                    'href' => '/carrito',
                ],
            ],
            'panels' => [
                [
                    'title' => 'Estado de tus pedidos',
                    'items' => [
                        [
                            'label' => 'Pendientes',
                            'value' => (clone $pedidosBase)->where('estatus', 'pendiente')->count(),
                        ],
                        [
                            'label' => 'Pagados',
                            'value' => (clone $pedidosBase)->where('estatus', 'pagado')->count(),
                        ],
                        [
                            'label' => 'En camino',
                            'value' => (clone $pedidosBase)->where('estatus', 'enviado')->count(),
                        ],
                        [
                            'label' => 'Entregados',
                            'value' => (clone $pedidosBase)->where('estatus', 'entregado')->count(),
                        ],
                    ],
                ],
                [
                    'title' => 'Tu cuenta',
                    'items' => [
                        [
                            'label' => 'Direcciones guardadas',
                            'value' => Direccion::query()->where('user_id', $userId)->count(),
                        ],
                        [
                            'label' => 'Pedidos cancelados',
                            'value' => (clone $pedidosBase)->where('estatus', 'cancelado')->count(),
                        ],
                        [
                            'label' => 'Pedidos reembolsados',
                            'value' => (clone $pedidosBase)->where('estatus', 'reembolsado')->count(),
                        ],
                    ],
                ],
            ],
        ];
    }

}
