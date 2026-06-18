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

require_once __DIR__ . '/config.php';

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
            // En producción: loguear el error y mostrar mensaje genérico.
            // NUNCA exponer detalles de conexión al usuario.
            error_log('[CheckIn-DB] Error de conexión PDO: ' . $e->getMessage());

            // Mensaje genérico para el usuario
            http_response_code(503);
            die('⚠️ El servicio no está disponible en este momento. Inténtalo de nuevo más tarde.');
        }
    }

    return $pdo;
}
