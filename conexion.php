<?php

$host = getenv('DB_HOST') ?: 'localhost';
$puerto = getenv('DB_PORT') ?: '3306';
$nombreBase = getenv('DB_NAME') ?: 'agendaweb';
$usuario = getenv('DB_USER') ?: 'root';
$contrasena = getenv('DB_PASSWORD');

if ($contrasena === false) {
    $contrasena = '';
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$puerto};dbname={$nombreBase};charset=utf8mb4",
        $usuario,
        $contrasena,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Error de conexión con la base de datos: ' . $e->getMessage());
    die('No se pudo conectar a MySQL. Revisa la configuración del servidor.');
}
