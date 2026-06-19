<?php
/**
 * =============================================================================
 * SISTEMA DE CHECK-IN — Conexión PDO
 * Versión ultra-robusta: sin variables locales, sin constantes externas.
 * Los valores se pasan directamente como literales de cadena a PDO.
 *
 * ⚠️  EDITA LAS 3 LÍNEAS MARCADAS con los datos de tu hPanel → MySQL
 * =============================================================================
 */

// Cargar funciones auxiliares (esc, sesiones, etc.)
require_once __DIR__ . '/config.php';

// ---------------------------------------------------------------------------
// Singleton PDO
// ---------------------------------------------------------------------------
if (!function_exists('obtener_pdo')) {

    function obtener_pdo(): PDO
    {
        static $instance = null;

        if ($instance !== null) {
            return $instance;
        }

        // ============================================================
        // ⚠️  AJUSTA ESTOS 3 VALORES con los de tu hPanel → MySQL
        //     Nombre BD  →  primera línea
        //     Usuario BD →  segunda línea
        //     Contraseña →  tercera línea
        // ============================================================
        try {
            $instance = new PDO(
                'mysql:host=localhost;port=3306;dbname=u412199647_Rutas;charset=utf8mb4',
                'u412199647_Rutas',     // ⚠️ usuario BD de u412199647_Rutas
                'PON_AQUI_TU_PASSWORD', // ⚠️ contraseña BD
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_PERSISTENT         => false,
                ]
            );
        } catch (PDOException $e) {
            error_log('[CheckIn-DB] Error PDO: ' . $e->getMessage());
            http_response_code(503);

            if (ini_get('display_errors') === '1' || ini_get('display_errors') === 'On') {
                die(
                    '<div style="font-family:monospace;background:#fff3cd;border:2px solid #ffc107;'
                    . 'border-radius:8px;padding:1.5rem;max-width:700px;margin:2rem auto;">'
                    . '<h3 style="color:#856404;margin:0 0 .75rem;">⚠️ Error de conexión a la base de datos</h3>'
                    . '<p><strong>Mensaje:</strong> '
                    . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>'
                    . '<hr style="border-color:#ffc107;margin:.75rem 0;">'
                    . '<p style="font-size:.88rem;"><strong>Solución:</strong> '
                    . 'Edita <code>db.php</code> — busca las 3 líneas marcadas con ⚠️ '
                    . 'y escribe las credenciales reales de tu hPanel → Bases de datos → MySQL.</p>'
                    . '</div>'
                );
            }

            die('El servicio no está disponible.');
        }

        return $instance;
    }

}
