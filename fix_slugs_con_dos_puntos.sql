-- ============================================
-- FIX: Limpiar slugs que contienen ':' (dos puntos)
-- y otros caracteres inválidos en las traducciones
-- ============================================
-- 
-- PROBLEMA: Muchos slugs en cultural_events_trads contienen ':'
-- porque el slug original del evento en español contiene ':'
-- (ej: "victor-manuel-en-concierto:-gira-2026...")
-- y el script CONCAT() no sanitizó los ':'.
--
-- SOLUCIÓN: Reemplazar ':' por '' (nada) en todos los slugs
-- y también verificar otros caracteres problemáticos.
-- ============================================

-- 1. PRIMERO: VERIFICAR CUÁNTOS SLUGS TIENEN ':' (DOS PUNTOS)
-- ============================================
SELECT 
    'ANTES DE LA CORRECCIÓN' as estado,
    COUNT(*) as total_slugs_con_dos_puntos
FROM cultural_events_trads
WHERE slug LIKE '%:%';

-- 2. VER DETALLE DE SLUGS AFECTADOS
-- ============================================
SELECT 
    cet.event_id,
    cet.language_code,
    cet.slug as slug_actual,
    REPLACE(cet.slug, ':', '') as slug_corregido,
    ce.name as nombre_evento
FROM cultural_events_trads cet
JOIN cultural_events ce ON cet.event_id = ce.id
WHERE cet.slug LIKE '%:%'
ORDER BY cet.event_id, cet.language_code;

-- 3. CORREGIR: ELIMINAR ':' DE TODOS LOS SLUGS
-- ============================================
UPDATE cultural_events_trads
SET slug = REPLACE(slug, ':', '')
WHERE slug LIKE '%:%';

-- 4. VERIFICAR QUE NO QUEDEN SLUGS CON ':'
-- ============================================
SELECT 
    'DESPUÉS DE LA CORRECCIÓN' as estado,
    COUNT(*) as total_slugs_con_dos_puntos
FROM cultural_events_trads
WHERE slug LIKE '%:%';

-- 5. VERIFICAR TAMBIÉN OTROS CARACTERES PROBLEMÁTICOS
-- ============================================
SELECT 
    'OTROS CARACTERES PROBLEMÁTICOS' as tipo,
    COUNT(*) as total
FROM cultural_events_trads
WHERE slug LIKE '%.%'    -- puntos
   OR slug LIKE '% %'    -- espacios
   OR slug LIKE '%/%'    -- barras
   OR slug LIKE '%?%'    -- interrogación
   OR slug LIKE '%#%'    -- hash
   OR slug LIKE '%&%'    -- ampersand
   OR slug LIKE '%(%'    -- paréntesis
   OR slug LIKE '%)%';   -- paréntesis

-- 6. MOSTRAR DETALLE DE OTROS CARACTERES PROBLEMÁTICOS (SI LOS HAY)
-- ============================================
SELECT 
    cet.event_id,
    cet.language_code,
    cet.slug as slug_actual,
    ce.name as nombre_evento
FROM cultural_events_trads cet
JOIN cultural_events ce ON cet.event_id = ce.id
WHERE cet.slug LIKE '%.%'
   OR cet.slug LIKE '% %'
   OR cet.slug LIKE '%/%'
   OR cet.slug LIKE '%?%'
   OR cet.slug LIKE '%#%'
   OR cet.slug LIKE '%&%'
   OR cet.slug LIKE '%(%'
   OR cet.slug LIKE '%)%'
ORDER BY cet.event_id, cet.language_code;

-- 7. CORREGIR TAMBIÉN PUNTOS (.) EN SLUGS SI LOS HAY
-- ============================================
UPDATE cultural_events_trads
SET slug = REPLACE(slug, '.', '')
WHERE slug LIKE '%.%';

-- 8. CORREGIR ESPACIOS EN SLUGS SI LOS HAY
-- ============================================
UPDATE cultural_events_trads
SET slug = REPLACE(slug, ' ', '-')
WHERE slug LIKE '% %';

-- 9. VERIFICACIÓN FINAL
-- ============================================
SELECT 
    'VERIFICACIÓN FINAL' as estado,
    COUNT(*) as total_slugs_con_caracteres_invalidos
FROM cultural_events_trads
WHERE slug LIKE '%:%'
   OR slug LIKE '%.%'
   OR slug LIKE '% %'
   OR slug LIKE '%/%'
   OR slug LIKE '%?%'
   OR slug LIKE '%#%'
   OR slug LIKE '%&%'
   OR slug LIKE '%(%'
   OR slug LIKE '%)%';

-- 10. MOSTRAR LOS SLUGS DEL EVENTO 2433 (CORPUS CHRISTI) CORREGIDOS
-- ============================================
SELECT 
    'SLUGS DEL EVENTO 2433 (CORPUS CHRISTI) CORREGIDOS' as info,
    cet.language_code,
    cet.slug as slug_corregido,
    cet.name as nombre_traducido
FROM cultural_events_trads cet
WHERE cet.event_id = 2433
ORDER BY cet.language_code;

-- ============================================
-- FIN DEL SCRIPT
-- ============================================
