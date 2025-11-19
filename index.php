<?php
session_start();
require_once("conexion.php");
$id_user = $_SESSION['user_id'] ?? null;
$rol = $_SESSION['rol'] ?? null;

$profile_pic = 'Imagenes/default_user.png';

if ($id_user) {
    $conn = conectarDB($rol);
    $consultaSQL = "SELECT u.username, c.PROFILE_PIC_URL 
                    FROM users u 
                    LEFT JOIN CLIENTE c ON u.ID_USER = c.ID_USER 
                    WHERE u.ID_USER = ?";
    
    if ($stmt = $conn->prepare($consultaSQL)) {
        $stmt->execute([$id_user]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($fila && !empty($fila['PROFILE_PIC_URL'])) {
            $profile_pic = 'pfp/'.$fila['PROFILE_PIC_URL'];
        }
    }
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

     <style>
        .hero {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 100px;
            margin-bottom: 50px;
            width: 100%;
        }

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
        
        .profile-username { font-weight: 600; }
        
        .carrusel {
            width: 600px;
            height: 400px;
            overflow: hidden;
            position: relative;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .carrusel-inner {
            display: flex;
            width: 100%;
            height: 100%;
            position: relative;
            left: 0;
        }

        .carrusel-item {
            min-width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #000; /* Fondo negro por si la imagen no carga */
        }
        
        /* Ajuste para imágenes del carrusel */
        .carrusel-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .btn-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 18px;
            z-index: 10;
            border-radius: 5px;
        }
        .btn-nav:hover { background: rgba(0,0,0,0.8); }
        .prev { left: 10px; }
        .next { right: 10px; }
        
        .contenedor {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* --- ESTILOS DEL FLYER --- */
        .flyer {
            width: 300px; /* Ancho fijo del flyer */
            height: 400px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s ease;
            position: relative;
            flex-shrink: 0; /* Evita que el flyer se encoja */
        }
        .flyer img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .flyer-content {
            padding: 15px;
            text-align: center;
        }
        .flyer h2 { margin: 10px 0; font-size: 1.4em; color: #333; }
        .flyer p { font-size: 0.95em; color: #555; }
        .flyer:hover { transform: scale(1.05); }
        
        .extra-info {
            display: none;
            padding: 10px;
            background: #fffae6;
            border-top: 1px solid #ddd;
            font-size: 0.9em;
            color: #333;
        }

        /* --- NUEVO: Contenedor para poner Flyers y Formulario en línea --- */
        

        .formulario {
            flex-grow: 1; /* El formulario ocupa el espacio disponible */
            max-width: 600px; /* Pero no más de 600px */
            min-width: 300px;
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
                        
                        <li class="nav-item dropdown">
                            <?php if (isset($_SESSION['user_id'])): ?>
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

        <section class="hero">
            <div class="carrusel">
                <div class="carrusel-inner">
                    <div class="carrusel-item">
                        <img src="Imagenes/nfts/oswi_cyberpunk.png" alt="Slide 1">
                    </div>
                    <div class="carrusel-item">
                        <img src="Imagenes/nfts/ulloa_port.png" alt="Slide 2">
                    </div>
                    <div class="carrusel-item">
                        <img src="Imagenes/nfts/oswi_port.png" alt="Slide 3">
                    </div>
                </div>

                <button class="btn-nav prev" id="anterior">&#10094;</button>
                <button class="btn-nav next" id="siguiente">&#10095;</button>
            </div>
        </section>
        
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            $(document).ready(function() {
                // --- LÓGICA DE FLYERS ---
                $(".flyer").click(function(){
                    $(this).find(".extra-info").slideToggle();
                });

                // --- LÓGICA DEL CARRUSEL ---
                var $carruselInner = $('.carrusel-inner');
                var $items = $('.carrusel-item');
                var totalItems = $items.length;
                var currentIndex = 0;

                function moverCarrusel() {
                    var position = currentIndex * -100;
                    $carruselInner.animate({
                        'left': position + '%'
                    }, 500);
                }

                $('#siguiente').click(function() {
                    if (currentIndex < totalItems - 1) {
                        currentIndex++;
                    } else {
                        currentIndex = 0;
                    }
                    moverCarrusel();
                });

                $('#anterior').click(function() {
                    if (currentIndex > 0) {
                        currentIndex--;
                    } else {
                        currentIndex = totalItems - 1;
                    }
                    moverCarrusel();
                });

                var autoPlay = setInterval(function() {
                    $('#siguiente').click();
                }, 3000);

                $('.carrusel').hover(function() {
                    clearInterval(autoPlay);
                }, function() {
                    autoPlay = setInterval(function() {
                        $('#siguiente').click();
                    }, 3000);
                });
            });
        </script>
    </header>

    <main class="contenedor sombra">
        <section class="nfts">
            <div class="nft">
                <img src="Imagenes/nfts/oswi_nft1.png" class="nft-img">
                <div class="nft-info">
                    <h3>La sonrisa que rompió a C++</h3>
                    <p>Precio Mínimo 8567 USD</p>
                </div>
                <div class="button-info-nfts-container">
                    <a href="coleccionsonrisa.php" class="btn">Ver colección</a>
                </div>
            </div>
            <div class="nft">
                <img src="Imagenes/nfts/ulloa_port.png" class="nft-img">
                <div class="nft-info">
                    <h3>El Amigo de los Mundos</h3>
                    <p>Precio Mínimo 8567 USD</p>
                </div>
                <div class="button-info-nfts-container">
                    <a href="coleccionamigo.php" class="btn">Ver colección</a>
                </div>
            </div>
            <div class="nft">
                <img src="Imagenes/nfts/oswi_port.png" class="nft-img">
                <div class="nft-info">
                    <h3>El brillo del mañana</h3>
                    <p>Precio Mínimo 8567 USD</p>
                </div>
                <div class="button-info-nfts-container">
                    <a href="coleccionbrillo.php" class="btn">Ver colección</a>
                </div>
            </div>
        </section>
    
        <section class="seccion-contacto"> 
            
            <div class="flyer">
                <img src="Imagenes/fl1.jpg" alt="Flyer">
                <div class="flyer-content">
                    <h2>Evento Especial</h2>
                    <p>¡No te pierdas nuestras ofertas!</p>
                </div>
                <div class="extra-info">
                    📍 Lugar: Auditorio A<br>
                    🎟 Descuento: 20%<br>
                    ☎ Info: 555-000-1111
                </div>
            </div>

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

            <div class="flyer">
                <img src="Imagenes/fl1.png" alt="Flyer">
                <div class="flyer-content">
                    <h2>Próximo Concierto</h2>
                    <p>Sábado a las 8 PM.</p>
                </div>
                <div class="extra-info">
                    📍 Lugar: Auditorio Central<br>
                    🎟 Entrada: $200 MXN<br>
                    ☎ Reservas: 555-123-4567
                </div>
            </div>

        </section>
    </main>

    <footer class="footer">
        <p>Todos los derechos reservados. Rodrigo Yahir Hernandez Caro Freelancer</p>        
    </footer>
</body>
</html>