<?php
session_start();

// Si ya hay una sesión activa, mostrar panel de sesión activa
if (isset($_SESSION['user_id']) && isset($_SESSION['rol'])) {
?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sesión Activa - OSWifts</title>
        <style>
            /* Estilos para la pantalla de sesión activa */
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                margin: 0;
            }
            .container {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                text-align: center;
                max-width: 400px;
                width: 100%;
            }
            .btn {
                display: block;
                width: 100%;
                padding: 10px;
                margin: 10px 0;
                text-decoration: none;
                color: white;
                border-radius: 5px;
                font-weight: bold;
            }
            .btn-primary { background-color: #667eea; }
            .btn-secondary { background-color: #dc3545; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="session-active">
                <h1>✅ Sesión Activa</h1>
                <p>Ya tienes una sesión iniciada</p>
                <p><strong>Rol:</strong> <?php echo ucfirst(htmlspecialchars($_SESSION['rol'])); ?></p>
                <div class="button-group">
                    <a href="index.php" class="btn btn-primary">Ir al inicio</a>
                    <a href="logout.php" class="btn btn-secondary">Cerrar sesión</a>
                </div>
            </div>
        </div>
    </body>
    </html>
<?php
    exit;
}

require_once('conexion.php');

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $mensaje = 'Por favor, completa todos los campos.';
        $tipo_mensaje = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'El email no es válido.';
        $tipo_mensaje = 'error';
    } else {
        try {
            $conn = conectarDB('invitado');
            $stmt = $conn->prepare("CALL sp_get_user_login_info(?)");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $user['ID_USER'] !== null) {
                if (password_verify($password, $user['PASSWORD_HASH'])) {
                    $stmt->closeCursor();
                    
                    $session_token = bin2hex(random_bytes(32));
                    $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                    
                    $stmt_session = $conn->prepare("INSERT INTO SESSIONS (SES_TOKEN, SES_LAST_ACTIVITY, SES_IP, ID_USER) VALUES (?, NOW(), ?, ?)");
                    $stmt_session->execute([$session_token, $user_ip, $user['ID_USER']]);
                    
                    $_SESSION['user_id'] = $user['ID_USER'];
                    $_SESSION['rol'] = $user['ROL'];
                    $_SESSION['session_token'] = $session_token;
                    
                    if ($user['ROL'] === 'admin') {
                        header('Location: admin_panel.php');
                    } else {
                        header('Location: index.php');
                    }
                    exit;
                } else {
                    $mensaje = 'Credenciales incorrectas.'; // Mensaje genérico por seguridad
                    $tipo_mensaje = 'error';
                }
            } else {
                $mensaje = 'Credenciales incorrectas.';
                $tipo_mensaje = 'error';
            }
        } catch (PDOException $e) {
            $mensaje = 'Error al iniciar sesión: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - OSWifts</title>
    <style>
        /* Estilos idénticos al registro para consistencia */
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
            max-width: 400px;
        }
        
        h1 { color: #333; margin-bottom: 10px; text-align: center; }
        .subtitle { color: #666; text-align: center; margin-bottom: 30px; font-size: 14px; }
        
        .form-group { margin-bottom: 20px; }
        
        label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input:focus { outline: none; border-color: #667eea; }
        
        /* --- ESTILOS DE VALIDACIÓN --- */
        .input-error { border-color: #dc3545 !important; }
        .input-success { border-color: #28a745 !important; }
        
        .error-text {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: block;
            height: 15px;
        }
        /* --------------------------- */

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
            transition: transform 0.2s;
        }
        
        button:hover { transform: translateY(-2px); }
        
        .mensaje { padding: 12px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .mensaje.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-box">
            <h1>Iniciar Sesión</h1>
            <p class="subtitle">Bienvenido de nuevo a OSWifts</p>

            <?php if ($mensaje): ?>
                <div class="mensaje <?php echo $tipo_mensaje; ?>">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm" novalidate>
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" 
                           placeholder="ejemplo@correo.com"
                           value="<?php echo htmlspecialchars($email ?? ''); ?>">
                    <small class="error-text" id="email-error"></small>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password">
                    <small class="error-text" id="password-error"></small>
                </div>

                <button type="submit" class="btn btn-primary">Entrar</button>
            </form>

            <p class="subtitle" style="margin-top:20px;">
                ¿No tienes cuenta? <a href="new_user.php">Regístrate aquí</a>
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('loginForm');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            // Regex Estricto para Email (Igual que en registro)
            const regexEmail = /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/;

            // Función utilitaria de estilos
            const setStatus = (input, errorId, isValid, msg) => {
                const errorElement = document.getElementById(errorId);
                if (isValid) {
                    input.classList.add('input-success');
                    input.classList.remove('input-error');
                    errorElement.textContent = '';
                } else {
                    input.classList.add('input-error');
                    input.classList.remove('input-success');
                    errorElement.textContent = msg;
                }
                return isValid;
            };

            // Validación en tiempo real: EMAIL
            emailInput.addEventListener('input', () => {
                if (emailInput.value.trim() === '') {
                    setStatus(emailInput, 'email-error', false, 'El correo es obligatorio.');
                } else if (!regexEmail.test(emailInput.value)) {
                    setStatus(emailInput, 'email-error', false, 'Formato de correo inválido.');
                } else {
                    setStatus(emailInput, 'email-error', true, '');
                }
            });

            // Validación en tiempo real: PASSWORD
            // Para Login solo validamos que no esté vacía para no molestar al usuario
            passwordInput.addEventListener('input', () => {
                if (passwordInput.value.trim() === '') {
                    setStatus(passwordInput, 'password-error', false, 'Ingresa tu contraseña.');
                } else {
                    setStatus(passwordInput, 'password-error', true, '');
                }
            });

            // Validación al Enviar (Submit)
            form.addEventListener('submit', (e) => {
                let isValid = true;

                // Validar Email final
                if (!regexEmail.test(emailInput.value)) {
                    setStatus(emailInput, 'email-error', false, 'Ingresa un correo válido.');
                    isValid = false;
                }

                // Validar Password final
                if (passwordInput.value.trim() === '') {
                    setStatus(passwordInput, 'password-error', false, 'Ingresa tu contraseña.');
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault(); // Detiene el envío si hay errores
                }
            });
        });
    </script>
</body>
</html>