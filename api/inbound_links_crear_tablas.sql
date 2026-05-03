-- ============================================================
-- SISTEMA DE INBOUND LINKS
-- Mapa de palabras clave → URLs internas
-- Los links se insertan al GUARDAR el contenido (SSR)
-- → Sin impacto en velocidad del usuario
-- ============================================================

-- 1. Tabla principal de keywords → URLs
CREATE TABLE IF NOT EXISTS `inbound_links` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `keyword`     VARCHAR(255) NOT NULL COMMENT 'Texto exacto a buscar (case-insensitive)',
    `url`         VARCHAR(500) NOT NULL COMMENT 'URL de destino con / inicial, ej: /mercados/castellano',
    `link_title`  VARCHAR(255) NOT NULL COMMENT 'Atributo title del enlace (SEO)',
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = activo, 0 = desactivado',
    `priority`    SMALLINT UNSIGNED NOT NULL DEFAULT 100 COMMENT 'Menor número = mayor prioridad (se aplica primero)',
    `mercado`     VARCHAR(5) NOT NULL DEFAULT 'es' COMMENT 'Mercado: es, en, fr, de, zh',
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_keyword` (`keyword`),
    KEY `idx_active_priority` (`is_active`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Mapa de Inbound Links: keyword → URL interna para SEO';

-- 2. Columna description_linked en cultural_events
--    Almacena la descripción ya procesada con los <a href> insertados
ALTER TABLE `cultural_events`
    ADD COLUMN IF NOT EXISTS `description_linked` MEDIUMTEXT DEFAULT NULL
    COMMENT 'description con inbound links ya insertados (se genera al guardar)';

-- 3. Columna description_linked en accommodations
ALTER TABLE `accommodations`
    ADD COLUMN IF NOT EXISTS `description_linked` MEDIUMTEXT DEFAULT NULL
    COMMENT 'description con inbound links ya insertados (se genera al guardar)';

-- ─── DATOS DE EJEMPLO ─────────────────────────────────────────────────────────
-- Puedes borrar estos ejemplos o adaptarlos a tus URLs reales
INSERT IGNORE INTO `inbound_links` (`keyword`, `url`, `link_title`, `is_active`, `priority`) VALUES
('turismo rural', '/alojamientos-turisticos', 'Alojamientos de turismo rural en España', 1, 10),
('rutas rurales', '/', 'Descubre las mejores rutas rurales de España', 1, 10),
('alojamiento rural', '/alojamientos-turisticos', 'Alojamientos rurales con encanto', 1, 20),
('eventos culturales', '/eventos-culturales', 'Calendario de eventos culturales', 1, 20);
