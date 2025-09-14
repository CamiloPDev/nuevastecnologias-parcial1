<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tienda Empanadas - Inicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container text-center mt-5">
        <h1 class="mb-4">Tienda de Empanadas</h1>
        <div class="d-grid gap-3 col-6 mx-auto">
            <a href="{{ url('/pos') }}" class="btn btn-primary btn-lg">Ir al Punto de Venta (POS)</a>
            <a href="{{ url('/admin') }}" class="btn btn-secondary btn-lg">Ir a Administración</a>
        </div>
    </div>
</body>
</html>
