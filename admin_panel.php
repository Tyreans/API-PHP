<?php
session_start();
require_once('conexion.php');

// Verificar permisos
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die('Acceso Denegado');
}

$conn = conectarDB($_SESSION['rol']);
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $salesman_id = $_SESSION['user_id'];
    $title = trim($_POST['title'] ?? '');
    $abstract = trim($_POST['abstract'] ?? '');
    $release_date = $_POST['release_date'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $tags = trim($_POST['tags'] ?? '');
    
    // Validaciones PHP (Servidor)
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
        $url_imagen = '';
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $imagen = $_FILES['imagen'];
            if ($imagen['size'] > 5 * 1024 * 1024) {
                $mensaje = 'La imagen es demasiado pesada (Máx 5MB).';
                $tipo_mensaje = 'error';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime_type = $finfo->file($imagen['tmp_name']);
                $mimes_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                if (!in_array($mime_type, $mimes_permitidos)) {
                    $mensaje = 'Archivo no válido. Solo JPG, PNG, GIF o WEBP.';
                    $tipo_mensaje = 'error';
                } else {
                    $extension = pathinfo($imagen['name'], PATHINFO_EXTENSION);
                    $nombre_unico = uniqid('nft_', true) . '.' . $extension;
                    $ruta_destino = 'nft/' . $nombre_unico;
                    if (!file_exists('nft')) mkdir('nft', 0755, true);
                    if (move_uploaded_file($imagen['tmp_name'], $ruta_destino)) {
                        $url_imagen = $nombre_unico;
                    } else {
                        $mensaje = 'Error al mover el archivo.';
                        $tipo_mensaje = 'error';
                    }
                }
            }
        } else {
            $mensaje = 'Debes seleccionar una imagen.';
            $tipo_mensaje = 'error';
        }

        if (empty($mensaje)) {
            try {
                $tags_array = explode(',', $tags);
                $tags_clean = array_map('trim', $tags_array);
                $tags_final = implode(',', array_filter($tags_clean));

                $stmt = $conn->prepare("CALL sp_upload_nft(?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$salesman_id, $title, $abstract, $release_date, $price, $url_imagen, $tags_final])) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $nft_id = $row['NFT_ID'] ?? 'OK';
                    $mensaje = "¡NFT Creado con éxito! (ID: $nft_id)";
                    $tipo_mensaje = 'success';
                    $_POST = [];
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
        /* Estilos base */
        .preview-container {
            width: 100%; height: 200px;
            border: 2px dashed #ccc; border-radius: 10px;
            display: flex; justify-content: center; align-items: center;
            margin-bottom: 10px; overflow: hidden; background-color: #f9f9f9;
            transition: all 0.3s ease; /* Animación suave */
        }
        
        /* Clase para DOM Use #3 (Drag & Drop activo) */
        .preview-container.drag-active {
            background-color: #e0f7fa;
            border-color: #009783;
            transform: scale(1.02);
        }
        
        .preview-container img {
            max-width: 100%; max-height: 100%;
            object-fit: contain; display: none;
        }
        .preview-text { color: #888; pointer-events: none; }
        
        .input-error { border-color: #dc3545 !important; background-color: #fff8f8; }
        .error-msg { color: #dc3545; font-size: 0.85em; margin-top: 5px; display: none; }
        
        /* Estilos para DOM Use #1 (Contador) */
        .char-counter {
            text-align: right; font-size: 0.8em; color: #666; margin-top: 5px;
        }
        
        /* Estilos para DOM Use #2 (Precio USD) */
        .price-conversion {
            font-weight: bold; color: #009783; margin-left: 10px; font-size: 0.9em;
        }
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
                <div class="error-msg" id="err-title">El título es obligatorio.</div>
            </div>

            <!-- DESCRIPCIÓN (DOM USE #1: Contador) -->
            <div class="form-group">
                <label for="abstract">Descripción</label>
                <textarea name="abstract" id="abstract" rows="4" maxlength="500"><?php echo htmlspecialchars($_POST['abstract'] ?? ''); ?></textarea>
                <div class="char-counter" id="char-counter">0 / 500 caracteres</div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="release_date">Fecha de lanzamiento</label>
                    <input type="date" name="release_date" id="release_date" 
                           value="<?php echo htmlspecialchars($_POST['release_date'] ?? ''); ?>">
                </div>

                <!-- PRECIO (DOM USE #2: Conversión) -->
                <div class="form-group">
                    <label for="price">Precio (créditos) * <span id="usd-display" class="price-conversion"></span></label>
                    <input type="number" name="price" id="price" step="0.01" min="0" required 
                           value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
                    <div class="error-msg" id="err-price">Precio inválido.</div>
                </div>
            </div>

            <!-- IMAGEN (DOM USE #3: Drag & Drop) -->
            <div class="form-group">
                <label for="imagen">Imagen del NFT *</label>
                
                <!-- Zona interactiva -->
                <div class="preview-container" id="drop-zone">
                    <span class="preview-text">Arrastra tu imagen aquí o selecciona</span>
                    <img id="img-preview" src="#" alt="Vista previa">
                </div>

                <input type="file" name="imagen" id="imagen" accept="image/*" required>
                <small>Máx: 5MB. Formatos: JPG, PNG, GIF, WEBP</small>
                <div class="error-msg" id="err-img"></div>
            </div>

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
        document.addEventListener('DOMContentLoaded', () => {
            
            // ============================================================
            // DOM USO 1: CONTADOR DE CARACTERES (Manipulación de Texto)
            // ============================================================
            const abstractInput = document.getElementById('abstract');
            const charCounter = document.getElementById('char-count'); // Ojo: en HTML le puse id="char-counter"
            const countDisplay = document.getElementById('char-counter');

            abstractInput.addEventListener('input', function() {
                const currentLength = this.value.length;
                const maxLength = this.getAttribute('maxlength');
                
                // Actualizamos el texto del DOM
                countDisplay.textContent = `${currentLength} / ${maxLength} caracteres`;
                
                // Cambiamos el color dinámicamente si se acerca al límite
                if (currentLength >= 450) {
                    countDisplay.style.color = 'orange';
                } else {
                    countDisplay.style.color = '#666';
                }
            });

            // ============================================================
            // DOM USO 2: CALCULADORA DE PRECIO (Lectura y Escritura)
            // ============================================================
            const priceInput = document.getElementById('price');
            const usdDisplay = document.getElementById('usd-display');
            const conversionRate = 0.05; // Supongamos 1 Crédito = $0.05 USD

            priceInput.addEventListener('input', function() {
                const credits = parseFloat(this.value);
                
                if (!isNaN(credits) && credits > 0) {
                    const usdValue = (credits * conversionRate).toFixed(2);
                    // Insertamos HTML/Texto en el span
                    usdDisplay.innerHTML = `(≈ $${usdValue} USD)`;
                } else {
                    usdDisplay.innerHTML = ''; // Limpiamos si no hay valor
                }
            });

            // ============================================================
            // DOM USO 3: DRAG & DROP VISUAL (Manipulación de Clases)
            // ============================================================
            const dropZone = document.getElementById('drop-zone');
            const imgInput = document.getElementById('imagen');
            const imgPreview = document.getElementById('img-preview');
            const previewText = document.querySelector('.preview-text');

            // Evitar comportamiento por defecto del navegador
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            // Agregar clase visual al arrastrar encima
            dropZone.addEventListener('dragover', () => {
                dropZone.classList.add('drag-active'); // Añade clase CSS
            });

            // Quitar clase visual al salir o soltar
            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => {
                    dropZone.classList.remove('drag-active'); // Quita clase CSS
                });
            });

            // Manejar el archivo soltado (Drop)
            dropZone.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                const files = dt.files;

                if (files.length > 0) {
                    imgInput.files = files; // Asignamos el archivo al input invisible
                    // Disparamos manualmente el evento 'change' para activar la previsualización
                    imgInput.dispatchEvent(new Event('change'));
                }
            });

            // --- Lógica de Previsualización existente ---
            imgInput.addEventListener('change', function() {
                const file = this.files[0];
                const errDiv = document.getElementById('err-img');
                
                if (file) {
                    if (file.size > 5 * 1024 * 1024) {
                        showError(imgInput, errDiv, 'Archivo muy pesado (>5MB).');
                        this.value = ''; 
                        resetPreview();
                        return;
                    }
                    // Previsualizar
                    clearError(imgInput, errDiv);
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imgPreview.src = e.target.result;
                        imgPreview.style.display = 'block';
                        previewText.style.display = 'none';
                    }
                    reader.readAsDataURL(file);
                }
            });

            function resetPreview() {
                imgPreview.src = '#';
                imgPreview.style.display = 'none';
                previewText.style.display = 'block';
            }

            // --- Funciones de Error (Validación) ---
            const form = document.getElementById('nftForm');
            form.addEventListener('submit', (e) => {
                let isValid = true;
                const title = document.getElementById('title');
                const price = document.getElementById('price');

                if (title.value.trim().length < 3) {
                    showError(title, document.getElementById('err-title'), 'Título muy corto.');
                    isValid = false;
                } else clearError(title, document.getElementById('err-title'));

                if (price.value === '' || parseFloat(price.value) < 0) {
                    showError(price, document.getElementById('err-price'), 'Precio inválido.');
                    isValid = false;
                } else clearError(price, document.getElementById('err-price'));

                if (imgInput.files.length === 0) {
                    showError(imgInput, document.getElementById('err-img'), 'Falta la imagen.');
                    isValid = false;
                }

                if (!isValid) e.preventDefault();
            });

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