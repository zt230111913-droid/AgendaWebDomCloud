<?php

require_once "conexion.php";

$leerCampo = static function (string $nombre): string {
    $valor = $_POST[$nombre] ?? '';

    return is_string($valor) ? trim($valor) : '';
};

$categorias = $pdo->query(
    'SELECT id, nombre FROM categorias ORDER BY nombre'
)->fetchAll();
$nombresCategorias = array_column($categorias, 'nombre', 'id');

$idRecibido = $_POST['id'] ?? $_GET['id'] ?? 0;
$id = is_scalar($idRecibido) ? (int) $idRecibido : 0;


if ($id <= 0) {
    header('Location: index.php?error=noexiste');
    exit;
}


/*
 * Buscar evento
 */

$stmt = $pdo->prepare("
    SELECT e.*, c.nombre AS categoria
    FROM eventos AS e
    JOIN categorias AS c ON c.id = e.categoria_id
    WHERE e.id = :id
");

$stmt->execute([
    ':id' => $id
]);

$evento = $stmt->fetch(
    PDO::FETCH_ASSOC
);


if (!$evento) {
    header('Location: index.php?error=noexiste');

    exit;
}

$titulo = $evento['titulo'];
$ubicacion = $evento['ubicacion'] ?? '';
$fecha = $evento['fecha'];
$hora = $evento['hora'] ? substr($evento['hora'], 0, 5) : '';
$categoriaId = (string) $evento['categoria_id'];
$descripcion = $evento['descripcion'] ?? '';
$errores = [];


/*
 * Actualizar
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $leerCampo('titulo');
    $ubicacion = $leerCampo('ubicacion');
    $fecha = $leerCampo('fecha');
    $hora = $leerCampo('hora');
    $categoriaId = $leerCampo('categoria_id');
    $descripcion = $leerCampo('descripcion');

    if ($titulo === '') {
        $errores['titulo'] = 'El título es obligatorio.';
    } elseif (mb_strlen($titulo) > 120) {
        $errores['titulo'] = 'El título no puede superar 120 caracteres.';
    }

    $fechaValida = DateTime::createFromFormat('!Y-m-d', $fecha);
    if (
        $fecha === '' ||
        $fechaValida === false ||
        $fechaValida->format('Y-m-d') !== $fecha
    ) {
        $errores['fecha'] = 'Escribe una fecha válida.';
    }

    $categoriasPermitidas = array_map('strval', array_column($categorias, 'id'));
    if (!ctype_digit($categoriaId) || !in_array($categoriaId, $categoriasPermitidas, true)) {
        $errores['categoria_id'] = 'Elige una categoría válida.';
    }

    if ($hora !== '' && !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $hora)) {
        $errores['hora'] = 'Escribe una hora válida.';
    }

    if (mb_strlen($ubicacion) > 255) {
        $errores['ubicacion'] = 'La ubicación no puede superar 255 caracteres.';
    }

    if (mb_strlen($descripcion) > 500) {
        $errores['descripcion'] = 'La descripción no puede superar 500 caracteres.';
    }

    if ($errores === []) {
        $stmt = $pdo->prepare("
            UPDATE eventos
            SET
                titulo = :titulo,
                ubicacion = :ubicacion,
                fecha = :fecha,
                hora = :hora,
                categoria_id = :categoria_id,
                descripcion = :descripcion

            WHERE id = :id
        ");

        $stmt->execute([
            ':titulo' => $titulo,
            ':ubicacion' => $ubicacion,
            ':fecha' => $fecha,
            ':hora' => $hora !== '' ? $hora : null,
            ':categoria_id' => (int) $categoriaId,
            ':descripcion' => $descripcion,
            ':id' => $id,
        ]);

        header('Location: index.php?ok=editado');
        exit;
    }

    $evento['titulo'] = $titulo;
    $evento['ubicacion'] = $ubicacion;
    $evento['fecha'] = $fecha;
    $evento['hora'] = $hora;
    $evento['categoria_id'] = $categoriaId;
    if (isset($nombresCategorias[(int) $categoriaId])) {
        $evento['categoria'] = $nombresCategorias[(int) $categoriaId];
    }
    $evento['descripcion'] = $descripcion;
}

?>


<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Editar Evento - AgendaWeb
    </title>


    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="css/estilos.css?v=7"
    >

</head>


<body>


<header class="barra-superior">

    <div class="barra-contenido">

        <a
            href="index.php"
            class="logo"
        >

            <span class="logo-icono">
                ▣
            </span>

            <span>
                Agenda<span>Web</span>
            </span>

        </a>


        <nav class="navegacion">

            <a
                href="index.php"
                class="nav-activo"
            >
                <span>⌂</span>
                Mis eventos
            </a>


            <a href="registrar.php">

                <span>＋</span>

                Nuevo evento

            </a>

        </nav>


        <div class="usuario-icono">
            ◯
        </div>

    </div>

</header>


<main class="contenedor">


    <section class="encabezado-pagina">

        <div>

            <h1>
                Editar evento
            </h1>

            <p>
                Modifica la información de tu evento.
            </p>

        </div>


        <a
            href="index.php"
            class="btn-secundario"
        >
            ← Mis eventos
        </a>

    </section>


    <section class="tarjeta formulario">

        <?php if ($errores !== []): ?>
            <div class="alert alert--error" role="alert">
                <?php foreach ($errores as $mensaje): ?>
                    <p><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form
            method="POST"
            action=""
        >
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="campo">

                <label for="titulo">
                    Título *
                </label>

                <input
                    type="text"
                    id="titulo"
                    name="titulo"
                    value="<?php echo htmlspecialchars($evento['titulo']); ?>"
                    required
                    maxlength="120"
                >

            </div>


            <div class="campo">

                <label for="categoria">
                    Categoría
                </label>

                <select
                    id="categoria"
                    name="categoria_id"
                    required
                >
                    <?php foreach ($categorias as $categoria): ?>
                        <option
                            value="<?php echo (int) $categoria['id']; ?>"
                            <?php echo (int) $evento['categoria_id'] === (int) $categoria['id'] ? 'selected' : ''; ?>
                        >
                            <?php echo htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

            </div>


            <div class="fila-formulario">


                <div class="campo">

                    <label for="fecha">
                        Fecha *
                    </label>

                    <input
                        type="date"
                        id="fecha"
                        name="fecha"
                        value="<?php echo htmlspecialchars($evento['fecha']); ?>"
                        required
                    >

                </div>


                <div class="campo">

                    <label for="hora">
                        Hora
                    </label>

                    <input
                        type="time"
                        id="hora"
                        name="hora"
                            value="<?php echo htmlspecialchars($hora, ENT_QUOTES, 'UTF-8'); ?>"
                    >

                </div>


            </div>


            <div class="campo">

                <label for="ubicacion">
                    Ubicación
                </label>

                <input
                    type="text"
                    id="ubicacion"
                    name="ubicacion"
                    value="<?php echo htmlspecialchars($evento['ubicacion']); ?>"
                    maxlength="255"
                >

            </div>


            <div class="campo">

                <label for="descripcion">
                    Descripción
                </label>

                <textarea
                    id="descripcion"
                    name="descripcion"
                    rows="5"
                    maxlength="500"
                ><?php echo htmlspecialchars($evento['descripcion']); ?></textarea>

            </div>


            <div class="botones-formulario">

                <button
                    type="submit"
                    class="btn-nuevo"
                >
                    Guardar Cambios
                </button>


                <a
                    href="index.php"
                    class="btn-cancelar"
                >
                    Cancelar
                </a>

            </div>


        </form>

    </section>

</main>


</body>

</html>