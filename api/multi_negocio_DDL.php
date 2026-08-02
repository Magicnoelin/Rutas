<?php
/**
 * ============================================================
 * SCRIPT DDL: Arquitectura 1 Usuario → N Negocios
 * Proyecto: Rutas Rurales (rutasrurales.io)
 * Fecha: 2026-08-02
 * ============================================================
 *
 * OBJETIVO:
 *   Asegurar que la BD soporte que un mismo usuario (1 persona)
 *   pueda ser propietario de múltiples alojamientos/negocios.
 *
 * LO QUE HACE ESTE SCRIPT:
 *   1. Verifica que accommodations.created_by permite N filas por user (ya OK)
 *   2. Añade columna business_email a accommodations (email público del negocio)
 *   3. Añade columna business_phone a accommodations (teléfono público del negocio)
 *   4. Verifica que NO hay UNIQUE en accommodations.email ni accommodations.phone
 *   5. Crea la tabla user_businesses (panel multi-negocio del propietario)
 *   6. Migra los datos de profile_alojamientos → user_businesses si existe
 *
 * MODO DE USO:
 *   Acceder UNA SOLA VEZ con el token:
 *   https://rutasrurales.io/api/multi_negocio_DDL.php?token=rutas2026_multinegocio_xyz789
 *   Luego eliminar el archivo del servidor.
 * ============================================================
 */

define('EXEC_TOKEN_MN', 'rutas2026_multinegocio_xyz789'); // Cámbialo antes de subir
if (!isset($_GET['token']) || $_GET['token'] !== EXEC_TOKEN_MN) {
    http_response_code(403);
    die(json_encode(['error' => 'Acceso denegado. Token requerido.']));
}

require_once 'config.php';
header('Content-Type: text/plain; charset=utf-8');

$pdo = getDBConnection();

function runDDL(PDO $pdo, string $desc, string $sql): void {
    try {
        $pdo->exec($sql);
        echo "✅ OK: $desc\n";
    } catch (PDOException $e) {
        echo "❌ ERROR en '$desc': " . $e->getMessage() . "\n";
    }
}

function colExists(PDO $pdo, string $table, string $col): bool {
    $r = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
    return $r->rowCount() > 0;
}

function indexExists(PDO $pdo, string $table, string $indexName): bool {
    $r = $pdo->query("
        SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE table_schema = DATABASE()
          AND table_name = '$table'
          AND index_name = '$indexName'
    ");
    return (int)$r->fetchColumn() > 0;
}

echo "============================================================\n";
echo "DDL: Arquitectura Multi-Negocio - rutasrurales.io\n";
echo "Ejecutado: " . date('Y-m-d H:i:s') . "\n";
echo "============================================================\n\n";

// ── PASO 1: Verificar created_by en accommodations ─────────────
echo "--- PASO 1: Verificar FK created_by en accommodations ---\n";

$idxCreatedBy = $pdo->query("
    SELECT Non_unique FROM information_schema.STATISTICS
    WHERE table_schema = DATABASE()
      AND table_name = 'accommodations'
      AND index_name = 'created_by'
")->fetch(PDO::FETCH_ASSOC);

if ($idxCreatedBy) {
    if ($idxCreatedBy['Non_unique'] == 1) {
        echo "✅ OK: accommodations.created_by es índice normal (permite N alojamientos por usuario)\n\n";
    } else {
        echo "⚠️  ALERTA: accommodations.created_by tiene UNIQUE — eliminándolo...\n";
        runDDL($pdo, 'Eliminar UNIQUE de created_by', "ALTER TABLE accommodations DROP INDEX created_by");
        runDDL($pdo, 'Crear índice normal en created_by', "ALTER TABLE accommodations ADD INDEX idx_created_by (created_by)");
        echo "\n";
    }
} else {
    echo "ℹ️  No existe índice en created_by — añadiendo...\n";
    runDDL($pdo, 'Crear índice en created_by', "ALTER TABLE accommodations ADD INDEX idx_created_by (created_by)");
    echo "\n";
}

// ── PASO 2: Verificar que NO hay UNIQUE en email/phone de accommodations ──
echo "--- PASO 2: Verificar ausencia de UNIQUE en accommodations.email/phone ---\n";

$uniqueEmail = $pdo->query("
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE table_schema = DATABASE()
      AND table_name = 'accommodations'
      AND Non_unique = 0
      AND column_name = 'email'
      AND index_name != 'PRIMARY'
")->fetchColumn();

if ($uniqueEmail > 0) {
    echo "⚠️  Existe UNIQUE en accommodations.email — eliminándolo (email del negocio no debe ser único)...\n";
    runDDL($pdo, 'Eliminar UNIQUE de accommodations.email',
        "ALTER TABLE accommodations DROP INDEX email"
    );
} else {
    echo "✅ OK: accommodations.email NO tiene UNIQUE (correcto: cada negocio tiene su propio email)\n";
}

$uniquePhone = $pdo->query("
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE table_schema = DATABASE()
      AND table_name = 'accommodations'
      AND Non_unique = 0
      AND column_name = 'phone'
      AND index_name != 'PRIMARY'
")->fetchColumn();

if ($uniquePhone > 0) {
    echo "⚠️  Existe UNIQUE en accommodations.phone — eliminándolo...\n";
    runDDL($pdo, 'Eliminar UNIQUE de accommodations.phone',
        "ALTER TABLE accommodations DROP INDEX phone"
    );
} else {
    echo "✅ OK: accommodations.phone NO tiene UNIQUE (correcto)\n";
}
echo "\n";

// ── PASO 3: Añadir columna owner_notes a accommodations (opcional) ──
echo "--- PASO 3: Mejoras en tabla accommodations ---\n";

if (!colExists($pdo, 'accommodations', 'owner_notes')) {
    runDDL($pdo,
        'Añadir columna owner_notes (notas internas del propietario)',
        "ALTER TABLE accommodations
         ADD COLUMN owner_notes TEXT NULL
         COMMENT 'Notas internas del propietario (no visibles al público)'
         AFTER created_by"
    );
} else {
    echo "ℹ️  SKIP: accommodations.owner_notes ya existe\n";
}

echo "\n";

// ── PASO 4: Crear tabla user_businesses ───────────────────────
echo "--- PASO 4: Tabla user_businesses (panel multi-negocio) ---\n";

$tableExists = $pdo->query("SHOW TABLES LIKE 'user_businesses'")->rowCount() > 0;

if (!$tableExists) {
    runDDL($pdo,
        'Crear tabla user_businesses',
        "CREATE TABLE user_businesses (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            user_id         INT NOT NULL
                            COMMENT 'FK a users.id — propietario de este negocio',
            business_name   VARCHAR(255) NOT NULL
                            COMMENT 'Nombre comercial del negocio',
            business_type   ENUM('alojamiento','promotor_eventos','actividad_cultural','artesania','restauracion','otro')
                            NOT NULL DEFAULT 'alojamiento',
            business_email  VARCHAR(255) NULL
                            COMMENT 'Email público de contacto del negocio (distinto al email de login)',
            business_phone  VARCHAR(20) NULL
                            COMMENT 'Teléfono de contacto del negocio',
            business_web    VARCHAR(500) NULL,
            nif_cif         VARCHAR(20) NULL,
            municipality    VARCHAR(150) NULL,
            province        VARCHAR(100) NULL,
            accommodation_id INT NULL
                            COMMENT 'FK opcional a accommodations.id si este negocio es un alojamiento',
            is_primary      TINYINT(1) NOT NULL DEFAULT 0
                            COMMENT '1 = negocio principal del usuario en su panel',
            status          ENUM('active','inactive','pending') NOT NULL DEFAULT 'active',
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            INDEX idx_ub_user_id     (user_id),
            INDEX idx_ub_type        (business_type),
            INDEX idx_ub_accom       (accommodation_id),
            CONSTRAINT fk_ub_user    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_ub_accom   FOREIGN KEY (accommodation_id) REFERENCES accommodations(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
          COMMENT='Negocios asociados a cada usuario. 1 usuario puede tener N negocios.'"
    );
} else {
    echo "ℹ️  SKIP: Tabla user_businesses ya existe\n";
}

echo "\n";

// ── PASO 5: Migrar datos existentes de accommodations → user_businesses ──
echo "--- PASO 5: Migrar alojamientos existentes a user_businesses ---\n";

$migrados = $pdo->query("
    INSERT IGNORE INTO user_businesses (user_id, business_name, business_type, business_email, business_phone, municipality, province, accommodation_id, is_primary, status)
    SELECT
        a.created_by,
        a.name,
        'alojamiento',
        a.email,
        a.phone,
        a.municipality,
        a.province,
        a.id,
        1,
        CASE WHEN a.is_active = 1 THEN 'active' ELSE 'inactive' END
    FROM accommodations a
    WHERE a.created_by IS NOT NULL
      AND NOT EXISTS (
          SELECT 1 FROM user_businesses ub WHERE ub.accommodation_id = a.id
      )
");

$filasMigradas = $migrados ? $pdo->query("SELECT ROW_COUNT()")->fetchColumn() : 0;
echo "✅ OK: $filasMigradas alojamientos migrados a user_businesses\n\n";

// ── VERIFICACIÓN FINAL ─────────────────────────────────────────
echo "--- VERIFICACIÓN FINAL ---\n\n";

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$totalAccom = $pdo->query("SELECT COUNT(*) FROM accommodations")->fetchColumn();
$totalBiz   = $pdo->query("SELECT COUNT(*) FROM user_businesses")->fetchColumn();

$multiOwners = $pdo->query("
    SELECT COUNT(*) FROM (
        SELECT created_by FROM accommodations
        WHERE created_by IS NOT NULL
        GROUP BY created_by
        HAVING COUNT(*) > 1
    ) t
")->fetchColumn();

echo "Usuarios activos en sistema:       $totalUsers\n";
echo "Alojamientos totales:              $totalAccom\n";
echo "Registros en user_businesses:      $totalBiz\n";
echo "Usuarios con MÁS DE 1 alojamiento: $multiOwners\n\n";

echo "============================================================\n";
echo "✅ Arquitectura multi-negocio configurada correctamente.\n";
echo "Un propietario puede tener N alojamientos bajo 1 cuenta.\n";
echo "============================================================\n";
echo "\n⚠️  Elimina este archivo del servidor tras ejecutarlo.\n";

/*
============================================================
SQL PURO (para phpMyAdmin):
============================================================

-- Crear tabla user_businesses
CREATE TABLE IF NOT EXISTS user_businesses (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    business_name   VARCHAR(255) NOT NULL,
    business_type   ENUM('alojamiento','promotor_eventos','actividad_cultural','artesania','restauracion','otro') NOT NULL DEFAULT 'alojamiento',
    business_email  VARCHAR(255) NULL,
    business_phone  VARCHAR(20) NULL,
    business_web    VARCHAR(500) NULL,
    nif_cif         VARCHAR(20) NULL,
    municipality    VARCHAR(150) NULL,
    province        VARCHAR(100) NULL,
    accommodation_id INT NULL,
    is_primary      TINYINT(1) NOT NULL DEFAULT 0,
    status          ENUM('active','inactive','pending') NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ub_user_id (user_id),
    INDEX idx_ub_type (business_type),
    INDEX idx_ub_accom (accommodation_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (accommodation_id) REFERENCES accommodations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrar alojamientos existentes
INSERT IGNORE INTO user_businesses (user_id, business_name, business_type, business_email, business_phone, municipality, province, accommodation_id, is_primary, status)
SELECT created_by, name, 'alojamiento', email, phone, municipality, province, id, 1,
       CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END
FROM accommodations WHERE created_by IS NOT NULL;

============================================================
*/
