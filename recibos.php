<?php
session_start();
require_once('conexion.php'); // Asegúrate de que este archivo exista

// 1. Verificación de seguridad: Redirigir si no está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2. Obtener datos del usuario de la sesión
$id_user = $_SESSION['user_id'];
$rol = $_SESSION['rol']; // Rol de 'cliente'
$profile_pic = 'pfp/default.png'; // Valor por defecto
$username = 'Usuario'; // Valor por defecto
$recibos = [];

try {
    $conn = conectarDB($rol);

    // 3. Obtener datos del perfil del usuario (para el header)
    $consultaSQL_user = "SELECT u.username, c.PROFILE_PIC_URL 
                         FROM users u 
                         LEFT JOIN CLIENTE c ON u.ID_USER = c.ID_USER 
                         WHERE u.ID_USER = ?";
    
    if ($stmt_user = $conn->prepare($consultaSQL_user)) {
        $stmt_user->execute([$id_user]);
        $fila_user = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if ($fila_user) {
            $username = $fila_user['username'];
            if (!empty($fila_user['PROFILE_PIC_URL'])) {
                $profile_pic = 'pfp/' . htmlspecialchars($fila_user['PROFILE_PIC_URL']);
            }
        }
    }

    // 4. Obtener los recibos (compras) del usuario
    // Consultamos las compras y las unimos con los detalles y los NFTs
    $consultaSQL_receipts = "
        SELECT 
            p.PURCHASE_ID,
            p.PURCHASE_DATE,
            p.TOTAL,
            p.receipt_url,
            n.TITLE AS NFT_TITLE
        FROM 
            PURCHASE p
        INNER JOIN 
            PURCHASE_DETAILS pd ON p.PURCHASE_ID = pd.PURCHASE_ID
        INNER JOIN 
            NFT n ON pd.ID_NFT = n.ID_NFT
        WHERE 
            p.ID_USER = ?
        ORDER BY 
            p.PURCHASE_DATE DESC
    ";

    $stmt_receipts = $conn->prepare($consultaSQL_receipts);
    $stmt_receipts->execute([$id_user]);
    $recibos = $stmt_receipts->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Manejo de errores
    die("Error de conexión o consulta: " . $e->getMessage());
} finally {
    $conn = null;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Recibos - OswiFTS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Krub&display=swap" rel="stylesheet">
    <link rel="preload" href="csspag/normalize.css" as="style">
    <link rel="stylesheet" href="csspag/normalize.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="preload" href="csspag/cssamigo.css" as ="style">
    <link href="csspag/cssamigo.css" rel="stylesheet"> 
    
    <style>
        .profile-dropdown-toggle { display: flex; align-items: center; gap: 10px; }
        .profile-pic-small { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; }
        .profile-username { font-weight: 600; }
        
        .table-responsive {
            margin-top: 20px;
        }
        .bton-pdf {
            text-decoration: none; 
            padding: 8px 12px; 
            background-color: #0d6efd; 
            color: white; 
            border-radius: 5px;
            font-size: 0.9rem;
        }
        .bton-pdf:hover {
            background-color: #0b5ed7;
        }
         .bton-pdf-disabled {
            background-color: #6c757d;
            cursor: not-allowed;
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
                            <a class="nav-link" href="index.php">Inicio</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Marketplace</a>
                        </li>
                        
                        <li class="nav-item dropdown">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a class="nav-link dropdown-toggle profile-dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Perfil" class="profile-pic-small">
                                    <span class="profile-username"><?php echo htmlspecialchars($username); ?></span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-dark">
                                    <li><a class="dropdown-item disabled" href="#">👋 Hola, <?php echo htmlspecialchars($username); ?></a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="library.php">📚 Mi Biblioteca</a></li>
                                    <li><a class="dropdown-item active" href="recibos.php" aria-current="page">🧾 Mis Recibos</a></li>
                                    <li><a class="dropdown-item" href="update_pfp.php">🖼️ Cambiar Foto</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="logout.php">🚪 Cerrar sesión</a></li>
                                </ul>
                            <?php else: ?>
                                <a class="nav-link" href="login.php">Iniciar sesión</a>
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        </nav>
    </header>

    <main class="contenedor sombra">
        <h1 style="text-align: center; margin-bottom: 40px;">🧾 Mis Recibos</h1>

        <div class="container-fluid">
            <?php if (empty($recibos)): ?>
                <div class="col-12 text-center">
                    <p>Aún no tienes ningún recibo de compra.</p>
                    <a href="marketplace.php" class="bton" style="max-width: 300px; margin: 20px auto;">¡Explorar el Marketplace!</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th scope="col">ID Compra</th>
                                <th scope="col">Fecha</th>
                                <th scope="col">Producto</th>
                                <th scope="col">Total</th>
                                <th scope="col">Recibo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recibos as $recibo): ?>
                                <tr>
                                    <th scope="row"><?php echo htmlspecialchars($recibo['PURCHASE_ID']); ?></th>
                                    <td><?php echo date("d/m/Y H:i", strtotime($recibo['PURCHASE_DATE'])); ?></td>
                                    <td><?php echo htmlspecialchars($recibo['NFT_TITLE']); ?></td>
                                    <td>$<?php echo number_format($recibo['TOTAL'], 2); ?></td>
                                    <td>
                                        <?php if (!empty($recibo['receipt_url']) && file_exists($recibo['receipt_url'])): ?>
                                            <a href="<?php echo htmlspecialchars($recibo['receipt_url']); ?>" 
                                               target="_blank" 
                                               class="bton-pdf">
                                               Ver PDF 📄
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">No disponible</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <p> Todos los derechos reservados. Rodrigo Yahir Hernandez Caro Freelancer</p>       
    </footer>

</body>
</html>