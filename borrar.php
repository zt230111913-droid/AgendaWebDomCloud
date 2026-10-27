<?php

require_once __DIR__ . '/partes/sesion.php';
$usuarioId = exigirSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$idRecibido = $_POST['id'] ?? 0;
$id = is_scalar($idRecibido) ? (int) $idRecibido : 0;

if ($id <= 0) {
    header('Location: index.php?error=noexiste');
    exit;
}

require_once __DIR__ . '/conexion.php';

$stmt = $pdo->prepare(
    'DELETE FROM eventos WHERE id = :id AND usuario_id = :usuario_id'
);
$stmt->execute([
    ':id' => $id,
    ':usuario_id' => $usuarioId,
]);
$borrado = $stmt->rowCount() === 1;

header($borrado ? 'Location: index.php?ok=borrado' : 'Location: index.php?error=noexiste');
exit;
