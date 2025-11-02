<?php
session_start();
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
            // Conectar con el rol correspondiente
            $rol_actual = 'invitado';
            $conn = conectarDB($rol_actual);

            // Llamar a la función SQL que devuelve el hash
            $stmt = $conn->prepare("SELECT fn_get_user_hash(?) AS user_hash");
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && !empty($result['user_hash'])) {
                $hash = $result['user_hash'];

                if (password_verify($password, $hash)) {
                    // Contraseña correcta -> obtener datos del usuario
                    $stmt = $conn->prepare("SELECT id_user, username, email FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                    $_SESSION['usuario'] = $usuario;
                    $_SESSION['autenticado'] = true;

                    // Redirigir al dashboard o página principal
                    header('Location: index.php');
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
        } finally {
            $conn = null;
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
        /* Mismo estilo que register */
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
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; }
        input[type="email"], input[type="password"] {
            width: 100%; padding: 12px; border: 2px solid #e1e1e1;
            border-radius: 5px; font-size: 14px; transition: border-color 0.3s;
        }
        input:focus { outline: none; border-color: #667eea; }
        button {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; border: none; border-radius: 5px;
            font-size: 16px; font-weight: 600;
            cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;
        }
        button:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); }
        .mensaje { padding: 12px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .mensaje.exito { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .mensaje.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
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
                <input type="email" id="email" name="email" required maxlength="100">
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>

            <button type="submit">Entrar</button>
        </form>

        <p class="subtitle" style="margin-top:20px;">
            ¿No tienes cuenta? <a href="new_user.php" style="color:#667eea; text-decoration:none;">Regístrate aquí</a>
        </p>
    </div>
</body>
</html>
