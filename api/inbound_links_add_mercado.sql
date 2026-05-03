-- ============================================================
-- MIGRACIÓN: Añadir columna 'mercado' a la tabla inbound_links
-- Ejecutar SOLO si ya habías creado la tabla con el SQL anterior
-- (si aún no tienes la tabla, ejecuta inbound_links_crear_tablas.sql)
-- ============================================================

ALTER TABLE `inbound_links`
    ADD COLUMN IF NOT EXISTS `mercado` VARCHAR(5) NOT NULL DEFAULT 'es'
    COMMENT 'Mercado al que aplica: es, en, fr, de, zh'
    AFTER `priority`;
