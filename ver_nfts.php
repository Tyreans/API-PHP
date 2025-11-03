<?php
session_start();
require_once("conexion.php");
$id_user = $_SESSION['user_id'] ?? null;
$rol = $_SESSION['rol'] ?? null;
$conn = conectarDB($rol);

$fila = null; // Imagen por defecto

if ($id_user) {
    $consultaSQL = "SELECT u.username, c.PROFILE_PIC_URL 
                    FROM users u 
                    LEFT JOIN CLIENTE c ON u.ID_USER = c.ID_USER 
                    WHERE u.ID_USER = ?";
    
    if ($stmt = $conn->prepare($consultaSQL)) {
        $stmt->execute([$id_user]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($fila && !empty($fila['PROFILE_PIC_URL'])) {
            $profile_pic = 'pfp/'.$fila['PROFILE_PIC_URL'];
            echo $profile_pic;
        }
    }
}

// ============================================
// PARÁMETROS DE FILTRO Y PAGINACIÓN
// ============================================
$tag_filtro = $_GET['tag'] ?? ''; // Tag seleccionado (vacío = todos)
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina_actual = max(1, $pagina_actual); // Mínimo página 1
$nfts_por_pagina = 12; // Docena
$offset = ($pagina_actual - 1) * $nfts_por_pagina;

// ============================================
// OBTENER TODOS LOS TAGS DISPONIBLES
// ============================================
$tags_disponibles = [];
try {
    $stmt_tags = $conn->query("SELECT DISTINCT TAG_NAME FROM TAGS ORDER BY TAG_NAME");
    $tags_disponibles = $stmt_tags->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Silenciar error
}

// ============================================
// OBTENER NFTs SEGÚN FILTRO
// ============================================
$nfts = [];
$total_nfts = 0;

try {
    if (!empty($tag_filtro)) {
        // Filtrar por tag usando el procedure
        $stmt = $conn->prepare("CALL sp_filtrar_nfts_por_tag(?)");
        $stmt->execute([$tag_filtro]);
        $nfts_completos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        
        // Calcular total y aplicar paginación manualmente
        $total_nfts = count($nfts_completos);
        $nfts = array_slice($nfts_completos, $offset, $nfts_por_pagina);
        
    } else {
        // Sin filtro: contar total primero
        $stmt_count = $conn->query("SELECT COUNT(DISTINCT ID_NFT) as total FROM vista_nfts_disponibles");
        $total_nfts = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Obtener NFTs con paginación
        $stmt = $conn->prepare("
            SELECT DISTINCT ID_NFT, TITLE, ABSTRACT, PRICE, url_imagen, VENDEDOR_NOMBRE
            FROM vista_nfts_disponibles
            GROUP BY ID_NFT
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $nfts_por_pagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $nfts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error_msg = "Error al obtener NFTs: " . $e->getMessage();
}

// Calcular total de páginas
$total_paginas = ceil($total_nfts / $nfts_por_pagina);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OswiFTS - Marketplace</title>
    <link href="https://fonts.googleapis.com/css2?family=Krub&display=swap" rel="stylesheet">
    <link rel="preload" href="csspag/normalize.css" as="style">
    <link rel="stylesheet" href="csspag/normalize.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <link rel="preload" href="csspag/cssamigo.css" as="style">
    <link href="csspag/cssamigo.css" rel="stylesheet">
    
    <style>
        /* Estilos para la foto de perfil en el menú */
        .profile-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .profile-pic-small {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
        }
        
        .profile-username {
            font-weight: 600;
        }

        /* Estilos adicionales para el filtro y cards */
        .filter-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 80px auto 30px;
            max-width: 1200px;
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-form select {
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            flex: 1;
            min-width: 200px;
        }
        
        .filter-form button {
            padding: 10px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .filter-form button:hover {
            transform: translateY(-2px);
        }
        
        .filter-form .btn-clear {
            background: #6c757d;
        }
        
        .nft-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .nft-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .nft-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .nft-card img {
            width: 100%;
            height: 280px;
            object-fit: cover;
        }
        
        .nft-card-content {
            padding: 20px;
        }
        
        .nft-card h3 {
            margin: 0 0 10px;
            color: #333;
            font-size: 1.3em;
        }
        
        .nft-price {
            font-size: 1.5em;
            color: #667eea;
            font-weight: bold;
            margin: 15px 0;
        }
        
        .nft-card button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .nft-card button:hover {
            transform: scale(1.05);
        }
        
        /* Paginación */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 40px 0;
            flex-wrap: wrap;
        }
        
        .pagination a,
        .pagination span {
            padding: 10px 15px;
            background: white;
            border: 2px solid #667eea;
            border-radius: 5px;
            text-decoration: none;
            color: #667eea;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .pagination a:hover {
            background: #667eea;
            color: white;
        }
        
        .pagination .active {
            background: #667eea;
            color: white;
        }
        
        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
            border-color: #ccc;
            color: #ccc;
        }
        
        .results-info {
            text-align: center;
            color: #666;
            margin: 20px 0;
            font-size: 1.1em;
        }
        
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-results h2 {
            font-size: 2em;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <header> 
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <img src="Imagenes/to_main.png" alt="Logo" width="30" height="24" class="d-inline-block align-middle">
                OSWI-FTS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDarkNavbar" aria-controls="offcanvasDarkNavbar" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="offcanvasDarkNavbar" aria-labelledby="offcanvasDarkNavbarLabel">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="offcanvasDarkNavbarLabel">Menú</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="index.php">Inicio</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Link</a>
                        </li>
                        
                        <!-- MENÚ DESPLEGABLE CON FOTO DE PERFIL -->
                        <li class="nav-item dropdown">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <!-- Usuario logueado: mostrar foto y nombre -->
                                <a class="nav-link dropdown-toggle profile-dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <img src="<?php echo htmlspecialchars($profile_pic); ?>" 
                                         alt="Perfil" 
                                         class="profile-pic-small">
                                    <span class="profile-username"><?php echo htmlspecialchars($fila['username']); ?></span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-dark">
                                    <li><a class="dropdown-item disabled" href="#">👋 Hola, <?php echo htmlspecialchars($fila['username']); ?></a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="library.php">📚 Mi Biblioteca</a></li>
                                    <li><a class="dropdown-item" href="recibos.php">🧾 Mis Recibos</a></li>
                                    <li><a class="dropdown-item" href="update_pfp.php">🖼️ Cambiar Foto</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="logout.php">🚪 Cerrar sesión</a></li>
                                </ul>
                            <?php else: ?>
                                <!-- Usuario no logueado: mostrar "Más" -->
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Más
                                </a>
                                <ul class="dropdown-menu dropdown-menu-dark">
                                    <li><a class="dropdown-item" href="login.php">Iniciar sesión</a></li>
                                    <li><a class="dropdown-item" href="new_user.php">Registrarse</a></li>
                                </ul>
                            <?php endif; ?>
                        </li>
                    </ul>
                    
                    <!-- BARRA DE BÚSQUEDA ELIMINADA -->
                </div>
            </div>
        </div>
        </nav>
    
    </header>

    <main class="contenedor">
        <!-- Filtro de Tags -->
        <div class="filter-container">
            <form method="GET" action="ver_nfts.php" class="filter-form">
                <label for="tag" style="font-weight: 600; color: #333;">Filtrar por tag:</label>
                <select name="tag" id="tag">
                    <option value="">Todos los NFTs</option>
                    <?php foreach ($tags_disponibles as $tag): ?>
                        <option value="<?php echo htmlspecialchars($tag); ?>" 
                                <?php echo ($tag_filtro === $tag) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tag); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Filtrar</button>
                <?php if (!empty($tag_filtro)): ?>
                    <a href="ver_nfts" class="filter-form button btn-clear" style="padding: 10px 25px; text-decoration: none; border-radius: 5px;">Limpiar filtro</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Información de resultados -->
        <div class="results-info">
            <?php if (!empty($tag_filtro)): ?>
                Mostrando NFTs con tag: <strong><?php echo htmlspecialchars($tag_filtro); ?></strong>
                (<?php echo $total_nfts; ?> resultado<?php echo $total_nfts !== 1 ? 's' : ''; ?>)
            <?php else: ?>
                Mostrando todos los NFTs disponibles (<?php echo $total_nfts; ?> total<?php echo $total_nfts !== 1 ? 'es' : ''; ?>)
            <?php endif; ?>
        </div>

        <!-- Grid de NFTs -->
        <?php if (count($nfts) > 0): ?>
            <div class="nft-grid">
                <?php foreach ($nfts as $nft): ?>
                    <div class="nft-card">
                        <img src="<?php echo htmlspecialchars('nft/'.$nft['url_imagen']); ?>" 
                             alt="<?php echo htmlspecialchars($nft['TITLE']); ?>">
                        <div class="nft-card-content">
                            <h3><?php echo htmlspecialchars($nft['TITLE']); ?></h3>
                            <div class="nft-price">$<?php echo number_format($nft['PRICE'], 2); ?></div>
                            <form action="product.php" method="get">
                                <input type="hidden" name="id" value="<?php echo $nft['ID_NFT']; ?>">
                                <button type="submit">Ver detalles</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <div class="pagination">
                    <!-- Botón anterior -->
                    <?php if ($pagina_actual > 1): ?>
                        <a href="?pagina=<?php echo $pagina_actual - 1; ?><?php echo !empty($tag_filtro) ? '&tag=' . urlencode($tag_filtro) : ''; ?>">
                            ← Anterior
                        </a>
                    <?php else: ?>
                        <span class="disabled">← Anterior</span>
                    <?php endif; ?>

                    <!-- Números de página -->
                    <?php
                    // Mostrar máximo 5 páginas alrededor de la actual
                    $rango = 2;
                    $inicio = max(1, $pagina_actual - $rango);
                    $fin = min($total_paginas, $pagina_actual + $rango);
                    
                    if ($inicio > 1): ?>
                        <a href="?pagina=1<?php echo !empty($tag_filtro) ? '&tag=' . urlencode($tag_filtro) : ''; ?>">1</a>
                        <?php if ($inicio > 2): ?>
                            <span>...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $inicio; $i <= $fin; $i++): ?>
                        <?php if ($i == $pagina_actual): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?pagina=<?php echo $i; ?><?php echo !empty($tag_filtro) ? '&tag=' . urlencode($tag_filtro) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($fin < $total_paginas): ?>
                        <?php if ($fin < $total_paginas - 1): ?>
                            <span>...</span>
                        <?php endif; ?>
                        <a href="?pagina=<?php echo $total_paginas; ?><?php echo !empty($tag_filtro) ? '&tag=' . urlencode($tag_filtro) : ''; ?>">
                            <?php echo $total_paginas; ?>
                        </a>
                    <?php endif; ?>

                    <!-- Botón siguiente -->
                    <?php if ($pagina_actual < $total_paginas): ?>
                        <a href="?pagina=<?php echo $pagina_actual + 1; ?><?php echo !empty($tag_filtro) ? '&tag=' . urlencode($tag_filtro) : ''; ?>">
                            Siguiente →
                        </a>
                    <?php else: ?>
                        <span class="disabled">Siguiente →</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="no-results">
                <h2>😔 No se encontraron NFTs</h2>
                <p>No hay NFTs disponibles<?php echo !empty($tag_filtro) ? ' para el tag "' . htmlspecialchars($tag_filtro) . '"' : ''; ?>.</p>
                <?php if (!empty($tag_filtro)): ?>
                    <a href="index.php" style="color: #667eea; font-weight: 600;">Ver todos los NFTs</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <p>Todos los derechos reservados. Rodrigo Yahir Hernandez Caro Freelancer</p>        
    </footer>
</body>
</html>