<?php
/**
 * =============================================================================
 * SISTEMA DE CHECK-IN — Conexión a la base de datos (PDO Singleton)
 * =============================================================================
 * Archivo  : db.php
 * Usa constantes con prefijo CHECKIN_ para evitar colisiones con el proyecto
 * principal del servidor (rutasrurales.io).
 * =============================================================================
 */

// Cargar configuración siempre (el guard interno de config.php evita doble carga)
require_once __DIR__ . '/config.php';

if (!function_exists('obtener_pdo')) {

    function obtener_pdo(): PDO
    {
        static $pdo = null;

        if ($pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                CHECKIN_DB_HOST,
                CHECKIN_DB_PORT,
                CHECKIN_DB_NAME,
                CHECKIN_DB_CHARSET
            );

            $opciones = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
            ];

            try {
                $pdo = new PDO($dsn, CHECKIN_DB_USER, CHECKIN_DB_PASS, $opciones);
            } catch (PDOException $e) {
                error_log('[CheckIn-DB] Error PDO: ' . $e->getMessage());
                http_response_code(503);

                // Muestra el error real mientras display_errors está activo (desarrollo)
                if (ini_get('display_errors') === '1' || ini_get('display_errors') === 'On') {
                    die(
                        '<div style="font-family:monospace;background:#fff3cd;border:2px solid #ffc107;'
                        . 'border-radius:8px;padding:1.5rem;max-width:700px;margin:2rem auto;">'
                        . '<h3 style="color:#856404;margin:0 0 .75rem;">⚠️ Error de conexión a la base de datos</h3>'
                        . '<p><strong>Mensaje:</strong> ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>'
                        . '<p><strong>BD:</strong> ' . CHECKIN_DB_NAME
                        . ' | <strong>Host:</strong> ' . CHECKIN_DB_HOST
                        . ' | <strong>Usuario:</strong> ' . CHECKIN_DB_USER . '</p>'
                        . '<hr style="border-color:#ffc107;margin:.75rem 0;">'
                        . '<p style="font-size:.88rem;"><strong>Solución:</strong> '
                        . 'Edita <code>config.php</code> con las credenciales reales de tu hPanel → Bases de datos → MySQL. '
                        . 'Asegúrate de que la base de datos <code>' . CHECKIN_DB_NAME . '</code> exista y el usuario tenga permisos.</p>'
                        . '</div>'
                    );
                }

                die('⚠️ El servicio no está disponible en este momento.');
            }
        }

        return $pdo;
    }

} // end function_exists
