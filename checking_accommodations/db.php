<?php
/**
 * =============================================================================
 * SISTEMA DE CHECK-IN — Conexión a la base de datos (PDO Singleton)
 * =============================================================================
 * Archivo  : db.php
 * Descripción: Proporciona una única instancia de PDO reutilizable en toda
 *              la aplicación. Usa prepared statements obligatorios para
 *              prevenir inyección SQL.
 *
 * USO:
 *   require_once __DIR__ . '/db.php';
 *   $pdo = obtener_pdo();
 *   $stmt = $pdo->prepare("SELECT * FROM alojamientos WHERE id = ?");
 *   $stmt->execute([$id]);
 * =============================================================================
 */

declare(strict_types=1);

// Carga config.php solo si las constantes aún no están definidas.
// Protege contra problemas de path resolution en algunos servidores compartidos.
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

/**
 * Retorna la instancia singleton de PDO.
 * La conexión se crea la primera vez y se reutiliza en llamadas posteriores.
 *
 * @throws PDOException Si la conexión falla (capturada internamente).
 * @return PDO Instancia de la conexión a la base de datos.
 */
function obtener_pdo(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $opciones = [
            // Lanza excepciones en errores de BD — nunca silenciarlos
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

            // Devuelve filas como arrays asociativos por defecto
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            // CRÍTICO: Deshabilitar emulación de prepared statements.
            // Garantiza que MySQL procese los parámetros como datos, nunca como SQL.
            PDO::ATTR_EMULATE_PREPARES   => false,

            // Persistencia desactivada (más seguro en entornos web)
            PDO::ATTR_PERSISTENT         => false,

            // Timeout de conexión: 5 segundos
            PDO::ATTR_TIMEOUT            => 5,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
            error_log('[CheckIn-DB] Error de conexión PDO: ' . $e->getMessage());

            http_response_code(503);

            // En modo desarrollo mostramos el error real para facilitar la configuración.
            // En producción: cambia display_errors a '0' en config.php para ocultar esto.
            if (ini_get('display_errors') === '1' || ini_get('display_errors') === 'On') {
                die(
                    '<div style="font-family:monospace;background:#fff3cd;border:2px solid #ffc107;'
                    . 'border-radius:8px;padding:1.5rem;max-width:700px;margin:2rem auto;">'
                    . '<h3 style="color:#856404;margin:0 0 .75rem;">⚠️ Error de conexión a la base de datos</h3>'
                    . '<p style="margin:.25rem 0;"><strong>Mensaje:</strong> ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>'
                    . '<p style="margin:.25rem 0;"><strong>BD:</strong> ' . DB_NAME . ' | <strong>Host:</strong> ' . DB_HOST . ' | <strong>Usuario:</strong> ' . DB_USER . '</p>'
                    . '<hr style="border-color:#ffc107;margin:.75rem 0;">'
                    . '<p style="margin:0;font-size:.88rem;"><strong>Solución:</strong> Edita <code>config.php</code> con las credenciales correctas de tu hosting '
                    . 'y asegúrate de que la base de datos <code>' . DB_NAME . '</code> existe en phpMyAdmin / hPanel.</p>'
                    . '</div>'
                );
            }

            // Mensaje genérico para producción
            die('⚠️ El servicio no está disponible en este momento. Inténtalo de nuevo más tarde.');
        }
    }

    return $pdo;
}
