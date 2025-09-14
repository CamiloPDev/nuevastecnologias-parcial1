<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Producto;

class ProductoController extends Controller
{
    /**
     * Obtener todos los productos disponibles para la venta
     */
    public function obtenerProductos()
    {
        $productos = Producto::orderBy('nombre')->get();
        
        return response()->json([
            'success' => true,
            'productos' => $productos
        ]);
    }

    /**
     * Obtener un producto específico por ID
     */
    public function obtenerProducto($id)
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ]);
        }

        return response()->json([
            'success' => true,
            'producto' => $producto
        ]);
    }

    /**
     * Buscar productos por nombre (para autocompletado)
     */
    public function buscarProductos(Request $request)
    {
        $query = $request->get('q', '');
        
        $productos = Producto::where('nombre', 'like', "%{$query}%")
            ->orderBy('nombre')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'productos' => $productos
        ]);
    }
}