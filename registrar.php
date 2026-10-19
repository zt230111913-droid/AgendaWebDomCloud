<?php

// Escapa texto antes de mostrarlo en HTML.
function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

// Valores iniciales y errores de cada campo.
$titulo = '';
$fecha = '';
$categoria = '';
$ubicacion = '';
$hora = '';
$errores = [];

// Recibe y valida los datos cuando se envía el formulario.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $fecha = trim($_POST['fecha'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $hora = trim($_POST['hora'] ?? '');

    if ($titulo === '') {
        $errores['titulo'] = 'Escribe un título.';
    } elseif (mb_strlen($titulo) > 120) {
        $errores['titulo'] = 'El título no puede superar 120 caracteres.';
    }

    if ($fecha === '') {
        $errores['fecha'] = 'Elige una fecha.';
    } else {
        $fechaValida = DateTime::createFromFormat('Y-m-d', $fecha);

        if ($fechaValida === false || $fechaValida->format('Y-m-d') !== $fecha) {
            $errores['fecha'] = 'Escribe una fecha válida.';
        }
    }

    $categoriasPermitidas = ['trabajo', 'personal', 'estudio', 'ocio'];
    if (!in_array($categoria, $categoriasPermitidas, true)) {
        $errores['categoria'] = 'Elige una categoría válida.';
    }

    // Guarda el evento solo si todos los campos son válidos.
    // TODO Paso 8 · Guardar en MySQL
    if ($errores === []) {
        try {
            require_once __DIR__ . '/conexion.php';

            $consulta = $pdo->prepare(
                'INSERT INTO eventos (titulo, ubicacion, fecha, hora, categoria)
                VALUES (:titulo, :ubicacion, :fecha, :hora, :categoria)'
            );
            $consulta->execute([
                ':titulo' => $titulo,
                ':ubicacion' => $ubicacion,
                ':fecha' => $fecha,
                ':hora' => $hora,
                ':categoria' => $categoria,
            ]);
            $consulta->closeCursor();

            header('Location: index.php?ok=1');
            exit;
        } catch (PDOException $e) {
            $errores['general'] = 'No se pudo guardar.';
        }
    }
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

    <title>Registrar Evento - AgendaWeb</title>


    <!-- Google Fonts -->

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700&display=swap"
        rel="stylesheet"
    >


    <!-- CSS -->

    <link
        rel="stylesheet"
        href="css/estilos.css?v=7"
    >

</head>


<body>


<main id="contenedor-principal">


    <!-- ENCABEZADO -->

    <header class="encabezado-inicio">

        <div>

            <h1>AgendaWeb</h1>

            <p class="subtitulo">
                Registrar un nuevo evento
            </p>

        </div>


        <a
            href="index.php"
            class="btn-secundario"
        >
            ← Mis eventos
        </a>

    </header>


    <!-- FORMULARIO -->

    <section class="tarjeta formulario">

        <h2>Nuevo Evento</h2>

        <p class="texto-secundario">
            Completa los datos para registrar un evento.
        </p>

        <!-- TODO Paso 7 · Aquí se mostrarán los mensajes de error -->
        <?php if (isset($errores['general'])): ?>
            <div class="alert alert--error"><?= e($errores['general']) ?></div>
        <?php endif; ?>

        <!-- FORMULARIO -->

        <form method="post" action="">
            <div class="form-group">
                <label class="form-label" for="titulo">Título</label>
                <input type="text" name="titulo" id="titulo" class="form-input<?= isset($errores['titulo']) ? ' is-invalid' : '' ?>" value="<?= e($titulo) ?>" required maxlength="120">
                <?php if (isset($errores['titulo'])): ?>
                    <p class="form-error"><?= e($errores['titulo']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="ubicacion">Ubicación</label>
                <input type="text" name="ubicacion" id="ubicacion" class="form-input" value="<?= e($ubicacion) ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="fecha">Fecha</label>
                <input type="date" name="fecha" id="fecha" class="form-input<?= isset($errores['fecha']) ? ' is-invalid' : '' ?>" value="<?= e($fecha) ?>" required>
                <?php if (isset($errores['fecha'])): ?>
                    <p class="form-error"><?= e($errores['fecha']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="hora">Hora</label>
                <input type="time" name="hora" id="hora" class="form-input" value="<?= e($hora) ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="categoria">Categoría</label>
                <select name="categoria" id="categoria" class="form-select<?= isset($errores['categoria']) ? ' is-invalid' : '' ?>" required>
                    <option value=""<?= $categoria === '' ? ' selected' : '' ?>>— Elige una —</option>
                    <option value="trabajo"<?= $categoria === 'trabajo' ? ' selected' : '' ?>>Trabajo</option>
                    <option value="personal"<?= $categoria === 'personal' ? ' selected' : '' ?>>Personal</option>
                    <option value="estudio"<?= $categoria === 'estudio' ? ' selected' : '' ?>>Estudio</option>
                    <option value="ocio"<?= $categoria === 'ocio' ? ' selected' : '' ?>>Ocio / Deporte</option>
                </select>
                <?php if (isset($errores['categoria'])): ?>
                    <p class="form-error"><?= e($errores['categoria']) ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-primary">Guardar evento</button>
        </form>

    </section>


</main>


</body>

</html>