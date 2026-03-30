-- Script SQL para activar todos los alojamientos de Soria
-- Este script establece is_active = 1 para todos los alojamientos de la provincia de Soria

-- 1. Ver estado actual (ANTES de actualizar)
SELECT 
    'ANTES DE ACTUALIZAR' as Estado,
    COUNT(*) as Total,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as Activos,
    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as Inactivos
FROM accommodations 
WHERE province = 'Soria';

-- 2. Ver detalle de alojamientos inactivos en Soria
SELECT id, name, municipality, is_active 
FROM accommodations 
WHERE province = 'Soria' AND is_active = 0
ORDER BY name;

-- 3. ACTIVAR todos los alojamientos de Soria
UPDATE accommodations 
SET is_active = 1 
WHERE province = 'Soria';

-- 4. Ver estado después de actualizar
SELECT 
    'DESPUÉS DE ACTUALIZAR' as Estado,
    COUNT(*) as Total,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as Activos,
    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as Inactivos
FROM accommodations 
WHERE province = 'Soria';

-- 5. Ver todos los alojamientos de Soria (debería mostrar todos activos)
SELECT id, name, municipality, is_active 
FROM accommodations 
WHERE province = 'Soria'
ORDER BY name;
