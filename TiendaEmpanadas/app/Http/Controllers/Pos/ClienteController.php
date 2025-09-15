<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cliente;
use Illuminate\Validation\ValidationException;

class ClienteController extends Controller
{
    /**
     * Buscar cliente por número de documento
     */
    public function buscarCliente(Request $request)
    {
        try {
            $request->validate([
                'numero_documento' => 'required|string'
            ]);

            $cliente = Cliente::where('numero_documento', $request->numero_documento)->first();

            if ($cliente) {
                return response()->json([
                    'success' => true, 
                    'cliente' => $cliente,
                    'message' => 'Cliente encontrado'
                ]);
            }

            return response()->json([
                'success' => false, 
                'message' => 'Cliente no encontrado'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Número de documento requerido',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al buscar cliente: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Crear un nuevo cliente desde el POS
     */
    public function crearCliente(Request $request)
    {
        try {
            $request->validate([
                'tipo_documento' => 'required|string|max:50',
                'numero_documento' => 'required|string|max:100|unique:clientes,numero_documento',
                'nombre' => 'required|string|max:255',
                'direccion' => 'required|string|max:255',
                'ciudad' => 'required|string|max:100',
                'telefono' => 'required|string|max:50'
            ], [
                'numero_documento.unique' => 'Ya existe un cliente con este número de documento',
                'tipo_documento.required' => 'El tipo de documento es obligatorio',
                'numero_documento.required' => 'El número de documento es obligatorio',
                'nombre.required' => 'El nombre es obligatorio',
                'direccion.required' => 'La dirección es obligatoria',
                'ciudad.required' => 'La ciudad es obligatoria',
                'telefono.required' => 'El teléfono es obligatorio'
            ]);

            $cliente = Cliente::create([
                'tipo_documento' => $request->tipo_documento,
                'numero_documento' => $request->numero_documento,
                'nombre' => $request->nombre,
                'direccion' => $request->direccion,
                'ciudad' => $request->ciudad,
                'telefono' => $request->telefono
            ]);
            
            return response()->json([
                'success' => true, 
                'cliente' => $cliente,
                'message' => 'Cliente creado exitosamente'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al crear cliente: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el cliente'
            ], 500);
        }
    }

    /**
     * Obtener lista de clientes
     */
    public function listarClientes()
    {
        try {
            $clientes = Cliente::orderBy('nombre')
                ->get(['id', 'nombre', 'numero_documento', 'telefono']);

            return response()->json([
                'success' => true,
                'clientes' => $clientes
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al listar clientes: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener clientes'
            ], 500);
        }
    }
}