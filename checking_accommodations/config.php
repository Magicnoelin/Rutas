<?php
/**
 * =============================================================================
 * SISTEMA DE CHECK-IN — Configuración central
 * =============================================================================
 * Archivo  : config.php
 * Descripción: Constantes con prefijo CHECKIN_ para evitar colisiones con
 *              otras constantes definidas en el proyecto principal.
 *
 * ⚠️ AJUSTA las credenciales de BD antes de subir al servidor.
 * =============================================================================
 */

// Evitar doble inclusión (compatible con require_once en todos los archivos)
if (defined('CHECKIN_CONFIG_LOADED')) {
    return;
}
define('CHECKIN_CONFIG_LOADED', true);

// =============================================================================
// CONFIGURACIÓN DE BASE DE DATOS
// ⚠️ Ajusta con los datos de tu hPanel → Bases de datos → MySQL
// =============================================================================
define('CHECKIN_DB_HOST',    'localhost');
define('CHECKIN_DB_PORT',    '3306');
define('CHECKIN_DB_NAME',    'u412199647_checkin');   // ⚠️ Tu nombre de BD
define('CHECKIN_DB_USER',    'u412199647_checkin');   // ⚠️ Tu usuario de BD
define('CHECKIN_DB_PASS',    '');                      // ⚠️ Tu contraseña de BD
define('CHECKIN_DB_CHARSET', 'utf8mb4');

// =============================================================================
// CONFIGURACIÓN DE SESIÓN
// =============================================================================
define('CHECKIN_SESSION_NAME',    'checkin_sess');
define('CHECKIN_SESSION_LIFETIME', 7200);             // 2 horas en segundos

// =============================================================================
// CONFIGURACIÓN DE LA APLICACIÓN
// =============================================================================
define('CHECKIN_APP_NAME',    'Check-in Alojamientos');
define('CHECKIN_APP_URL',     'https://rutasrurales.io/checking_accommodations');

// =============================================================================
// ZONA HORARIA
// =============================================================================
date_default_timezone_set('Europe/Madrid');

// =============================================================================
// ERRORES (cambiar a 0 en producción)
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
