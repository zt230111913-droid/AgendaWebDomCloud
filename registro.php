<?php

require_once __DIR__ . '/partes/sesion.php';

if (isset($_SESSION['uid']) && is_int($_SESSION['uid']) && $_SESSION['uid'] > 0) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/conexion.php';

function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

$nombre = '';
$correo = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreRecibido = $_POST['nombre'] ?? '';
    $correoRecibido = $_POST['correo'] ?? '';
    $clave = $_POST['clave'] ?? '';
    $clave2 = $_POST['clave2'] ?? '';

    $nombre = is_string($nombreRecibido) ? trim($nombreRecibido) : '';
    $correo = is_string($correoRecibido)
        ? mb_strtolower(trim($correoRecibido))
        : '';
    $clave = is_string($clave) ? $clave : '';
    $clave2 = is_string($clave2) ? $clave2 : '';

    if ($nombre === '' || mb_strlen($nombre) > 80) {
        $errores['nombre'] = 'Escribe tu nombre (máximo 80 caracteres).';
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 120) {
        $errores['correo'] = 'Escribe un correo válido (máximo 120 caracteres).';
    }

    if (mb_strlen($clave) < 8) {
        $errores['clave'] = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($clave !== $clave2) {
        $errores['clave2'] = 'Las contraseñas no coinciden.';
    }

    if ($errores === []) {
        $hash = password_hash($clave, PASSWORD_DEFAULT);

        try {
            $consulta = $pdo->prepare(
                'INSERT INTO usuarios (nombre, correo, clave_hash)
                VALUES (:nombre, :correo, :clave_hash)'
            );
            $consulta->execute([
                ':nombre' => $nombre,
                ':correo' => $correo,
                ':clave_hash' => $hash,
            ]);

            header('Location: login.php?registrado=1');
            exit;
        } catch (PDOException $e) {
            $codigoDuplicado = $e->errorInfo[1] ?? null;
            if ((int) $codigoDuplicado === 1062) {
                $errores['correo'] = 'Ese correo ya tiene una cuenta.';
            } else {
                error_log('No se pudo crear la cuenta: ' . $e->getMessage());
                $errores['general'] = 'No se pudo crear la cuenta. Intenta de nuevo.';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta - AgendaWeb</title>
    <link rel="stylesheet" href="css/estilos.css?v=8">
</head>
<body>
<main id="contenedor-principal">
    <header class="encabezado-inicio">
        <div>
            <h1>AgendaWeb</h1>
            <p class="subtitulo">Crea tu cuenta personal</p>
        </div>
    </header>

    <section class="tarjeta formulario">
        <h2>Registro</h2>
        <p class="texto-secundario">Cada cuenta mantiene sus eventos privados.</p>

        <?php if (isset($errores['general'])): ?>
            <div class="alert alert--error" role="alert"><?= e($errores['general']) ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label class="form-label" for="nombre">Nombre</label>
                <input class="form-input<?= isset($errores['nombre']) ? ' is-invalid' : '' ?>" type="text" id="nombre" name="nombre" maxlength="80" value="<?= e($nombre) ?>" autocomplete="name" required>
                <?php if (isset($errores['nombre'])): ?>
                    <p class="form-error"><?= e($errores['nombre']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="correo">Correo</label>
                <input class="form-input<?= isset($errores['correo']) ? ' is-invalid' : '' ?>" type="email" id="correo" name="correo" maxlength="120" value="<?= e($correo) ?>" autocomplete="email" required>
                <?php if (isset($errores['correo'])): ?>
                    <p class="form-error"><?= e($errores['correo']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="clave">Contraseña</label>
                <input class="form-input<?= isset($errores['clave']) ? ' is-invalid' : '' ?>" type="password" id="clave" name="clave" minlength="8" autocomplete="new-password" required>
                <?php if (isset($errores['clave'])): ?>
                    <p class="form-error"><?= e($errores['clave']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="clave2">Repite la contraseña</label>
                <input class="form-input<?= isset($errores['clave2']) ? ' is-invalid' : '' ?>" type="password" id="clave2" name="clave2" minlength="8" autocomplete="new-password" required>
                <?php if (isset($errores['clave2'])): ?>
                    <p class="form-error"><?= e($errores['clave2']) ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-primary">Crear cuenta</button>
        </form>
        <p class="texto-secundario">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>.</p>
    </section>
</main>
</body>
</html>
