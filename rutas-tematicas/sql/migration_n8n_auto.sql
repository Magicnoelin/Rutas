-- ============================================================
-- MIGRACIÓN: Sistema de Rutas Automáticas via n8n
-- Base de datos : u412199647_Rutas (Hostinger)
-- Archivo       : rutas-tematicas/sql/migration_n8n_auto.sql
-- Ejecutar en   : phpMyAdmin → pestaña SQL → pegar y ejecutar
-- SEGURO: No borra ni modifica datos existentes.
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- BLOQUE 1: Ampliar tabla `routes` con columnas n8n
-- ─────────────────────────────────────────────────────────────
ALTER TABLE `routes`
    ADD COLUMN IF NOT EXISTS `seo_title_fr`         TEXT          NULL COMMENT 'Meta title en francés (LLM)',
    ADD COLUMN IF NOT EXISTS `seo_description_fr`   TEXT          NULL COMMENT 'Meta description en francés',
    ADD COLUMN IF NOT EXISTS `titulo_fr`            VARCHAR(255)  NULL COMMENT 'Título en francés',
    ADD COLUMN IF NOT EXISTS `seo_keywords_fr`      TEXT          NULL COMMENT 'Keywords SEO en francés',
    ADD COLUMN IF NOT EXISTS `descripcion_larga_es` LONGTEXT      NULL COMMENT 'Descripción SEO larga ES (400-600 palabras)',
    ADD COLUMN IF NOT EXISTS `descripcion_larga_fr` LONGTEXT      NULL COMMENT 'Descripción SEO larga FR',
    ADD COLUMN IF NOT EXISTS `schema_json`          LONGTEXT      NULL COMMENT 'Schema.org TouristTrip JSON-LD',
    ADD COLUMN IF NOT EXISTS `polyline_osrm`        LONGTEXT      NULL COMMENT 'Google Encoded Polyline (OSRM) para Leaflet.js',
    ADD COLUMN IF NOT EXISTS `distancia_total_km`   DECIMAL(8,2)  NULL COMMENT 'Distancia total del recorrido (km)',
    ADD COLUMN IF NOT EXISTS `duracion_min`         INT UNSIGNED  NULL COMMENT 'Duración estimada en coche (minutos)',
    ADD COLUMN IF NOT EXISTS `total_paradas`        TINYINT       NULL COMMENT 'Número total de paradas',
    ADD COLUMN IF NOT EXISTS `ancla_poi_id`         INT UNSIGNED  NULL COMMENT 'ID del POI ancla origen de la ruta',
    ADD COLUMN IF NOT EXISTS `ancla_poi_tipo`       VARCHAR(20)   NULL COMMENT 'Tipo del POI ancla',
    ADD COLUMN IF NOT EXISTS `generated_by`         VARCHAR(50)   NULL DEFAULT 'manual' COMMENT 'manual|n8n-auto',
    ADD COLUMN IF NOT EXISTS `generation_date`      DATETIME      NULL COMMENT 'Timestamp de generación automática';

ALTER TABLE `routes`
    ADD INDEX IF NOT EXISTS `idx_routes_generated_by`  (`generated_by`),
    ADD INDEX IF NOT EXISTS `idx_routes_ancla`         (`ancla_poi_id`, `ancla_poi_tipo`),
    ADD INDEX IF NOT EXISTS `idx_routes_generation`    (`generation_date`);

-- ─────────────────────────────────────────────────────────────
-- BLOQUE 2: Columnas adicionales en `route_items`
-- ─────────────────────────────────────────────────────────────
ALTER TABLE `route_items`
    ADD COLUMN IF NOT EXISTS `latitude`      DECIMAL(10,7) NULL COMMENT 'Latitud cacheada (evita JOINs en el mapa)',
    ADD COLUMN IF NOT EXISTS `longitude`     DECIMAL(10,7) NULL COMMENT 'Longitud cacheada',
    ADD COLUMN IF NOT EXISTS `poi_categoria` VARCHAR(100)  NULL COMMENT 'Categoría del POI cacheada';

-- ─────────────────────────────────────────────────────────────
-- BLOQUE 3: Tabla `rutas_generadas` — trazabilidad n8n
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `rutas_generadas` (
    `id`                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `route_id`            INT UNSIGNED  NULL     COMMENT 'FK → routes.id (NULL si hubo error)',
    `n8n_execution_id`    VARCHAR(120)  NULL     COMMENT 'ID de ejecución de n8n',
    `ancla_poi_id`        INT UNSIGNED  NOT NULL,
    `ancla_poi_tipo`      ENUM('place','accommodation','activity','event','stop') NOT NULL DEFAULT 'place',
    `ancla_poi_nombre`    VARCHAR(255)  NOT NULL,
    `provincia`           VARCHAR(100)  NOT NULL,
    `pois_candidatos`     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `pois_seleccionados`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `distancia_km`        DECIMAL(8,2)  NULL,
    `duracion_min`        INT UNSIGNED  NULL,
    `llm_modelo`          VARCHAR(80)   NULL,
    `llm_tokens_input`    INT UNSIGNED  NULL,
    `llm_tokens_output`   INT UNSIGNED  NULL,
    `estado`              ENUM('ok','error','pendiente') NOT NULL DEFAULT 'pendiente',
    `error_mensaje`       TEXT          NULL,
    `created_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_rg_route_id`  (`route_id`),
    INDEX `idx_rg_provincia` (`provincia`),
    INDEX `idx_rg_estado`    (`estado`),
    INDEX `idx_rg_created`   (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Trazabilidad de ejecuciones del workflow n8n de rutas automáticas';

-- ─────────────────────────────────────────────────────────────
-- BLOQUE 4: Tabla `auxiliar_poi` — POIs de apoyo
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `auxiliar_poi` (
    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `name`         VARCHAR(255)  NOT NULL,
    `municipality` VARCHAR(150)  NULL,
    `province`     VARCHAR(100)  NULL,
    `latitude`     DECIMAL(10,7) NULL,
    `longitude`    DECIMAL(10,7) NULL,
    `category`     VARCHAR(100)  NULL COMMENT 'mirador, merendero, area-descanso, pueblo...',
    `description`  TEXT          NULL,
    `poi_source`   VARCHAR(100)  NULL COMMENT 'osm, manual, wikidata',
    `status`       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_apoi_province` (`province`),
    INDEX `idx_apoi_coords`   (`latitude`, `longitude`),
    INDEX `idx_apoi_status`   (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='POIs auxiliares de apoyo para el generador de rutas';

-- ─────────────────────────────────────────────────────────────
-- BLOQUE 5: Vista v_rutas_con_mapa
-- ─────────────────────────────────────────────────────────────
CREATE OR REPLACE VIEW `v_rutas_con_mapa` AS
SELECT
    r.id, r.name, r.slug, r.province, r.route_type,
    r.season, r.duration_days, r.difficulty_level,
    r.distancia_total_km, r.duracion_min, r.total_paradas,
    r.seo_title, r.seo_description, r.hero_image, r.cover_color,
    r.generated_by, r.generation_date, r.created_at,
    COUNT(ri.id) AS items_count
FROM routes r
LEFT JOIN route_items ri ON ri.route_id = r.id
WHERE r.status = 'published' AND r.is_public = 1
  AND r.polyline_osrm IS NOT NULL
GROUP BY r.id;

-- ─────────────────────────────────────────────────────────────
-- VERIFICACIÓN FINAL
-- ─────────────────────────────────────────────────────────────
SELECT 'routes'          AS tabla, COUNT(*) AS filas,
       SUM(polyline_osrm IS NOT NULL) AS con_polyline,
       SUM(generated_by = 'n8n-auto') AS auto_generadas
FROM routes
UNION ALL
SELECT 'rutas_generadas', COUNT(*), NULL, NULL FROM rutas_generadas
UNION ALL
SELECT 'auxiliar_poi',    COUNT(*), NULL, NULL FROM auxiliar_poi
UNION ALL
SELECT 'route_items',     COUNT(*), NULL, NULL FROM route_items;
