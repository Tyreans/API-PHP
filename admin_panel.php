<?php
session_start();
require_once('conexion.php');

// Verificar permisos
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die('Acceso Denegado'); // Simplificado para este ejemplo
}

$conn = conectarDB($_SESSION['rol']);
$mensaje = '';
$tipo_mensaje = '';

// --- LÓGICA PHP MEJORADA (SERVIDOR) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $salesman_id = $_SESSION['user_id'];
    $title = trim($_POST['title'] ?? '');
    $abstract = trim($_POST['abstract'] ?? '');
    $release_date = $_POST['release_date'] ?? '';
    $price = floatval($_POST['price'] ?? 0); // Convertir a flotante
    $tags = trim($_POST['tags'] ?? '');
    
    // 1. Validaciones de datos lógicos
    if (empty($title) || empty($price)) {
        $mensaje = 'Título y Precio son obligatorios.';
        $tipo_mensaje = 'error';
    } elseif ($price < 0) {
        $mensaje = 'El precio no puede ser negativo.';
        $tipo_mensaje = 'error';
    } elseif (strlen($title) < 3) {
        $mensaje = 'El título es muy corto.';
        $tipo_mensaje = 'error';
    } else {
        // 2. Validación estricta de Imagen
        $url_imagen = '';
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $imagen = $_FILES['imagen'];
            
            // Validar TAMAÑO (Ej: Max 5MB)
            if ($imagen['size'] > 5 * 1024 * 1024) {
                $mensaje = 'La imagen es demasiado pesada (Máx 5MB).';
                $tipo_mensaje = 'error';
            } else {
                // Validar TIPO MIME REAL (Seguridad)
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime_type = $finfo->file($imagen['tmp_name']);
                $mimes_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                if (!in_array($mime_type, $mimes_permitidos)) {
                    $mensaje = 'Archivo no válido. Solo JPG, PNG, GIF o WEBP.';
                    $tipo_mensaje = 'error';
                } else {
                    // Procesar subida
                    $extension = pathinfo($imagen['name'], PATHINFO_EXTENSION);
                    $nombre_unico = uniqid('nft_', true) . '.' . $extension;
                    $ruta_destino = 'nft/' . $nombre_unico;
                    
                    if (!file_exists('nft')) mkdir('nft', 0755, true);
                    
                    if (move_uploaded_file($imagen['tmp_name'], $ruta_destino)) {
                        $url_imagen = $nombre_unico;
                    } else {
                        $mensaje = 'Error al mover el archivo al servidor.';
                        $tipo_mensaje = 'error';
                    }
                }
            }
        } else {
            $mensaje = 'Debes seleccionar una imagen.';
            $tipo_mensaje = 'error';
        }

        // 3. Insertar en BD si no hay errores previos
        if (empty($mensaje)) {
            try {
                // Limpieza de tags (quitar espacios extra)
                $tags_array = explode(',', $tags);
                $tags_clean = array_map('trim', $tags_array);
                $tags_final = implode(',', array_filter($tags_clean));

                $stmt = $conn->prepare("CALL sp_upload_nft(?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$salesman_id, $title, $abstract, $release_date, $price, $url_imagen, $tags_final])) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $nft_id = $row['NFT_ID'] ?? 'OK';
                    $mensaje = "¡NFT Creado con éxito! (ID: $nft_id)";
                    $tipo_mensaje = 'success';
                    $_POST = []; // Limpiar campos
                } else {
                    throw new Exception("Error al ejecutar el procedimiento.");
                }
            } catch (Exception $e) {
                $mensaje = 'Error DB: ' . $e->getMessage();
                $tipo_mensaje = 'error';
                if (!empty($url_imagen) && file_exists('nft/'.$url_imagen)) unlink('nft/'.$url_imagen);
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
    <style>
        /* Estilos extra para validación visual */
        .preview-container {
            width: 100%;
            height: 200px;
            border: 2px dashed #ccc;
            border-radius: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
            overflow: hidden;
            background-color: #f9f9f9;
        }
        .preview-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            display: none; /* Oculto por defecto */
        }
        .preview-text { color: #888; }
        
        .input-error { border-color: #dc3545 !important; background-color: #fff8f8; }
        .error-msg { color: #dc3545; font-size: 0.85em; margin-top: 5px; display: none; }
        
        .btn-submit:disabled { background-color: #ccc; cursor: not-allowed; }
    </style>
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

        <form method="POST" enctype="multipart/form-data" class="form-upload" id="nftForm" novalidate>
            
            <!-- TÍTULO -->
            <div class="form-group">
                <label for="title">Título del NFT *</label>
                <input type="text" name="title" id="title" maxlength="100" required 
                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                       placeholder="Ej: Cyberpunk Oswi #001">
                <div class="error-msg" id="err-title">El título es obligatorio (mínimo 3 letras).</div>
            </div>

            <!-- DESCRIPCIÓN -->
            <div class="form-group">
                <label for="abstract">Descripción</label>
                <textarea name="abstract" id="abstract" rows="4"><?php echo htmlspecialchars($_POST['abstract'] ?? ''); ?></textarea>
            </div>

            <div class="form-row">
                <!-- FECHA -->
                <div class="form-group">
                    <label for="release_date">Fecha de lanzamiento</label>
                    <input type="date" name="release_date" id="release_date" 
                           value="<?php echo htmlspecialchars($_POST['release_date'] ?? ''); ?>">
                    <div class="error-msg" id="err-date">Fecha inválida.</div>
                </div>

                <!-- PRECIO -->
                <div class="form-group">
                    <label for="price">Precio (créditos) *</label>
                    <input type="number" name="price" id="price" step="0.01" min="0" required 
                           value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
                    <div class="error-msg" id="err-price">El precio debe ser mayor o igual a 0.</div>
                </div>
            </div>

            <!-- IMAGEN CON PREVIEW -->
            <div class="form-group">
                <label for="imagen">Imagen del NFT *</label>
                
                <!-- Area de Previsualización -->
                <div class="preview-container" id="drop-zone">
                    <span class="preview-text">Vista previa de la imagen</span>
                    <img id="img-preview" src="#" alt="Vista previa">
                </div>

                <input type="file" name="imagen" id="imagen" accept="image/png, image/jpeg, image/gif, image/webp" required>
                <small>Máx: 5MB. Formatos: JPG, PNG, GIF, WEBP</small>
                <div class="error-msg" id="err-img"></div>
            </div>

            <!-- TAGS -->
            <div class="form-group">
                <label for="tags">Tags</label>
                <input type="text" name="tags" id="tags" placeholder="arte, digital, raro" 
                       value="<?php echo htmlspecialchars($_POST['tags'] ?? ''); ?>">
                <small>Separados por comas</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit" id="btn-submit">Subir NFT</button>
                <a href="admin_panel.php" class="btn-cancel">Cancelar</a>
            </div>
        </form>
    </div>

    <script>
        console.log("JS Cargado correctamente");
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('nftForm');
            const imgInput = document.getElementById('imagen');
            const imgPreview = document.getElementById('img-preview');
            const previewText = document.querySelector('.preview-text');
            
            // --- 1. VALIDACIÓN DE IMAGEN Y PREVIEW ---
            imgInput.addEventListener('change', function() {
                const file = this.files[0];
                const errDiv = document.getElementById('err-img');
                
                if (file) {
                    // Validar tamaño (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        showError(imgInput, errDiv, 'El archivo es muy pesado (Máximo 5MB).');
                        this.value = ''; // Limpiar input
                        resetPreview();
                        return;
                    }

                    // Validar tipo
                    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!validTypes.includes(file.type)) {
                        showError(imgInput, errDiv, 'Formato no válido. Solo imágenes.');
                        this.value = '';
                        resetPreview();
                        return;
                    }

                    // Si todo ok, mostrar preview
                    clearError(imgInput, errDiv);
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imgPreview.src = e.target.result;
                        imgPreview.style.display = 'block';
                        previewText.style.display = 'none';
                    }
                    reader.readAsDataURL(file);
                } else {
                    resetPreview();
                }
            });

            function resetPreview() {
                imgPreview.src = '#';
                imgPreview.style.display = 'none';
                previewText.style.display = 'block';
            }

            // --- 2. VALIDACIÓN DEL FORMULARIO AL ENVIAR ---
            form.addEventListener('submit', (e) => {
                let isValid = true;

                // Validar Título
                const title = document.getElementById('title');
                const errTitle = document.getElementById('err-title');
                if (title.value.trim().length < 3) {
                    showError(title, errTitle, 'El título debe tener al menos 3 caracteres.');
                    isValid = false;
                } else {
                    clearError(title, errTitle);
                }

                // Validar Precio
                const price = document.getElementById('price');
                const errPrice = document.getElementById('err-price');
                if (price.value === '' || parseFloat(price.value) < 0) {
                    showError(price, errPrice, 'Ingresa un precio válido (positivo).');
                    isValid = false;
                } else {
                    clearError(price, errPrice);
                }

                // Validar Imagen obligatoria
                const errImg = document.getElementById('err-img');
                if (imgInput.files.length === 0) {
                    showError(imgInput, errImg, 'Debes seleccionar una imagen para el NFT.');
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault(); // Detener envío
                }
            });

            // Funciones auxiliares de UI
            function showError(input, errDiv, msg) {
                input.classList.add('input-error');
                errDiv.textContent = msg;
                errDiv.style.display = 'block';
            }

            function clearError(input, errDiv) {
                input.classList.remove('input-error');
                errDiv.style.display = 'none';
            }
        });
    </script>
</body>
</html>