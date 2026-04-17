-- ============================================================
-- SISTEMA DE RUTAS TEMÁTICAS - rutasrurales.io
-- setup.sql — Ejecutar en phpMyAdmin (base de datos: u412199647_Rutas)
-- ============================================================

-- ============================================================
-- PASO 1: Ver qué columnas tiene route_items ahora mismo
-- (Ejecuta esto primero para confirmar la estructura)
-- ============================================================
-- DESCRIBE route_items;

-- ============================================================
-- PASO 2: Añadir columnas que necesitamos a route_items
-- (Solo añade las que no existen — seguro ejecutar)
-- ============================================================
ALTER TABLE route_items
  ADD COLUMN IF NOT EXISTS item_type      VARCHAR(50)  DEFAULT 'lugar'    COMMENT 'alojamiento, lugar, actividad, evento',
  ADD COLUMN IF NOT EXISTS item_id        INT          DEFAULT NULL        COMMENT 'ID del item en su tabla original',
  ADD COLUMN IF NOT EXISTS display_order  INT          DEFAULT 0           COMMENT 'Orden de visualización',
  ADD COLUMN IF NOT EXISTS day_number     TINYINT      DEFAULT 1           COMMENT 'Día del itinerario (1, 2, 3...)',
  ADD COLUMN IF NOT EXISTS time_slot      VARCHAR(50)  DEFAULT NULL        COMMENT 'mañana, tarde, noche, todo-el-dia',
  ADD COLUMN IF NOT EXISTS editorial_note TEXT         DEFAULT NULL        COMMENT 'Nota editorial para este item',
  ADD COLUMN IF NOT EXISTS is_highlight   TINYINT(1)   DEFAULT 0           COMMENT '1 = item destacado';

-- ============================================================
-- PASO 3: Añadir columnas SEO a routes si no existen
-- (Las columnas hero_image, seo_title, etc. ya existen según phpMyAdmin)
-- ============================================================
ALTER TABLE routes
  ADD COLUMN IF NOT EXISTS hero_image      VARCHAR(500) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS seo_keywords    TEXT         DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS seo_title       VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS seo_description VARCHAR(320) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS province        VARCHAR(100) DEFAULT 'Soria',
  ADD COLUMN IF NOT EXISTS season          VARCHAR(50)  DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS cover_color     VARCHAR(7)   DEFAULT '#2F5233',
  ADD COLUMN IF NOT EXISTS itinerary_json  LONGTEXT     DEFAULT NULL;

-- ============================================================
-- PASO 4: INSERTAR RUTA — Puente del 1 de Mayo en Soria 2026
-- Usando SOLO columnas confirmadas de routes:
--   name, slug, description, duration_days, difficulty_level,
--   status, is_public, is_featured, hero_image, seo_keywords,
--   seo_title, seo_description, province, season, cover_color,
--   itinerary_json
-- ============================================================
INSERT INTO routes (
    name,
    slug,
    description,
    duration_days,
    difficulty_level,
    status,
    is_public,
    is_featured,
    hero_image,
    seo_keywords,
    seo_title,
    seo_description,
    province,
    season,
    cover_color,
    itinerary_json
)
SELECT
    'Puente del 1 de Mayo en Soria 2026',
    'puente-1-mayo-soria',
    'Escápate a Soria este puente del 1 de mayo y descubre por qué es uno de los destinos rurales más auténticos de España. Tres días de naturaleza, historia, gastronomía y cultura en la provincia menos masificada de Castilla y León.',
    3,
    'facil',
    'published',
    1,
    1,
    'https://rutasrurales.io/menu_images/urbion.jpg',
    'puente 1 de mayo Soria, escapada rural puente mayo, qué hacer Soria puente mayo, casas rurales Soria puente mayo, turismo rural Soria mayo 2026',
    'Puente 1 de Mayo en Soria 2026 | Escapada Rural + Alojamientos y Eventos',
    'Descubre qué hacer en Soria el puente del 1 de mayo 2026. Itinerario de 3 días, casas rurales disponibles, eventos culturales y los mejores lugares. ¡Reserva ya!',
    'Soria',
    'primavera',
    '#2F5233',
    '[{"dia":1,"fecha":"2026-04-29","titulo":"Llegada y primer contacto con Soria","descripcion":"Llega a Soria, instálate en tu alojamiento rural y da un primer paseo por el casco histórico.","icono":"🚗"},{"dia":2,"fecha":"2026-04-30","titulo":"Historia y naturaleza soriana","descripcion":"Mañana en Numancia. Tarde en el Cañón del Río Lobos o los Picos de Urbión.","icono":"🏛️"},{"dia":3,"fecha":"2026-05-01","titulo":"Día del Trabajador: Fiesta y cultura","descripcion":"El 1 de mayo Soria se llena de vida. Eventos culturales y actividades al aire libre.","icono":"🎉"},{"dia":4,"fecha":"2026-05-02","titulo":"Vuelta con parada en Medinaceli","descripcion":"Visita Medinaceli, el único arco romano de tres vanos de España.","icono":"🏛️"}]'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM routes WHERE slug = 'puente-1-mayo-soria'
);

-- ============================================================
-- PASO 5: Obtener el ID de la ruta
-- ============================================================
SET @ruta_id = (SELECT id FROM routes WHERE slug = 'puente-1-mayo-soria' LIMIT 1);

-- ============================================================
-- PASO 6: INSERTAR ITEMS — solo con columnas que existen
-- (route_id + las columnas que acabamos de añadir en PASO 2)
--
-- ⚠️ AJUSTA los item_id con IDs reales de tu BD:
--   SELECT id, name FROM accommodations WHERE province LIKE '%Soria%' LIMIT 10;
--   SELECT id, name FROM places_of_interest WHERE province LIKE '%Soria%' LIMIT 10;
--   SELECT id, name FROM tourist_activities WHERE province LIKE '%Soria%' LIMIT 10;
-- ============================================================
INSERT INTO route_items (route_id, item_type, item_id, display_order, day_number, time_slot, is_highlight, editorial_note)
SELECT v.route_id, v.item_type, v.item_id, v.display_order, v.day_number, v.time_slot, v.is_highlight, v.editorial_note
FROM (
    SELECT @ruta_id AS route_id, 'alojamiento' AS item_type, 1  AS item_id, 1  AS display_order, 1 AS day_number, 'todo-el-dia' AS time_slot, 1 AS is_highlight, 'Casa rural con encanto en plena naturaleza soriana.' AS editorial_note
    UNION ALL SELECT @ruta_id, 'alojamiento', 2,  2, 1, 'todo-el-dia', 1, 'Apartamento rural en el corazón de la comarca de Pinares.'
    UNION ALL SELECT @ruta_id, 'alojamiento', 3,  3, 1, 'todo-el-dia', 0, 'Casa rural tradicional con chimenea. Ideal para desconectar.'
    UNION ALL SELECT @ruta_id, 'lugar',        1,  4, 2, 'mañana',      1, 'La ciudad celtíbera que resistió 20 años al Imperio Romano.'
    UNION ALL SELECT @ruta_id, 'lugar',        2,  5, 2, 'tarde',       1, 'El claustro más único del mundo: arcos entrelazados mudéjar.'
    UNION ALL SELECT @ruta_id, 'lugar',        3,  6, 2, 'mañana',      1, 'La montaña más alta de Soria (2.228m) con lagunas glaciares.'
    UNION ALL SELECT @ruta_id, 'lugar',        4,  7, 2, 'tarde',       1, 'Parque Natural con formaciones rocosas y ermita románica.'
    UNION ALL SELECT @ruta_id, 'actividad',    1,  8, 3, 'mañana',      1, 'Ruta circular de senderismo hasta la mítica Laguna Negra.'
    UNION ALL SELECT @ruta_id, 'actividad',    2,  9, 3, 'noche',       1, 'Soria tiene certificado Starlight. Los cielos más limpios de España.'
    UNION ALL SELECT @ruta_id, 'actividad',    3, 10, 3, 'tarde',       0, 'Con guía experto por los bosques sorianos.'
    UNION ALL SELECT @ruta_id, 'lugar',        5, 11, 4, 'mañana',      1, 'El único arco romano de tres vanos de España.'
    UNION ALL SELECT @ruta_id, 'lugar',        6, 12, 4, 'mañana',      0, 'Catedral gótica y casco histórico medieval.'
) AS v
WHERE @ruta_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM route_items WHERE route_id = @ruta_id LIMIT 1);

-- ============================================================
-- VERIFICACIÓN FINAL
-- ============================================================
SELECT r.id, r.name, r.slug, r.status, r.is_public, COUNT(ri.id) AS items
FROM routes r
LEFT JOIN route_items ri ON r.id = ri.route_id
WHERE r.slug = 'puente-1-mayo-soria'
GROUP BY r.id, r.name, r.slug, r.status, r.is_public;
