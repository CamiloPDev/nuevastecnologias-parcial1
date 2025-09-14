<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cliente;

class ClienteController extends Controller
{
    /**
     * Buscar cliente por número de documento
     */
    public function buscarCliente(Request $request)
    {
        $cliente = Cliente::where('numero_documento', $request->numero_documento)
            ->where('id', '!=', 1) // Excluir cliente de mostrador
            ->first();

        if ($cliente) {
            return response()->json([
                'success' => true, 
                'cliente' => $cliente
            ]);
        }

        return response()->json([
            'success' => false, 
            'message' => 'Cliente no encontrado'
        ]);
    }

    /**
     * Crear un nuevo cliente desde el POS
     */
    public function crearCliente(Request $request)
    {
        $request->validate([
            'tipo_documento' => 'required',
            'numero_documento' => 'required|unique:clientes',
            'nombre' => 'required',
            'direccion' => 'required',
            'ciudad' => 'required',
            'telefono' => 'required'
        ]);

        try {
            $cliente = Cliente::create($request->all());
            
            return response()->json([
                'success' => true, 
                'cliente' => $cliente
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Error al crear cliente'
            ]);
        }
    }
}