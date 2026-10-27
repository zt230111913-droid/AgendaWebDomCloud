<?php

require_once __DIR__ . '/partes/sesion.php';
$usuarioId = exigirSesion();

require_once "conexion.php";
$id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


if ($id <= 0) {

    header("Location: index.php");

    exit;
}
$stmt = $pdo->prepare("
    SELECT e.*, c.nombre AS categoria
    FROM eventos AS e
    JOIN categorias AS c ON c.id = e.categoria_id
    WHERE e.id = :id AND e.usuario_id = :usuario_id
");


$stmt->execute([
    ':id' => $id,
    ':usuario_id' => $usuarioId,
]);

$evento = $stmt->fetch(
    PDO::FETCH_ASSOC
);

if (!$evento) {

    header("Location: index.php");

    exit;
}

function claseCategoriaEvento($categoria)
{
    switch (strtolower(trim($categoria))) {

        case 'trabajo':
            return 'categoria-trabajo';

        case 'ocio':
        case 'ocio / deporte':
            return 'categoria-ocio';

        case 'estudio':
            return 'categoria-estudio';

        default:
            return 'categoria-personal';
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

    <title>
        <?php echo htmlspecialchars($evento['titulo']); ?>
        - AgendaWeb
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
        href="css/estilos.css?v=8"
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
            <a href="logout.php">
                Cerrar sesión (<?php echo htmlspecialchars((string) ($_SESSION['nombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>)
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
                Detalles del evento
            </h1>

            <p>
                Consulta la información registrada.
            </p>

        </div>


        <a
            href="index.php"
            class="btn-secundario"
        >
            ← Mis eventos
        </a>

    </section>


    <section class="tarjeta detalle-evento">


        <span
            class="etiqueta grande <?php echo claseCategoriaEvento($evento['categoria']); ?>"
        >
            <?php
            echo htmlspecialchars(
                $evento['categoria']
            );
            ?>
        </span>


        <h2>

            <?php
            echo htmlspecialchars(
                $evento['titulo']
            );
            ?>

        </h2>


        <div class="detalle-grid">


            <div>

                <strong>
                    Fecha
                </strong>

                <span>
                    <?php
                    echo date(
                        'd/m/Y',
                        strtotime($evento['fecha'])
                    );
                    ?>
                </span>

            </div>


            <div>

                <strong>
                    Hora
                </strong>

                <span>
                    <?php
                    echo htmlspecialchars(
                        $evento['hora']
                        ?: 'Sin hora'
                    );
                    ?>
                </span>

            </div>


            <div>

                <strong>
                    Ubicación
                </strong>

                <span>
                    <?php
                    echo htmlspecialchars(
                        $evento['ubicacion']
                        ?: 'Sin ubicación'
                    );
                    ?>
                </span>

            </div>


        </div>


        <div class="descripcion-evento">

            <strong>
                Descripción
            </strong>

            <p>

                <?php
                echo nl2br(
                    htmlspecialchars(
                        $evento['descripcion']
                        ?: 'Sin descripción.'
                    )
                );
                ?>

            </p>

        </div>


        <div class="acciones-detalle">

            <a
                href="editar.php?id=<?php echo $evento['id']; ?>"
                class="btn-nuevo"
            >
                ✎ Editar evento
            </a>


            <a
                href="index.php"
                class="btn-cancelar"
            >
                Regresar
            </a>

        </div>


    </section>

</main>


</body>

</html>