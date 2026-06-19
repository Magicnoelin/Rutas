<?php
/**
 * =============================================================================
 * SISTEMA DE CHECK-IN — Configuración central
 * =============================================================================
 * Cada constante se protege individualmente con if(!defined()) para evitar
 * que un auto_prepend_file del proyecto principal bloquee la carga completa.
 * =============================================================================
 */

// Marca de carga — permite detectar si el archivo ya fue incluido
if (!defined('CHECKIN_CONFIG_LOADED')) {
    define('CHECKIN_CONFIG_LOADED', true);
}

// =============================================================================
// CONFIGURACIÓN DE LA APLICACIÓN
// ⚠️ Ajusta CHECKIN_APP_URL si el directorio cambia
// =============================================================================
if (!defined('CHECKIN_APP_NAME')) {
    define('CHECKIN_APP_NAME', 'Check-in Alojamientos');
}

if (!defined('CHECKIN_APP_URL')) {
    define('CHECKIN_APP_URL', 'https://rutasrurales.io/checking_accommodations');
}

// =============================================================================
// CONFIGURACIÓN DE SESIÓN
// =============================================================================
if (!defined('CHECKIN_SESSION_NAME')) {
    define('CHECKIN_SESSION_NAME', 'checkin_sess');
}

if (!defined('CHECKIN_SESSION_LIFETIME')) {
    define('CHECKIN_SESSION_LIFETIME', 7200); // 2 horas
}

// =============================================================================
// ZONA HORARIA
// =============================================================================
date_default_timezone_set('Europe/Madrid');

// =============================================================================
// ERRORES — poner a 0 en producción cuando todo funcione
// =============================================================================
error_reporting(E_ALL);
ini_set('display_errors', '1');

// =============================================================================
// FUNCIONES AUXILIARES (protegidas contra doble declaración)
// =============================================================================

if (!function_exists('iniciar_sesion_segura')) {
    function iniciar_sesion_segura(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(CHECKIN_SESSION_NAME);

            $es_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                        || (($_SERVER['SERVER_PORT'] ?? 80) == 443);

            session_set_cookie_params([
                'lifetime' => CHECKIN_SESSION_LIFETIME,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $es_https,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);

            session_start();
        }
    }
}

if (!function_exists('requiere_autenticacion')) {
    function requiere_autenticacion(): void
    {
        iniciar_sesion_segura();

        if (empty($_SESSION['alojamiento_id']) || !is_int($_SESSION['alojamiento_id'])) {
            header('Location: login.php');
            exit;
        }
    }
}

if (!function_exists('esc')) {
    function esc(string $valor): string
    {
        return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('obtener_ip')) {
    function obtener_ip(): string
    {
        $headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = trim(explode(',', $_SERVER[$header])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return 'desconocida';
    }
}
