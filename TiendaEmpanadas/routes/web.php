<?php

use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\Pos\ClienteController;
use App\Http\Controllers\Pos\VentaController;
use App\Http\Controllers\Pos\ProductoController;

// Ruta principal del POS
Route::get('/pos', [PosController::class, 'index'])->name('pos.index');

// Rutas para gestión de clientes en el POS
Route::prefix('pos/clientes')->name('pos.clientes.')->group(function () {
    Route::post('buscar', [ClienteController::class, 'buscarCliente'])->name('buscar');
    Route::post('crear', [ClienteController::class, 'crearCliente'])->name('crear');
});

// Rutas para procesamiento de ventas
Route::prefix('pos/ventas')->name('pos.ventas.')->group(function () {
    Route::post('procesar', [VentaController::class, 'procesarVenta'])->name('procesar');
    Route::get('historial', [VentaController::class, 'historial'])->name('historial');
});

// Rutas para productos en el POS
Route::prefix('pos/productos')->name('pos.productos.')->group(function () {
    Route::get('/', [ProductoController::class, 'obtenerProductos'])->name('obtener');
    Route::get('{id}', [ProductoController::class, 'obtenerProducto'])->name('obtener.uno');
    Route::get('buscar/query', [ProductoController::class, 'buscarProductos'])->name('buscar');
});