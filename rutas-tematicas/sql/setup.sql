-- ============================================================
-- SISTEMA DE RUTAS TEMÁTICAS - rutasrurales.io
-- setup.sql — Ejecutar en phpMyAdmin (base de datos: u412199647_Rutas)
--
-- ESTRUCTURA REAL CONFIRMADA de route_items:
--   id, route_id, day_number, item_order, item_type (enum: accommodation,place,activity,event,restaurant,stop)
--   item_id, title, description, notes, estimated_duration,
--   arrival_time, departure_time, latitude, longitude, address,
--   created_at, updated_at, time_slot, editorial_note, is_highlight, display_order
-- ============================================================

-- ============================================================
-- PASO 1: Corregir los items ya insertados (item_type estaba vacío)
-- ============================================================
UPDATE route_items SET item_type = 'accommodation' WHERE route_id = 4 AND item_id IN (1,2,3) AND day_number = 1;
UPDATE route_items SET item_type = 'place'         WHERE route_id = 4 AND item_id IN (1,2,3,4,5,6) AND day_number IN (2,4);
UPDATE route_items SET item_type = 'activity'      WHERE route_id = 4 AND item_id IN (1,2,3) AND day_number = 3;

-- ============================================================
-- PASO 2: Si los items no existen aún, insertarlos correctamente
-- (Primero borra los que están vacíos si los hay)
-- ============================================================
-- Opcional: limpiar items sin item_type si quieres empezar limpio:
-- DELETE FROM route_items WHERE route_id = 4;

-- ============================================================
-- PASO 3: Insertar la ruta si no existe
-- ============================================================
INSERT INTO routes (
    name, slug, description, duration_days, difficulty_level,
    status, is_public, is_featured,
    hero_image, seo_keywords, seo_title, seo_description,
    province, season, cover_color, itinerary_json
)
SELECT
    'Puente del 1 de Mayo en Soria 2026',
    'puente-1-mayo-soria',
    'Escápate a Soria este puente del 1 de mayo y descubre por qué es uno de los destinos rurales más auténticos de España. Tres días de naturaleza, historia, gastronomía y cultura en la provincia menos masificada de Castilla y León.',
    3, 'facil', 'published', 1, 1,
    'https://rutasrurales.io/menu_images/urbion.jpg',
    'puente 1 de mayo Soria, escapada rural puente mayo, qué hacer Soria puente mayo, casas rurales Soria puente mayo, turismo rural Soria mayo 2026',
    'Puente 1 de Mayo en Soria 2026 | Escapada Rural + Alojamientos y Eventos',
    'Descubre qué hacer en Soria el puente del 1 de mayo 2026. Itinerario de 3 días, casas rurales disponibles, eventos culturales y los mejores lugares. ¡Reserva ya!',
    'Soria', 'primavera', '#2F5233',
    '[{"dia":1,"fecha":"2026-04-29","titulo":"Llegada y primer contacto con Soria","descripcion":"Llega a Soria, instálate en tu alojamiento rural y da un primer paseo por el casco histórico.","icono":"🚗"},{"dia":2,"fecha":"2026-04-30","titulo":"Historia y naturaleza soriana","descripcion":"Mañana en Numancia. Tarde en el Cañón del Río Lobos o los Picos de Urbión.","icono":"🏛️"},{"dia":3,"fecha":"2026-05-01","titulo":"Día del Trabajador: Fiesta y cultura","descripcion":"El 1 de mayo Soria se llena de vida. Eventos culturales y actividades al aire libre.","icono":"🎉"},{"dia":4,"fecha":"2026-05-02","titulo":"Vuelta con parada en Medinaceli","descripcion":"Visita Medinaceli, el único arco romano de tres vanos de España.","icono":"🏛️"}]'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM routes WHERE slug = 'puente-1-mayo-soria');

SET @ruta_id = (SELECT id FROM routes WHERE slug = 'puente-1-mayo-soria' LIMIT 1);

-- ============================================================
-- PASO 4: Insertar items con item_type correcto (enum real)
-- enum: 'accommodation','place','activity','event','restaurant','stop'
-- ⚠️ Ajusta los item_id con IDs reales de Soria en tu BD
-- ============================================================
INSERT INTO route_items (route_id, item_type, item_id, day_number, display_order, time_slot, is_highlight, editorial_note)
SELECT v.*
FROM (
    -- DÍA 1: Alojamientos (accommodation)
    SELECT @ruta_id, 'accommodation', 1,  1, 1,  'todo-el-dia', 1, 'Casa rural con encanto en plena naturaleza soriana.'
    UNION ALL SELECT @ruta_id, 'accommodation', 2,  1, 2,  'todo-el-dia', 1, 'Apartamento rural en el corazón de la comarca de Pinares.'
    UNION ALL SELECT @ruta_id, 'accommodation', 3,  1, 3,  'todo-el-dia', 0, 'Casa rural tradicional con chimenea. Ideal para desconectar.'
    -- DÍA 2: Lugares (place)
    UNION ALL SELECT @ruta_id, 'place',         1,  2, 4,  'mañana',      1, 'La ciudad celtíbera que resistió 20 años al Imperio Romano.'
    UNION ALL SELECT @ruta_id, 'place',         2,  2, 5,  'tarde',       1, 'El claustro más único del mundo: arcos entrelazados mudéjar.'
    UNION ALL SELECT @ruta_id, 'place',         3,  2, 6,  'mañana',      1, 'La montaña más alta de Soria (2.228m) con lagunas glaciares.'
    UNION ALL SELECT @ruta_id, 'place',         4,  2, 7,  'tarde',       1, 'Parque Natural con formaciones rocosas y ermita románica.'
    -- DÍA 3: Actividades (activity)
    UNION ALL SELECT @ruta_id, 'activity',      1,  3, 8,  'mañana',      1, 'Ruta circular de senderismo hasta la mítica Laguna Negra.'
    UNION ALL SELECT @ruta_id, 'activity',      2,  3, 9,  'noche',       1, 'Soria tiene certificado Starlight. Los cielos más limpios de España.'
    UNION ALL SELECT @ruta_id, 'activity',      3,  3, 10, 'tarde',       0, 'Con guía experto por los bosques sorianos.'
    -- DÍA 4: Lugares vuelta (place)
    UNION ALL SELECT @ruta_id, 'place',         5,  4, 11, 'mañana',      1, 'El único arco romano de tres vanos de España.'
    UNION ALL SELECT @ruta_id, 'place',         6,  4, 12, 'mañana',      0, 'Catedral gótica y casco histórico medieval.'
) AS v(route_id, item_type, item_id, day_number, display_order, time_slot, is_highlight, editorial_note)
WHERE @ruta_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM route_items
    WHERE route_id = @ruta_id AND item_type != '' AND item_type IS NOT NULL
    LIMIT 1
  );

-- ============================================================
-- VERIFICACIÓN
-- ============================================================
SELECT r.id, r.name, r.slug, r.status, r.is_public,
       COUNT(ri.id) AS total_items,
       SUM(ri.item_type = 'accommodation') AS alojamientos,
       SUM(ri.item_type = 'place') AS lugares,
       SUM(ri.item_type = 'activity') AS actividades
FROM routes r
LEFT JOIN route_items ri ON r.id = ri.route_id
WHERE r.slug = 'puente-1-mayo-soria'
GROUP BY r.id, r.name, r.slug, r.status, r.is_public;
