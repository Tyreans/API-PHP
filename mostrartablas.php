<?php
    $host = 'localhost';
    $db = 'oswifts';
    $usr = 'app_publico';
    $pwd = 'PubPwdSegura123!';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

    try{
        $conn = new PDO($dsn, $usr, $pwd);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo 'Conexion Exitosa';
    }catch(PDOException $e){
        echo "Error en la conexion: ". $e->getMessage();
    }

    echo $usr;