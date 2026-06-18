<?php
/**
 * =============================================================================
 * SISTEMA DE CHECK-IN — Configuración central
 * =============================================================================
 * Archivo  : config.php
 * Descripción: Constantes de configuración para base de datos y sesiones.
 *              NUNCA incluir credenciales reales en el repositorio.
 *              En producción, mover las credenciales a variables de entorno
 *              o a un archivo fuera del document root.
 * =============================================================================
 */

declare(strict_types=1);

// =============================================================================
// CONFIGURACIÓN DE BASE DE DATOS
// ⚠️ AJUSTA estos valores en tu panel de hosting (cPanel / Hostinger / etc.)
//
// En Hostinger:
//   - Entra en hPanel → Bases de datos → MySQL
//   - Crea una base de datos y anota: nombre_bd, usuario_bd, contraseña_bd
//   - DB_HOST suele ser 'localhost' (o el host que te indique Hostinger)
// =============================================================================
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'u412199647_checkin');   // ⚠️ Cambia por tu nombre de BD real
define('DB_USER',    'u412199647_checkin');   // ⚠️ Cambia por tu usuario de BD real
define('DB_PASS',    '');                      // ⚠️ Cambia por tu contraseña de BD real
define('DB_CHARSET', 'utf8mb4');

// =============================================================================
// CONFIGURACIÓN DE SESIÓN
// =============================================================================
define('SESSION_NAME',    'checkin_sess');   // Nombre de la cookie de sesión
define('SESSION_LIFETIME', 7200);            // Duración en segundos (2 horas)

// =============================================================================
// CONFIGURACIÓN DE LA APLICACIÓN
// =============================================================================
define('APP_NAME',    'Check-in Alojamientos');
define('APP_VERSION', '1.0');

// URL base — sin barra final.
// ⚠️ AJUSTA esta URL a la de tu servidor real.
define('APP_URL', 'https://rutasrurales.io/checking_accommodations');

// =============================================================================
// ZONA HORARIA
// =============================================================================
date_default_timezone_set('Europe/Madrid');

// =============================================================================
// ERRORES
// En producción: error_reporting(0); ini_set('display_errors', '0');
// En desarrollo: dejar como está.
// =============================================================================
error_reporting(E_ALL);
ini_set('display_errors', '1');   // Cambiar a '0' en producción

// =============================================================================
// INICIALIZACIÓN SEGURA DE SESIÓN
// Se llama desde cada archivo que requiera sesión (login, panel, ficha, logout).
// =============================================================================
function iniciar_sesion_segura(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        // Configuración de la cookie de sesión (antes de session_start)
        session_name(SESSION_NAME);

        // Detecta HTTPS automáticamente (funciona en HTTP y HTTPS sin cambios)
        $es_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || (($_SERVER['SERVER_PORT'] ?? 80) == 443);

        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'domain'   => '',           // Dejar vacío para usar el dominio actual
            'secure'   => $es_https,    // true en HTTPS, false en HTTP (autodetectado)
            'httponly' => true,         // Impide acceso desde JavaScript
            'samesite' => 'Strict',     // Protección CSRF básica
        ]);

        session_start();
    }
}

// =============================================================================
// VERIFICAR SESIÓN ACTIVA (para páginas del panel privado)
// Redirige al login si no hay sesión válida.
// =============================================================================
function requiere_autenticacion(): void
{
    iniciar_sesion_segura();

    if (empty($_SESSION['alojamiento_id']) || !is_int($_SESSION['alojamiento_id'])) {
        header('Location: login.php');
        exit;
    }
}

// =============================================================================
// FUNCIÓN AUXILIAR: Sanitizar output (prevenir XSS)
// Usar SIEMPRE al imprimir datos en HTML.
// =============================================================================
function esc(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// =============================================================================
// FUNCIÓN AUXILIAR: Obtener IP real del visitante (IPv4/IPv6)
// =============================================================================
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
