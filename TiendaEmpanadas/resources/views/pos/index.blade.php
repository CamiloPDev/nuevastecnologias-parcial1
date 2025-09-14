<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Punto de Venta</title>
</head>
<body>
    <h1>Punto de Venta - Empanadas y Papas</h1>
    
    <div id="alertas"></div>

    <!-- Productos -->
    <h2>Productos</h2>
    @foreach($productos as $producto)
    <div onclick="agregarProducto({{ $producto->id }}, '{{ $producto->nombre }}', {{ $producto->precio }})">
        <h3>{{ $producto->nombre }}</h3>
        <p>Precio: ${{ number_format($producto->precio, 0) }}</p>
        <p>{{ $producto->descripcion }}</p>
        <hr>
    </div>
    @endforeach

    <!-- Cliente -->
    <h2>Cliente</h2>
    <label><input type="radio" name="tipo_cliente" value="mostrador" checked onchange="toggleCliente()"> Cliente de Mostrador</label>
    <label><input type="radio" name="tipo_cliente" value="registrado" onchange="toggleCliente()"> Cliente Registrado</label>

    <div id="cliente-registrado" style="display: none;">
        <input type="text" id="numero-documento" placeholder="Número documento">
        <button onclick="buscarCliente()">Buscar Cliente</button>
        
        <div id="cliente-info" style="display: none;">
            <p><strong>Cliente:</strong> <span id="cliente-nombre"></span></p>
            <button onclick="limpiarCliente()">Cambiar Cliente</button>
        </div>
        
        <button id="btn-nuevo-cliente" style="display: none;" onclick="mostrarFormCliente()">Crear Nuevo Cliente</button>
    </div>

    <div id="form-nuevo-cliente" style="display: none;">
        <h3>Crear Nuevo Cliente</h3>
        <select id="tipo-documento">
            <option value="">Tipo Documento</option>
            <option value="CC">Cédula</option>
            <option value="TI">Tarjeta Identidad</option>
            <option value="CE">Cédula Extranjería</option>
        </select><br><br>
        
        <input type="text" id="nuevo-documento" placeholder="Número documento"><br><br>
        <input type="text" id="nuevo-nombre" placeholder="Nombre completo"><br><br>
        <input type="text" id="nueva-direccion" placeholder="Dirección"><br><br>
        <input type="text" id="nueva-ciudad" placeholder="Ciudad"><br><br>
        <input type="text" id="nuevo-telefono" placeholder="Teléfono"><br><br>
        
        <button onclick="crearCliente()">Crear Cliente</button>
        <button onclick="cancelarCliente()">Cancelar</button>
    </div>

    <!-- Carrito -->
    <h2>Carrito de Compras</h2>
    <div id="carrito"></div>
    <h3>Total: $<span id="total">0</span></h3>

    <!-- Pago -->
    <h2>Método de Pago</h2>
    <select id="metodo-pago">
        <option value="">Seleccione método de pago</option>
        <option value="efectivo">Efectivo</option>
        <option value="tarjeta">Tarjeta</option>
        <option value="transferencia">Transferencia</option>
    </select><br><br>

    <button onclick="procesarVenta()">PROCESAR VENTA</button>
    <button onclick="limpiarTodo()">LIMPIAR TODO</button>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let carrito = [];
        let clienteSeleccionado = { id: 1, nombre: 'Cliente de Mostrador' };
        let total = 0;

        function toggleCliente() {
            const tipo = document.querySelector('input[name="tipo_cliente"]:checked').value;
            const panel = document.getElementById('cliente-registrado');
            if (tipo === 'mostrador') {
                panel.style.display = 'none';
                clienteSeleccionado = { id: 1, nombre: 'Cliente de Mostrador' };
                limpiarCliente();
            } else {
                panel.style.display = 'block';
                clienteSeleccionado = null;
            }
        }

        function buscarCliente() {
    const documento = document.getElementById('numero-documento').value;
    console.log('Buscando cliente con documento:', documento);
    
    if (!documento) {
        alert('Ingrese un número de documento');
        return;
    }

    // Debug: verificar la URL
    const url = '{{ route("pos.clientes.buscar") }}';
    console.log('URL de búsqueda:', url);

    fetch(url, {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json', 
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ numero_documento: documento })
    })
    .then(response => {
        console.log('Respuesta recibida:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Datos recibidos:', data);
        
        if (data.success) {
            document.getElementById('cliente-nombre').textContent = data.cliente.nombre;
            document.getElementById('cliente-info').style.display = 'block';
            document.getElementById('btn-nuevo-cliente').style.display = 'none';
            clienteSeleccionado = data.cliente;
            mostrarAlerta(`Cliente encontrado: ${data.cliente.nombre}`);
        } else {
            document.getElementById('btn-nuevo-cliente').style.display = 'block';
            mostrarAlerta('Cliente no encontrado. Puede crear uno nuevo.');
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        mostrarAlerta(`Error al buscar cliente: ${error.message}`);
    });
}

        function limpiarCliente() {
            document.getElementById('cliente-info').style.display = 'none';
            document.getElementById('btn-nuevo-cliente').style.display = 'none';
            document.getElementById('numero-documento').value = '';
            document.getElementById('form-nuevo-cliente').style.display = 'none';
            clienteSeleccionado = null;
        }

        function mostrarFormCliente() {
            const doc = document.getElementById('numero-documento').value;
            document.getElementById('nuevo-documento').value = doc;
            document.getElementById('form-nuevo-cliente').style.display = 'block';
        }

        function cancelarCliente() {
            document.getElementById('form-nuevo-cliente').style.display = 'none';
            ['tipo-documento', 'nuevo-documento', 'nuevo-nombre', 'nueva-direccion', 'nueva-ciudad', 'nuevo-telefono'].forEach(id => {
                document.getElementById(id).value = '';
            });
        }

        function crearCliente() {
            const data = {
                tipo_documento: document.getElementById('tipo-documento').value,
                numero_documento: document.getElementById('nuevo-documento').value,
                nombre: document.getElementById('nuevo-nombre').value,
                direccion: document.getElementById('nueva-direccion').value,
                ciudad: document.getElementById('nueva-ciudad').value,
                telefono: document.getElementById('nuevo-telefono').value
            };

            if (!data.tipo_documento || !data.numero_documento || !data.nombre || !data.direccion || !data.ciudad || !data.telefono) {
                mostrarAlerta('Complete todos los campos');
                return;
            }

            fetch('{{ route("pos.clientes.crear") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('cliente-nombre').textContent = data.cliente.nombre;
                    document.getElementById('cliente-info').style.display = 'block';
                    document.getElementById('form-nuevo-cliente').style.display = 'none';
                    clienteSeleccionado = data.cliente;
                    mostrarAlerta('Cliente creado exitosamente');
                } else {
                    mostrarAlerta('Error al crear cliente');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta('Error al crear cliente');
            });
        }

        function agregarProducto(id, nombre, precio) {
            const item = carrito.find(i => i.id === id);
            if (item) {
                item.cantidad++;
            } else {
                carrito.push({ id, nombre, precio, cantidad: 1 });
            }
            actualizarCarrito();
        }

        function actualizarCarrito() {
            const container = document.getElementById('carrito');
            container.innerHTML = '';
            total = 0;

            carrito.forEach((item, index) => {
                const subtotal = item.precio * item.cantidad;
                total += subtotal;

                const div = document.createElement('div');
                div.innerHTML = `
                    <p><strong>${item.nombre}</strong> - Precio: $${item.precio}</p>
                    <p>Cantidad: 
                        <button onclick="cambiarCantidad(${index}, -1)">-</button>
                        ${item.cantidad}
                        <button onclick="cambiarCantidad(${index}, 1)">+</button>
                        <button onclick="eliminarItem(${index})">Eliminar</button>
                    </p>
                    <p>Subtotal: $${subtotal}</p>
                    <hr>
                `;
                container.appendChild(div);
            });

            document.getElementById('total').textContent = total;
        }

        function cambiarCantidad(index, cambio) {
            carrito[index].cantidad += cambio;
            if (carrito[index].cantidad <= 0) {
                carrito.splice(index, 1);
            }
            actualizarCarrito();
        }

        function eliminarItem(index) {
            carrito.splice(index, 1);
            actualizarCarrito();
        }

        function procesarVenta() {
    console.log('Iniciando procesamiento de venta...');
    console.log('Carrito:', carrito);
    console.log('Cliente seleccionado:', clienteSeleccionado);
    
    if (carrito.length === 0) {
        alert('El carrito está vacío');
        return;
    }

    const tipo = document.querySelector('input[name="tipo_cliente"]:checked').value;
    console.log('Tipo de cliente:', tipo);
    
    if (tipo === 'registrado' && !clienteSeleccionado) {
        alert('Debe seleccionar un cliente');
        return;
    }

    const metodoPago = document.getElementById('metodo-pago').value;
    console.log('Método de pago:', metodoPago);
    
    if (!metodoPago) {
        alert('Debe seleccionar un método de pago');
        return;
    }

    const data = {
        cliente_id: clienteSeleccionado?.id || 1,
        productos: carrito.map(item => ({ id: item.id, cantidad: item.cantidad })),
        metodo_pago: metodoPago
    };
    
    console.log('Datos a enviar:', data);
    
    const url = '{{ route("pos.ventas.procesar") }}';
    console.log('URL de venta:', url);

    fetch(url, {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json', 
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Respuesta de venta:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Respuesta de venta exitosa:', data);
        
        if (data.success) {
            // MEJORADO: Mostrar resumen detallado
            const resumen = `
¡VENTA EXITOSA!

Cliente: ${data.venta.cliente.nombre}
Total: $${data.venta.total}
Método de pago: ${data.venta.metodo_pago}
Fecha: ${new Date().toLocaleString()}

Productos vendidos:
${data.venta.detalles.map(d => `• ${d.producto.nombre} x${d.cantidad} = $${d.subtotal}`).join('\n')}
            `;
            
            alert(resumen);
            limpiarTodo();
        } else {
            alert(`Error: ${data.message || 'Error desconocido'}`);
        }
    })
    .catch(error => {
        console.error('Error al procesar venta:', error);
        alert(`Error al procesar la venta: ${error.message}`);
    });
}

        function limpiarTodo() {
            carrito = [];
            actualizarCarrito();
            document.getElementById('metodo-pago').value = '';
            document.querySelector('input[name="tipo_cliente"][value="mostrador"]').checked = true;
            toggleCliente();
        }

        function mostrarAlerta(mensaje) {
            const div = document.createElement('div');
            div.textContent = mensaje;
            div.style.padding = '10px';
            div.style.margin = '10px 0';
            div.style.border = '1px solid #ccc';
            div.style.backgroundColor = '#f9f9f9';
            document.getElementById('alertas').appendChild(div);
            setTimeout(() => div.remove(), 3000);
        }

        // Inicializar la vista
        document.addEventListener('DOMContentLoaded', function() {
            actualizarCarrito();
        });
    </script>
</body>
</html>