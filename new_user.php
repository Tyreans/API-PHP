<?php
// Configuración de la base de datos
session_start();
require_once('conexion.php');

// Inicializar variables
$mensaje = '';
$tipo_mensaje = '';
$username = ''; 
$email = ''; 

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validaciones básicas del lado del servidor (Respaldo de seguridad)
    if (empty($username) || empty($email) || empty($password)) {
        $mensaje = 'Todos los campos son obligatorios';
        $tipo_mensaje = 'error';
    } elseif ($password !== $confirm_password) {
        $mensaje = 'Las contraseñas no coinciden';
        $tipo_mensaje = 'error';
    } elseif (strlen($password) < 8) {
        $mensaje = 'La contraseña debe tener al menos 8 caracteres';
        $tipo_mensaje = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'El email no es válido';
        $tipo_mensaje = 'error';
    } else {
        $conn = null; 
        try {
            $rol_actual = 'invitado';
            $conn = conectarDB($rol_actual);
            
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("CALL sp_register_user(?, ?, ?)");
            $stmt->execute([$username, $email, $password_hash]);
            $stmt->closeCursor(); 
            
            // Iniciar sesión automáticamente
            $stmt_user = $conn->prepare("CALL sp_get_user_login_info(?)");
            $stmt_user->execute([$email]);
            $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

            if ($user && $user['ID_USER'] !== null) {
                $stmt_user->closeCursor();
                $session_token = bin2hex(random_bytes(32));
                $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                
                $stmt_session = $conn->prepare("INSERT INTO SESSIONS (SES_TOKEN, SES_LAST_ACTIVITY, SES_IP, ID_USER) VALUES (?, NOW(), ?, ?)");
                $stmt_session->execute([$session_token, $user_ip, $user['ID_USER']]);
                
                $_SESSION['user_id'] = $user['ID_USER'];
                $_SESSION['rol'] = $user['ROL'];
                $_SESSION['session_token'] = $session_token;
                
                header("Location: index.php");
                exit();
            } else {
                $mensaje = 'Error al iniciar sesión post-registro.';
                $tipo_mensaje = 'error';
            }
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $mensaje = 'El nombre de usuario o email ya existe';
            } else {
                $mensaje = 'Error al registrar usuario: ' . $e->getMessage();
            }
            $tipo_mensaje = 'error';
        } finally {
            if ($conn) $conn = null;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario - OSWifts</title>
    <style>
        /* ... ESTILOS PREVIOS ... */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            width: 100%;
            max-width: 450px;
        }
        h1 { color: #333; margin-bottom: 10px; text-align: center; }
        .subtitle { color: #666; text-align: center; margin-bottom: 30px; font-size: 14px; }
        .form-group { margin-bottom: 20px; position: relative; } /* Position relative para mensajes */
        label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input:focus { outline: none; border-color: #667eea; }
        
        /* --- NUEVOS ESTILOS PARA VALIDACIÓN --- */
        .input-error { border-color: #dc3545 !important; }
        .input-success { border-color: #28a745 !important; }
        
        .error-text {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: block;
            height: 15px; /* Altura fija para evitar saltos bruscos */
        }
        /* ------------------------------------- */

        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        button:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
        .mensaje { padding: 12px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .mensaje.exito { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .mensaje.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .password-hint { font-size: 12px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Crear Cuenta</h1>
        <p class="subtitle">Regístrate en OSWifts</p>
        
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="registerForm" novalidate>
            <div class="form-group">
                <label for="username">Nombre de Usuario</label>
                <input type="text" id="username" name="username" 
                       value="<?php echo htmlspecialchars($username); ?>" 
                       placeholder="Ej. usuario123" maxlength="50">
                <small class="error-text" id="username-error"></small>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($email); ?>" 
                       placeholder="ejemplo@correo.com" maxlength="100">
                <small class="error-text" id="email-error"></small>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password">
                <small class="error-text" id="password-error"></small>
                <p class="password-hint">8+ caracteres, mayúscula, minúscula y número.</p>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña</label>
                <input type="password" id="confirm_password" name="confirm_password">
                <small class="error-text" id="confirm-error"></small>
            </div>
            
            <button type="submit" id="btn-submit">Registrarse</button>
        </form>
         <p class="subtitle" style="margin-top:20px;">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
         </p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('registerForm');
            
            // Inputs
            const usernameInput = document.getElementById('username');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirm_password');

            // Expresiones Regulares (Mascaras de formato)
            // Usuario: Letras a-z, números 0-9, guion bajo. De 4 a 15 caracteres.
            const regexUsername = /^[a-zA-Z0-9_]{4,15}$/;
            
            // Email: Estándar complejo para validar correos reales.
            const regexEmail = /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/;
            
            // Password: Mínimo 8 chars, 1 mayúscula, 1 minúscula, 1 número.
            const regexPassword = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/;

            // Función genérica para validar un campo
            const validateField = (input, regex, errorId, errorMsg) => {
                const errorElement = document.getElementById(errorId);
                
                // Si el campo está vacío
                if (input.value.trim() === '') {
                    setError(input, errorElement, 'Este campo es obligatorio.');
                    return false;
                } 
                // Si no cumple la expresión regular
                else if (!regex.test(input.value)) {
                    setError(input, errorElement, errorMsg);
                    return false;
                } 
                // Éxito
                else {
                    setSuccess(input, errorElement);
                    return true;
                }
            };

            // Función específica para comparar contraseñas
            const validateMatch = () => {
                const errorElement = document.getElementById('confirm-error');
                if (confirmInput.value.trim() === '') {
                    setError(confirmInput, errorElement, 'Confirma tu contraseña.');
                    return false;
                } else if (passwordInput.value !== confirmInput.value) {
                    setError(confirmInput, errorElement, 'Las contraseñas no coinciden.');
                    return false;
                } else {
                    setSuccess(confirmInput, errorElement);
                    return true;
                }
            };

            // Utilidades visuales
            const setError = (input, errorElement, msg) => {
                input.classList.add('input-error');
                input.classList.remove('input-success');
                errorElement.textContent = msg;
            };

            const setSuccess = (input, errorElement) => {
                input.classList.add('input-success');
                input.classList.remove('input-error');
                errorElement.textContent = ''; // Limpiar mensaje
            };

            // --- EVENT LISTENERS (Validación en tiempo real) ---

            // Validar Username al escribir
            usernameInput.addEventListener('input', () => {
                validateField(usernameInput, regexUsername, 'username-error', '4-15 caracteres (letras, números, guion bajo).');
            });

            // Validar Email al escribir
            emailInput.addEventListener('input', () => {
                validateField(emailInput, regexEmail, 'email-error', 'Ingresa un correo electrónico válido.');
            });

            // Validar Password al escribir
            passwordInput.addEventListener('input', () => {
                validateField(passwordInput, regexPassword, 'password-error', 'Debe contener mayúscula, minúscula y número.');
                // Si cambiamos el password, re-validar la confirmación
                if(confirmInput.value !== '') validateMatch();
            });

            // Validar Confirmación al escribir
            confirmInput.addEventListener('input', validateMatch);

            // --- EVENTO SUBMIT DEL FORMULARIO ---
            form.addEventListener('submit', (e) => {
                // Ejecutar todas las validaciones
                const isUserValid = validateField(usernameInput, regexUsername, 'username-error', 'Usuario inválido (4-15 caracteres alfanuméricos).');
                const isEmailValid = validateField(emailInput, regexEmail, 'email-error', 'Correo inválido.');
                const isPassValid = validateField(passwordInput, regexPassword, 'password-error', 'Contraseña insegura.');
                const isMatchValid = validateMatch();

                // Si alguna falla, prevenimos el envío
                if (!isUserValid || !isEmailValid || !isPassValid || !isMatchValid) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>