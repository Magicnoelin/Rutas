-- ============================================================
-- AUDITORIA DE SLUGS — places_of_interest_trads
-- ============================================================
-- Ejecuta estas consultas en tu cliente SQL (phpMyAdmin,
-- TablePlus, DBeaver...) ANTES de lanzar el script PHP de
-- regeneracion, para tener una foto del estado actual.
--
-- 25/09/2026 — Generado como parte de la refactorizacion v2
-- ============================================================


-- ── 1. RESUMEN GENERAL ──────────────────────────────────────
-- Cuantos registros hay por idioma
SELECT
    language_code AS idioma,
    COUNT(*)      AS total_registros,
    COUNT(CASE WHEN slug IS NULL OR slug = '' THEN 1 END) AS slugs_vacios
FROM places_of_interest_trads
GROUP BY language_code
ORDER BY language_code;


-- ── 2. SLUGS CON CARACTERES INVALIDOS ───────────────────────
-- Detecta slugs con signos de puntuacion, espacios o mayusculas
SELECT
    t.id,
    t.place_id,
    t.language_code,
    t.slug,
    p.name AS nombre_lugar
FROM places_of_interest_trads t
JOIN places_of_interest p ON t.place_id = p.id
WHERE t.slug REGEXP '[^a-z0-9\-]'   -- cualquier caracter fuera de a-z, 0-9, guion
   OR t.slug LIKE '%---%'            -- guiones triples o mas
   OR t.slug LIKE '-%'               -- empieza por guion
   OR t.slug LIKE '%-'               -- termina en guion
ORDER BY t.language_code, t.place_id;


-- ── 3. SLUGS VACIOS O NULOS ──────────────────────────────────
SELECT
    t.id,
    t.place_id,
    t.language_code,
    t.slug,
    p.name AS nombre_lugar
FROM places_of_interest_trads t
JOIN places_of_interest p ON t.place_id = p.id
WHERE t.slug IS NULL OR t.slug = ''
ORDER BY t.language_code, t.place_id;


-- ── 4. SLUGS DUPLICADOS (mismo slug para distintos lugares) ──
SELECT
    language_code,
    slug,
    COUNT(*) AS veces,
    GROUP_CONCAT(place_id ORDER BY place_id) AS places_ids
FROM places_of_interest_trads
WHERE slug IS NOT NULL AND slug != ''
GROUP BY language_code, slug
HAVING COUNT(*) > 1
ORDER BY language_code, veces DESC;


-- ── 5. POSIBLES MEZCLAS DE IDIOMA EN SLUG ZH ─────────────────
-- Detecta slugs zh que NO empiezan por un prefijo Pinyin conocido
-- (indicio de que se generaron con el prefijo en espanol)
SELECT
    t.id,
    t.place_id,
    t.slug,
    p.name AS nombre_lugar,
    COALESCE(c.name,'') AS categoria
FROM places_of_interest_trads t
JOIN places_of_interest p ON t.place_id = p.id
LEFT JOIN categories_places c ON p.category_id = c.id
WHERE t.language_code = 'zh'
  AND t.slug NOT REGEXP '^(si|xiuyuan|dasheng|shengdi|yinxiuchu|xiaojiaotang|jiaotang|chengbao|gongguan|chengqiang|ta|gongjian|chengmen|qiao|lishi-jiequ|gucheng|guangchang|quantou|mofang|bowuguan|juchang|shichang|kaoguzhi|feixu|jinianbei|shishi-muzhang|luoma-gudao|ziranbaohuqu|guanjingdian|dongxue|hu|haitan|senlin|pubao|jiuzhuang|canting|jiuba|zhuangyuan|difang)'
ORDER BY t.place_id;


-- ── 6. SLUGS QUE AUN CONTIENEN PALABRAS EN ESPANOL ──────────
-- Busca slugs que posiblemente contienen la palabra de categoria
-- en espanol mezclada (ej. "eglise-iglesia-..." o "church-iglesia-...")
SELECT
    t.id,
    t.place_id,
    t.language_code,
    t.slug,
    p.name AS nombre_lugar
FROM places_of_interest_trads t
JOIN places_of_interest p ON t.place_id = p.id
WHERE t.language_code != 'es'   -- solo traducciones (no es)
  AND (
      t.slug LIKE '%-iglesia-%'
   OR t.slug LIKE '%-castillo-%'
   OR t.slug LIKE '%-palacio-%'
   OR t.slug LIKE '%-monasterio-%'
   OR t.slug LIKE '%-catedral-%'
   OR t.slug LIKE '%-ermita-%'
   OR t.slug LIKE '%-capilla-%'
   OR t.slug LIKE '%-convento-%'
   OR t.slug LIKE '%-parque-natural-%'
   OR t.slug LIKE '%-museo-%'    -- 'museo' igual en varios idiomas pero es senial
  )
ORDER BY t.language_code, t.place_id;


-- ── 7. PREVISUALIZAR SLUGS ACTUALES vs NOMBRE PADRE ──────────
-- Vista general para auditar manualmente una muestra
SELECT
    t.id                 AS trad_id,
    t.place_id,
    t.language_code      AS lang,
    p.name               AS nombre_es,
    COALESCE(c.name,'')  AS categoria,
    p.municipality,
    t.slug               AS slug_actual
FROM places_of_interest_trads t
JOIN places_of_interest p ON t.place_id = p.id
LEFT JOIN categories_places c ON p.category_id = c.id
WHERE p.is_active = 1
ORDER BY t.language_code, t.place_id
LIMIT 200;


-- ── 8. SNAPSHOT ANTES DEL UPDATE (ejecutar ANTES del script PHP)
-- Guarda una copia de los slugs actuales en una tabla temporal
-- para poder hacer rollback si fuese necesario.

CREATE TABLE IF NOT EXISTS _backup_slugs_places_trads_20260925 AS
SELECT id, place_id, language_code, slug
FROM places_of_interest_trads;

-- Para restaurar (solo si hay problema tras el UPDATE):
-- UPDATE places_of_interest_trads t
-- JOIN _backup_slugs_places_trads_20260925 b ON t.id = b.id
-- SET t.slug = b.slug;

-- Para eliminar el backup cuando ya no sea necesario:
-- DROP TABLE IF EXISTS _backup_slugs_places_trads_20260925;
