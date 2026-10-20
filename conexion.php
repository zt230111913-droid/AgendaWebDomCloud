<?php

$archivoLocal = __DIR__ . '/conexion.local.php';
$configuracionLocal = [];

if (is_file($archivoLocal)) {
    define('AGENDAWEB_CONNECTION', true);
    $configuracionLocal = require $archivoLocal;
}

if (!is_array($configuracionLocal)) {
    throw new RuntimeException('conexion.local.php debe devolver un arreglo de configuración.');
}

$configurar = static function (
    string $variable,
    string $clave,
    string $valorPredeterminado
) use ($configuracionLocal): string {
    $valorEntorno = getenv($variable);

    if ($valorEntorno !== false && $valorEntorno !== '') {
        return $valorEntorno;
    }

    return (string) ($configuracionLocal[$clave] ?? $valorPredeterminado);
};

$host = $configurar('DB_HOST', 'host', 'localhost');
$puerto = $configurar('DB_PORT', 'port', '3306');
$nombreBase = $configurar('DB_NAME', 'database', 'agendaweb');
$usuario = $configurar('DB_USER', 'username', 'root');
$contrasena = $configurar('DB_PASSWORD', 'password', '');

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
