-- ============================================================
-- SISTEMA DE RUTAS TEMÁTICAS - rutasrurales.io
-- setup.sql — Ejecutar en phpMyAdmin (base de datos: u412199647_Rutas)
--
-- INSTRUCCIONES:
--   1. Abre phpMyAdmin → selecciona u412199647_Rutas
--   2. Pestaña SQL → pega este archivo completo → Ejecutar
--   3. Ajusta los item_id del PASO 6 a IDs reales de tu BD
-- ============================================================

-- ============================================================
-- ESTRUCTURA REAL CONFIRMADA de la tabla routes:
--   id, user_id, name, slug, description, duration_days,
--   total_distance, difficulty_level, route_type, themes,
--   suitable_for, best_season, is_public, is_featured,
--   is_ai_generated, status, views_count, clones_count,
--   rating_avg, main_image, cover_image, created_at, updated_at,
--   hero_image, seo_keywords, seo_title, seo_description,
--   province, season, cover_color, itinerary_json
-- ============================================================

-- ============================================================
-- PASO 1: Añadir columnas editoriales a route_items si no existen
-- (Las columnas SEO de routes ya existen según phpMyAdmin)
-- ============================================================
ALTER TABLE route_items
  ADD COLUMN IF NOT EXISTS day_number     TINYINT DEFAULT 1 COMMENT 'Día del itinerario (1, 2, 3...)',
  ADD COLUMN IF NOT EXISTS time_slot      VARCHAR(50) DEFAULT NULL COMMENT 'mañana, tarde, noche, todo-el-dia',
  ADD COLUMN IF NOT EXISTS editorial_note TEXT DEFAULT NULL COMMENT 'Nota editorial para este item en esta ruta',
  ADD COLUMN IF NOT EXISTS is_highlight   TINYINT(1) DEFAULT 0 COMMENT '1 = item destacado de la ruta';

-- ============================================================
-- PASO 2: Índices de rendimiento (IF NOT EXISTS = seguro)
-- ============================================================
CREATE INDEX IF NOT EXISTS idx_routes_slug   ON routes(slug);
CREATE INDEX IF NOT EXISTS idx_routes_status ON routes(status);
CREATE INDEX IF NOT EXISTS idx_ri_route      ON route_items(route_id);
CREATE INDEX IF NOT EXISTS idx_ri_type       ON route_items(item_type);
CREATE INDEX IF NOT EXISTS idx_ri_day        ON route_items(day_number);

-- ============================================================
-- PASO 3: INSERTAR RUTA — Puente del 1 de Mayo en Soria 2026
-- Columnas reales: name, slug, description, duration_days,
--   difficulty_level, status, hero_image, seo_keywords,
--   seo_title, seo_description, province, season,
--   cover_color, itinerary_json, is_public, is_featured
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
    'Escápate a Soria este puente del 1 de mayo y descubre por qué es uno de los destinos rurales más auténticos de España. Tres días de naturaleza, historia, gastronomía y cultura en la provincia menos masificada de Castilla y León. Alojamientos con encanto, rutas de senderismo espectaculares y eventos culturales únicos te esperan del 29 de abril al 2 de mayo de 2026.',
    3,
    'facil',
    'published',
    1,
    1,
    'https://rutasrurales.io/menu_images/urbion.jpg',
    'puente 1 de mayo Soria, escapada rural puente mayo, qué hacer Soria puente mayo, casas rurales Soria puente mayo, turismo rural Soria mayo 2026, Soria puente laboral, fin de semana largo Soria',
    'Puente 1 de Mayo en Soria 2026 | Escapada Rural + Alojamientos y Eventos',
    'Descubre qué hacer en Soria el puente del 1 de mayo 2026. Itinerario de 3 días, casas rurales disponibles, eventos culturales y los mejores lugares. ¡Reserva ya!',
    'Soria',
    'primavera',
    '#2F5233',
    '[{"dia":1,"fecha":"2026-04-29","titulo":"Llegada y primer contacto con Soria","descripcion":"Llega a Soria, instálate en tu alojamiento rural y da un primer paseo por el casco histórico. Cena con productos locales: torreznos, migas y vino de la tierra.","icono":"🚗"},{"dia":2,"fecha":"2026-04-30","titulo":"Historia y naturaleza soriana","descripcion":"Mañana en Numancia, la ciudad celtíbera que resistió a Roma. Tarde en el Cañón del Río Lobos o los Picos de Urbión. Noche de estrellas en uno de los cielos más limpios de España.","icono":"🏛️"},{"dia":3,"fecha":"2026-05-01","titulo":"Día del Trabajador: Fiesta y cultura","descripcion":"El 1 de mayo Soria se llena de vida. Eventos culturales, mercados artesanales y actividades al aire libre. Disfruta de la gastronomía local en alguno de los mejores restaurantes de la provincia.","icono":"🎉"},{"dia":4,"fecha":"2026-05-02","titulo":"Vuelta con parada en Medinaceli","descripcion":"Antes de volver, visita Medinaceli, el único arco romano de tres vanos de España. Un broche de oro para una escapada perfecta.","icono":"🏛️"}]'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM routes WHERE slug = 'puente-1-mayo-soria'
);

-- ============================================================
-- PASO 4: Obtener el ID de la ruta
-- ============================================================
SET @ruta_id = (SELECT id FROM routes WHERE slug = 'puente-1-mayo-soria' LIMIT 1);

-- ============================================================
-- PASO 5: INSERTAR ITEMS DE LA RUTA
-- ⚠️  IMPORTANTE: Sustituye los item_id por los IDs reales:
--
--   Para ver IDs de alojamientos:
--     SELECT id, name, slug FROM accommodations WHERE province LIKE '%Soria%' LIMIT 20;
--
--   Para ver IDs de lugares:
--     SELECT id, name, slug FROM places_of_interest WHERE province LIKE '%Soria%' LIMIT 20;
--
--   Para ver IDs de actividades:
--     SELECT id, name, slug FROM tourist_activities WHERE province LIKE '%Soria%' LIMIT 20;
-- ============================================================
INSERT INTO route_items (route_id, item_type, item_id, item_name, display_order, day_number, time_slot, is_highlight, editorial_note)
SELECT vals.*
FROM (
    -- DÍA 1: Alojamientos (sustituye 1, 2, 3 por IDs reales)
    SELECT @ruta_id, 'alojamiento', 1, 'Alojamiento Soria 1 — ajusta este ID', 1, 1, 'todo-el-dia', 1, 'Casa rural con encanto en plena naturaleza soriana. Perfecta para grupos.'
    UNION ALL SELECT @ruta_id, 'alojamiento', 2, 'Alojamiento Soria 2 — ajusta este ID', 2, 1, 'todo-el-dia', 1, 'Apartamento rural en el corazón de la comarca de Pinares.'
    UNION ALL SELECT @ruta_id, 'alojamiento', 3, 'Alojamiento Soria 3 — ajusta este ID', 3, 1, 'todo-el-dia', 0, 'Casa rural tradicional con chimenea. Ideal para desconectar.'
    -- DÍA 2: Lugares de interés (sustituye 1, 2, 3, 4 por IDs reales)
    UNION ALL SELECT @ruta_id, 'lugar', 1, 'Lugar Soria 1 — ajusta este ID', 4, 2, 'mañana', 1, 'La ciudad celtíbera que resistió 20 años al Imperio Romano. Imprescindible.'
    UNION ALL SELECT @ruta_id, 'lugar', 2, 'Lugar Soria 2 — ajusta este ID', 5, 2, 'tarde', 1, 'El claustro más único del mundo: arcos entrelazados de inspiración mudéjar.'
    UNION ALL SELECT @ruta_id, 'lugar', 3, 'Lugar Soria 3 — ajusta este ID', 6, 2, 'mañana', 1, 'La montaña más alta de Soria (2.228m) con lagunas glaciares de ensueño.'
    UNION ALL SELECT @ruta_id, 'lugar', 4, 'Lugar Soria 4 — ajusta este ID', 7, 2, 'tarde', 1, 'Parque Natural con formaciones rocosas espectaculares y ermita románica.'
    -- DÍA 3: Actividades (sustituye 1, 2, 3 por IDs reales)
    UNION ALL SELECT @ruta_id, 'actividad', 1, 'Actividad Soria 1 — ajusta este ID', 8, 3, 'mañana', 1, 'Ruta circular de senderismo hasta la mítica Laguna Negra. 3-4 horas.'
    UNION ALL SELECT @ruta_id, 'actividad', 2, 'Actividad Soria 2 — ajusta este ID', 9, 3, 'noche', 1, 'Soria tiene certificado Starlight. Los cielos más limpios de España.'
    UNION ALL SELECT @ruta_id, 'actividad', 3, 'Actividad Soria 3 — ajusta este ID', 10, 3, 'tarde', 0, 'Con guía experto por los bosques sorianos. Naturaleza y gastronomía.'
    -- DÍA 4: Lugares vuelta (sustituye 5, 6 por IDs reales)
    UNION ALL SELECT @ruta_id, 'lugar', 5, 'Lugar Soria 5 — ajusta este ID', 11, 4, 'mañana', 1, 'El único arco romano de tres vanos de España. Conjunto Histórico Declarado.'
    UNION ALL SELECT @ruta_id, 'lugar', 6, 'Lugar Soria 6 — ajusta este ID', 12, 4, 'mañana', 0, 'Catedral gótica y casco histórico medieval. Villa episcopal única.'
) AS vals(route_id, item_type, item_id, item_name, display_order, day_number, time_slot, is_highlight, editorial_note)
WHERE @ruta_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM route_items WHERE route_id = @ruta_id LIMIT 1
  );

-- ============================================================
-- VERIFICACIÓN FINAL
-- ============================================================
SELECT
    r.id,
    r.name,
    r.slug,
    r.status,
    r.is_public,
    r.is_featured,
    COUNT(ri.id) AS total_items
FROM routes r
LEFT JOIN route_items ri ON r.id = ri.route_id
WHERE r.slug = 'puente-1-mayo-soria'
GROUP BY r.id, r.name, r.slug, r.status, r.is_public, r.is_featured;
