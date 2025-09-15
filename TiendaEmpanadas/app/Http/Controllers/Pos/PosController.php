<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Venta;

class PosController extends Controller
{
    /**
     * Mostrar la vista principal del punto de venta
     */
    public function index()
    {
        try {
            // Obtener productos disponibles
            $productos = Producto::orderBy('nombre')->get();
            
            // Obtener clientes existentes (todos los clientes registrados)
            $clientes = Cliente::orderBy('nombre')->get();
            
            // Obtener estadísticas básicas del día (opcional)
            $ventasHoy = Venta::whereDate('created_at', today())->count();
            $montoHoy = Venta::whereDate('created_at', today())->sum('total');
            
            return view('pos.index', compact(
                'productos', 
                'clientes', 
                'ventasHoy', 
                'montoHoy'
            ));
            
        } catch (\Exception $e) {
            \Log::error('Error al cargar POS: ' . $e->getMessage());
            
            // En caso de error, cargar vista con datos vacíos
            return view('pos.index', [
                'productos' => collect(),
                'clientes' => collect(),
                'ventasHoy' => 0,
                'montoHoy' => 0,
                'error' => 'Error al cargar datos del sistema'
            ]);
        }
    }
}