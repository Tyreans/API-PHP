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
$nfts_comprados = [];

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

    // 4. Obtener los NFTs de la biblioteca del usuario
    // Esta es la consulta principal que une la biblioteca del usuario con los NFTs
    $consultaSQL_library = "
        SELECT 
            n.ID_NFT,
            n.TITLE,
            n.ABSTRACT,
            n.url_imagen,
            u_vendedor.USERNAME AS VENDEDOR_NOMBRE,
            l.DATE_ACQUIRED
        FROM 
            LIBRARY l
        INNER JOIN 
            NFT n ON l.ID_NFT = n.ID_NFT
        INNER JOIN 
            VENDEDOR v ON n.SALESMAN_ID = v.ID_USER
        INNER JOIN 
            USERS u_vendedor ON v.ID_USER = u_vendedor.ID_USER
        WHERE 
            l.ID_USER = ?
        ORDER BY 
            l.DATE_ACQUIRED DESC
    ";

    $stmt_library = $conn->prepare($consultaSQL_library);
    $stmt_library->execute([$id_user]);
    $nfts_comprados = $stmt_library->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Mi Biblioteca - OswiFTS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Krub&display=swap" rel="stylesheet">
    <link rel="preload" href="csspag/normalize.css" as="style">
    <link rel="stylesheet" href="csspag/normalize.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="preload" href="csspag/cssamigo.css" as ="style">
    <link href="csspag/cssamigo.css" rel="stylesheet"> 
    
    <style>
        /* Estilos para la foto de perfil en el menú (de tu archivo) */
        .profile-dropdown-toggle { display: flex; align-items: center; gap: 10px; }
        .profile-pic-small { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; }
        .profile-username { font-weight: 600; }
        
        /* Estilos para las tarjetas de la biblioteca */
        .card-img-top-library {
            width: 100%;
            height: 250px; /* Altura fija para la imagen */
            object-fit: cover; /* Asegura que la imagen cubra el espacio */
        }
        .card {
            background-color: #343a40; /* Fondo oscuro para la tarjeta */
            border: 1px solid #495057;
            color: #fff; /* Texto blanco */
            height: 100%; /* Asegura que todas las tarjetas tengan la misma altura */
        }
        .card-body {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .card-title {
            font-weight: bold;
        }
        .card-artist, .card-date {
            font-size: 0.9rem;
            color: #adb5bd; /* Color grisáceo para detalles */
        }
        .bton { /* Reutilizando tu clase de botón */
            display: block;
            width: 100%;
            text-align: center;
            background-color: #0d6efd; /* Color de Bootstrap primary */
            color: white;
            padding: 10px;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            margin-top: 15px; /* Espacio arriba del botón */
        }
        .bton:hover {
            background-color: #0b5ed7;
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
                            <a class="nav-link" href="marketplace.php">Marketplace</a>
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
                                    <li><a class="dropdown-item active" href="library.php" aria-current="page">📚 Mi Biblioteca</a></li>
                                    <li><a class="dropdown-item" href="recibos.php">🧾 Mis Recibos</a></li>
                                    <li><a class="dropdown-item" href="update_pfp.php">🖼️ Cambiar Foto</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="logout.php">🚪 Cerrar sesión</a></li>
                                </ul>
                            <?php else: ?>
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
                </div>
            </div>
        </div>
        </nav>
    </header>

    <main class="contenedor sombra">
        <h1 style="text-align: center; margin-bottom: 40px;">📚 Mi Biblioteca</h1>

        <div class="container-fluid">
            <div class="row">
                <?php if (empty($nfts_comprados)): ?>
                    <div class="col-12 text-center">
                        <p>Aún no tienes ningún NFT en tu biblioteca.</p>
                        <a href="marketplace.php" class="bton" style="max-width: 300px; margin: 20px auto;">¡Explorar el Marketplace!</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($nfts_comprados as $nft): ?>
                        <div class="col-12 col-md-6 col-lg-4 col-xl-3 mb-4">
                            <div class="card">
                                <img src="<?php echo htmlspecialchars(string: 'nft/'.$nft['url_imagen']); ?>" class="card-img-top-library" alt="<?php echo htmlspecialchars($nft['TITLE']); ?>">
                                <div class="card-body">
                                    <div>
                                        <h5 class="card-title"><?php echo htmlspecialchars($nft['TITLE']); ?></h5>
                                        <p class="card-artist">Artista: <?php echo htmlspecialchars($nft['VENDEDOR_NOMBRE']); ?></p>
                                        <p class="card-date">Adquirido: <?php echo date("d/m/Y", strtotime($nft['DATE_ACQUIRED'])); ?></p>
                                    </div>
                                    <a href="product.php?id=<?php echo $nft['ID_NFT']; ?>" class="bton">Ver Detalles</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p> Todos los derechos reservados. Rodrigo Yahir Hernandez Caro Freelancer</p>       
    </footer>

</body>
</html>