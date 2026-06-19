-- =============================================================================
-- SISTEMA DE CHECK-IN — Migración sobre BD existente del proyecto
-- =============================================================================
-- Base de datos : u412199647_Rutas  (la BD principal del proyecto)
-- Tabla existente: accommodations  (ya existe, le añadimos columnas)
-- Tabla nueva   : huespedes_registro
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

-- Índice para búsqueda rápida por token
CREATE INDEX IF NOT EXISTS `idx_token_publico`
    ON `accommodations` (`token_publico`);

-- =============================================================================
-- 2. TABLA DE HUÉSPEDES REGISTRADOS
--    FK → accommodations.id
-- =============================================================================
CREATE TABLE IF NOT EXISTS `huespedes_registro` (

    -- Clave primaria
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,

    -- Multi-tenant: FK al alojamiento de la tabla accommodations existente
    `alojamiento_id`        INT             NOT NULL,

    -- -------------------------------------------------------------------------
    -- CAMPOS OBLIGATORIOS SEGÚN REAL DECRETO 933/2021 / SES.MIR
    -- -------------------------------------------------------------------------
    `nombre`                VARCHAR(100)    NOT NULL,
    `apellidos`             VARCHAR(150)    NOT NULL,
    `sexo`                  ENUM('H','M','X') NOT NULL,
    `fecha_nacimiento`      DATE            NOT NULL,
    `nacionalidad`          VARCHAR(80)     NOT NULL,

    -- Documento de identidad
    `tipo_documento`        ENUM('DNI','NIE','Pasaporte','Otro') NOT NULL,
    `numero_documento`      VARCHAR(30)     NOT NULL,
    `fecha_expedicion_doc`  DATE            NOT NULL,
    `numero_soporte`        VARCHAR(9)      NULL
        COMMENT 'Obligatorio si tipo_documento = DNI (3 letras + 6 números)',

    -- Contacto
    `telefono`              VARCHAR(20)     NOT NULL,
    `email`                 VARCHAR(180)    NOT NULL,

    -- Dirección completa
    `direccion_calle`       VARCHAR(200)    NOT NULL,
    `direccion_numero`      VARCHAR(20)     NOT NULL,
    `provincia`             VARCHAR(100)    NOT NULL,
    `codigo_postal`         VARCHAR(10)     NOT NULL,
    `pais`                  VARCHAR(80)     NOT NULL DEFAULT 'España',

    -- Fechas de estancia
    `fecha_entrada`         DATE            NOT NULL,
    `fecha_salida_prevista` DATE            NOT NULL,

    -- Metadatos de auditoría
    `ip_registro`           VARCHAR(45)     NULL
        COMMENT 'IP del dispositivo al enviar el formulario (IPv4/IPv6)',
    `created_at`            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    -- Clave foránea hacia la tabla existente
    CONSTRAINT `fk_huesped_accommodation`
        FOREIGN KEY (`alojamiento_id`)
        REFERENCES `accommodations` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    -- Índices para consultas del panel (filtrado por alojamiento)
    INDEX `idx_alojamiento_fecha`  (`alojamiento_id`, `created_at`),
    INDEX `idx_estancia`           (`alojamiento_id`, `fecha_entrada`, `fecha_salida_prevista`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Registros de huéspedes — RD 933/2021. FK → accommodations';
