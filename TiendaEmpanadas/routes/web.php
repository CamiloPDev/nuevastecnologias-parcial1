<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
});

Route::get('/admin', function () {
    return view('admin.index');
})->name('admin.index');

use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ClienteController;
Route::prefix('admin')->group(function () {
    Route::resource('productos', ProductoController::class);
    Route::resource('clientes', ClienteController::class);
});