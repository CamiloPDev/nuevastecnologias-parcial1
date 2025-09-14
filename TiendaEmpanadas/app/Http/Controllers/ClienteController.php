<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Venta;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::orderBy('id','desc')->get();
        return view('admin.clientes.index', compact('clientes'));
    }

    public function create()
    {
        return view('admin.clientes.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo_documento'   => 'nullable|string|max:50',
            'numero_documento' => 'nullable|string|max:100|unique:clientes,numero_documento',
            'nombre'           => 'required|string|max:255',
            'direccion'        => 'nullable|string|max:255',
            'ciudad'           => 'nullable|string|max:100',
            'telefono'         => 'nullable|string|max:50',
        ]);

        Cliente::create($data);

        return redirect()->route('clientes.index')->with('success', 'Cliente creado correctamente.');
    }

    public function edit(Cliente $cliente)
    {
        return view('admin.clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $data = $request->validate([
            'tipo_documento'   => 'nullable|string|max:50',
            'numero_documento' => 'nullable|string|max:100|unique:clientes,numero_documento,' . $cliente->id,
            'nombre'           => 'required|string|max:255',
            'direccion'        => 'nullable|string|max:255',
            'ciudad'           => 'nullable|string|max:100',
            'telefono'         => 'nullable|string|max:50',
        ]);

        $cliente->update($data);

        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado.');
    }

    public function destroy(Cliente $cliente)
    {
        // Evitar borrar cliente con ventas registradas
        $tieneVentas = Venta::where('cliente_id', $cliente->id)->exists();

        if ($tieneVentas) {
            return redirect()->route('clientes.index')->with('error', 'No se puede eliminar el cliente porque tiene ventas registradas.');
        }

        $cliente->delete();
        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado.');
    }
}
