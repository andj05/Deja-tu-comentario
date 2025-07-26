<?php
// Configuración para desarrollo
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Configuración de Firebase
define('FIREBASE_PROJECT_ID', 'comentarios-4ec7a');
define('FIREBASE_DATABASE_URL', 'https://comentarios-4ec7a-default-rtdb.firebaseio.com');

// Configuraciones del sistema
define('MAX_LONGITUD_NOMBRE', 100);
define('MAX_LONGITUD_COMENTARIO', 500);

// Variables para mensajes
$mensaje_exito = '';
$mensaje_error = '';
$comentarios = [];

// Función para sanitizar entrada
function sanitizarEntrada($datos)
{
    return htmlspecialchars(trim($datos), ENT_QUOTES, 'UTF-8');
}

// Función para validar comentario
function validarComentario($nombre, $comentario)
{
    $errores = [];

    if (empty(trim($nombre))) {
        $errores[] = "El nombre es obligatorio";
    } elseif (strlen(trim($nombre)) > MAX_LONGITUD_NOMBRE) {
        $errores[] = "El nombre no puede exceder " . MAX_LONGITUD_NOMBRE . " caracteres";
    }

    if (empty(trim($comentario))) {
        $errores[] = "El comentario es obligatorio";
    } elseif (strlen(trim($comentario)) > MAX_LONGITUD_COMENTARIO) {
        $errores[] = "El comentario no puede exceder " . MAX_LONGITUD_COMENTARIO . " caracteres";
    }

    return $errores;
}

// Función para obtener IP del cliente
function obtenerIPCliente()
{
    // Verificar si estamos en CLI
    if (php_sapi_name() === 'cli') {
        return 'localhost';
    }

    $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

// Verificar si estamos en un entorno web
$is_web_request = isset($_SERVER['REQUEST_METHOD']);

// Procesar envío de comentarios solo si es una petición web POST
if ($is_web_request && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitizarEntrada($_POST['nombre'] ?? '');
    $comentario = sanitizarEntrada($_POST['comentario'] ?? '');

    // Validar entrada
    $errores = validarComentario($nombre, $comentario);

    if (empty($errores)) {
        // Los datos se enviarán a Firebase via JavaScript
        $mensaje_exito = "¡Comentario enviado exitosamente!";

        // Limpiar variables para evitar reenvío
        $nombre = '';
        $comentario = '';
    } else {
        $mensaje_error = implode('. ', $errores);
    }
}

// Si se ejecuta desde CLI, mostrar mensaje informativo
if (!$is_web_request) {
    echo "Sistema de Comentarios con Firebase + PHP\n";
    echo "==========================================\n";
    echo "Para usar este sistema:\n";
    echo "1. Inicia un servidor web: php -S localhost:8000\n";
    echo "2. Abre tu navegador en: http://localhost:8000\n";
    echo "3. O sube los archivos a un servidor web\n\n";
    echo "Archivos necesarios:\n";
    echo "- index.php (este archivo)\n";
    echo "- styles.css\n";
    echo "- script.js\n\n";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Comentarios - Firebase + PHP</title>
    <meta name="description" content="Comparta sus comentarios y ayúdenos a mejorar nuestro servicio">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header text-center mb-5">
            <h1><i class="fas fa-comments me-2"></i>Su opinión es importante</h1>
            <p class="text-muted">Comparta sus comentarios y ayúdenos a mejorar</p>
        </div>

        <!-- Alertas PHP -->
        <?php if (!empty($mensaje_exito)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($mensaje_exito); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($mensaje_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($mensaje_error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Alertas JavaScript -->
        <div id="alertas-container"></div>


        <!-- Formulario de comentarios -->
        <div class="form-section mb-5">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title mb-4">
                        <i class="fas fa-edit me-2"></i>Dejar un comentario
                    </h3>
                    <form id="comentarioForm">
                        <div class="form-group mb-3">
                            <label for="nombre" class="form-label">
                                <i class="fas fa-user me-1"></i>Su nombre *
                            </label>
                            <input type="text" class="form-control" id="nombre" name="nombre"
                                placeholder="Ingrese su nombre completo" maxlength="<?php echo MAX_LONGITUD_NOMBRE; ?>"
                                required>
                            <div class="invalid-feedback">
                                Por favor ingrese su nombre.
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="comentario" class="form-label">
                                <i class="fas fa-comment me-1"></i>Comentario *
                            </label>
                            <textarea class="form-control" id="comentario" name="comentario" rows="4"
                                placeholder="Escriba aquí su comentario..."
                                maxlength="<?php echo MAX_LONGITUD_COMENTARIO; ?>" required></textarea>
                            <div class="invalid-feedback">
                                Por favor escriba su comentario.
                            </div>
                            <div class="form-text">
                                <span id="contador-caracteres">0/<?php echo MAX_LONGITUD_COMENTARIO; ?></span>
                                caracteres
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <button type="submit" class="btn btn-primary submit-btn">
                                <i class="fas fa-paper-plane me-2"></i>
                                Enviar Comentario
                            </button>
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Todos los campos son obligatorios
                            </small>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Información del grupo -->
        <div class="alert alert-primary alert-dismissible fade show" role="alert">
            <i class="fas fa-users me-2"></i>
            <strong>Desarrollado por el Grupo 2-D</strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <!-- Loading -->
        <div id="loading" class="text-center mb-4" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando comentarios...</span>
            </div>
            <p class="mt-2 text-muted">Cargando comentarios...</p>
        </div>

        <!-- Sección de comentarios -->
        <div class="comments-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="fas fa-comments me-2"></i>Comentarios recibidos
                </h2>
                <span class="badge bg-secondary comments-count">
                    <i class="fas fa-hashtag me-1"></i>
                    <span id="total-comentarios">0</span> comentarios
                </span>
            </div>

            <!-- Comentarios se cargarán aquí dinámicamente -->
            <div id="comentarios-lista">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <h5>Cargando comentarios...</h5>
                    <p class="mb-0">Por favor espere mientras cargamos los comentarios desde Firebase.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center text-muted mt-5 py-4">
        <small>
            <i class="fas fa-heart text-danger"></i>
            Sistema de Comentarios | Desarrollado por el Grupo 2-D
        </small>
    </footer>

    <!-- Firebase SDK -->
    <script type="module">
        // Import Firebase functions
        import { initializeApp } from "https://www.gstatic.com/firebasejs/12.0.0/firebase-app.js";
        import { getFirestore, collection, addDoc, getDocs, orderBy, query, serverTimestamp } from "https://www.gstatic.com/firebasejs/12.0.0/firebase-firestore.js";

        // Firebase configuration
        const firebaseConfig = {
            apiKey: "AIzaSyA8BJRv5a5ksR8HJB9XegFCPIYWO108TY8",
            authDomain: "comentarios-4ec7a.firebaseapp.com",
            projectId: "comentarios-4ec7a",
            storageBucket: "comentarios-4ec7a.firebasestorage.app",
            messagingSenderId: "716619404683",
            appId: "1:716619404683:web:9b69cbf93afffb6db60b8e",
            measurementId: "G-B2YBTB9MQ5"
        };

        try {
            // Initialize Firebase
            const app = initializeApp(firebaseConfig);
            const db = getFirestore(app);

            // Actualizar estado de Firebase (ya no necesario mostrar)
            console.log('Firebase inicializado correctamente');

            console.log('Firebase inicializado correctamente');

            // Referencias DOM
            const form = document.getElementById('comentarioForm');
            const nombreInput = document.getElementById('nombre');
            const comentarioTextarea = document.getElementById('comentario');
            const submitBtn = document.querySelector('.submit-btn');
            const alertasContainer = document.getElementById('alertas-container');
            const comentariosLista = document.getElementById('comentarios-lista');
            const totalComentarios = document.getElementById('total-comentarios');
            const contadorCaracteres = document.getElementById('contador-caracteres');
            const loading = document.getElementById('loading');

            // Función para mostrar alertas
            function mostrarAlerta(mensaje, tipo = 'success') {
                const alerta = document.createElement('div');
                alerta.className = `alert alert-${tipo} alert-dismissible fade show`;
                alerta.innerHTML = `
                    <i class="fas fa-${tipo === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                    ${mensaje}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                alertasContainer.appendChild(alerta);

                // Auto-hide después de 5 segundos
                setTimeout(() => {
                    if (alerta.parentNode) {
                        alerta.remove();
                    }
                }, 5000);
            }

            // Función para obtener IP del cliente
            function obtenerIPCliente() {
                return fetch('https://api.ipify.org?format=json')
                    .then(response => response.json())
                    .then(data => data.ip)
                    .catch(() => 'localhost');
            }

            // Función para enviar comentario a Firebase
            async function enviarComentario(nombre, comentario) {
                try {
                    const ip = await obtenerIPCliente();

                    const docRef = await addDoc(collection(db, "comentarios"), {
                        nombre: nombre,
                        comentario: comentario,
                        fecha: serverTimestamp(),
                        ip: ip,
                        userAgent: navigator.userAgent || 'unknown'
                    });

                    console.log("Comentario guardado con ID: ", docRef.id);
                    return true;
                } catch (e) {
                    console.error("Error al guardar comentario: ", e);
                    return false;
                }
            }

            // Función para cargar comentarios desde Firebase
            async function cargarComentarios() {
                try {
                    loading.style.display = 'block';
                    comentariosLista.innerHTML = '';

                    const q = query(collection(db, "comentarios"), orderBy("fecha", "desc"));
                    const querySnapshot = await getDocs(q);

                    if (querySnapshot.empty) {
                        comentariosLista.innerHTML = `
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle fa-2x mb-3"></i>
                                <h5>No hay comentarios aún</h5>
                                <p class="mb-0">¡Sé el primero en comentar y compartir tu opinión!</p>
                            </div>
                        `;
                    } else {
                        let comentariosHTML = '';
                        let contador = 0;

                        querySnapshot.forEach((doc) => {
                            const data = doc.data();
                            const fecha = data.fecha ? data.fecha.toDate() : new Date();
                            const fechaFormateada = formatearFecha(fecha);

                            comentariosHTML += `
                                <div class="comment-card card mb-3" style="animation-delay: ${contador * 0.1}s">
                                    <div class="card-body">
                                        <div class="comment-header d-flex justify-content-between align-items-start mb-3">
                                            <div class="comment-author-info">
                                                <h5 class="comment-author mb-1">
                                                    <i class="fas fa-user-circle me-2"></i>
                                                    ${escapeHtml(data.nombre)}
                                                </h5>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    ${fechaFormateada}
                                                </small>
                                            </div>
                                            <span class="badge bg-light text-dark">
                                                #${doc.id.substring(0, 8)}
                                            </span>
                                        </div>
                                        <div class="comment-content">
                                            <p class="mb-0">
                                                <i class="fas fa-quote-left me-2 text-muted"></i>
                                                ${escapeHtml(data.comentario).replace(/\n/g, '<br>')}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            `;
                            contador++;
                        });

                        comentariosLista.innerHTML = comentariosHTML;
                        totalComentarios.textContent = contador;
                    }

                    loading.style.display = 'none';
                } catch (error) {
                    console.error("Error al cargar comentarios:", error);
                    comentariosLista.innerHTML = `
                        <div class="alert alert-danger text-center">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                            <h5>Error al cargar comentarios</h5>
                            <p class="mb-0">No se pudieron cargar los comentarios. Verifique la configuración de Firebase.</p>
                            <button class="btn btn-outline-danger btn-sm mt-2" onclick="location.reload()">
                                <i class="fas fa-refresh me-1"></i>Recargar página
                            </button>
                        </div>
                    `;
                    loading.style.display = 'none';
                }
            }

            // Función para formatear fecha
            function formatearFecha(fecha) {
                const ahora = new Date();
                const diferencia = Math.floor((ahora - fecha) / 1000);

                if (diferencia < 60) {
                    return `Hace ${diferencia} segundo${diferencia !== 1 ? 's' : ''}`;
                } else if (diferencia < 3600) {
                    const minutos = Math.floor(diferencia / 60);
                    return `Hace ${minutos} minuto${minutos !== 1 ? 's' : ''}`;
                } else if (diferencia < 86400) {
                    const horas = Math.floor(diferencia / 3600);
                    return `Hace ${horas} hora${horas !== 1 ? 's' : ''}`;
                } else {
                    return fecha.toLocaleString('es-ES', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
            }

            // Función para escapar HTML
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Manejar envío de formulario
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const nombre = nombreInput.value.trim();
                const comentario = comentarioTextarea.value.trim();

                // Validación
                if (!nombre || !comentario) {
                    mostrarAlerta('Por favor complete todos los campos.', 'danger');
                    return;
                }

                if (nombre.length > <?php echo MAX_LONGITUD_NOMBRE; ?>) {
                    mostrarAlerta('El nombre es demasiado largo.', 'danger');
                    return;
                }

                if (comentario.length > <?php echo MAX_LONGITUD_COMENTARIO; ?>) {
                    mostrarAlerta('El comentario es demasiado largo.', 'danger');
                    return;
                }

                // Mostrar estado de carga
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

                // Enviar a Firebase
                const exito = await enviarComentario(nombre, comentario);

                if (exito) {
                    mostrarAlerta('¡Comentario enviado exitosamente!', 'success');
                    form.reset();
                    contadorCaracteres.textContent = '0/<?php echo MAX_LONGITUD_COMENTARIO; ?>';

                    // Recargar comentarios
                    setTimeout(() => {
                        cargarComentarios();
                    }, 1000);
                } else {
                    mostrarAlerta('Error al enviar el comentario. Verifique su conexión e intente nuevamente.', 'danger');
                }

                // Restaurar botón
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Enviar Comentario';
            });

            // Contador de caracteres
            comentarioTextarea.addEventListener('input', () => {
                const longitud = comentarioTextarea.value.length;
                contadorCaracteres.textContent = `${longitud}/<?php echo MAX_LONGITUD_COMENTARIO; ?>`;

                if (longitud > <?php echo MAX_LONGITUD_COMENTARIO; ?> * 0.8) {
                    contadorCaracteres.className = 'text-warning';
                } else {
                    contadorCaracteres.className = 'text-muted';
                }
            });

            // Cargar comentarios al iniciar
            window.addEventListener('load', () => {
                cargarComentarios();
            });

            // Hacer funciones globales para debugging
            window.cargarComentarios = cargarComentarios;
            window.enviarComentario = enviarComentario;

        } catch (error) {
            console.error('Error inicializando Firebase:', error);

            // Mostrar mensaje de error
            document.getElementById('comentarios-lista').innerHTML = `
                <div class="alert alert-danger text-center">
                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                    <h5>Error de conexión con Firebase</h5>
                    <p class="mb-0">No se pudo conectar con Firebase. Verifique:</p>
                    <ul class="list-unstyled mt-2">
                        <li>• Conexión a internet</li>
                        <li>• Configuración de Firebase</li>
                        <li>• Reglas de Firestore</li>
                    </ul>
                </div>
            `;
        }
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Script personalizado -->
    <script src="script.js"></script>
</body>

</html>