<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Cliente;

class PosController extends Controller
{
    /**
     * Mostrar la vista principal del punto de venta
     */
    public function index()
    {
        $productos = Producto::all();
        // Excluir el cliente de mostrador (ID = 1) de la lista
        $clientes = Cliente::where('id', '!=', 1)->get();
        
        return view('pos.index', compact('productos', 'clientes'));
    }
}