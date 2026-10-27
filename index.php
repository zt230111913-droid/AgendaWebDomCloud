<?php

require_once __DIR__ . '/partes/sesion.php';
$usuarioId = exigirSesion();

require_once "conexion.php";

/*
 * Mes y año actuales
 */
$mes = isset($_GET['mes'])
    ? (int) $_GET['mes']
    : (int) date('n');

$anio = isset($_GET['anio'])
    ? (int) $_GET['anio']
    : (int) date('Y');


/*
 * Validar mes
 */
if ($mes < 1) {

    $mes = 12;
    $anio--;

}

if ($mes > 12) {

    $mes = 1;
    $anio++;

}


/*
 * Nombres de los meses
 */
$meses = [

    1 => 'Enero',
    2 => 'Febrero',
    3 => 'Marzo',
    4 => 'Abril',
    5 => 'Mayo',
    6 => 'Junio',
    7 => 'Julio',
    8 => 'Agosto',
    9 => 'Septiembre',
    10 => 'Octubre',
    11 => 'Noviembre',
    12 => 'Diciembre'

];


$nombreMes = $meses[$mes];


/*
 * Primer día del mes
 */
$primerDia = mktime(
    0,
    0,
    0,
    $mes,
    1,
    $anio
);


/*
 * Cantidad de días
 */
$diasMes = date(
    't',
    $primerDia
);


/*
 * Día de la semana.
 * Convertimos para que lunes sea 0.
 */
$diaSemana = date(
    'N',
    $primerDia
);

$espaciosIniciales = $diaSemana - 1;


/*
 * Obtener eventos
 */
$stmt = $pdo->prepare("
    SELECT e.*, c.nombre AS categoria
    FROM eventos AS e
    JOIN categorias AS c ON c.id = e.categoria_id
    WHERE e.usuario_id = :usuario_id
    ORDER BY e.fecha ASC, e.hora ASC
");
$stmt->execute([':usuario_id' => $usuarioId]);

$eventos = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);

$buscarRecibido = $_GET['buscar'] ?? '';
if (!is_string($buscarRecibido)) {
    http_response_code(400);
    exit('El término de búsqueda no es válido.');
}
$buscar = trim($buscarRecibido);
$textoBusqueda = '%' . $buscar . '%';

$stmt = $pdo->prepare("
    SELECT e.*, c.nombre AS categoria
    FROM eventos AS e
    JOIN categorias AS c ON c.id = e.categoria_id
    WHERE e.usuario_id = :usuario_id
        AND e.titulo LIKE :buscar
    ORDER BY e.fecha ASC, e.hora ASC
");
$stmt->execute([
    ':usuario_id' => $usuarioId,
    ':buscar' => $textoBusqueda,
]);
$eventosLista = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT c.id, c.nombre, COUNT(e.id) AS total
    FROM categorias AS c
    LEFT JOIN eventos AS e
        ON e.categoria_id = c.id
        AND e.usuario_id = :usuario_id
    GROUP BY c.id, c.nombre
    ORDER BY c.nombre
");
$stmt->execute([':usuario_id' => $usuarioId]);
$resumenCategorias = $stmt->fetchAll();


/*
 * Organizar eventos por fecha
 */
$eventosPorFecha = [];

foreach ($eventos as $evento) {

    $fecha = $evento['fecha'];

    if (!isset($eventosPorFecha[$fecha])) {

        $eventosPorFecha[$fecha] = [];
    }

    $eventosPorFecha[$fecha][] = $evento;
}


/*
 * Eventos del mes actual
 */
$eventosMes = [];

foreach ($eventos as $evento) {

    $fechaEvento = strtotime($evento['fecha']);

    if (
        date('n', $fechaEvento) == $mes &&
        date('Y', $fechaEvento) == $anio
    ) {

        $eventosMes[] = $evento;
    }
}


/*
 * Categorías
 */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}


function claseCategoria($categoria)
{

    switch (strtolower($categoria)) {

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


/*
 * Formatear fecha
 */
function fechaBonita($fecha)
{

    $timestamp = strtotime($fecha);

    return date(
        'd/m/Y',
        $timestamp
    );
}


function mostrarEvento(array $ev): string
{
    $id = (int) $ev['id'];
    $categoria = (string) ($ev['categoria'] ?? '');
    $ubicacion = $ev['ubicacion'] ?? '';
    $hora = $ev['hora'] ?? '';
    $descripcion = $ev['descripcion'] ?? '';

    $html = '<tr>';
    $html .= '<td><div class="titulo-evento">';
    $html .= '<span class="etiqueta ' . claseCategoria($categoria) . '">'
        . e($categoria) . '</span>';
    $html .= '<a href="evento.php?id=' . $id . '" class="nombre-evento">'
        . e($ev['titulo'] ?? '') . '</a>';
    $html .= '</div></td>';
    $html .= '<td>' . e($ubicacion !== '' ? $ubicacion : '—') . '</td>';
    $html .= '<td>' . e(fechaBonita((string) $ev['fecha'])) . '</td>';
    $html .= '<td>' . e($hora !== '' ? substr($hora, 0, 5) : '—') . '</td>';
    $html .= '<td>' . e($descripcion !== '' ? $descripcion : 'Sin descripción') . '</td>';
    $html .= '<td><div class="acciones">';
    $html .= '<a href="editar.php?id=' . $id . '" class="btn-editar">✎ Editar</a>';
    $html .= '<form action="borrar.php" method="POST" '
        . 'onsubmit="return confirm(\'¿Borrar este evento? No se puede deshacer.\');">';
    $html .= '<input type="hidden" name="id" value="' . $id . '">';
    $html .= '<button type="submit" class="btn-eliminar">♜ Eliminar</button>';
    $html .= '</form></div></td></tr>';

    return $html;
}

$avisos = [
    '1' => 'Evento guardado correctamente.',
    'editado' => 'Cambios guardados.',
    'borrado' => 'Evento borrado.',
];
$claveAviso = $_GET['ok'] ?? '';
$aviso = is_string($claveAviso) ? ($avisos[$claveAviso] ?? null) : null;
$hayError = isset($_GET['error']) && $_GET['error'] === 'noexiste';

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
        AgendaWeb - Mis eventos
    </title>


    <!-- Google Fonts -->

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


    <!-- CSS -->

    <link
        rel="stylesheet"
        href="css/estilos.css?v=8"
    >

</head>


<body>


<!-- =================================
     BARRA SUPERIOR
================================= -->

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


            <a
                href="registrar.php"
            >
                <span>＋</span>
                Nuevo evento
            </a>

            <a href="logout.php">
                Cerrar sesión (<?= e((string) ($_SESSION['nombre'] ?? '')) ?>)
            </a>

        </nav>


        <div class="usuario-icono">
            ◯
        </div>

    </div>

</header>


<!-- =================================
     CONTENIDO
================================= -->

<main class="contenedor">


    <!-- ENCABEZADO -->

    <section class="encabezado-pagina">

        <div>

            <h1>
                Mis eventos
            </h1>

            <p>
                Organiza tus actividades y mantén todo en un solo lugar.
            </p>

        </div>


        <a
            href="registrar.php"
            class="btn-nuevo"
        >
            <span>＋</span>
            Nuevo Evento
        </a>

    </section>

    <div class="resumen-categorias" aria-label="Resumen de eventos por categoría">
        <h2>Eventos por categoría</h2>
        <div class="resumen-categorias-lista">
            <?php foreach ($resumenCategorias as $resumen): ?>
                <div class="resumen-categoria">
                    <span><?= e($resumen['nombre']) ?></span>
                    <strong><?= (int) $resumen['total'] ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- =================================
         CALENDARIO
    ================================= -->

    <section class="tarjeta calendario">


        <!-- CABECERA DEL CALENDARIO -->

        <div class="calendario-header">

            <div class="titulo-calendario">

                <span class="icono-calendario">
                    ▣
                </span>

                <h2>
                    <?php echo $nombreMes; ?>
                    <?php echo $anio; ?>
                </h2>

            </div>


            <div class="controles-mes">

                <?php

                $mesAnterior = $mes - 1;
                $anioAnterior = $anio;

                if ($mesAnterior < 1) {

                    $mesAnterior = 12;
                    $anioAnterior--;
                }


                $mesSiguiente = $mes + 1;
                $anioSiguiente = $anio;

                if ($mesSiguiente > 12) {

                    $mesSiguiente = 1;
                    $anioSiguiente++;
                }

                ?>


                <a
                    href="?mes=<?php echo $mesAnterior; ?>&anio=<?php echo $anioAnterior; ?>"
                    class="flecha"
                >
                    ‹
                </a>


                <span>
                    <?php echo $nombreMes; ?>
                    <?php echo $anio; ?>
                </span>


                <a
                    href="?mes=<?php echo $mesSiguiente; ?>&anio=<?php echo $anioSiguiente; ?>"
                    class="flecha"
                >
                    ›
                </a>

            </div>

        </div>


        <!-- DÍAS DE LA SEMANA -->

        <div class="dias-semana">

            <div>Lun</div>
            <div>Mar</div>
            <div>Mié</div>
            <div>Jue</div>
            <div>Vie</div>
            <div>Sáb</div>
            <div>Dom</div>

        </div>


        <!-- DÍAS -->

        <div class="dias-calendario">


            <!-- ESPACIOS ANTERIORES -->

            <?php

            for (
                $i = 0;
                $i < $espaciosIniciales;
                $i++
            ):

            ?>

                <div class="dia calendario-vacio"></div>

            <?php endfor; ?>


            <!-- DÍAS DEL MES -->

            <?php

            for (
                $dia = 1;
                $dia <= $diasMes;
                $dia++
            ):

                $fechaActual = sprintf(
                    '%04d-%02d-%02d',
                    $anio,
                    $mes,
                    $dia
                );


                $tieneEventos =
                    isset(
                        $eventosPorFecha[$fechaActual]
                    );

                $claseCategoriaDia = $tieneEventos
                    ? claseCategoria($eventosPorFecha[$fechaActual][0]['categoria'])
                    : '';


                $esHoy =
                    $fechaActual === date('Y-m-d');

            ?>

                <div
                    class="dia
                    <?php echo $esHoy ? 'dia-hoy' : ''; ?>
                    <?php echo $tieneEventos ? 'dia-evento ' . $claseCategoriaDia : ''; ?>"
                >

                    <span class="numero-dia">
                        <?php echo $dia; ?>
                    </span>


                    <?php if ($tieneEventos): ?>

                        <div class="eventos-dia">
                            <?php foreach (array_slice($eventosPorFecha[$fechaActual], 0, 2) as $eventoDia): ?>
                                <a
                                    class="evento-dia"
                                    href="evento.php?id=<?php echo (int) $eventoDia['id']; ?>"
                                    title="<?php echo e($eventoDia['titulo']); ?>"
                                >
                                    <span class="punto <?php echo claseCategoria($eventoDia['categoria']); ?>"></span>
                                    <span class="evento-dia-titulo"><?php echo e($eventoDia['titulo']); ?></span>
                                </a>
                            <?php endforeach; ?>

                            <?php if (count($eventosPorFecha[$fechaActual]) > 2): ?>
                                <span class="eventos-dia-mas">
                                    +<?php echo count($eventosPorFecha[$fechaActual]) - 2; ?> más
                                </span>
                            <?php endif; ?>
                        </div>

                    <?php endif; ?>

                </div>

            <?php endfor; ?>


            <!-- ESPACIOS FINALES -->

            <?php

            $totalCeldas =
                $espaciosIniciales + $diasMes;

            $restantes =
                7 - ($totalCeldas % 7);

            if ($restantes < 7):

                for (
                    $i = 0;
                    $i < $restantes;
                    $i++
                ):

            ?>

                    <div class="dia calendario-vacio"></div>

            <?php

                endfor;

            endif;

            ?>

        </div>


        <!-- LEYENDA -->

        <div class="leyenda">

            <span>
                <i class="punto categoria-trabajo"></i>
                Trabajo
            </span>

            <span>
                <i class="punto categoria-ocio"></i>
                Ocio
            </span>

            <span>
                <i class="punto categoria-estudio"></i>
                Estudio
            </span>

            <span>
                <i class="punto categoria-personal"></i>
                Personal
            </span>

        </div>


    </section>


    <!-- =================================
         LISTA DE EVENTOS
    ================================= -->

    <section class="tarjeta lista-eventos">
        <?php if ($aviso !== null): ?>
            <div class="alert alert--ok" role="status">
                <?= e($aviso) ?>
            </div>
        <?php endif; ?>
        <?php if ($hayError): ?>
            <div class="alert alert--error" role="alert">
                Ese evento no existe (quizá ya fue borrado).
            </div>
        <?php endif; ?>

        <div class="lista-header">

            <div>

                <h2>
                    ☷ &nbsp; Lista de Eventos
                </h2>

            </div>


            <span class="contador-eventos">

                <?php echo count($eventosLista); ?>

                evento<?php echo count($eventosLista) != 1 ? 's' : ''; ?>

            </span>

        </div>

        <form class="busqueda-eventos" method="get" action="index.php">
            <label class="sr-only" for="buscar">Buscar eventos por título</label>
            <input
                class="form-input"
                type="search"
                id="buscar"
                name="buscar"
                value="<?= e($buscar) ?>"
                placeholder="Buscar evento..."
            >
            <button type="submit" class="btn-primary">Buscar</button>
            <?php if ($buscar !== ''): ?>
                <a class="btn-secundario" href="index.php">Limpiar</a>
            <?php endif; ?>
        </form>

        <?php if (empty($eventosLista) && $buscar !== ''): ?>

            <div class="sin-eventos">
                <h3>Sin resultados para tu búsqueda</h3>
                <p>No encontramos eventos cuyo título coincida con “<?= e($buscar) ?>”.</p>
            </div>

        <?php elseif (empty($eventosLista)): ?>


            <div class="sin-eventos">

                <div class="sin-eventos-icono">
                    +
                </div>

                <h3>
                    No hay eventos registrados
                </h3>

                <p>
                    Agrega tu primer evento para comenzar.
                </p>

                <a
                    href="registrar.php"
                    class="btn-nuevo"
                >
                    + Nuevo Evento
                </a>

            </div>


        <?php else: ?>


            <div class="tabla-responsive">

                <table class="tabla-eventos">

                    <thead>

                        <tr>

                            <th>
                                Título
                            </th>

                            <th>
                                Ubicación
                            </th>

                            <th>
                                Fecha
                            </th>

                            <th>
                                Hora
                            </th>

                            <th>
                                Descripción
                            </th>

                            <th>
                                Acciones
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach ($eventosLista as $evento): ?>
                        <?= mostrarEvento($evento) ?>
                    <?php endforeach; ?>


                    </tbody>

                </table>

            </div>


        <?php endif; ?>


    </section>


</main>


<footer class="pie">

    <p>
        AgendaWeb &copy;
        <?php echo date('Y'); ?>
    </p>

</footer>


</body>

</html>