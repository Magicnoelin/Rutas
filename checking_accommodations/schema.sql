-- =============================================================================
-- SISTEMA DE CHECK-IN PARA ALOJAMIENTOS RURALES
-- Real Decreto 933/2021 — Portal SES.MIR (Ministerio del Interior de España)
-- =============================================================================
-- Archivo: schema.sql
-- Descripción: Estructura de la base de datos con aislamiento multi-inquilino.
--              Cada alojamiento solo puede acceder a sus propios registros.
-- Versión: 1.0 | Fecha: 2026
-- =============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+02:00";
SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- Base de datos (ajusta el nombre según tu entorno)
-- -----------------------------------------------------------------------------
-- CREATE DATABASE IF NOT EXISTS `checkin_db`
--   CHARACTER SET utf8mb4
--   COLLATE utf8mb4_unicode_ci;
-- USE `checkin_db`;

-- =============================================================================
-- TABLA: alojamientos
-- Almacena los alojamientos rurales que utilizan el sistema de check-in.
-- Cada alojamiento tiene un token público único para su formulario de check-in.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `alojamientos` (
    `id`             INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `nombre`         VARCHAR(150)    NOT NULL COMMENT 'Nombre comercial del alojamiento',
    `token_publico`  CHAR(64)        NOT NULL COMMENT 'Token único para el formulario público (hex de 32 bytes)',
    `email`          VARCHAR(180)    NOT NULL COMMENT 'Email de acceso del administrador',
    `password_hash`  VARCHAR(255)    NOT NULL COMMENT 'Hash bcrypt de la contraseña (password_hash PHP)',
    `activo`         TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '1=activo, 0=desactivado',
    `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_token_publico` (`token_publico`),
    UNIQUE KEY `uq_email`         (`email`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Alojamientos rurales registrados en el sistema de check-in';


-- =============================================================================
-- TABLA: huespedes_registro
-- Almacena los registros de huéspedes según los campos exigidos por el
-- Real Decreto 933/2021 para el portal de Hospedajes SES.MIR.
--
-- SEGURIDAD MULTI-TENANT:
--   - La columna `alojamiento_id` es SIEMPRE obligatoria.
--   - FK con ON DELETE CASCADE: si se elimina un alojamiento, sus huéspedes
--     también se eliminan (cumplimiento RGPD / derecho al olvido del operador).
--   - Las consultas de aplicación SIEMPRE deben filtrar por alojamiento_id.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `huespedes_registro` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,

    -- -------------------------------------------------------------------------
    -- CLAVE FORÁNEA — Aislamiento multi-tenant
    -- -------------------------------------------------------------------------
    `alojamiento_id`        INT UNSIGNED    NOT NULL COMMENT 'FK al alojamiento propietario del registro',

    -- -------------------------------------------------------------------------
    -- DATOS PERSONALES (RD 933/2021 — Artículo 2)
    -- -------------------------------------------------------------------------
    `nombre`                VARCHAR(100)    NOT NULL,
    `apellidos`             VARCHAR(150)    NOT NULL,
    `sexo`                  ENUM('H','M','X') NOT NULL COMMENT 'H=Hombre, M=Mujer, X=No binario/Otro',
    `fecha_nacimiento`      DATE            NOT NULL,
    `nacionalidad`          VARCHAR(80)     NOT NULL,

    -- -------------------------------------------------------------------------
    -- DOCUMENTO DE IDENTIDAD (RD 933/2021)
    -- -------------------------------------------------------------------------
    `tipo_documento`        ENUM('DNI','NIE','Pasaporte','Otro') NOT NULL,
    `numero_documento`      VARCHAR(30)     NOT NULL COMMENT 'Número del documento de identidad',
    `fecha_expedicion_doc`  DATE            NOT NULL COMMENT 'Fecha de expedición del documento',
    `numero_soporte`        VARCHAR(15)     NULL     COMMENT 'Obligatorio si tipo_documento=DNI. Formato: 3 letras + 6 números (ej: ABC123456)',

    -- -------------------------------------------------------------------------
    -- DATOS DE CONTACTO
    -- -------------------------------------------------------------------------
    `telefono`              VARCHAR(20)     NOT NULL COMMENT 'Teléfono móvil de contacto',
    `email`                 VARCHAR(180)    NOT NULL COMMENT 'Correo electrónico del huésped',

    -- -------------------------------------------------------------------------
    -- DIRECCIÓN COMPLETA
    -- -------------------------------------------------------------------------
    `direccion_calle`       VARCHAR(200)    NOT NULL COMMENT 'Nombre de la calle o vía',
    `direccion_numero`      VARCHAR(20)     NOT NULL COMMENT 'Número, piso, letra, etc.',
    `provincia`             VARCHAR(100)    NOT NULL,
    `codigo_postal`         VARCHAR(10)     NOT NULL,
    `pais`                  VARCHAR(80)     NOT NULL DEFAULT 'España',

    -- -------------------------------------------------------------------------
    -- DATOS DE ESTANCIA
    -- -------------------------------------------------------------------------
    `fecha_entrada`         DATE            NOT NULL COMMENT 'Fecha de check-in previsto',
    `fecha_salida_prevista` DATE            NOT NULL COMMENT 'Fecha de check-out previsto',

    -- -------------------------------------------------------------------------
    -- METADATOS DE REGISTRO
    -- -------------------------------------------------------------------------
    `ip_registro`           VARCHAR(45)     NULL     COMMENT 'IP del huésped al enviar el formulario (IPv4/IPv6)',
    `created_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha/hora de envío del formulario',

    PRIMARY KEY (`id`),

    -- Índice compuesto para las consultas más frecuentes del panel
    INDEX `idx_alojamiento_fecha` (`alojamiento_id`, `created_at` DESC),

    -- Índice para búsquedas por documento (dentro del contexto del alojamiento)
    INDEX `idx_alojamiento_documento` (`alojamiento_id`, `numero_documento`),

    -- Clave foránea — aislamiento garantizado a nivel de base de datos
    CONSTRAINT `fk_huesped_alojamiento`
        FOREIGN KEY (`alojamiento_id`)
        REFERENCES `alojamientos` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Registros de huéspedes según RD 933/2021 — SES.MIR';


-- =============================================================================
-- DATOS DE EJEMPLO (comentar/eliminar en producción)
-- =============================================================================
-- Genera un token seguro en PHP con: bin2hex(random_bytes(32))
-- Genera un hash de contraseña en PHP con: password_hash('tu_password', PASSWORD_BCRYPT)
--
-- INSERT INTO `alojamientos` (`nombre`, `token_publico`, `email`, `password_hash`) VALUES
-- (
--     'Casa Rural El Roble',
--     'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2',
--     'admin@casaruralroble.es',
--     '$2y$12$...'  -- Reemplazar con hash real generado por PHP
-- );
-- =============================================================================
