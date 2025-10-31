<?php
    session_start();
    require_once('conexion.php');

    $rol_actual = $_SESSION['rol'] ?? 'invitado';
    $conn = conectarDB($rol_actual);

    $proID = filter_input(INPUT_GET,'id', FILTER_SANITIZE_NUMBER_INT);

    $consultaSQL = "Select * FROM nft WHERE ID_NFT = ?";

    if($stmt = $conn->prepare($consultaSQL)) {
        $stmt->execute([$proID]);

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if($fila){
            echo "ID: ".$fila['ID_NFT'].", Nombre: ".$fila['TITLE'].", Descripcion: ".$fila['ABSTRACT'];
            echo '<img src="nft/'. $fila['url_imagen'].'"/>';
        }
    }else{
        die("Error al preparar la consulta: ". $conn->error);
    }

    $titulo_pagina = "Producto no encontrado";
$nombre_producto = "N/A";
$descripcion_producto = "El NFT que buscas no existe o el ID es incorrecto.";
$imagen_producto = "Imagenes/nfts/oswi_port.png"; // Imagen por defecto
$alt_imagen = "Producto no encontrado";
$precio_entero = "0";
$precio_centavos = "00";
$fecha_lanzamiento = "No especificada";

if ($producto_encontrado) {
    // Sobrescribimos las variables con los datos de la BD
    $titulo_pagina = htmlspecialchars($fila['TITLE']);
    $nombre_producto = htmlspecialchars($fila['TITLE']);
    
    // Usamos nl2br para respetar los saltos de línea de la descripción
    $descripcion_producto = nl2br(htmlspecialchars($fila['ABSTRACT']));
    
    // Ruta de la imagen (basado en tu script)
    $imagen_producto = 'nft/' . htmlspecialchars($fila['url_imagen']);
    $alt_imagen = htmlspecialchars($fila['TITLE']);

    // Formatear el precio
    $precio = $fila['price'] ?? 0;
    $precio_entero = floor($precio);
    $precio_centavos = sprintf('%02d', ($precio - $precio_entero) * 100);

    // Formatear la fecha
    if (!empty($fila['release_date'])) {
        try {
            $date = new DateTime($fila['release_date']);
            $fecha_lanzamiento = $date->format('d/m/Y'); // Formato Día/Mes/Año
        } catch (Exception $e) {
            $fecha_lanzamiento = "Fecha inválida";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?php echo $titulo_pagina; ?> - OswiFTS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Krub&display=swap" 
    rel="stylesheet">
    <link rel="preload" href="csspag/normalize.css" as="style">
    <link rel="stylesheet" href="csspag/normalize.css">
    
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    
    <link rel="preload" href="csspag/styles.css" as ="style">
    <link href="csspag/styles.css" rel="stylesheet"> 
</head>
<body>
      
    <header> 
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.html"> <img src="Imagenes/to_main.png" alt="Logo" width="30" height="24" class="d-inline-block align-middle">
            OSWI-FTS
            </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDarkNavbar" aria-controls="offcanvasDarkNavbar" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
                </button>
                <div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="offcanvasDarkNavbar" aria-labelledby="offcanvasDarkNavbarLabel">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="offcanvasDarkNavbarLabel">Menu</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                    <li class="nav-item">
                        <a class="nav-link" href="index.html">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Link</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Dropdown
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark">
                        <li><a class="dropdown-item" href="#">Action</a></li>
                        <li><a class="dropdown-item" href="#">Another action</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="#">Iniciar Sesion</a></li>
                        </ul>
                    </li>
                    </ul>
                    <form class="d-flex mt-3" role="search">
                    <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search"/>
                    <button class="btn btn-success" type="submit">Search</button>
                    </form>
                    <a href="carrito.html" class="carrito"> <img src="Imagenes/carro2.png" width="30" height="24" class="d-inline-block align-middle carro">
                    </a>
                </div>
            </div>
        </div>
        </nav>
    </header>

    <main class="contenedor sombra product-page">
        
        <?php if ($producto_encontrado): ?>
            <div class="row gy-4">
                
                <div class="col-lg-7">
                    <img src="<?php echo $imagen_producto; ?>" 
                         class="img-fluid rounded" 
                         alt="<?php echo $alt_imagen; ?>">
                </div>

                <div class="col-lg-5 d-flex flex-column">
                    
                    <div>
                        <p class="text-muted small">Lanzamiento: <?php echo $fecha_lanzamiento; ?></p>
                        
                        <h2><?php echo $nombre_producto; ?></h2>
                        
                        <h3 class="display-4 fw-bold my-3 precio-producto">
                            $ <?php echo number_format($precio_entero); ?>.<sup><?php echo $precio_centavos; ?></sup>
                        </h3>
                        
                        <p class="lead">
                            <?php echo $descripcion_producto; ?>
                        </p>
                    </div>

                    <hr class="my-4">

                    <div class="mt-auto">
                        <form action="carrito_agregar.php" method="POST">
                            
                            <input type="hidden" name="id_producto" value="<?php echo $fila['ID_NFT']; ?>">

                            <h5 class="fw-bold">Stock disponible</h5>
                            
                            <div class="d-flex align-items-center mb-3">
                                <label for="cantidad" class="form-label me-3 mb-0">Cantidad:</label>
                                <input type="number" class="form-control input-cantidad" 
                                       id="cantidad" name="cantidad" value="1" min="1" max="10"> <span class="text-muted ms-3">(Disponibles)</span>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="accion" value="comprar" class="btn btn-lg btn-comprar">Comprar ahora</button>
                                <button type="submit" name="accion" value="agregar" class="btn btn-lg btn-carrito">Agregar al carrito</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        
        <?php else: ?>
            <div class="row text-center justify-content-center">
                <div class="col-md-8">
                    <h2 class="text-danger" style="font-size: 3rem;">⛔ Producto no encontrado</h2>
                    <p class="lead" style="font-size: 1.5rem;">
                        <?php echo $descripcion_producto; ?>
                    </p>
                    <a href="index.html" class="btn btn-lg btn-comprar" style="width: auto; text-decoration: none; font-size: 1.2rem; padding: 1rem 2rem;">
                        Volver al Inicio
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
    </main>


    <footer class="footer">
        <p> Todos los derechos reservados. Rodrigo Yahir Hernandez Caro Freelancer</p>         
    </footer>

</body>
</html>