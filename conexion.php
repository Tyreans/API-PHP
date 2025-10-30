<?php
// conexion.php

function obtenerCredenciales($rol) {
    // Definimos las credenciales de forma local dentro de la función o las leemos desde
    // un archivo de configuración SEGURO.
    switch ($rol) {
        case 'admin':
            return [
                'usuario' => 'ADMIN',
                'contrasena' => 'AdmPwdSegura789!',
                'dbname' => 'oswifts'
            ];
        case 'cliente':
            return [
                'usuario' => 'app_cliente',
                'contrasena' => 'CliPwdSegura456!',
                'dbname' => 'oswifts'
            ];
        case 'invitado':
        default:
            return [
                'usuario' => 'app_publico',
                'contrasena' => 'PubPwdSegura123!',
                'dbname' => 'oswifts'
            ];
    }
}

function conectarDB($rol) {
    $creds = obtenerCredenciales($rol);
    $host = 'localhost'; // O la dirección de tu servidor DB
    $dsn = "mysql:host=$host;dbname={$creds['dbname']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $creds['usuario'], $creds['contrasena'], $options);
        return $pdo;
    } catch (\PDOException $e) {
        // En un entorno de producción, nunca muestres el error de conexión directo al usuario.
        // En su lugar, registra el error y muestra un mensaje genérico.
        die("Error de conexión a la base de datos: " . $e->getMessage());
    }
}

?>