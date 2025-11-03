<?php
// Configuración de la base de datos
session_start();
require_once('conexion.php');

// Inicializar variables
$mensaje = '';
$tipo_mensaje = '';
$username = ''; // Definir para mantener el valor en el formulario
$email = '';    // Definir para mantener el valor en el formulario

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validaciones básicas
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
        $conn = null; // Asegurarse de que $conn esté inicializada
        try {
            // Conectar a la base de datos
            $rol_actual = 'invitado';
            $conn = conectarDB($rol_actual);
            
            // Hash de la contraseña
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Llamar al procedimiento almacenado para registrar
            $stmt = $conn->prepare("CALL sp_register_user(?, ?, ?)");
            $stmt->execute([$username, $email, $password_hash]);
            $stmt->closeCursor(); // Cerrar el cursor después de registrar
            
            // --- INICIO DE LA LÓGICA CORREGIDA ---
            // Ahora que el usuario está creado, iniciamos sesión como en login.php

            // 1. Obtener la información del usuario recién creado
            $stmt_user = $conn->prepare("CALL sp_get_user_login_info(?)");
            $stmt_user->execute([$email]);
            $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

            if ($user && $user['ID_USER'] !== null) {
                $stmt_user->closeCursor();
                
                // 2. Generar token único para la sesión
                $session_token = bin2hex(random_bytes(32));
                $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                
                // 3. Registrar la sesión en la base de datos (como en login.php)
                $stmt_session = $conn->prepare("
                    INSERT INTO SESSIONS (SES_TOKEN, SES_LAST_ACTIVITY, SES_IP, ID_USER)
                    VALUES (?, NOW(), ?, ?)
                ");
                $stmt_session->execute([$session_token, $user_ip, $user['ID_USER']]);
                
                // 4. Guardar datos CORRECTOS en la sesión de PHP
                $_SESSION['user_id'] = $user['ID_USER'];
                $_SESSION['rol'] = $user['ROL'];
                $_SESSION['session_token'] = $session_token;
                
                // 5. Redirigir al index
                header("Location: index.php");
                exit();

            } else {
                // Esto no debería pasar si el registro fue exitoso
                $mensaje = 'Error al iniciar sesión después del registro. Intenta iniciar sesión manualmente.';
                $tipo_mensaje = 'error';
            }
            // --- FIN DE LA LÓGICA CORREGIDA ---
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $mensaje = 'El nombre de usuario o email ya existe';
            } else {
                $mensaje = 'Error al registrar usuario: ' . $e->getMessage();
            }
            $tipo_mensaje = 'error';
        } finally {
            if ($conn) {
                $conn = null;
            }
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
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
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
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
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .mensaje {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .mensaje.exito {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .mensaje.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .password-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
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
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Nombre de Usuario</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    value="<?php echo htmlspecialchars($username); ?>"
                    required
                    maxlength="50"
                >
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?php echo htmlspecialchars($email); ?>"
                    required
                    maxlength="100"
                >
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    minlength="8"
                >
                <p class="password-hint">Mínimo 8 caracteres</p>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    required
                    minlength="8"
                >
            </div>
            
            <button type="submit">Registrarse</button>
        </form>
         <p class="subtitle" style="margin-top:20px;">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
         </p>
    </div>
</body>
</html>