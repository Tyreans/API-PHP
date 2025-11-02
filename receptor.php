<?php
session_start();
require_once('conexion.php');

require 'vendor/autoload.php';

// Para creacion del pdf
use Dompdf\Dompdf;
use Dompdf\Options;

$setting = new Options();
$setting->set('defaultFont','Helvetica');
$setting->set('isPhpEnabled',true);
$setting->set('isHtml5ParseEnabled',true);
$setting->set('isRemoteEnabled',true);
$setting->set('chroot',__DIR__);

$dompdf = new Dompdf($setting);

//Consulta nombre usuario

$rol_actual = $_SESSION['rol'] ?? 'invitado';
$conn = conectarDB($rol_actual);
$id_user = $_SESSION['user_id'];

$consultaSQL = "SELECT username, email FROM users WHERE ID_USER = ?";
    
if ($stmt = $conn->prepare($consultaSQL)) {
    $stmt->execute([$id_user]);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($fila) {
        $name = $fila['username'];
        $email = $fila['email'];
    }
} else {
    // Error al preparar la consulta (puedes loguearlo)
    die("Error al preparar la consulta: ". $conn->error); // Quita el comentario para depurar
}

$baseUrl = 'http://localhost/API-PHP';
$paypal_hostname = 'www.sandbox.paypal.com';
$pdt_identity_token = 'wTZ7n7-0pA8FrnUsFE5u5faNmlGzlflEx-BMJPcv7klY-JV-sUjnVW5MfwC';

// Verificar que recibimos el tx
if (!isset($_GET['tx'])) {
    die('Error: Transacción no válida');
}

$tx = $_GET['tx'];
$query = "cmd=_notify-synch&tx=$tx&at=$pdt_identity_token";

// 1️⃣ VERIFICAR EL PAGO CON PAYPAL (Seguridad)
$request = curl_init();
curl_setopt($request, CURLOPT_URL, "https://$paypal_hostname/cgi-bin/webscr");
curl_setopt($request, CURLOPT_POST, TRUE);
curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($request, CURLOPT_POSTFIELDS, $query);
curl_setopt($request, CURLOPT_SSL_VERIFYPEER, TRUE);
curl_setopt($request, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($request, CURLOPT_HTTPHEADER, array("Host: $paypal_hostname"));

$response = curl_exec($request);
curl_close($request);

if (!$response) {
    die("Error de conexión con PayPal");
}

// Procesar respuesta
$lines = explode("\n", trim($response));
$keyarray = array();

if (strcmp($lines[0], "SUCCESS") == 0) {
    // Extraer datos verificados
    for ($i = 1; $i < count($lines); $i++) {
        $temp = explode("=", $lines[$i], 2);
        $keyarray[urldecode($temp[0])] = urldecode($temp[1]);
    }

    // Obtener datos del pago
    $payment_status = $keyarray['payment_status'];
    $mc_gross = $keyarray['mc_gross'];
    $mc_currency = $keyarray['mc_currency'];
    $item_number = $keyarray['item_number']; // ID del NFT
    $custom = $keyarray['custom']; // user_id|nft_id
    
    // 2️⃣ VALIDAR QUE EL PAGO ESTÉ COMPLETADO
    if ($payment_status !== 'Completed') {
        die("Error: El pago no está completado. Estado: $payment_status");
    }

    // Extraer user_id y nft_id
    list($user_id, $nft_id) = explode('|', $custom);

    // 3️⃣ REGISTRAR EN LA BASE DE DATOS
    $conn = conectarDB($rol_actual);

    try {
        $stmt = $conn->prepare("CALL sp_process_purchase(?, ?, ?)");
        $stmt->execute([$user_id, $nft_id, $mc_gross]);
        
        $purchase_info = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt->closeCursor();

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
        // ####################################
        // ########## P D F ###################
        // ####################################
        $logo_path = __DIR__."/Imagenes/to_main.png";
        $fecha_formateada = date("d/m/y H:i");

        $html = "
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { 
                        font-family: Arial, sans-serif; 
                        margin: 0; 
                        padding: 50px; 
                        text-align: center; 
                    }
                    .header-logo {
                        text-align: center;
                        margin-bottom: 40px;
                    }
                    .header-logo img {
                        width: 40px; /* Ajusta el tamaño del logo si es necesario */
                    }
                    h1, h2 {
                        font-weight: bold;
                    }
                    h1 {
                        font-size: 36px;
                        margin-bottom: 50px;
                    }
                    .saludo {
                        margin-bottom: 50px;
                        font-size: 16px;
                    }
                    .id-factura {
                        font-size: 28px;
                        margin-bottom: 50px;
                    }
                    .seccion-info {
                        text-align: left;
                        margin-bottom: 40px;
                    }
                    .seccion-info h3 {
                        color: #555;
                        font-size: 11px;
                        text-transform: uppercase;
                        border-bottom: 1px solid #ccc;
                        padding-bottom: 5px;
                        margin-bottom: 15px;
                    }
                    .info-grid {
                        display: block; /* Dompdf maneja mejor el layout con tablas o bloques simples */
                        width: 100%;
                    }
                    .info-col {
                        width: 48%;
                        display: inline-block;
                        vertical-align: top;
                        margin-bottom: 15px;
                    }
                    .info-label {
                        font-size: 14px;
                        color: #555;
                        margin-bottom: 5px;
                    }
                    .info-dato {
                        font-weight: bold;
                        font-size: 14px;
                        margin-bottom: 15px;
                    }
                    .tabla-pedido {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 20px;
                        text-align: left;
                    }
                    .tabla-pedido th {
                        font-size: 11px;
                        color: #555;
                        text-transform: uppercase;
                        border-bottom: 1px solid #ccc;
                        padding: 10px 0;
                    }
                    .tabla-pedido td {
                        padding: 15px 0 5px 0;
                        border-bottom: 1px solid #eee;
                    }
                    .tabla-pedido .precio {
                        text-align: right;
                        font-weight: bold;
                    }
                </style>
            </head>
            <body>

                <div class='header-logo'>
                    <img src='{$logo_path}' alt='Logo de la Tienda'>
                </div>

                <h1>¡Gracias!</h1>

                <div class='saludo'>
                    Hola {$name}.<br>
                    ¡Gracias por tu compra!
                </div>

                <div class='id-factura'>
                    ID de la factura:<br>
                    <strong>{$purchase_info["PURCHASE_ID"]}</strong>
                </div>

                <div class='seccion-info'>
                    <h3>INFORMACIÓN SOBRE TU PEDIDO:</h3>
                    
                    <div class='info-grid'>
                        <div class='info-col'>
                            
                            <div class='info-label'>Fecha del pedido:</div>
                            <div class='info-dato'>{$fecha_formateada}</div>
                        </div>

                        <div class='info-col' style='float: right;'>
                            <div class='info-label'>Facturado a:</div>
                            <div class='info-dato'>{$email}</div>
                            
                            <div class='info-label'>Fuente:</div>
                            <div class='info-dato'>OswiFTS's Store</div>
                        </div>
                    </div>
                </div>
                
                <div class='seccion-info'>
                    <h3>ESTE ES TU PEDIDO:</h3>
                    
                    <table class='tabla-pedido'>
                        <thead>
                            <tr>
                                <th style='width: 55%;'>Descripción</th>
                                <th style='width: 25%;'>Distribuidor:</th>
                                <th style='width: 20%; text-align: right;'>Precio:</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{$nft["TITLE"]}</td>
                                <td>{$nft["VENDEDOR"]}</td>
                                <td class='precio'>{$nft["PRICE"]}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan='2' style='text-align: right; font-weight: bold; border-top: 1px solid #ccc;'>TOTAL:</td>
                                <td class='precio' style='border-top: 1px solid #ccc;'>{$purchase_info["TOTAL_PAID"]}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </body>
            </html>
        ";
        
        $fecha_formateada = date("d-m-y-h-i");

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $ruta_guardado = 'receipts/'.$id_user.'/factura_'.$fecha_formateada.'.pdf';
        $ruta_carpeta = dirname($ruta_guardado);

        if(!is_dir($ruta_carpeta)){
            if(!mkdir($ruta_carpeta, 077, true)){
                die('Error: '.$ruta_guardado.'');
            }
        }

        $output = $dompdf->output();
        file_put_contents($ruta_guardado, $output);
         // 4️⃣ MOSTRAR CONFIRMACIÓN
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Compra Exitosa</title>
            <style>
                .success-box {
                    max-width: 600px;
                    margin: 50px auto;
                    padding: 30px;
                    background: #d4edda;
                    border: 2px solid #28a745;
                    border-radius: 10px;
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class="success-box">
                <h1>✅ ¡Compra Exitosa!</h1>
                <p><strong>ID Transacción PayPal:</strong> <?= htmlspecialchars($tx) ?></p>
                <p><strong>Total Pagado:</strong> $<?= number_format($mc_gross, 2) ?> <?= $mc_currency ?></p>
                <p><strong>Estado:</strong> <?= $payment_status ?></p>
                <a 
                    href="<?php echo htmlspecialchars($ruta_guardado); ?>" 
                    target="_blank" 
                    class="btn btn-primary"
                    style="text-decoration: none; padding: 10px 15px; background-color: #007bff; color: white; border-radius: 5px;"
                >
                    Ver y Descargar Factura PDF
                </a>
                <p><a href="mi_biblioteca.php">📚 Ver mi Biblioteca</a></p>
                <p><a href="marketplace.php">🛒 Volver al Marketplace</a></p>
            </div>
        </body>
        </html>
        <?php

    } catch (PDOException $e) {
        echo "<div style='color: red; padding: 20px;'>";
        echo "<h1>❌ Error al procesar la compra</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Por favor, contacta a soporte con el ID de transacción: $tx</p>";
        echo "</div>";
    }

    $conn = null;

} else if (strcmp($lines[0], "FAIL") == 0) {
    // PayPal no pudo verificar la transacción
    echo "<h1>❌ Error: No se pudo verificar el pago</h1>";
    echo "<p>Por favor, contacta a soporte.</p>";
}
?>