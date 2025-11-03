<?php
session_start();
require_once('conexion.php'); // <-- 1. LA SOLUCIÓN PRINCIPAL

$id_user = $_SESSION['user_id'] ?? null;
$rol = $_SESSION['rol'] ?? null;

// 2. DEFINE UNA IMAGEN POR DEFECTO
$profile_pic = 'pfp/default_user.png'; // (o la ruta de tu imagen por defecto)
$fila = null; 

if ($id_user) {
    $conn = conectarDB($rol); // Ahora $conn solo se crea si el user existe
    $consultaSQL = "SELECT u.username, c.PROFILE_PIC_URL 
                    FROM users u 
                    LEFT JOIN CLIENTE c ON u.ID_USER = c.ID_USER 
                    WHERE u.ID_USER = ?";
    
    if ($stmt = $conn->prepare($consultaSQL)) {
        $stmt->execute([$id_user]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si hay datos Y la foto no está vacía, actualiza la variable
        if ($fila && !empty($fila['PROFILE_PIC_URL'])) {
            $profile_pic = 'pfp/'.$fila['PROFILE_PIC_URL'];
            // echo $profile_pic; // <-- 3. QUITA ESTE ECHO, no es necesario aquí
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OswiFTS</title>
    <link href="https://fonts.googleapis.com/css2?family=Krub&display=swap" 
    rel="stylesheet">
    <link rel="preload" href="csspag/normalize.css" as="style">
    <link rel="stylesheet" href="csspag/normalize.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <!--
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    
    <link rel="preload" href="csspag/cssamigo.css" as ="style">
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

    <main class="contenedor sombra">
        <section class="original">
            <div class="amigo-original">
                <img src="imgAmigo/Ulloa_nft1.png" class="amigo-img-original">
                <div class="amigo-original-contenido">
                    <div class="nft-info-original">
                        <h2>El Primer Amigo</h2>
                        <p>Este es el primer NFT de la colección "El Primer Amigo", que representa la amistad y la lealtad. Cada NFT en esta colección es único y captura la esencia de tener un amigo verdadero a tu lado.</p>
                    </div>
                    <div class="button-info-nfts-container">
                        <form action="product.php" method="get">
                            <input type="hidden" name="id" value="8"> 
                            <input type="submit" value="Detalles" class="bton">
                        </form>
                    </div>
                </div>
            </div>
        </section>
        <section class="variantes">
            <div class="amigos">
                <img src="imgAmigo/A1.png" class="amigo-img">
                <div class="elemts">
                    <div class="nft-info">
                        <h3>Amigo #1</h3>
                    </div>
                    <div class="button-info-nfts-container">
                        <form action="product.php" method="get">
                            <input type="hidden" name="id" value="1"> 
                            <input type="submit" value="Detalles" class="bton">
                        </form>
                    </div>
                </div>
            </div>
            <div class="amigos">
                <img src="imgAmigo/A2.png" class="amigo-img">
                <div class="nft-info">
                    <h3>Amigo #2</h3>
                </div>
                <div class="button-info-nfts-container">
                    <form action="product.php" method="get">
                        <input type="hidden" name="id" value="2"> 
                        <input type="submit" value="Detalles" class="bton">
                    </form>
                </div>
            </div>
            <div class="amigos">
                <img src="imgAmigo/A3.png" class="amigo-img">
                <div class="nft-info">
                    <h3>Amigo #3</h3>
                </div>
                <div class="button-info-nfts-container">
                    <form action="product.php" method="get">
                        <input type="hidden" name="id" value="3"> 
                        <input type="submit" value="Detalles" class="bton">
                    </form>
                </div>
            </div>
            <div class="amigos">
                <img src="imgAmigo/A4.png" class="amigo-img">
                <div class="nft-info">
                    <h3>Amigo #4</h3>
                </div>
                <div class="button-info-nfts-container">
                    <form action="product.php" method="get">
                        <input type="hidden" name="id" value="12"> 
                        <input type="submit" value="Detalles" class="bton">
                    </form>
                </div>
            </div>
            <div class="amigos">
                <img src="imgAmigo/A5.png" class="amigo-img">
                <div class="nft-info">
                    <h3>Amigo #5</h3>
                </div>
                <div class="button-info-nfts-container">
                    <form action="product.php" method="get">
                        <input type="hidden" name="id" value="13"> 
                        <input type="submit" value="Detalles" class="bton">
                    </form>
                </div>
            </div>
            <div class="amigos">
                <img src="imgAmigo/A6.png" class="amigo-img">
                <div class="nft-info">
                    <h3>Amigo #6</h3>
                </div>
                <div class="button-info-nfts-container">
                    <form action="product.php" method="get">
                        <input type="hidden" name="id" value="14"> 
                        <input type="submit" value="Detalles" class="bton">
                    </form>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <p> Todos los derechos reservados. Rodrigo Yahir Hernandez Caro Freelancer</p>        
    </footer>

</body>
</html>