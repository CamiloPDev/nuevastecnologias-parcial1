<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Punto de Venta - Empanadas y Papas</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .contenedor { display: flex; gap: 20px; }
        .columna { flex: 1; }
        .producto-item { border: 1px solid #ccc; padding: 10px; margin: 10px 0; cursor: pointer; }
        .producto-item:hover { background-color: #f0f0f0; }
        .carrito-item { border: 1px solid #ddd; padding: 10px; margin: 5px 0; }
        .botones { margin: 10px 0; }
        .botones button { margin-right: 10px; padding: 5px 10px; }
        .total { font-size: 1.2em; font-weight: bold; }
        input, select { padding: 5px; margin: 5px 0; width: 200px; }
        .cliente-panel { border: 1px solid #ccc; padding: 15px; margin: 10px 0; }
        .hidden { display: none; }
        .alerta { padding: 10px; margin: 10px 0; border: 1px solid #ccc; background-color: #f9f9f9; }
    </style>
</head>
<body>
    <h1>Punto de Venta - Empanadas y Papas</h1>
    
    <div id="alertas"></div>

    <div class="contenedor">
        <!-- Columna de Productos -->
        <div class="columna">
            <h2>Productos Disponibles</h2>
            <div id="productos-lista">
                @foreach($productos as $producto)
                <div class="producto-item" onclick="agregarProducto({{ $producto->id }}, '{{ $producto->nombre }}', {{ $producto->precio }})">
                    <h3>{{ $producto->nombre }}</h3>
                    <p><strong>Precio: ${{ number_format($producto->precio, 0) }}</strong></p>
                    @if($producto->descripcion)
                        <p>{{ $producto->descripcion }}</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- Columna de Carrito y Cliente -->
        <div class="columna">
            <!-- Panel de Cliente -->
            <div class="cliente-panel">
                <h2>Cliente</h2>
                <div class="botones">
                    <label><input type="radio" name="tipo_cliente" value="mostrador" checked onchange="toggleCliente()"> Cliente de Mostrador</label>
                    <label><input type="radio" name="tipo_cliente" value="registrado" onchange="toggleCliente()"> Cliente Registrado</label>
                </div>

                <div id="cliente-actual">
                    <p><strong>Cliente:</strong> <span id="cliente-nombre-actual">Cliente de Mostrador</span></p>
                </div>

                <div id="cliente-registrado" class="hidden">
                    <input type="text" id="numero-documento" placeholder="Número de documento">
                    <button onclick="buscarCliente()">Buscar Cliente</button>
                    
                    <div id="cliente-info" class="hidden">
                        <p><strong>Cliente encontrado:</strong> <span id="cliente-nombre"></span></p>
                        <button onclick="limpiarCliente()">Cambiar Cliente</button>
                    </div>
                    
                    <button id="btn-nuevo-cliente" class="hidden" onclick="mostrarFormCliente()">Crear Nuevo Cliente</button>
                </div>

                <div id="form-nuevo-cliente" class="hidden">
                    <h3>Crear Nuevo Cliente</h3>
                    <select id="tipo-documento">
                        <option value="">Seleccione tipo de documento</option>
                        <option value="CC">Cédula de Ciudadanía</option>
                        <option value="TI">Tarjeta de Identidad</option>
                        <option value="CE">Cédula de Extranjería</option>
                        <option value="NIT">NIT</option>
                    </select>
                    
                    <input type="text" id="nuevo-documento" placeholder="Número de documento">
                    <input type="text" id="nuevo-nombre" placeholder="Nombre completo">
                    <input type="text" id="nueva-direccion" placeholder="Dirección">
                    <input type="text" id="nueva-ciudad" placeholder="Ciudad">
                    <input type="text" id="nuevo-telefono" placeholder="Teléfono">
                    
                    <div class="botones">
                        <button onclick="crearCliente()">Crear Cliente</button>
                        <button onclick="cancelarCliente()">Cancelar</button>
                    </div>
                </div>
            </div>

            <!-- Carrito de Compras -->
            <h2>Carrito de Compras</h2>
            <div id="carrito">
                <p>El carrito está vacío</p>
            </div>
            <div class="total">
                <p>Total: $<span id="total">0</span></p>
            </div>

            <!-- Método de Pago -->
            <h2>Método de Pago</h2>
            <select id="metodo-pago">
                <option value="">Seleccione método de pago</option>
                <option value="efectivo">Efectivo</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="transferencia">Transferencia</option>
            </select>

            <!-- Botones de Acción -->
            <div class="botones">
                <button onclick="procesarVenta()" style="background-color: green; color: white; padding: 10px 20px; font-weight: bold;">PROCESAR VENTA</button>
                <button onclick="limpiarTodo()" style="background-color: orange; color: white; padding: 10px 20px;">LIMPIAR TODO</button>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let carrito = [];
        let clienteSeleccionado = null; // null = cliente de mostrador
        let total = 0;

        const baseUrl = window.location.origin;
        const API_URLS = {
            buscarCliente: `${baseUrl}/pos/clientes/buscar`,
            crearCliente: `${baseUrl}/pos/clientes/crear`,
            procesarVenta: `${baseUrl}/pos/ventas/procesar`,
            obtenerProductos: `${baseUrl}/pos/productos`
        };

        // Función para alternar entre tipos de cliente
        function toggleCliente() {
            const tipo = document.querySelector('input[name="tipo_cliente"]:checked').value;
            const panelRegistrado = document.getElementById('cliente-registrado');
            
            if (tipo === 'mostrador') {
                panelRegistrado.classList.add('hidden');
                clienteSeleccionado = null; // null = cliente de mostrador
                document.getElementById('cliente-nombre-actual').textContent = 'Cliente de Mostrador';
                limpiarCliente();
            } else {
                panelRegistrado.classList.remove('hidden');
                clienteSeleccionado = null;
                document.getElementById('cliente-nombre-actual').textContent = 'Sin seleccionar';
            }
        }

        // Función para buscar cliente existente
        function buscarCliente() {
            const documento = document.getElementById('numero-documento').value.trim();
            
            if (!documento) {
                mostrarAlerta('Ingrese un número de documento', 'error');
                return;
            }

            fetch(API_URLS.buscarCliente, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ numero_documento: documento })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    document.getElementById('cliente-nombre').textContent = data.cliente.nombre;
                    document.getElementById('cliente-info').classList.remove('hidden');
                    document.getElementById('btn-nuevo-cliente').classList.add('hidden');
                    document.getElementById('cliente-nombre-actual').textContent = data.cliente.nombre;
                    clienteSeleccionado = data.cliente;
                    mostrarAlerta(`Cliente encontrado: ${data.cliente.nombre}`, 'success');
                } else {
                    document.getElementById('cliente-info').classList.add('hidden');
                    document.getElementById('btn-nuevo-cliente').classList.remove('hidden');
                    mostrarAlerta('Cliente no encontrado. Puede crear uno nuevo.', 'warning');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta(`Error al buscar cliente: ${error.message}`, 'error');
            });
        }

        // Función para limpiar selección de cliente
        function limpiarCliente() {
            document.getElementById('cliente-info').classList.add('hidden');
            document.getElementById('btn-nuevo-cliente').classList.add('hidden');
            document.getElementById('numero-documento').value = '';
            document.getElementById('form-nuevo-cliente').classList.add('hidden');
            
            const tipo = document.querySelector('input[name="tipo_cliente"]:checked').value;
            if (tipo === 'registrado') {
                clienteSeleccionado = null;
                document.getElementById('cliente-nombre-actual').textContent = 'Sin seleccionar';
            }
        }

        // Función para mostrar formulario de nuevo cliente
        function mostrarFormCliente() {
            const doc = document.getElementById('numero-documento').value;
            document.getElementById('nuevo-documento').value = doc;
            document.getElementById('form-nuevo-cliente').classList.remove('hidden');
        }

        // Función para cancelar creación de cliente
        function cancelarCliente() {
            document.getElementById('form-nuevo-cliente').classList.add('hidden');
            
            // Limpiar campos del formulario
            const campos = ['tipo-documento', 'nuevo-documento', 'nuevo-nombre', 'nueva-direccion', 'nueva-ciudad', 'nuevo-telefono'];
            campos.forEach(id => {
                const elemento = document.getElementById(id);
                if (elemento) elemento.value = '';
            });
        }

        // Función para crear nuevo cliente
        function crearCliente() {
            const data = {
                tipo_documento: document.getElementById('tipo-documento').value,
                numero_documento: document.getElementById('nuevo-documento').value.trim(),
                nombre: document.getElementById('nuevo-nombre').value.trim(),
                direccion: document.getElementById('nueva-direccion').value.trim(),
                ciudad: document.getElementById('nueva-ciudad').value.trim(),
                telefono: document.getElementById('nuevo-telefono').value.trim()
            };

            // Validar campos requeridos
            const camposRequeridos = ['tipo_documento', 'numero_documento', 'nombre', 'direccion', 'ciudad', 'telefono'];
            const camposFaltantes = camposRequeridos.filter(campo => !data[campo]);
            
            if (camposFaltantes.length > 0) {
                mostrarAlerta('Complete todos los campos obligatorios', 'error');
                return;
            }

            fetch(API_URLS.crearCliente, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    document.getElementById('cliente-nombre').textContent = data.cliente.nombre;
                    document.getElementById('cliente-info').classList.remove('hidden');
                    document.getElementById('form-nuevo-cliente').classList.add('hidden');
                    document.getElementById('cliente-nombre-actual').textContent = data.cliente.nombre;
                    clienteSeleccionado = data.cliente;
                    mostrarAlerta('Cliente creado exitosamente', 'success');
                    cancelarCliente();
                } else {
                    mostrarAlerta(data.message || 'Error al crear cliente', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta(`Error al crear cliente: ${error.message}`, 'error');
            });
        }

        // Función para agregar productos al carrito
        function agregarProducto(id, nombre, precio) {
            const item = carrito.find(i => i.id === id);
            
            if (item) {
                item.cantidad++;
            } else {
                carrito.push({ id, nombre, precio, cantidad: 1 });
            }
            
            actualizarCarrito();
            mostrarAlerta(`${nombre} agregado al carrito`, 'success');
        }

        // Función para actualizar la vista del carrito
        function actualizarCarrito() {
            const container = document.getElementById('carrito');
            
            if (carrito.length === 0) {
                container.innerHTML = '<p>El carrito está vacío</p>';
                total = 0;
            } else {
                container.innerHTML = '';
                total = 0;

                carrito.forEach((item, index) => {
                    const subtotal = item.precio * item.cantidad;
                    total += subtotal;

                    const div = document.createElement('div');
                    div.className = 'carrito-item';
                    div.innerHTML = `
                        <div>
                            <strong>${item.nombre}</strong><br>
                            Precio unitario: $${item.precio.toLocaleString()}<br>
                            Cantidad: 
                            <button onclick="cambiarCantidad(${index}, -1)">-</button>
                            <span style="margin: 0 10px;">${item.cantidad}</span>
                            <button onclick="cambiarCantidad(${index}, 1)">+</button>
                            <button onclick="eliminarItem(${index})" style="background-color: red; color: white; margin-left: 10px;">Eliminar</button><br>
                            <strong>Subtotal: $${subtotal.toLocaleString()}</strong>
                        </div>
                    `;
                    container.appendChild(div);
                });
            }

            document.getElementById('total').textContent = total.toLocaleString();
        }

        // Función para cambiar cantidad de un producto
        function cambiarCantidad(index, cambio) {
            carrito[index].cantidad += cambio;
            
            if (carrito[index].cantidad <= 0) {
                carrito.splice(index, 1);
            }
            
            actualizarCarrito();
        }

        // Función para eliminar un item del carrito
        function eliminarItem(index) {
            const nombreProducto = carrito[index].nombre;
            carrito.splice(index, 1);
            actualizarCarrito();
            mostrarAlerta(`${nombreProducto} eliminado del carrito`, 'info');
        }

        // Función para procesar la venta
        function procesarVenta() {
            // Validaciones
            if (carrito.length === 0) {
                mostrarAlerta('El carrito está vacío', 'error');
                return;
            }

            const tipo = document.querySelector('input[name="tipo_cliente"]:checked').value;
            if (tipo === 'registrado' && !clienteSeleccionado) {
                mostrarAlerta('Debe seleccionar un cliente registrado', 'error');
                return;
            }

            const metodoPago = document.getElementById('metodo-pago').value;
            if (!metodoPago) {
                mostrarAlerta('Debe seleccionar un método de pago', 'error');
                return;
            }

            const data = {
                cliente_id: clienteSeleccionado?.id || null, // null = cliente de mostrador
                productos: carrito.map(item => ({ 
                    id: item.id, 
                    cantidad: item.cantidad 
                })),
                metodo_pago: metodoPago
            };

            fetch(API_URLS.procesarVenta, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Mostrar resumen detallado de la venta
                    const fecha = new Date(data.venta.created_at).toLocaleString();
                    const detalles = data.venta.detalles.map(d => 
                        `• ${d.producto.nombre} x${d.cantidad} = $${parseInt(d.subtotal).toLocaleString()}`
                    ).join('\n');
                    
                    const resumen = `¡VENTA EXITOSA!

                    Venta #${data.venta.id}
                    Cliente: ${data.venta.cliente.nombre}
                    Total: $${parseInt(data.venta.total).toLocaleString()}
                    Método de pago: ${data.venta.metodo_pago.charAt(0).toUpperCase() + data.venta.metodo_pago.slice(1)}
                    Fecha: ${fecha}

                    Productos vendidos:
                    ${detalles}`;
                    
                    alert(resumen);
                    limpiarTodo();
                    mostrarAlerta('Venta procesada exitosamente', 'success');
                } else {
                    mostrarAlerta(`Error: ${data.message || 'Error desconocido'}`, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta(`Error al procesar la venta: ${error.message}`, 'error');
            });
        }

        // Función para limpiar todo
        function limpiarTodo() {
            carrito = [];
            actualizarCarrito();
            document.getElementById('metodo-pago').value = '';
            
            // Resetear cliente a mostrador
            document.querySelector('input[name="tipo_cliente"][value="mostrador"]').checked = true;
            toggleCliente();
        }

        // Función para mostrar alertas
        function mostrarAlerta(mensaje, tipo = 'info') {
            const div = document.createElement('div');
            div.className = 'alerta';
            div.textContent = mensaje;
            
            // Colores según tipo
            switch(tipo) {
                case 'success': div.style.backgroundColor = '#d4edda'; div.style.borderColor = '#c3e6cb'; break;
                case 'error': div.style.backgroundColor = '#f8d7da'; div.style.borderColor = '#f5c6cb'; break;
                case 'warning': div.style.backgroundColor = '#fff3cd'; div.style.borderColor = '#ffeaa7'; break;
                default: div.style.backgroundColor = '#d1ecf1'; div.style.borderColor = '#bee5eb';
            }
            
            const container = document.getElementById('alertas');
            container.appendChild(div);
            
            // Auto-eliminar después de 4 segundos
            setTimeout(() => {
                if (div.parentNode) {
                    div.parentNode.removeChild(div);
                }
            }, 4000);
        }

        // Inicializar la aplicación
        document.addEventListener('DOMContentLoaded', function() {
            actualizarCarrito();
            console.log('Sistema POS inicializado correctamente');
        });
    </script>
</body>
</html>