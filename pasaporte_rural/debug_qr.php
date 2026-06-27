<?php
/**
 * =============================================================================
 * PASAPORTE RURAL — Diagnóstico del endpoint QR
 * =============================================================================
 * BORRAR ESTE ARCHIVO EN PRODUCCIÓN TRAS USARLO
 * Accede a: https://rutasrurales.io/pasaporte_rural/debug_qr.php
 * =============================================================================
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO PASAPORTE RURAL ===\n\n";

// 1. PHP
echo "1. PHP versión: " . PHP_VERSION . "\n";

// 2. Sesión
session_start();
echo "2. user_id en sesión: " . ($_SESSION['user_id'] ?? '(NO — debes estar logueado)') . "\n\n";

// 3. Config
echo "3. Cargando api/config.php...\n";
define('API_NO_HEADERS', true);
require_once __DIR__ . '/../api/config.php';
echo "   OK — DB_NAME=" . DB_NAME . "\n\n";

// 4. BD
echo "4. Conexión a BD...\n";
try {
    $pdo = getDBConnection();
    echo "   OK\n\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
    exit;
}

// 5. Tablas del módulo
echo "5. Tablas del módulo:\n";
foreach (['pasaporte_turistas', 'qr_temporales', 'historico_sellos'] as $t) {
    try {
        $n = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "   OK $t ($n filas)\n";
    } catch (Exception $e) {
        echo "   FALTA $t — Ejecuta schema.sql en phpMyAdmin\n";
    }
}
echo "\n";

// 6. is_premium
echo "6. Columna is_premium en accommodations:\n";
try {
    $cols = $pdo->query("SHOW COLUMNS FROM accommodations LIKE 'is_premium'")->fetchAll();
    if ($cols) {
        $n = $pdo->query("SELECT COUNT(*) FROM accommodations WHERE is_premium=1")->fetchColumn();
        echo "   OK — $n alojamientos Premium\n";
    } else {
        echo "   FALTA — Ejecuta el ALTER TABLE del schema.sql\n";
    }
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// 7. Pasaporte del usuario logueado
if (!empty($_SESSION['user_id'])) {
    $uid = (int) $_SESSION['user_id'];
    echo "7. Pasaporte de user_id=$uid:\n";
    try {
        $p = $pdo->prepare('SELECT id, estado FROM pasaporte_turistas WHERE user_id=? LIMIT 1');
        $p->execute([$uid]);
        $row = $p->fetch();
        echo $row ? "   OK — id={$row['id']} estado={$row['estado']}\n" : "   Aún no existe (se creará al acceder a mi-pasaporte.php)\n";
    } catch (Exception $e) {
        echo "   ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "7. Inicia sesión para verificar tu pasaporte\n";
}

echo "\n8. URL del endpoint JS:\n";
echo "   https://rutasrurales.io/pasaporte_rural/generar_token_qr.php\n";

// 9. LLAMADA DIRECTA al endpoint para ver qué devuelve
echo "\n9. Llamada directa a generar_token_qr.php (simulada):\n";
if (!empty($_SESSION['user_id'])) {
    // Simular lo que hace el endpoint sin redirigir
    $uid = (int) $_SESSION['user_id'];
    try {
        // Cargar config del módulo
        require_once __DIR__ . '/config.php';

        // Limpiar tokens viejos
        $pdo->prepare(
            'UPDATE qr_temporales SET estado="expirado"
              WHERE pasaporte_id = (SELECT id FROM pasaporte_turistas WHERE user_id=?)
                AND estado="pendiente"
                AND created_at < DATE_SUB(NOW(), INTERVAL 120 SECOND)'
        )->execute([$uid]);

        // Obtener pasaporte
        $st = $pdo->prepare(
            'SELECT pt.id, pt.estado, pt.descuento_actual, pt.puntos_totales, pt.nivel
               FROM pasaporte_turistas pt WHERE pt.user_id=? LIMIT 1'
        );
        $st->execute([$uid]);
        $pas = $st->fetch();

        if (!$pas) {
            echo "   ERROR: no hay pasaporte para user_id=$uid\n";
        } else {
            // Intentar generar token
            $hash = bin2hex(random_bytes(48));
            $pdo->prepare(
                'INSERT INTO qr_temporales (pasaporte_id, hash_token, estado, ip_generacion)
                 VALUES (?, ?, "pendiente", "debug")'
            )->execute([$pas['id'], $hash]);
            echo "   OK — Token generado correctamente: " . substr($hash, 0, 20) . "...\n";
            echo "   URL QR sería: https://rutasrurales.io/pasaporte_rural/validar_pasaporte.php?token=$hash\n";

            // Limpiar el token de prueba
            $pdo->prepare('DELETE FROM qr_temporales WHERE hash_token=?')->execute([$hash]);
            echo "   (Token de prueba eliminado)\n";
        }
    } catch (Exception $e) {
        echo "   EXCEPCIÓN: " . $e->getMessage() . "\n";
    }
} else {
    echo "   Debes iniciar sesión\n";
}

// 10. Verificar output buffer / warnings
echo "\n10. Verificando si hay output antes del JSON:\n";
echo "    Si ves caracteres extraños o HTML entre el inicio y aquí, ese es el problema.\n";
echo "    error_reporting activo: " . error_reporting() . " (32767 = E_ALL)\n";

echo "\n=== BORRA ESTE ARCHIVO TRAS EL DIAGNÓSTICO ===\n";
