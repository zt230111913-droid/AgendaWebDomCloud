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

$correo = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correoRecibido = $_POST['correo'] ?? '';
    $claveRecibida = $_POST['clave'] ?? '';
    $correo = is_string($correoRecibido)
        ? mb_strtolower(trim($correoRecibido))
        : '';
    $clave = is_string($claveRecibida) ? $claveRecibida : '';

    if (filter_var($correo, FILTER_VALIDATE_EMAIL) && $clave !== '') {
        try {
            $consulta = $pdo->prepare(
                'SELECT id, nombre, clave_hash FROM usuarios WHERE correo = :correo'
            );
            $consulta->execute([':correo' => $correo]);
            $usuario = $consulta->fetch();

            if ($usuario && password_verify($clave, $usuario['clave_hash'])) {
                session_regenerate_id(true);
                $_SESSION['uid'] = (int) $usuario['id'];
                $_SESSION['nombre'] = (string) $usuario['nombre'];

                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log('No se pudo iniciar sesión: ' . $e->getMessage());
            $error = 'No se pudo iniciar sesión. Intenta de nuevo.';
        }
    }

    if ($error === '') {
        $error = 'El correo o la contraseña no son correctos.';
    }
}

$registrado = ($_GET['registrado'] ?? '') === '1';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - AgendaWeb</title>
    <link rel="stylesheet" href="css/estilos.css?v=8">
</head>
<body>
<main id="contenedor-principal">
    <header class="encabezado-inicio">
        <div>
            <h1>AgendaWeb</h1>
            <p class="subtitulo">Inicia sesión para ver tus eventos</p>
        </div>
    </header>

    <section class="tarjeta formulario">
        <h2>Iniciar sesión</h2>
        <?php if ($registrado): ?>
            <div class="alert alert--success" role="status">Cuenta creada. Ya puedes iniciar sesión.</div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert--error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label class="form-label" for="correo">Correo</label>
                <input class="form-input" type="email" id="correo" name="correo" maxlength="120" value="<?= e($correo) ?>" autocomplete="username" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="clave">Contraseña</label>
                <input class="form-input" type="password" id="clave" name="clave" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn-primary">Iniciar sesión</button>
        </form>
        <p class="texto-secundario">¿No tienes cuenta? <a href="registro.php">Crear cuenta</a>.</p>
    </section>
</main>
</body>
</html>
