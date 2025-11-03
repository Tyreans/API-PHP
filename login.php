<?php
session_start();

// Si ya hay una sesión activa, mostrar opciones
if (isset($_SESSION['user_id']) && isset($_SESSION['rol'])) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sesión Activa - OSWifts</title>
        <link rel="stylesheet" href="login.css">
    </head>
    <body>
        <div class="container">
            <div class="session-active">
                <h1>✅ Sesión Activa</h1>
                <p class="subtitle">Ya tienes una sesión iniciada</p>
                <div class="user-info">
                    <p><strong>Rol:</strong> <?php echo ucfirst(htmlspecialchars($_SESSION['rol'])); ?></p>
                </div>
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
            // Conectar con rol invitado
            $conn = conectarDB('invitado');

            // Llamar al procedure que obtiene info del usuario
            $stmt = $conn->prepare("CALL sp_get_user_login_info(?)");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $user['ID_USER'] !== null) {
                // Verificar la contraseña
                if (password_verify($password, $user['PASSWORD_HASH'])) {
                    // Cerrar el statement anterior
                    $stmt->closeCursor();
                    
                    // Generar token único para la sesión
                    $session_token = bin2hex(random_bytes(32));
                    $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                    
                    // Registrar la sesión en la base de datos
                    $stmt_session = $conn->prepare("
                        INSERT INTO SESSIONS (SES_TOKEN, SES_LAST_ACTIVITY, SES_IP, ID_USER)
                        VALUES (?, NOW(), ?, ?)
                    ");
                    $stmt_session->execute([$session_token, $user_ip, $user['ID_USER']]);
                    
                    // Guardar datos en la sesión de PHP
                    $_SESSION['user_id'] = $user['ID_USER'];
                    $_SESSION['rol'] = $user['ROL'];
                    $_SESSION['session_token'] = $session_token;
                    
                    // Redirigir según el rol
                    if ($user['ROL'] === 'admin') {
                        header('Location: admin_panel.php');
                    } else {
                        header('Location: index.php');
                    }
                    exit;
                } else {
                    $mensaje = 'Contraseña incorrecta.';
                    $tipo_mensaje = 'error';
                }
            } else {
                $mensaje = 'El usuario no existe.';
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
    <link rel="stylesheet" href="login.css">
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

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" required maxlength="100"
                           value="<?php echo htmlspecialchars($email ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>

                <button type="submit" class="btn btn-primary">Entrar</button>
            </form>

            <p class="subtitle" style="margin-top:20px;">
                ¿No tienes cuenta? <a href="new_user.php">Regístrate aquí</a>
            </p>
        </div>
    </div>
</body>
</html>