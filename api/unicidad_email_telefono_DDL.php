<?php
/**
 * ============================================================
 * SCRIPT DDL: Unicidad de Email y Teléfono en tabla `users`
 * Proyecto: Rutas Rurales (rutasrurales.io)
 * Fecha: 2026-01-08
 * ============================================================
 *
 * MODO DE USO:
 *   - Acceder desde el navegador o CLI para ejecutar el DDL.
 *   - REQUIERE: que el usuario autenticado sea admin.
 *   - Ejecutar UNA SOLA VEZ en producción y luego eliminar
 *     este archivo del servidor por seguridad.
 *
 * SEGURIDAD: Añade un secret token para evitar ejecución accidental.
 * Llama a: /api/unicidad_email_telefono_DDL.php?token=TU_TOKEN_SECRETO
 * ============================================================
 */

// ── PROTECCIÓN: token secreto de ejecución única ─────────────
define('EXEC_TOKEN', 'rutas2026_ddl_unicidad_abc123'); // Cámbialo antes de subir
if (!isset($_GET['token']) || $_GET['token'] !== EXEC_TOKEN) {
    http_response_code(403);
    die(json_encode(['error' => 'Acceso denegado. Token requerido.']));
}

require_once 'config.php';

header('Content-Type: text/plain; charset=utf-8');

$pdo = getDBConnection();
$resultados = [];

// ── Función helper para ejecutar y registrar ─────────────────
function ejecutarSQL(PDO $pdo, string $descripcion, string $sql): void {
    global $resultados;
    try {
        $pdo->exec($sql);
        $resultados[] = "✅ OK: $descripcion";
        echo "✅ OK: $descripcion\n";
    } catch (PDOException $e) {
        $resultados[] = "❌ ERROR en '$descripcion': " . $e->getMessage();
        echo "❌ ERROR en '$descripcion': " . $e->getMessage() . "\n";
    }
}

echo "============================================================\n";
echo "DDL: Unicidad de Email y Teléfono - rutasrurales.io\n";
echo "Ejecutado: " . date('Y-m-d H:i:s') . "\n";
echo "============================================================\n\n";

// ── DIAGNÓSTICO PREVIO ────────────────────────────────────────
echo "--- DIAGNÓSTICO PREVIO ---\n\n";

// Emails duplicados (case-insensitive)
$stmtDupEmail = $pdo->query("
    SELECT LOWER(email) AS email_norm, COUNT(*) AS total
    FROM users
    GROUP BY LOWER(email)
    HAVING total > 1
");
$dupEmails = $stmtDupEmail->fetchAll(PDO::FETCH_ASSOC);

if (empty($dupEmails)) {
    echo "✅ No hay emails duplicados en la base de datos.\n\n";
} else {
    echo "⚠️  Se encontraron " . count($dupEmails) . " email(s) duplicado(s):\n";
    foreach ($dupEmails as $dup) {
        echo "   - {$dup['email_norm']} (aparece {$dup['total']} veces)\n";
    }
    echo "\n⚠️  ACCIÓN REQUERIDA: Revisar duplicados antes de aplicar el UNIQUE.\n";
    echo "   El script intentará neutralizarlos automáticamente (suspendiendo el registro más antiguo).\n\n";
}

// Teléfonos duplicados (no nulos)
$stmtDupPhone = $pdo->query("
    SELECT phone, COUNT(*) AS total
    FROM users
    WHERE phone IS NOT NULL AND TRIM(phone) <> ''
    GROUP BY phone
    HAVING total > 1
");
$dupPhones = $stmtDupPhone->fetchAll(PDO::FETCH_ASSOC);

if (empty($dupPhones)) {
    echo "✅ No hay teléfonos duplicados en la base de datos.\n\n";
} else {
    echo "⚠️  Se encontraron " . count($dupPhones) . " teléfono(s) duplicado(s):\n";
    foreach ($dupPhones as $dup) {
        echo "   - {$dup['phone']} (aparece {$dup['total']} veces)\n";
    }
    echo "\n";
}

echo "--- APLICANDO CAMBIOS ---\n\n";

// ── PASO 1: Normalizar emails a minúsculas ────────────────────
ejecutarSQL($pdo,
    'Normalizar emails existentes a minúsculas',
    "UPDATE users SET email = LOWER(TRIM(email)) WHERE email <> LOWER(TRIM(email))"
);

// ── PASO 2: Convertir teléfonos vacíos a NULL ─────────────────
ejecutarSQL($pdo,
    'Convertir teléfonos vacíos en NULL',
    "UPDATE users SET phone = NULL WHERE phone IS NOT NULL AND TRIM(phone) = ''"
);

// ── PASO 3: Normalizar teléfonos (eliminar separadores) ───────
ejecutarSQL($pdo,
    'Normalizar formato de teléfonos existentes',
    "UPDATE users
     SET phone = REGEXP_REPLACE(TRIM(phone), '[\\\\s\\\\-\\.\\\\(\\\\)]', '')
     WHERE phone IS NOT NULL AND TRIM(phone) <> ''"
);

// ── PASO 4: Neutralizar emails duplicados ─────────────────────
if (!empty($dupEmails)) {
    ejecutarSQL($pdo,
        'Neutralizar registros duplicados de email (marca como suspendido el más antiguo)',
        "UPDATE users u
         JOIN (
             SELECT LOWER(email) AS email_norm, MAX(id) AS id_a_mantener
             FROM users
             GROUP BY LOWER(email)
             HAVING COUNT(*) > 1
         ) dup ON LOWER(u.email) = dup.email_norm AND u.id <> dup.id_a_mantener
         SET u.email = CONCAT('duplicado_', u.id, '@revisar.local'),
             u.status = 'suspended'"
    );
}

// ── PASO 5: Neutralizar teléfonos duplicados ──────────────────
if (!empty($dupPhones)) {
    ejecutarSQL($pdo,
        'Eliminar duplicados de teléfono (pone NULL en el registro más antiguo)',
        "UPDATE users u
         JOIN (
             SELECT phone, MAX(id) AS id_a_mantener
             FROM users
             WHERE phone IS NOT NULL AND phone <> ''
             GROUP BY phone
             HAVING COUNT(*) > 1
         ) dup ON u.phone = dup.phone AND u.id <> dup.id_a_mantener
         SET u.phone = NULL"
    );
}

// ── PASO 6: Ajustar columna phone ────────────────────────────
ejecutarSQL($pdo,
    'Ajustar tipo de columna phone a VARCHAR(20) NULL',
    "ALTER TABLE users
     MODIFY COLUMN phone VARCHAR(20) NULL DEFAULT NULL
     COMMENT 'Teléfono normalizado: solo dígitos con prefijo + (ej: +34605249696)'"
);

// ── PASO 7: Añadir UNIQUE INDEX en email (seguro) ─────────────
$emailIdxExiste = $pdo->query("
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND index_name = 'uq_users_email'
")->fetchColumn();

if ($emailIdxExiste) {
    echo "ℹ️  SKIP: UNIQUE INDEX uq_users_email ya existe en users.email\n";
} else {
    ejecutarSQL($pdo,
        'Añadir UNIQUE INDEX uq_users_email en users.email',
        "ALTER TABLE users ADD UNIQUE INDEX uq_users_email (email)"
    );
}

// ── PASO 8: Añadir UNIQUE INDEX en phone (NULL-safe) ──────────
// En MySQL/MariaDB, NULL no es igual a NULL en índices UNIQUE,
// así que múltiples NULL conviven sin conflicto. Solo los valores
// NO NULOS idénticos provocarán SQLSTATE 23000.
$phoneIdxExiste = $pdo->query("
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND index_name = 'uq_users_phone'
")->fetchColumn();

if ($phoneIdxExiste) {
    echo "ℹ️  SKIP: UNIQUE INDEX uq_users_phone ya existe en users.phone\n";
} else {
    ejecutarSQL($pdo,
        'Añadir UNIQUE INDEX uq_users_phone en users.phone (NULL permitido)',
        "ALTER TABLE users ADD UNIQUE INDEX uq_users_phone (phone)"
    );
}

// ── VERIFICACIÓN FINAL ────────────────────────────────────────
echo "\n--- VERIFICACIÓN FINAL DE ÍNDICES ---\n\n";

$indices = $pdo->query("
    SELECT INDEX_NAME, COLUMN_NAME, NON_UNIQUE, NULLABLE
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND INDEX_NAME IN ('uq_users_email', 'uq_users_phone', 'PRIMARY')
    ORDER BY INDEX_NAME
")->fetchAll(PDO::FETCH_ASSOC);

echo sprintf("%-25s %-15s %-12s %-10s\n", 'INDEX_NAME', 'COLUMN_NAME', 'NON_UNIQUE', 'NULLABLE');
echo str_repeat('-', 65) . "\n";
foreach ($indices as $idx) {
    $unique = $idx['NON_UNIQUE'] == 0 ? '✅ UNIQUE' : '  normal';
    echo sprintf("%-25s %-15s %-12s %-10s\n",
        $idx['INDEX_NAME'],
        $idx['COLUMN_NAME'],
        $unique,
        $idx['NULLABLE']
    );
}

echo "\n============================================================\n";
echo "RESULTADO: NON_UNIQUE=0 → índice UNIQUE activo\n";
echo "           NULLABLE=YES en phone → permite múltiples NULL\n";
echo "============================================================\n";
echo "\n⚠️  IMPORTANTE: Elimina este archivo del servidor tras ejecutarlo.\n";

/*
============================================================
SQL PURO (para ejecutar directamente en phpMyAdmin o CLI):
============================================================

-- 1. Normalizar emails
UPDATE users SET email = LOWER(TRIM(email)) WHERE email <> LOWER(TRIM(email));

-- 2. Limpiar teléfonos vacíos
UPDATE users SET phone = NULL WHERE phone IS NOT NULL AND TRIM(phone) = '';

-- 3. Normalizar teléfonos
UPDATE users
SET phone = REGEXP_REPLACE(TRIM(phone), '[\\s\\-\\.\\(\\)]', '')
WHERE phone IS NOT NULL AND TRIM(phone) <> '';

-- 4. Ajustar columna phone
ALTER TABLE users
    MODIFY COLUMN phone VARCHAR(20) NULL DEFAULT NULL
    COMMENT 'Teléfono normalizado: solo dígitos con prefijo + si aplica';

-- 5. UNIQUE en email (si no existe)
ALTER TABLE users ADD UNIQUE INDEX uq_users_email (email);

-- 6. UNIQUE en phone (NULL-safe: múltiples NULL no conflictan)
ALTER TABLE users ADD UNIQUE INDEX uq_users_phone (phone);

-- 7. Verificación
SELECT INDEX_NAME, COLUMN_NAME, NON_UNIQUE, NULLABLE
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'users'
  AND INDEX_NAME IN ('uq_users_email', 'uq_users_phone', 'PRIMARY')
ORDER BY INDEX_NAME;

============================================================
*/
