-- ═══════════════════════════════════════════════════════════════════════════════
--  fulltext_index_eventos.sql — rutasrurales.io
--  Índices MySQL para el buscador de eventos culturales
--  Motor: InnoDB (MySQL 5.6+ / Hostinger ✓)
-- ═══════════════════════════════════════════════════════════════════════════════

-- ─── 1. FULLTEXT PRINCIPAL sobre cultural_events ────────────────────────────
--  Cubre: título, resumen, municipio, provincia y descripción larga.
--  Operadores BOOLEAN MODE: '+roca*' (prefijo), '+fiesta -toros' (exclusión).

DROP PROCEDURE IF EXISTS _drop_ft_search_eventos;
DELIMITER $$
CREATE PROCEDURE _drop_ft_search_eventos()
BEGIN
    IF (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE table_schema = DATABASE()
          AND table_name   = 'cultural_events'
          AND index_name   = 'ft_search_eventos') > 0 THEN
        ALTER TABLE cultural_events DROP INDEX ft_search_eventos;
    END IF;
END$$
DELIMITER ;
CALL _drop_ft_search_eventos();
DROP PROCEDURE IF EXISTS _drop_ft_search_eventos;

ALTER TABLE cultural_events
    ADD FULLTEXT INDEX ft_search_eventos (
        name,               -- Título (máximo peso semántico)
        short_description,  -- Resumen corto
        municipality,       -- Localidad/municipio
        province,           -- Provincia
        description         -- Descripción larga (complementaria)
    );

-- ─── 2. FULLTEXT sobre tabla de TRADUCCIONES (EN, FR, ZH) ───────────────────

DROP PROCEDURE IF EXISTS _drop_ft_trads_eventos;
DELIMITER $$
CREATE PROCEDURE _drop_ft_trads_eventos()
BEGIN
    IF (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE table_schema = DATABASE()
          AND table_name   = 'cultural_events_trads'
          AND index_name   = 'ft_search_trads') > 0 THEN
        ALTER TABLE cultural_events_trads DROP INDEX ft_search_trads;
    END IF;
END$$
DELIMITER ;
CALL _drop_ft_trads_eventos();
DROP PROCEDURE IF EXISTS _drop_ft_trads_eventos;

ALTER TABLE cultural_events_trads
    ADD FULLTEXT INDEX ft_search_trads (name, short_description, description);

-- ─── 3. Filtro rápido: activos + vigentes + aprobados ───────────────────────
--  Usado ANTES de MATCH AGAINST para reducir el conjunto de filas.

DROP PROCEDURE IF EXISTS _drop_idx_active;
DELIMITER $$
CREATE PROCEDURE _drop_idx_active()
BEGIN
    IF (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE table_schema = DATABASE()
          AND table_name   = 'cultural_events'
          AND index_name   = 'idx_active_date_status') > 0 THEN
        ALTER TABLE cultural_events DROP INDEX idx_active_date_status;
    END IF;
END$$
DELIMITER ;
CALL _drop_idx_active();
DROP PROCEDURE IF EXISTS _drop_idx_active;

ALTER TABLE cultural_events
    ADD INDEX idx_active_date_status (is_active, moderation_status, start_date);

-- ─── 4. Filtro por provincia + categoría ────────────────────────────────────

DROP PROCEDURE IF EXISTS _drop_idx_prov;
DELIMITER $$
CREATE PROCEDURE _drop_idx_prov()
BEGIN
    IF (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE table_schema = DATABASE()
          AND table_name   = 'cultural_events'
          AND index_name   = 'idx_prov_cat_active') > 0 THEN
        ALTER TABLE cultural_events DROP INDEX idx_prov_cat_active;
    END IF;
END$$
DELIMITER ;
CALL _drop_idx_prov();
DROP PROCEDURE IF EXISTS _drop_idx_prov;

ALTER TABLE cultural_events
    ADD INDEX idx_prov_cat_active (is_active, province, category_id, start_date);

-- ─── 5. Filtro "eventos gratuitos" ──────────────────────────────────────────

DROP PROCEDURE IF EXISTS _drop_idx_free;
DELIMITER $$
CREATE PROCEDURE _drop_idx_free()
BEGIN
    IF (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE table_schema = DATABASE()
          AND table_name   = 'cultural_events'
          AND index_name   = 'idx_is_free') > 0 THEN
        ALTER TABLE cultural_events DROP INDEX idx_is_free;
    END IF;
END$$
DELIMITER ;
CALL _drop_idx_free();
DROP PROCEDURE IF EXISTS _drop_idx_free;

ALTER TABLE cultural_events
    ADD INDEX idx_is_free (is_active, is_free, start_date);

-- ─── 6. Verificación ────────────────────────────────────────────────────────
-- SHOW INDEX FROM cultural_events;
-- SHOW INDEX FROM cultural_events_trads;
