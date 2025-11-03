<?php
    session_start();
    require_once('conexion.php');

    // Verificar que el usuario esté logueado
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }

    // Verificar que se recibió el ID del NFT
    if (!isset($_POST['ID_NFT']) || empty($_POST['ID_NFT'])) {
        die('Error: No se especificó el NFT a comprar');
    }

    $nft_id = intval($_POST['ID_NFT']);
    $user_id = $_SESSION['user_id'];

    // 1. OBTENER DATOS DEL NFT
    // ======================================
    $rol_actual = $_SESSION['rol'] ?? 'invitado';
    $conn = conectarDB($rol_actual);
    $baseUrl = 'http://localhost/API-PHP';

    // Consultar información del NFT
    $stmt = $conn->prepare("
        SELECT 
            n.ID_NFT,
            n.TITLE,
            n.ABSTRACT,
            n.PRICE,
            n.url_imagen,
            u.USERNAME as VENDEDOR
        FROM NFT n
        INNER JOIN VENDEDOR v ON n.SALESMAN_ID = v.ID_USER
        INNER JOIN USERS u ON v.ID_USER = u.ID_USER
        WHERE n.ID_NFT = ?
    ");
    $stmt->execute([$nft_id]);
    $nft = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$nft) {
        die('Error: NFT no encontrado');
    }

    // Verificar si el NFT ya fue vendido
    $stmt_sold = $conn->prepare("SELECT fn_is_nft_sold(?) AS is_sold");
    $stmt_sold->execute([$nft_id]);
    $sold_result = $stmt_sold->fetch(PDO::FETCH_ASSOC);

    if ($sold_result['is_sold'] == 1) {
        die('Error: Este NFT ya fue vendido');
    }

    $conn = null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago - <?= htmlspecialchars($nft['TITLE']) ?></title>
    <style>
        .nft-preview {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .nft-preview img {
            max-width: 100%;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <h1>Confirmar Compra</h1>
    
    <!-- Vista previa del NFT -->
    <div class="nft-preview">
        <h2><?= htmlspecialchars($nft['TITLE']) ?></h2>
        <?php if (!empty($nft['url_imagen'])): ?>
            <img src="nft/<?= htmlspecialchars($nft['url_imagen']) ?>" alt="<?= htmlspecialchars($nft['TITLE']) ?>">
        <?php endif; ?>
        <p><strong>Descripción:</strong> <?= htmlspecialchars($nft['ABSTRACT']) ?></p>
        <p><strong>Vendedor:</strong> <?= htmlspecialchars($nft['VENDEDOR']) ?></p>
        <p><strong>Precio:</strong> $<?= number_format($nft['PRICE'], 2) ?> MXN</p>
    </div>

    <h2>Formulario de pago</h2>

    <!-- Para cambiar al entorno de producción usar: https://www.paypal.com/cgi-bin/webscr -->
    <form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post" id="form_pay">

        <!-- Valores requeridos -->
        <input type="hidden" name="business" value="sb-faswo47039917@business.example.com">
        <input type="hidden" name="cmd" value="_xclick">

        <!-- Datos del NFT -->
        <input type="hidden" name="item_name" value="<?= htmlspecialchars($nft['TITLE']) ?>">
        <input type="hidden" name="item_number" value="<?= $nft['ID_NFT'] ?>">
        <input type="hidden" name="amount" value="<?= number_format($nft['PRICE'], 2, '.', '') ?>">
        <input type="hidden" name="currency_code" value="MXN">
        <input type="hidden" name="quantity" value="1">

        <!-- Valores opcionales -->
        <input type="hidden" name="lc" value="es_ES">
        
        <!-- Imagen del NFT en PayPal (opcional) -->
        <?php if (!empty($nft['url_imagen'])): ?>
            <input type="hidden" name="image_url" value="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/imagenes/' . htmlspecialchars($nft['url_imagen']) ?>">
        <?php endif; ?>

        <!-- Variables personalizadas para identificar al usuario y NFT -->
        <input type="hidden" name="custom" value="<?= $user_id ?>|<?= $nft_id ?>">

        <!-- URLs de retorno -->
        <input type="hidden" name="return" value="<?= $baseUrl ?>/receptor.php">
        <input type="hidden" name="cancel_return" value="<?= $baseUrl ?>/pago_cancelado.php">
        <input type="hidden" name="notify_url" value="<?= $baseUrl ?>/ipn.php">

        <hr>

        <div style="text-align: center;">
            <p><strong>Total a pagar: $<?= number_format($nft['PRICE'], 2) ?> MXN</strong></p>
            <button type="submit" style="padding: 15px 30px; font-size: 16px; cursor: pointer;">
                Pagar ahora con PayPal
            </button>
            <br><br>
            <a href="marketplace.php">Cancelar y volver</a>
        </div>

    </form>
</body>
</html>