-- ============================================================
--  SCRIPT: Añadir suscripcion_nivel a accommodations
--  Ejecutar UNA SOLA VEZ en phpMyAdmin o línea de comandos
-- ============================================================
--
--  Por qué este campo y no solo is_premium (binario):
--    - is_premium solo distingue 0/1. suscripcion_nivel permite
--      escalar a más niveles (1=Gratuito, 2=Básico, 3=Premium,
--      4=Enterprise) sin tocar la lógica de queries.
--    - is_premium se mantiene intacto (ya lo usan traducciones, etc.)
--    - El ORDER BY en las landings solo necesita este número para
--      decidir quién va primero: más alto = más arriba.
-- ============================================================

-- 1. Añadir columna escalable (si no existe)
ALTER TABLE accommodations
    ADD COLUMN suscripcion_nivel TINYINT UNSIGNED NOT NULL DEFAULT 1
    COMMENT '1=Gratuito, 2=Básico, 3=Premium, 4=Enterprise — determina prioridad en landings';

-- 2. Migrar datos desde is_premium existente
UPDATE accommodations SET suscripcion_nivel = 3 WHERE is_premium = 1;
UPDATE accommodations SET suscripcion_nivel = 1 WHERE is_premium = 0;

-- 3. Índice compuesto para que las queries de landing vuelen
--    (cubre WHERE is_active=1 AND province=X → ORDER BY suscripcion_nivel)
ALTER TABLE accommodations
    ADD INDEX idx_accom_landing_prio (suscripcion_nivel, is_active, province);

-- 4. Verificar resultado
SELECT
    suscripcion_nivel,
    COUNT(*) AS total,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS activos
FROM accommodations
GROUP BY suscripcion_nivel
ORDER BY suscripcion_nivel DESC;
