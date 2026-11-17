<?php

session_start();

// La API siempre responde JSON, también cuando hay un error.
header('Content-Type: application/json; charset=utf-8');

function responder(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

// Sin sesión no hay datos: 401, no una redirección al login.
if (!isset($_SESSION['uid']) || !is_numeric((string) $_SESSION['uid']) || (int) $_SESSION['uid'] <= 0) {
    responder(401, ['error' => 'Inicia sesión para consultar tus eventos.']);
}

$usuarioId = (int) $_SESSION['uid'];

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    responder(405, ['error' => 'Método no permitido. Usa GET.']);
}

require __DIR__ . '/conexion.php';

$q = '';
if (isset($_GET['q'])) {
    $qRecibida = $_GET['q'];
    if (is_string($qRecibida)) {
        $q = mb_substr(trim($qRecibida), 0, 60);
    }
}

$categoriaRecibida = $_GET['categoria'] ?? 0;
$categoriaId = 0;

if (is_string($categoriaRecibida) && ctype_digit($categoriaRecibida)) {
    $categoriaId = (int) $categoriaRecibida;
} elseif (is_int($categoriaRecibida) && $categoriaRecibida > 0) {
    $categoriaId = $categoriaRecibida;
}

if ($categoriaId > 0) {
    try {
        $consultaCategoria = $pdo->prepare('SELECT id FROM categorias WHERE id = :id LIMIT 1');
        $consultaCategoria->execute([':id' => $categoriaId]);

        if (!$consultaCategoria->fetch()) {
            $categoriaId = 0;
        }
    } catch (PDOException $e) {
        error_log('No se pudo validar la categoría para la API: ' . $e->getMessage());
        responder(500, ['error' => 'No se pudo consultar tus eventos.']);
    }
}

$condiciones = ['e.usuario_id = :usuario_id'];
$parametros = [':usuario_id' => $usuarioId];

if ($q !== '') {
    $patron = '%' . addcslashes($q, '%_\\') . '%';
    $condiciones[] = '(e.titulo LIKE :titulo OR e.descripcion LIKE :descripcion)';
    $parametros[':titulo'] = $patron;
    $parametros[':descripcion'] = $patron;
}

if ($categoriaId > 0) {
    $condiciones[] = 'e.categoria_id = :categoria_id';
    $parametros[':categoria_id'] = $categoriaId;
}

$sql = "
    /* Solo se entregan los campos necesarios; el usuario siempre queda aislado. */
    SELECT e.id, e.titulo, e.fecha,
           TIME_FORMAT(e.hora, '%H:%i') AS hora,
           c.nombre AS categoria, e.descripcion
    FROM eventos AS e
    JOIN categorias AS c ON c.id = e.categoria_id
    WHERE " . implode(' AND ', $condiciones) . '
    ORDER BY e.fecha, e.hora
';

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('No se pudo consultar los eventos: ' . $e->getMessage());
    responder(500, ['error' => 'No se pudo consultar tus eventos.']);
}

responder(200, [
    'usuario' => $_SESSION['nombre'] ?? '',
    'filtros' => [
        'q' => $q,
        'categoria' => $categoriaId,
    ],
    'total' => count($eventos),
    'eventos' => $eventos,
]);
