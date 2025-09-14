<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    /**
     * Procesar una nueva venta
     */
    public function procesarVenta(Request $request)
    {
        $request->validate([
            'cliente_id' => 'nullable|exists:clientes,id',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'metodo_pago' => 'required|in:efectivo,tarjeta,transferencia'
        ]);

        DB::beginTransaction();
        
        try {
            // Si no se especifica cliente, usar cliente de mostrador (ID = 1)
            $clienteId = $request->cliente_id ?? 1;
            $total = 0;

            // Crear la venta
            $venta = Venta::create([
                'cliente_id' => $clienteId,
                'total' => 0, // Se calculará después
                'metodo_pago' => $request->metodo_pago
            ]);

            // Crear detalles y calcular total
            foreach ($request->productos as $item) {
                $producto = Producto::find($item['id']);
                $cantidad = $item['cantidad'];
                $subtotal = $cantidad * $producto->precio;

                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $producto->precio,
                    'subtotal' => $subtotal
                ]);

                $total += $subtotal;
            }

            // Actualizar total de la venta
            $venta->update(['total' => $total]);

            DB::commit();

            // Cargar relaciones para la respuesta
            $venta->load(['cliente', 'detalles.producto']);

            return response()->json([
                'success' => true, 
                'venta' => $venta
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false, 
                'message' => 'Error al procesar venta'
            ]);
        }
    }

    /**
     * Obtener historial de ventas (opcional para el POS)
     */
    public function historial(Request $request)
    {
        $ventas = Venta::with(['cliente', 'detalles.producto'])
            ->orderBy('created_at', 'desc')
            ->limit($request->get('limit', 20))
            ->get();

        return response()->json([
            'success' => true,
            'ventas' => $ventas
        ]);
    }
}