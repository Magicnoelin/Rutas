-- =============================================================================
-- SISTEMA DE CHECK-IN — Migración sobre BD existente del proyecto
-- =============================================================================
-- Base de datos : u412199647_Rutas  (la BD principal del proyecto)
-- Tabla existente: accommodations  (ya existe, le añadimos columnas)
-- Tabla nueva/corregida: huespedes_registro
--
-- EJECUTAR EN phpMyAdmin seleccionando la BD u412199647_Rutas
-- =============================================================================

-- 1. AÑADIR COLUMNAS A LA TABLA EXISTENTE 'accommodations'
--    (IF NOT EXISTS evita errores si ya se ejecutó antes)
-- -----------------------------------------------------------------------------

ALTER TABLE `accommodations`
    ADD COLUMN IF NOT EXISTS `token_publico`  VARCHAR(64)  NULL UNIQUE
        COMMENT 'Token público único para el enlace de check-in del huésped',
    ADD COLUMN IF NOT EXISTS `password_hash`  VARCHAR(255) NULL
        COMMENT 'Hash bcrypt de la contraseña del administrador del alojamiento';

-- Índice para búsqueda rápida por token (IF NOT EXISTS disponible en MariaDB 10.1+)
CREATE INDEX IF NOT EXISTS `idx_token_publico`
    ON `accommodations` (`token_publico`);

-- =============================================================================
-- 2. CORREGIR LA FK DE huespedes_registro SI YA EXISTE
--    (el error 1452 indica que la FK apunta a 'alojamientos' en vez de 'accommodations')
-- =============================================================================

-- Eliminar la FK antigua (si existe) — ignorar error si no existe
SET @exist_fk = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME        = 'huespedes_registro'
      AND CONSTRAINT_NAME   = 'fk_huesped_alojamiento'
      AND CONSTRAINT_TYPE   = 'FOREIGN KEY'
);

SET @sql_drop = IF(@exist_fk > 0,
    'ALTER TABLE huespedes_registro DROP FOREIGN KEY fk_huesped_alojamiento',
    'SELECT 1'
);
PREPARE stmt FROM @sql_drop;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- También intentar eliminar el índice que deja la FK antigua
ALTER TABLE `huespedes_registro` DROP INDEX IF EXISTS `fk_huesped_alojamiento`;

-- =============================================================================
-- 3. CREAR TABLA HUESPEDES_REGISTRO (si no existe)
--    FK → accommodations.id
-- =============================================================================
CREATE TABLE IF NOT EXISTS `huespedes_registro` (

    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `alojamiento_id`        INT             NOT NULL,

    -- Campos RD 933/2021 / SES.MIR
    `nombre`                VARCHAR(100)    NOT NULL,
    `apellidos`             VARCHAR(150)    NOT NULL,
    `sexo`                  ENUM('H','M','X') NOT NULL,
    `fecha_nacimiento`      DATE            NOT NULL,
    `nacionalidad`          VARCHAR(80)     NOT NULL,
    `tipo_documento`        ENUM('DNI','NIE','Pasaporte','Otro') NOT NULL,
    `numero_documento`      VARCHAR(30)     NOT NULL,
    `fecha_expedicion_doc`  DATE            NOT NULL,
    `numero_soporte`        VARCHAR(9)      NULL,
    `telefono`              VARCHAR(20)     NOT NULL,
    `email`                 VARCHAR(180)    NOT NULL,
    `direccion_calle`       VARCHAR(200)    NOT NULL,
    `direccion_numero`      VARCHAR(20)     NOT NULL,
    `provincia`             VARCHAR(100)    NOT NULL,
    `codigo_postal`         VARCHAR(10)     NOT NULL,
    `pais`                  VARCHAR(80)     NOT NULL DEFAULT 'España',
    `fecha_entrada`         DATE            NOT NULL,
    `fecha_salida_prevista` DATE            NOT NULL,
    `ip_registro`           VARCHAR(45)     NULL,
    `created_at`            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    INDEX `idx_alojamiento_fecha`  (`alojamiento_id`, `created_at`),
    INDEX `idx_estancia`           (`alojamiento_id`, `fecha_entrada`, `fecha_salida_prevista`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Registros de huéspedes — RD 933/2021';

-- =============================================================================
-- 4. AÑADIR LA FK CORRECTA → accommodations.id
--    (si ya existe la correcta, ignorar el error)
-- =============================================================================
SET @exist_fk2 = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME        = 'huespedes_registro'
      AND CONSTRAINT_NAME   = 'fk_huesped_accommodation'
      AND CONSTRAINT_TYPE   = 'FOREIGN KEY'
);

SET @sql_add = IF(@exist_fk2 = 0,
    'ALTER TABLE huespedes_registro ADD CONSTRAINT fk_huesped_accommodation FOREIGN KEY (alojamiento_id) REFERENCES accommodations(id) ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt2 FROM @sql_add;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;
