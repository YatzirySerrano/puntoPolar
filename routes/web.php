<?php

use App\Http\Controllers\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Admin\CategoriaController as AdminCategoriaController;
use App\Http\Controllers\Admin\ConfiguracionController as AdminConfiguracionController;
use App\Http\Controllers\Admin\CuponController as AdminCuponController;
use App\Http\Controllers\Admin\MarcaController as AdminMarcaController;
use App\Http\Controllers\Admin\MetodoPagoController as AdminMetodoPagoController;
use App\Http\Controllers\Admin\PagoController as AdminPagoController;
use App\Http\Controllers\Admin\PedidoController as AdminPedidoController;
use App\Http\Controllers\Admin\ProductoController as AdminProductoController;
use App\Http\Controllers\Admin\UsuarioController as AdminUsuarioController;
use App\Http\Controllers\Cliente\MisPedidosController;
use App\Http\Controllers\Publico\CarritoController;
use App\Http\Controllers\Publico\TiendaController;
use App\Http\Controllers\Vendedor\PedidoController as VendedorPedidoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TiendaController::class, 'index'])->name('home');

Route::get('/productos/{producto:slug}', [TiendaController::class, 'show'])->name('tienda.show');

Route::get('/carrito', [CarritoController::class, 'index'])->name('carrito.index');
Route::post('/carrito/agregar', [CarritoController::class, 'agregar'])->name('carrito.agregar');
Route::patch('/carrito/{producto}', [CarritoController::class, 'actualizar'])->name('carrito.actualizar');
Route::delete('/carrito/{producto}', [CarritoController::class, 'eliminar'])->name('carrito.eliminar');
Route::delete('/carrito', [CarritoController::class, 'vaciar'])->name('carrito.vaciar');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('/dashboard', 'Dashboard')->name('dashboard');

    Route::middleware('role:cliente,admin,vendedor')->group(function () {
        Route::get('/mi-cuenta/pedidos', [MisPedidosController::class, 'index'])
            ->name('cliente.pedidos.index');
    });

    Route::prefix('vendedor')
        ->name('vendedor.')
        ->middleware('role:vendedor,admin')
        ->group(function () {
            Route::get('/pedidos', [VendedorPedidoController::class, 'index'])
                ->name('pedidos.index');

            Route::get('/pedidos/{pedido}', [VendedorPedidoController::class, 'show'])
                ->name('pedidos.show');

            Route::patch('/pedidos/{pedido}/estatus', [VendedorPedidoController::class, 'updateStatus'])
                ->name('pedidos.estatus');
        });

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:admin')
        ->group(function () {
            Route::get('/productos', [AdminProductoController::class, 'index'])
                ->name('productos.index');
            Route::post('/productos', [AdminProductoController::class, 'store'])
                ->name('productos.store');
            Route::get('/productos/{producto}', [AdminProductoController::class, 'show'])
                ->name('productos.show');
            Route::put('/productos/{producto}', [AdminProductoController::class, 'update'])
                ->name('productos.update');
            Route::delete('/productos/{producto}', [AdminProductoController::class, 'destroy'])
                ->name('productos.destroy');

            Route::get('/categorias', [AdminCategoriaController::class, 'index'])
                ->name('categorias.index');
            Route::post('/categorias', [AdminCategoriaController::class, 'store'])
                ->name('categorias.store');
            Route::get('/categorias/{categoria}', [AdminCategoriaController::class, 'show'])
                ->name('categorias.show');
            Route::put('/categorias/{categoria}', [AdminCategoriaController::class, 'update'])
                ->name('categorias.update');
            Route::delete('/categorias/{categoria}', [AdminCategoriaController::class, 'destroy'])
                ->name('categorias.destroy');

            Route::get('/marcas', [AdminMarcaController::class, 'index'])
                ->name('marcas.index');
            Route::post('/marcas', [AdminMarcaController::class, 'store'])
                ->name('marcas.store');
            Route::get('/marcas/{marca}', [AdminMarcaController::class, 'show'])
                ->name('marcas.show');
            Route::put('/marcas/{marca}', [AdminMarcaController::class, 'update'])
                ->name('marcas.update');
            Route::delete('/marcas/{marca}', [AdminMarcaController::class, 'destroy'])
                ->name('marcas.destroy');

            Route::get('/pedidos', [AdminPedidoController::class, 'index'])
                ->name('pedidos.index');
            Route::get('/pedidos/{pedido}', [AdminPedidoController::class, 'show'])
                ->name('pedidos.show');
            Route::patch('/pedidos/{pedido}/estatus', [AdminPedidoController::class, 'updateStatus'])
                ->name('pedidos.estatus');

            Route::get('/usuarios', [AdminUsuarioController::class, 'index'])
                ->name('usuarios.index');
            Route::post('/usuarios', [AdminUsuarioController::class, 'store'])
                ->name('usuarios.store');
            Route::get('/usuarios/{user}', [AdminUsuarioController::class, 'show'])
                ->name('usuarios.show');
            Route::put('/usuarios/{user}', [AdminUsuarioController::class, 'update'])
                ->name('usuarios.update');
            Route::delete('/usuarios/{user}', [AdminUsuarioController::class, 'destroy'])
                ->name('usuarios.destroy');

            Route::get('/cupones', [AdminCuponController::class, 'index'])
                ->name('cupones.index');
            Route::post('/cupones', [AdminCuponController::class, 'store'])
                ->name('cupones.store');
            Route::get('/cupones/{cupon}', [AdminCuponController::class, 'show'])
                ->name('cupones.show');
            Route::put('/cupones/{cupon}', [AdminCuponController::class, 'update'])
                ->name('cupones.update');
            Route::delete('/cupones/{cupon}', [AdminCuponController::class, 'destroy'])
                ->name('cupones.destroy');

            Route::get('/banners', [AdminBannerController::class, 'index'])
                ->name('banners.index');
            Route::post('/banners', [AdminBannerController::class, 'store'])
                ->name('banners.store');
            Route::get('/banners/{banner}', [AdminBannerController::class, 'show'])
                ->name('banners.show');
            Route::put('/banners/{banner}', [AdminBannerController::class, 'update'])
                ->name('banners.update');
            Route::delete('/banners/{banner}', [AdminBannerController::class, 'destroy'])
                ->name('banners.destroy');
            Route::post('/banners/reorder', [AdminBannerController::class, 'reorder'])
                ->name('banners.reorder');

            Route::get('/metodos-pago', [AdminMetodoPagoController::class, 'index'])
                ->name('metodos-pago.index');
            Route::post('/metodos-pago', [AdminMetodoPagoController::class, 'store'])
                ->name('metodos-pago.store');
            Route::get('/metodos-pago/{metodoPago}', [AdminMetodoPagoController::class, 'show'])
                ->name('metodos-pago.show');
            Route::put('/metodos-pago/{metodoPago}', [AdminMetodoPagoController::class, 'update'])
                ->name('metodos-pago.update');
            Route::delete('/metodos-pago/{metodoPago}', [AdminMetodoPagoController::class, 'destroy'])
                ->name('metodos-pago.destroy');

            Route::get('/pagos', [AdminPagoController::class, 'index'])
                ->name('pagos.index');
            Route::get('/pagos/{pago}', [AdminPagoController::class, 'show'])
                ->name('pagos.show');
            Route::put('/pagos/{pago}', [AdminPagoController::class, 'update'])
                ->name('pagos.update');

            Route::get('/configuraciones', [AdminConfiguracionController::class, 'index'])
                ->name('configuraciones.index');
            Route::post('/configuraciones', [AdminConfiguracionController::class, 'store'])
                ->name('configuraciones.store');
            Route::put('/configuraciones/{configuracion}', [AdminConfiguracionController::class, 'update'])
                ->name('configuraciones.update');
            Route::delete('/configuraciones/{configuracion}', [AdminConfiguracionController::class, 'destroy'])
                ->name('configuraciones.destroy');

        });
});

require __DIR__.'/settings.php';
