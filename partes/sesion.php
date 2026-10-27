<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = !empty($_SERVER['HTTPS'])
        && strtolower((string) $_SERVER['HTTPS']) !== 'off';

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function exigirSesion(): int
{
    $usuarioId = $_SESSION['uid'] ?? null;

    if (!is_int($usuarioId) || $usuarioId <= 0) {
        header('Location: login.php');
        exit;
    }

    return $usuarioId;
}
