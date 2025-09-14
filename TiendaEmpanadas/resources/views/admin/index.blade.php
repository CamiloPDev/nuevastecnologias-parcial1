<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Tienda Empanadas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-white">
    <div class="container mt-4">
        <h1>Administración</h1>
        <p>Selecciona el módulo que deseas gestionar:</p>

        <div class="list-group">
            <a href="{{ url('/admin/productos') }}" class="list-group-item list-group-item-action">Gestión de Productos</a>
            <a href="{{ url('/admin/clientes') }}" class="list-group-item list-group-item-action">Gestión de Clientes</a>
        </div>

        <div class="mt-4">
            <a href="{{ url('/') }}" class="btn btn-link">Volver al inicio</a>
        </div>
    </div>
</body>
</html>
