<?php

require_once "conexion.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: index.php");

    exit;
}

$id = isset($_POST['id'])
    ? (int) $_POST['id']
    : 0;


if ($id > 0) {

    $stmt = $pdo->prepare("
        DELETE FROM eventos
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $id
    ]);
}


header(
    "Location: index.php"
);

exit;

?>