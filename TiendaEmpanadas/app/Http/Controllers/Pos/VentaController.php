<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Cliente;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VentaController extends Controller
{
    /**
     * Procesar una nueva venta
     */
    public function procesarVenta(Request $request)
    {
        try {
            $request->validate([
                'cliente_id' => 'nullable|exists:clientes,id',
                'productos' => 'required|array|min:1',
                'productos.*.id' => 'required|exists:productos,id',
                'productos.*.cantidad' => 'required|integer|min:1',
                'metodo_pago' => 'required|in:efectivo,tarjeta,transferencia'
            ], [
                'productos.required' => 'Debe seleccionar al menos un producto',
                'productos.*.id.exists' => 'Uno o más productos no existen',
                'productos.*.cantidad.min' => 'La cantidad debe ser mayor a 0',
                'metodo_pago.required' => 'Debe seleccionar un método de pago',
                'metodo_pago.in' => 'Método de pago no válido'
            ]);

            DB::beginTransaction();
            
            // Manejar cliente - null significa "cliente de mostrador"
            $clienteId = $request->cliente_id;
            $clienteData = null;
            
            if ($clienteId) {
                // Si se especifica un cliente, verificar que existe
                $clienteData = Cliente::find($clienteId);
                if (!$clienteData) {
                    throw new \Exception('Cliente especificado no encontrado');
                }
            }

            $total = 0;
            $productosVenta = [];

            // Validar productos y calcular total
            foreach ($request->productos as $item) {
                $producto = Producto::find($item['id']);
                if (!$producto) {
                    throw new \Exception("Producto con ID {$item['id']} no encontrado");
                }

                $cantidad = $item['cantidad'];
                $subtotal = $cantidad * $producto->precio;
                $total += $subtotal;

                $productosVenta[] = [
                    'producto' => $producto,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $producto->precio,
                    'subtotal' => $subtotal
                ];
            }

            // Crear la venta
            $venta = Venta::create([
                'cliente_id' => $clienteId, // Puede ser null para cliente de mostrador
                'total' => $total,
                'metodo_pago' => $request->metodo_pago
            ]);

            // Crear detalles de venta
            foreach ($productosVenta as $item) {
                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $item['producto']->id,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['subtotal']
                ]);
            }

            DB::commit();

            // Preparar respuesta con información del cliente
            $ventaResponse = $venta->toArray();
            $ventaResponse['cliente'] = $clienteData ? $clienteData : [
                'id' => null,
                'nombre' => 'Cliente de Mostrador'
            ];
            
            // Cargar detalles con productos
            $ventaResponse['detalles'] = $venta->detalles()->with('producto')->get();

            return response()->json([
                'success' => true, 
                'venta' => $ventaResponse,
                'message' => 'Venta procesada exitosamente'
            ]);
            
        } catch (ValidationException $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error al procesar venta: ' . $e->getMessage());
            
            return response()->json([
                'success' => false, 
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener historial de ventas recientes
     */
    public function historial(Request $request)
    {
        try {
            $limite = $request->get('limit', 20);
            $fecha = $request->get('fecha');

            $query = Venta::with(['cliente', 'detalles.producto'])
                ->orderBy('created_at', 'desc');

            // Filtrar por fecha si se proporciona
            if ($fecha) {
                $query->whereDate('created_at', $fecha);
            }

            $ventas = $query->limit($limite)->get();

            // Agregar información de cliente de mostrador para ventas sin cliente
            $ventas->transform(function ($venta) {
                if (!$venta->cliente) {
                    $venta->cliente_mostrador = true;
                    $venta->cliente = (object)[
                        'id' => null,
                        'nombre' => 'Cliente de Mostrador'
                    ];
                }
                return $venta;
            });

            // Calcular estadísticas básicas
            $totalVentas = $ventas->count();
            $montoTotal = $ventas->sum('total');
            $ventaPromedio = $totalVentas > 0 ? $montoTotal / $totalVentas : 0;

            return response()->json([
                'success' => true,
                'ventas' => $ventas,
                'estadisticas' => [
                    'total_ventas' => $totalVentas,
                    'monto_total' => $montoTotal,
                    'venta_promedio' => round($ventaPromedio, 2)
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al obtener historial: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial de ventas'
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de ventas del día
     */
    public function estadisticasHoy()
    {
        try {
            $hoy = now()->format('Y-m-d');
            
            $ventasHoy = Venta::whereDate('created_at', $hoy)->get();
            $totalVentas = $ventasHoy->count();
            $montoTotal = $ventasHoy->sum('total');
            
            // Separar ventas de mostrador vs clientes registrados
            $ventasMostrador = $ventasHoy->whereNull('cliente_id')->count();
            $ventasRegistrados = $totalVentas - $ventasMostrador;
            
            // Productos más vendidos hoy
            $productosVendidos = DetalleVenta::whereHas('venta', function($query) use ($hoy) {
                $query->whereDate('created_at', $hoy);
            })
            ->with('producto')
            ->selectRaw('producto_id, SUM(cantidad) as total_vendido')
            ->groupBy('producto_id')
            ->orderBy('total_vendido', 'desc')
            ->limit(5)
            ->get();

            // Métodos de pago
            $metodosPago = Venta::whereDate('created_at', $hoy)
                ->selectRaw('metodo_pago, COUNT(*) as cantidad, SUM(total) as monto')
                ->groupBy('metodo_pago')
                ->get();

            return response()->json([
                'success' => true,
                'estadisticas' => [
                    'fecha' => $hoy,
                    'total_ventas' => $totalVentas,
                    'ventas_mostrador' => $ventasMostrador,
                    'ventas_registrados' => $ventasRegistrados,
                    'monto_total' => $montoTotal,
                    'venta_promedio' => $totalVentas > 0 ? round($montoTotal / $totalVentas, 2) : 0,
                    'productos_mas_vendidos' => $productosVendidos,
                    'metodos_pago' => $metodosPago
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al obtener estadísticas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas'
            ], 500);
        }
    }
}