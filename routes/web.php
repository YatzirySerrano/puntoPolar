<?php

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

    Route::middleware('role:cliente,admin')->group(function () {
        Route::get('/mi-cuenta/pedidos', [MisPedidosController::class, 'index'])->name('cliente.pedidos.index');
    });

    Route::prefix('vendedor')
        ->name('vendedor.')
        ->middleware('role:vendedor,admin')
        ->group(function () {
            Route::get('/pedidos', [VendedorPedidoController::class, 'index'])->name('pedidos.index');
            Route::patch('/pedidos/{pedido}/estatus', [VendedorPedidoController::class, 'updateStatus'])->name('pedidos.estatus');
        });

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:admin')
        ->group(function () {
            Route::get('/productos', [AdminProductoController::class, 'index'])->name('productos.index');
            Route::post('/productos', [AdminProductoController::class, 'store'])->name('productos.store');
            Route::put('/productos/{producto}', [AdminProductoController::class, 'update'])->name('productos.update');
            Route::delete('/productos/{producto}', [AdminProductoController::class, 'destroy'])->name('productos.destroy');

            Route::get('/usuarios', [AdminUsuarioController::class, 'index'])->name('usuarios.index');
            Route::put('/usuarios/{user}', [AdminUsuarioController::class, 'update'])->name('usuarios.update');
            Route::delete('/usuarios/{user}', [AdminUsuarioController::class, 'destroy'])->name('usuarios.destroy');

            Route::get('/pedidos', [AdminPedidoController::class, 'index'])->name('pedidos.index');
            Route::patch('/pedidos/{pedido}/estatus', [AdminPedidoController::class, 'updateStatus'])->name('pedidos.estatus');
        });
});

require __DIR__.'/settings.php';
