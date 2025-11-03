<?php
session_start();
require_once("conexion.php");
$id_user = $_SESSION['user_id'] ?? null;
$rol = $_SESSION['rol'] ?? null;
$conn = conectarDB($rol);

$consultaSQL = "SELECT username FROM users WHERE ID_USER = ?";

if ($stmt = $conn->prepare($consultaSQL)) {
        $stmt->execute([$id_user]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // Error al preparar la consulta (puedes loguearlo)
    die("Error al preparar la consulta: ". $conn->error); // Quita el comentario para depurar
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OswiFTS</title>
    <link href="https://fonts.googleapis.com/css2?family=Krub&display=swap" rel="stylesheet">
    <link rel="preload" href="csspag/normalize.css" as="style">
    <link rel="stylesheet" href="csspag/normalize.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <link rel="preload" href="csspag/styles.css" as="style">
    <link href="csspag/styles.css" rel="stylesheet">
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
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Más
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark">
                                <li><a class="dropdown-item" href="#">Action</a></li>
                                <li><a class="dropdown-item" href="#">Another action</a></li>
                                <li><hr class="dropdown-divider"></li>

                                <!-- Aquí viene la parte dinámica -->
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <li><a class="dropdown-item disabled" href="#">👋 Hola, <?php echo htmlspecialchars($fila['username']); ?></a></li>
                                    <li><a class="dropdown-item" href="logout.php">Cerrar sesión</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="login.php">Iniciar sesión</a></li>
                                    <li><a class="dropdown-item" href="new_user.php">Registrarse</a></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    </ul>
                    <form class="d-flex mt-3" role="search">
                        <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search"/>
                        <button class="btn btn-success" type="submit">Search</button>
                    </form>
                </div>
            </div>
        </div>
        </nav>

        <section class="hero">
            <div class="contenido-hero">
                <div class="ubicacion sombra">
                    <img src="Imagenes/neon-oswi.png" class="img-neonOswi">
                    <div class="descripciones-Header">
                        <h2>Oswi-FTS</h2>
                        <h3>Cyber-Oswi</h3>
                        <p>Edición limitada de Oswi-FT's, con <br>
                            cyber-oswi mantente actualizado</p>
                    </div>
                </div>
            </div>
        </section>
    
    </header>

    <main class="contenedor sombra">
        <section class="nfts">
            <div class="nft">
                <img src="Imagenes/nfts/oswi_nft1.png" class="nft-img">
                <div class="nft-info">
                    <h3>La sonrisa que rompió a C++</h3>
                    <p>Obtenga ingresos usando la imagen del ingeniero Oswaldo <br><br><br> 
                        Volumen Total: 999999 Pejecoins <br>
                        Precio Mínimo 8567 USD<br>
                        Precio de Venta Unitario 100 LOKA<br>
                        Total de unidades emitidas 10
                    </p>
                </div>
                <div class="button-info-nfts-container">
                    <a href="coleccionsonrisa.php" class="btn">Página de la colección</a>
                    <a href="#" class="btn">Más descripciones</a>
                </div>
            </div>
            <div class="nft">
                <img src="Imagenes/nfts/ulloa_port.png" class="nft-img">
                <div class="nft-info">
                    <h3>El Amigo de los Mundos</h3>
                    <p>Sea educado y aprenda a saludar a Edmundo y Raymundo<br><br><br> 
                        Volumen Total: 999999 Pejecoins <br>
                        Precio Mínimo 8567 USD<br>
                        Precio de Venta Unitario 100 LOKA<br>
                        Total de unidades emitidas 10
                    </p>
                </div>
                <div class="button-info-nfts-container">
                    <a href="coleccionamigo.php" class="btn">Página de la colección</a>
                    <a href="#" class="btn">Más descripciones</a>
                </div>
            </div>
            <div class="nft">
                <img src="Imagenes/nfts/oswi_port.png" class="nft-img">
                <div class="nft-info">
                    <h3>El brillo del mañana</h3>
                    <p>Descabellados misterios del futuro pasado<br><br><br> 
                        Volumen Total: 999999 Pejecoins <br>
                        Precio Mínimo 8567 USD<br>
                        Precio de Venta Unitario 100 LOKA<br>
                        Total de unidades emitidas 10
                    </p>
                </div>
                <div class="button-info-nfts-container">
                    <a href="coleccionbrillo.php" class="btn">Página de la colección</a>
                    <a href="#" class="btn">Más descripciones</a>
                </div>
            </div>
        </section>
    
        <section> 
            <form class="formulario">
                <fieldset>
                    <legend>Contáctanos llenando todos los campos</legend>

                    <div class="contenedor-campos">
                        <div class="campo">
                            <label>Nombre</label>
                            <input class="input-text" type="text" placeholder="Nombre">
                        </div>
                        <div class="campo">
                            <label>Teléfono</label>
                            <input class="input-text" type="tel" placeholder="Teléfono">
                        </div>
                        <div class="campo">
                            <label>Correo</label>
                            <input class="input-text" type="email" placeholder="Correo">
                        </div>
                        <div class="campo">
                            <label>Mensaje</label>
                            <textarea class="input-text"></textarea>
                        </div>
                    </div>

                    <div class="alinear-derecha flex">
                        <input class="boton w-sm-100" type="submit" value="Enviar">
                    </div>
                    
                </fieldset>
            </form>
        </section>
    </main>

    <footer class="footer">
        <p>Todos los derechos reservados. Rodrigo Yahir Hernandez Caro Freelancer</p>        
    </footer>

</body>
</html>
