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