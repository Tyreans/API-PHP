<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'oswifts');
define('DB_USER', 'app_cliente');
define('DB_PASS', 'CliPwdSegura456!');

// Configuración de carga de archivos
define('UPLOAD_DIR', 'pfp/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// Crear directorio si no existe
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

$mensaje = '';
$tipo_mensaje = '';
$user_id = $_GET['id'] ?? 1; // Por defecto ID 1 para pruebas

// Obtener información actual del usuario
$usuario_actual = null;
try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $stmt = $conn->prepare("
        SELECT 
            u.id_user, 
            u.username, 
            c.PROFILE_PIC_URL as profile_picture
        FROM USERS u
        LEFT JOIN CLIENTE c ON u.id_user = c.id_user
        WHERE u.id_user = ?
    ");

    $stmt->execute([$user_id]);
    $usuario_actual = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario_actual) {
        $mensaje = 'Usuario no encontrado';
        $tipo_mensaje = 'error';
    }
} catch (PDOException $e) {
    $mensaje = 'Error al obtener usuario: ' . $e->getMessage();
    $tipo_mensaje = 'error';
}

// Procesar la subida de imagen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    
    // Validar que no hay errores
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $mensaje = 'Error al subir el archivo';
        $tipo_mensaje = 'error';
    }
    // Validar tamaño
    elseif ($file['size'] > MAX_FILE_SIZE) {
        $mensaje = 'El archivo es demasiado grande. Máximo 5MB';
        $tipo_mensaje = 'error';
    }
    // Validar tipo de archivo
    elseif (!in_array($file['type'], $allowed_types)) {
        $mensaje = 'Tipo de archivo no permitido. Solo JPG, PNG, GIF o WEBP';
        $tipo_mensaje = 'error';
    } else {
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            $mensaje = 'Extensión de archivo no permitida';
            $tipo_mensaje = 'error';
        } else {
            // Generar nombre único para el archivo
            $new_filename = 'user_' . $user_id . '_' . uniqid() . '.' . $file_extension;
            $upload_path = UPLOAD_DIR . $new_filename;
            
            // Mover archivo a la carpeta de destino
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                try {
                    // Actualizar base de datos
                    $stmt = $conn->prepare("CALL sp_update_profile_picture(?, ?)");
                    $stmt->execute([$user_id, $new_filename]);
                    
                    // Eliminar imagen anterior si existe y es diferente
                    if ($usuario_actual['profile_picture'] && 
                        $usuario_actual['profile_picture'] !== $new_filename &&
                        file_exists(UPLOAD_DIR . $usuario_actual['profile_picture'])) {
                        unlink(UPLOAD_DIR . $usuario_actual['profile_picture']);
                    }
                    
                    $mensaje = '¡Imagen de perfil actualizada exitosamente!';
                    $tipo_mensaje = 'exito';
                    
                    // Actualizar información del usuario
                    $usuario_actual['profile_picture'] = $new_filename;
                    
                } catch (PDOException $e) {
                    // Si falla la BD, eliminar el archivo subido
                    if (file_exists($upload_path)) {
                        unlink($upload_path);
                    }
                    $mensaje = 'Error al actualizar en la base de datos: ' . $e->getMessage();
                    $tipo_mensaje = 'error';
                }
            } else {
                $mensaje = 'Error al mover el archivo';
                $tipo_mensaje = 'error';
            }
        }
    }
}

$conn = null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Imagen de Perfil - OSWifts</title>
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
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            width: 100%;
            max-width: 500px;
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
        
        .profile-preview {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #667eea;
            margin-bottom: 15px;
        }
        
        .no-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .username {
            color: #333;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .user-id {
            color: #999;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 10px;
            color: #333;
            font-weight: 500;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        
        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px dashed #667eea;
            border-radius: 8px;
            cursor: pointer;
            background: #f8f9ff;
            transition: all 0.3s;
        }
        
        input[type="file"]:hover {
            background: #e8ebff;
            border-color: #764ba2;
        }
        
        .file-info {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
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
            border-radius: 8px;
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
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Actualizar Imagen de Perfil</h1>
        <p class="subtitle">Sube tu foto de perfil</p>
        
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($usuario_actual): ?>
            <div class="profile-preview">
                <?php if ($usuario_actual['profile_picture'] && file_exists(UPLOAD_DIR . $usuario_actual['profile_picture'])): ?>
                    <img src="<?php echo UPLOAD_DIR . htmlspecialchars($usuario_actual['profile_picture']); ?>" 
                         alt="Perfil" class="profile-image">
                <?php else: ?>
                    <div class="no-image">
                        <?php echo strtoupper(substr($usuario_actual['username'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                
                <div class="username"><?php echo htmlspecialchars($usuario_actual['username']); ?></div>
                <div class="user-id">ID: <?php echo $usuario_actual['id_user']; ?></div>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="profile_picture">Seleccionar nueva imagen</label>
                    <input 
                        type="file" 
                        id="profile_picture" 
                        name="profile_picture" 
                        accept="image/jpeg,image/png,image/gif,image/webp"
                        required
                    >
                    <p class="file-info">Formatos permitidos: JPG, PNG, GIF, WEBP (Máx. 5MB)</p>
                </div>
                
                <button type="submit">Actualizar Imagen</button>
            </form>
            
            <div class="back-link">
                <a href="ver_usuarios.php">← Ver todos los usuarios</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Mostrar preview de la imagen antes de subir
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector('.profile-image, .no-image');
                    if (preview.tagName === 'IMG') {
                        preview.src = e.target.result;
                    } else {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'profile-image';
                        preview.parentNode.replaceChild(img, preview);
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>