<?php
session_start();
require_once('conexion.php');

// Verificar que el usuario tenga rol de admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die('
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso Denegado</title>
        <link rel="stylesheet" href="upload_nft.css">
    </head>
    <body>
        <div class="container">
            <div class="error-box">
                <h1>⛔ Acceso Denegado</h1>
                <p>No tienes permisos para acceder a esta página.</p>
                <a href="index.php" class="btn-back">Volver al inicio</a>
            </div>
        </div>
    </body>
    </html>
    ');
}

// Conectar con rol admin
$conn = conectarDB($_SESSION['rol']);

$mensaje = '';
$tipo_mensaje = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener ID del vendedor desde la sesión
    $salesman_id = $_SESSION['user_id'];
    
    // Obtener datos del formulario
    $title = $_POST['title'] ?? '';
    $abstract = $_POST['abstract'] ?? '';
    $release_date = $_POST['release_date'] ?? '';
    $price = $_POST['price'] ?? '';
    $tags = $_POST['tags'] ?? '';
    
    // Validar campos obligatorios
    if (empty($title) || empty($price)) {
        $mensaje = 'Por favor completa todos los campos obligatorios.';
        $tipo_mensaje = 'error';
    } else {
        // Procesar la imagen
        $url_imagen = '';
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $imagen = $_FILES['imagen'];
            $nombre_archivo = $imagen['name'];
            $tmp_name = $imagen['tmp_name'];
            $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
            
            // Validar extensión
            $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($extension, $extensiones_permitidas)) {
                $mensaje = 'Solo se permiten imágenes (jpg, jpeg, png, gif, webp).';
                $tipo_mensaje = 'error';
            } else {
                // Crear nombre único para la imagen
                $nombre_unico = uniqid('nft_', true) . '.' . $extension;
                $ruta_destino = 'nft/' . $nombre_unico;
                
                // Crear carpeta si no existe
                if (!file_exists('nft')) {
                    mkdir('nft', 0755, true);
                }
                
                // Mover archivo
                if (move_uploaded_file($tmp_name, $ruta_destino)) {
                    $url_imagen = $nombre_unico;
                } else {
                    $mensaje = 'Error al subir la imagen.';
                    $tipo_mensaje = 'error';
                }
            }
        }
        
        // Si todo está bien, llamar al procedure
        if (empty($mensaje)) {
            try {
                $stmt = $conn->prepare("CALL sp_upload_nft(?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt->execute([
                    $salesman_id,
                    $title,
                    $abstract,
                    $release_date,
                    $price,
                    $url_imagen,
                    $tags
                ])) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $nft_id = $row['NFT_ID'] ?? 'N/A';
                    
                    $mensaje = "NFT subido exitosamente. ID: $nft_id";
                    $tipo_mensaje = 'success';
                    
                    // Limpiar formulario
                    $_POST = [];
                } else {
                    $mensaje = 'Error al subir el NFT';
                    $tipo_mensaje = 'error';
                    
                    // Eliminar imagen si hubo error
                    if (!empty($url_imagen) && file_exists($url_imagen)) {
                        unlink($url_imagen);
                    }
                }
            } catch (Exception $e) {
                $mensaje = 'Error: ' . $e->getMessage();
                $tipo_mensaje = 'error';
                
                // Eliminar imagen si hubo error
                if (!empty($url_imagen) && file_exists($url_imagen)) {
                    unlink($url_imagen);
                }
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
    <title>Subir NFT - OSWIFTS</title>
    <link rel="stylesheet" href="upload_nft.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📤 Subir Nuevo NFT</h1>
            <p class="subtitle">Panel de administración</p>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="form-upload">
            <div class="form-group">
                <label for="title">Título del NFT *</label>
                <input type="text" 
                       name="title" 
                       id="title" 
                       maxlength="100" 
                       required
                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="abstract">Descripción</label>
                <textarea name="abstract" 
                          id="abstract" 
                          rows="4"><?php echo htmlspecialchars($_POST['abstract'] ?? ''); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="release_date">Fecha de lanzamiento</label>
                    <input type="date" 
                           name="release_date" 
                           id="release_date"
                           value="<?php echo htmlspecialchars($_POST['release_date'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="price">Precio (créditos) *</label>
                    <input type="number" 
                           name="price" 
                           id="price" 
                           step="0.01" 
                           min="0" 
                       required
                       value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="imagen">Imagen del NFT *</label>
                <input type="file" 
                       name="imagen" 
                       id="imagen" 
                       accept="image/*" 
                       required>
                <small>Formatos permitidos: JPG, JPEG, PNG, GIF, WEBP</small>
            </div>

            <div class="form-group">
                <label for="tags">Tags (separados por comas)</label>
                <input type="text" 
                       name="tags" 
                       id="tags" 
                       placeholder="Ej: arte,digital,abstracto"
                       value="<?php echo htmlspecialchars($_POST['tags'] ?? ''); ?>">
                <small>Separa cada tag con una coma</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Subir NFT</button>
                <a href="admin_panel.php" class="btn-cancel">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>