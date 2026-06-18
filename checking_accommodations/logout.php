<?php
/**
 * =============================================================================
 * SISTEMA DE CHECK-IN — Cierre de sesión seguro
 * =============================================================================
 * Archivo  : logout.php
 * Acceso   : Privado (requiere sesión activa)
 * Descripción: Destruye la sesión activa de forma completa y segura.
 *              Elimina datos de sesión, cookie y redirige al login.
 * =============================================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Iniciar sesión para poder destruirla
iniciar_sesion_segura();

// ---------------------------------------------------------------------------
// Destruir la sesión de forma completa y segura
// ---------------------------------------------------------------------------

// 1. Limpiar todos los datos de sesión
$_SESSION = [];

// 2. Eliminar la cookie de sesión del navegador del usuario
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        [
            'expires'  => time() - 42000,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => 'Strict',
        ]
    );
}

// 3. Destruir la sesión en el servidor
session_destroy();

// ---------------------------------------------------------------------------
// Redirigir al login con mensaje de confirmación
// ---------------------------------------------------------------------------
header('Location: login.php?logout=1');
exit;
