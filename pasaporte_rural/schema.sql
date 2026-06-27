-- =============================================================================
-- PASAPORTE RURAL by rutasrurales.io — Migración de Base de Datos
-- =============================================================================
-- Base de datos : u412199647_Rutas
-- Tablas nuevas : pasaporte_turistas, qr_temporales, historico_sellos
--
-- EJECUCIÓN: Importar en phpMyAdmin seleccionando la BD u412199647_Rutas
--            O ejecutar con: mysql -u usuario -p u412199647_Rutas < schema.sql
--
-- DEPENDENCIAS:
--   • Tabla 'users'          (ya existe en el proyecto principal)
--   • Tabla 'accommodations' (ya existe — necesita columna is_premium)
-- =============================================================================

-- ─────────────────────────────────────────────────────────────────────────────
-- 0. PREREQUISITOS: Asegurar columna is_premium en accommodations
--    (Seguramente ya existe, el IF NOT EXISTS protege contra doble ejecución)
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE `accommodations`
    ADD COLUMN IF NOT EXISTS `is_premium` TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = alojamiento Premium con acceso al Pasaporte Rural';

-- ─────────────────────────────────────────────────────────────────────────────
-- 1. TABLA: pasaporte_turistas
--    Registro maestro de cada carnet digital.
--    Cada turista registrado tiene exactamente UN pasaporte.
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `pasaporte_turistas` (

    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT
        COMMENT 'PK interna del pasaporte',

    `user_id`           INT             NOT NULL
        COMMENT 'FK → users.id del turista propietario del carnet',

    -- Token fijo del carnet (NO es el QR; es el identificador permanente del pasaporte)
    -- Permite generar URLs de perfil, históricos, etc.
    `token_fijo`        VARCHAR(64)     NOT NULL
        COMMENT 'UUID/hash permanente único del carnet — no expira nunca',

    -- ── Descuento y gamificación ──────────────────────────────────────────
    `descuento_actual`  TINYINT(3)      NOT NULL DEFAULT 5
        COMMENT 'Porcentaje de descuento aplicable: 5% base → 10% máximo',

    `puntos_totales`    INT UNSIGNED    NOT NULL DEFAULT 0
        COMMENT 'Suma histórica de todos los puntos acumulados',

    `puntos_periodo`    INT UNSIGNED    NOT NULL DEFAULT 0
        COMMENT 'Puntos del período activo (se puede resetear por temporada)',

    -- ── Nivel (gamificación escalable) ───────────────────────────────────
    -- Niveles actuales: Viajero (0-100), Explorador (101-300), Embajador (301+)
    -- Diseñado para añadir más niveles en el futuro sin romper la estructura
    `nivel`             ENUM(
                            'Viajero',      -- Nivel inicial (0-100 puntos)
                            'Explorador',   -- Nivel medio   (101-300 puntos)
                            'Embajador'     -- Nivel máximo  (301+ puntos)
                        ) NOT NULL DEFAULT 'Viajero'
        COMMENT 'Nivel de gamificación calculado en base a puntos_totales',

    -- ── Estado del pasaporte ─────────────────────────────────────────────
    `estado`            ENUM(
                            'activo',       -- En uso normal
                            'suspendido',   -- Suspendido temporalmente (admin)
                            'baneado'       -- Vetado por mal comportamiento
                        ) NOT NULL DEFAULT 'activo'
        COMMENT 'Estado operativo del pasaporte',

    -- ── Metadatos ────────────────────────────────────────────────────────
    `total_sellos`      INT UNSIGNED    NOT NULL DEFAULT 0
        COMMENT 'Contador denormalizado de sellos totales (performance)',

    `ultimo_sello_at`   TIMESTAMP       NULL DEFAULT NULL
        COMMENT 'Fecha del último sello recibido',

    `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,

    -- ── Constraints ──────────────────────────────────────────────────────
    PRIMARY KEY (`id`),
    UNIQUE KEY  `uk_user_id`      (`user_id`),       -- Un pasaporte por turista
    UNIQUE KEY  `uk_token_fijo`   (`token_fijo`),    -- Token global único
    INDEX       `idx_estado`      (`estado`),
    INDEX       `idx_nivel`       (`nivel`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Carnets digitales del Pasaporte Rural — registro maestro por turista';


-- ─────────────────────────────────────────────────────────────────────────────
-- 2. TABLA: qr_temporales
--    Tokens OTP de un solo uso que expiran en 60 segundos.
--    El QR que muestra el turista codifica la URL con este hash.
--    Seguridad: hash único criptográfico + expiración por timestamp + uso único.
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `qr_temporales` (

    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,

    `pasaporte_id`      INT UNSIGNED    NOT NULL
        COMMENT 'FK → pasaporte_turistas.id del turista que generó el token',

    -- Token de 96 caracteres hexadecimales (48 bytes de entropía criptográfica)
    -- Generado con bin2hex(random_bytes(48)) en PHP
    `hash_token`        VARCHAR(128)    NOT NULL
        COMMENT 'Hash OTP único — va embebido en la URL del QR',

    -- ── Ciclo de vida ────────────────────────────────────────────────────
    `estado`            ENUM(
                            'pendiente',    -- Generado, aún no escaneado
                            'usado',        -- Escaneado y sello completado
                            'expirado'      -- Marcado como expirado (limpieza)
                        ) NOT NULL DEFAULT 'pendiente'
        COMMENT 'Estado del token OTP',

    -- ── Auditoría ────────────────────────────────────────────────────────
    `ip_generacion`     VARCHAR(45)     NULL
        COMMENT 'IP desde la que el turista generó el QR (IPv4/IPv6)',

    `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
        COMMENT 'Momento exacto de creación — base para calcular los 60s de validez',

    `usado_at`          TIMESTAMP       NULL DEFAULT NULL
        COMMENT 'Momento en que fue escaneado/sellado (NULL = no usado aún)',

    -- ── Constraints ──────────────────────────────────────────────────────
    PRIMARY KEY (`id`),
    UNIQUE KEY  `uk_hash_token`       (`hash_token`),
    INDEX       `idx_pasaporte_estado` (`pasaporte_id`, `estado`),
    INDEX       `idx_created_at`       (`created_at`)   -- Para limpiar expirados

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Tokens OTP temporales para los QR dinámicos del Pasaporte Rural (TTL: 60s)';


-- ─────────────────────────────────────────────────────────────────────────────
-- 3. TABLA: historico_sellos
--    Registro completo e inmutable de cada "sello" dado por un alojamiento.
--    Contiene la puntuación recibida y el cálculo de puntos/descuento resultante.
--    Diseñada para soportar futuras funciones de marketing (rutas, badges, etc.)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `historico_sellos` (

    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,

    -- ── Quién recibe el sello (turista) ──────────────────────────────────
    `pasaporte_id`          INT UNSIGNED    NOT NULL
        COMMENT 'FK → pasaporte_turistas.id del turista sellado',

    -- ── Quién da el sello (alojamiento Premium) ───────────────────────────
    `alojamiento_id`        INT             NOT NULL
        COMMENT 'FK → accommodations.id del alojamiento que sella',

    `propietario_user_id`   INT             NOT NULL
        COMMENT 'FK → users.id del propietario/gestor que realizó el sello',

    -- ── Token QR utilizado para el sello ─────────────────────────────────
    `qr_token_id`           INT UNSIGNED    NOT NULL
        COMMENT 'FK → qr_temporales.id — el token exacto escaneado en este sello',

    -- ── Puntuaciones del propietario al turista ───────────────────────────
    `puntuacion_limpieza`   TINYINT(1)      NOT NULL
        COMMENT 'Valoración de limpieza: 1 (muy malo) a 5 (excelente)',

    `puntuacion_civismo`    TINYINT(1)      NOT NULL
        COMMENT 'Valoración de comportamiento/civismo: 1 a 5',

    -- ── Cálculo de puntos ────────────────────────────────────────────────
    `puntos_base`           TINYINT(2)      NOT NULL DEFAULT 0
        COMMENT 'Puntos base: suma de las dos puntuaciones (máx. 10)',

    `puntos_bonus`          TINYINT(2)      NOT NULL DEFAULT 0
        COMMENT 'Bonus por excelencia: +2 si ambas puntuaciones >= 4',

    `puntos_sumados`        TINYINT(2)      NOT NULL DEFAULT 0
        COMMENT 'Total sumado al pasaporte en este sello (base + bonus)',

    -- ── Snapshot de descuento antes/después ──────────────────────────────
    `descuento_previo`      TINYINT(3)      NOT NULL
        COMMENT 'Descuento del turista ANTES de este sello',

    `descuento_nuevo`       TINYINT(3)      NOT NULL
        COMMENT 'Descuento del turista DESPUÉS de este sello (igual o mayor)',

    `subio_nivel`           TINYINT(1)      NOT NULL DEFAULT 0
        COMMENT '1 si este sello provocó un cambio de nivel de gamificación',

    -- ── Información adicional ────────────────────────────────────────────
    `notas_propietario`     TEXT            NULL
        COMMENT 'Observaciones opcionales privadas del propietario sobre la estancia',

    -- ── Metadatos de trazabilidad ─────────────────────────────────────────
    `ip_sello`              VARCHAR(45)     NULL
        COMMENT 'IP desde la que el propietario realizó el sello',

    `created_at`            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
        COMMENT 'Fecha y hora exacta del sello',

    -- ── Constraints ──────────────────────────────────────────────────────
    PRIMARY KEY (`id`),
    UNIQUE KEY  `uk_qr_token_id`        (`qr_token_id`),      -- Un sello por token
    INDEX       `idx_pasaporte_fecha`    (`pasaporte_id`, `created_at`),
    INDEX       `idx_alojamiento_fecha`  (`alojamiento_id`, `created_at`),
    INDEX       `idx_propietario`        (`propietario_user_id`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Histórico inmutable de todos los sellos del Pasaporte Rural';


-- =============================================================================
-- 4. FOREIGN KEYS (separadas para facilitar ejecución en entornos sin InnoDB)
-- =============================================================================

-- FK: pasaporte_turistas → users
ALTER TABLE `pasaporte_turistas`
    ADD CONSTRAINT `fk_pasaporte_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE;

-- FK: qr_temporales → pasaporte_turistas
ALTER TABLE `qr_temporales`
    ADD CONSTRAINT `fk_qr_pasaporte`
        FOREIGN KEY (`pasaporte_id`)
        REFERENCES `pasaporte_turistas`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE;

-- FK: historico_sellos → pasaporte_turistas
ALTER TABLE `historico_sellos`
    ADD CONSTRAINT `fk_sello_pasaporte`
        FOREIGN KEY (`pasaporte_id`)
        REFERENCES `pasaporte_turistas`(`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE;

-- FK: historico_sellos → accommodations
ALTER TABLE `historico_sellos`
    ADD CONSTRAINT `fk_sello_alojamiento`
        FOREIGN KEY (`alojamiento_id`)
        REFERENCES `accommodations`(`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE;

-- FK: historico_sellos → qr_temporales
ALTER TABLE `historico_sellos`
    ADD CONSTRAINT `fk_sello_qr_token`
        FOREIGN KEY (`qr_token_id`)
        REFERENCES `qr_temporales`(`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE;


-- =============================================================================
-- 5. VISTA ÚTIL: resumen de pasaportes con datos del usuario
--    Facilita consultas en el panel admin sin JOINs complejos
-- =============================================================================
CREATE OR REPLACE VIEW `v_pasaportes_resumen` AS
    SELECT
        pt.id                   AS pasaporte_id,
        pt.user_id,
        CONCAT(u.first_name, ' ', u.last_name) AS nombre_turista,
        u.email                 AS email_turista,
        u.avatar_url            AS avatar_turista,
        pt.descuento_actual,
        pt.puntos_totales,
        pt.nivel,
        pt.estado,
        pt.total_sellos,
        pt.ultimo_sello_at,
        pt.created_at           AS pasaporte_creado_at
    FROM `pasaporte_turistas` pt
    INNER JOIN `users` u ON u.id = pt.user_id;

-- =============================================================================
-- FIN DEL SCRIPT
-- Tablas creadas: pasaporte_turistas, qr_temporales, historico_sellos
-- Vista creada: v_pasaportes_resumen
-- =============================================================================
