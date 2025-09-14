<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Productos - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-white">
    <div class="container mt-4">
        <h2>Gestión de Productos</h2>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <a href="{{ route('productos.create') }}" class="btn btn-primary mb-3">Nuevo Producto</a>
        <a href="{{ url('/admin') }}" class="btn btn-secondary mb-3">Volver a Admin</a>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Precio</th>
                    <th>Descripción</th>
                    <th style="width:180px">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($productos as $producto)
                <tr>
                    <td>{{ $producto->nombre }}</td>
                    <td>{{ $producto->precio }}</td>
                    <td>{{ $producto->descripcion }}</td>
                    <td>
                        <a href="{{ route('productos.edit', $producto) }}" class="btn btn-warning btn-sm">Editar</a>
                        <form action="{{ route('productos.destroy', $producto) }}" method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar producto? Esta acción no se puede revertir.');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm">Borrar</button>
                        </form>
                    </td>
                </tr>
                @endforeach
                @if($productos->isEmpty())
                <tr>
                    <td colspan="4" class="text-center">No hay productos aún.</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</body>
</html>
