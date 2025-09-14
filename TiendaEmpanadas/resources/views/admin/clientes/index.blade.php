<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clientes - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-white">
    <div class="container mt-4">
        <h2>Gestión de Clientes</h2>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <a href="{{ route('clientes.create') }}" class="btn btn-primary mb-3">Nuevo Cliente</a>
        <a href="{{ url('/admin') }}" class="btn btn-secondary mb-3">Volver a Admin</a>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Tipo Doc</th>
                    <th>Número Doc</th>
                    <th>Nombre</th>
                    <th>Ciudad</th>
                    <th>Teléfono</th>
                    <th style="width:160px">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($clientes as $cliente)
                <tr>
                    <td>{{ $cliente->tipo_documento }}</td>
                    <td>{{ $cliente->numero_documento }}</td>
                    <td>{{ $cliente->nombre }}</td>
                    <td>{{ $cliente->ciudad }}</td>
                    <td>{{ $cliente->telefono }}</td>
                    <td>
                        <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-warning btn-sm">Editar</a>
                        <form action="{{ route('clientes.destroy', $cliente) }}" method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar cliente? Esta acción no se puede revertir.');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm">Borrar</button>
                        </form>
                    </td>
                </tr>
                @endforeach
                @if($clientes->isEmpty())
                <tr>
                    <td colspan="6" class="text-center">No hay clientes aún.</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</body>
</html>
