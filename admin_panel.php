<?php
session_start();
require_once("conexion.php");

// Verificar que el usuario esté logueado y sea admin
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$id_user = $_SESSION['user_id'];
$conn = conectarDB($_SESSION['rol']);

// Obtener información del vendedor
$vendedor_info = null;
$consultaSQL = "SELECT username, email FROM users WHERE ID_USER = ?";
if ($stmt = $conn->prepare($consultaSQL)) {
    $stmt->execute([$id_user]);
    $vendedor_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ============================================
// PARÁMETROS DE PAGINACIÓN
// ============================================
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina_actual = max(1, $pagina_actual);
$nfts_por_pagina = 12;
$offset = ($pagina_actual - 1) * $nfts_por_pagina;

// ============================================
// OBTENER NFTs DEL VENDEDOR
// ============================================
$nfts = [];
$total_nfts = 0;
$nfts_vendidos = 0;
$nfts_disponibles = 0;

try {
    // Contar total de NFTs del vendedor
    $stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM NFT WHERE SALESMAN_ID = ?");
    $stmt_count->execute([$id_user]);
    $total_nfts = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Obtener NFTs con información de si están vendidos
    $stmt = $conn->prepare("
        SELECT 
            n.ID_NFT,
            n.TITLE,
            n.ABSTRACT,
            n.PRICE,
            n.url_imagen,
            n.RELEASE_DATE,
            CASE 
                WHEN l.ID_NFT IS NOT NULL THEN 'VENDIDO'
                ELSE 'DISPONIBLE'
            END AS ESTADO,
            l.DATE_ACQUIRED AS FECHA_VENTA
        FROM NFT n
        LEFT JOIN LIBRARY l ON n.ID_NFT = l.ID_NFT
        WHERE n.SALESMAN_ID = ?
        ORDER BY n.ID_NFT DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $nfts_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute([$id_user]);
    $nfts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar vendidos y disponibles
    $stmt_stats = $conn->prepare("
        SELECT 
            SUM(CASE WHEN l.ID_NFT IS NOT NULL THEN 1 ELSE 0 END) as vendidos,
            SUM(CASE WHEN l.ID_NFT IS NULL THEN 1 ELSE 0 END) as disponibles
        FROM NFT n
        LEFT JOIN LIBRARY l ON n.ID_NFT = l.ID_NFT
        WHERE n.SALESMAN_ID = ?
    ");
    $stmt_stats->execute([$id_user]);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    $nfts_vendidos = $stats['vendidos'] ?? 0;
    $nfts_disponibles = $stats['disponibles'] ?? 0;
    
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
    <title>Panel de Administración - OSWI-FTS</title>
    <link href="https://fonts.googleapis.com/css2?family=Krub&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="csspag/normalize.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <link href="csspag/cssamigo.css" rel="stylesheet">
    
    <style>
        /* Estilos del panel de admin */
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        
        .admin-nav {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .admin-logo {
            display: flex;
            align-items: center;
            gap: 15px;
            color: white;
            font-size: 1.5em;
            font-weight: 600;
            text-decoration: none;
        }
        
        .admin-logo img {
            width: 40px;
            height: 40px;
        }
        
        .admin-user-info {
            display: flex;
            align-items: center;
            gap: 20px;
            color: white;
        }
        
        .admin-username {
            font-size: 1.1em;
            font-weight: 600;
        }
        
        .admin-logout {
            padding: 8px 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .admin-logout:hover {
            background: white;
            color: #667eea;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 100px auto 40px;
            padding: 0 20px;
        }
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .stat-card .number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
        }
        
        .actions-bar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .btn-upload {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s;
        }
        
        .btn-upload:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .section-title {
            font-size: 1.8em;
            color: #333;
            margin-bottom: 20px;
        }
        
        .nft-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .nft-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }
        
        .nft-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .nft-status {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85em;
            z-index: 10;
        }
        
        .status-vendido {
            background: #dc3545;
            color: white;
        }
        
        .status-disponible {
            background: #28a745;
            color: white;
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
        
        .nft-date {
            color: #666;
            font-size: 0.9em;
            margin-top: 10px;
        }
        
        .nft-card button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .nft-card button:hover {
            background: #764ba2;
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
        
        .no-nfts {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .no-nfts h2 {
            font-size: 2em;
            color: #666;
            margin-bottom: 20px;
        }
        
        .no-nfts p {
            color: #999;
            margin-bottom: 30px;
        }
        
        @media (max-width: 768px) {
            .admin-nav {
                flex-direction: column;
                gap: 15px;
            }
            
            .actions-bar {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header de Admin -->
    <header class="admin-header">
        <nav class="admin-nav">
            <a href="admin_panel.php" class="admin-logo">
                <img src="Imagenes/to_main.png" alt="Logo">
                OSWI-FTS Admin
            </a>
            <div class="admin-user-info">
                <div class="admin-username">
                    👋 Hola, <?php echo htmlspecialchars($vendedor_info['username']); ?>
                </div>
                <a href="logout.php" class="admin-logout">Cerrar sesión</a>
            </div>
        </nav>
    </header>

    <main class="admin-container">
        <!-- Estadísticas -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Total de NFTs</h3>
                <div class="number"><?php echo $total_nfts; ?></div>
            </div>
            <div class="stat-card">
                <h3>NFTs Vendidos</h3>
                <div class="number" style="color: #dc3545;"><?php echo $nfts_vendidos; ?></div>
            </div>
            <div class="stat-card">
                <h3>NFTs Disponibles</h3>
                <div class="number" style="color: #28a745;"><?php echo $nfts_disponibles; ?></div>
            </div>
        </div>

        <!-- Barra de acciones -->
        <div class="actions-bar">
            <h2 class="section-title" style="margin: 0;">Mis NFTs</h2>
            <a href="upload_nft.php" class="btn-upload">📤 Subir Nuevo NFT</a>
        </div>

        <!-- Grid de NFTs -->
        <?php if (count($nfts) > 0): ?>
            <div class="nft-grid">
                <?php foreach ($nfts as $nft): ?>
                    <div class="nft-card">
                        <span class="nft-status <?php echo $nft['ESTADO'] === 'VENDIDO' ? 'status-vendido' : 'status-disponible'; ?>">
                            <?php echo $nft['ESTADO']; ?>
                        </span>
                        <img src="<?php echo htmlspecialchars($nft['url_imagen']); ?>" 
                             alt="<?php echo htmlspecialchars($nft['TITLE']); ?>">
                        <div class="nft-card-content">
                            <h3><?php echo htmlspecialchars($nft['TITLE']); ?></h3>
                            <div class="nft-price">$<?php echo number_format($nft['PRICE'], 2); ?></div>
                            
                            <?php if ($nft['ESTADO'] === 'VENDIDO'): ?>
                                <div class="nft-date" style="color: #dc3545; font-weight: 600;">
                                    🎉 Vendido el: <?php echo date('d/m/Y', strtotime($nft['FECHA_VENTA'])); ?>
                                </div>
                            <?php else: ?>
                                <div class="nft-date">
                                    📅 Publicado: <?php echo $nft['RELEASE_DATE'] ? date('d/m/Y', strtotime($nft['RELEASE_DATE'])) : 'Sin fecha'; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form action="product.php" method="get" style="margin-top: 15px;">
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
                        <a href="?pagina=<?php echo $pagina_actual - 1; ?>">← Anterior</a>
                    <?php else: ?>
                        <span class="disabled">← Anterior</span>
                    <?php endif; ?>

                    <!-- Números de página -->
                    <?php
                    $rango = 2;
                    $inicio = max(1, $pagina_actual - $rango);
                    $fin = min($total_paginas, $pagina_actual + $rango);
                    
                    if ($inicio > 1): ?>
                        <a href="?pagina=1">1</a>
                        <?php if ($inicio > 2): ?>
                            <span>...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $inicio; $i <= $fin; $i++): ?>
                        <?php if ($i == $pagina_actual): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($fin < $total_paginas): ?>
                        <?php if ($fin < $total_paginas - 1): ?>
                            <span>...</span>
                        <?php endif; ?>
                        <a href="?pagina=<?php echo $total_paginas; ?>"><?php echo $total_paginas; ?></a>
                    <?php endif; ?>

                    <!-- Botón siguiente -->
                    <?php if ($pagina_actual < $total_paginas): ?>
                        <a href="?pagina=<?php echo $pagina_actual + 1; ?>">Siguiente →</a>
                    <?php else: ?>
                        <span class="disabled">Siguiente →</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="no-nfts">
                <h2>📦 No tienes NFTs publicados</h2>
                <p>Comienza a vender creando tu primer NFT</p>
                <a href="upload_nft.php" class="btn-upload">Subir mi primer NFT</a>
            </div>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <p>Todos los derechos reservados. Rodrigo Yahir Hernandez Caro Freelancer</p>        
    </footer>
</body>
</html>