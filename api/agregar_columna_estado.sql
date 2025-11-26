-- Script SQL para agregar columna Estado a la tabla de alojamientos
-- Ejecutar este script en la base de datos u412199647_Alojamientos

-- 1. Agregar columna Estado si no existe
ALTER TABLE alojamientos_csv 
ADD COLUMN IF NOT EXISTS Estado VARCHAR(20) DEFAULT 'pendiente';

-- 2. Actualizar alojamientos existentes a estado 'publicado'
-- (Asumimos que los alojamientos ya existentes deben ser visibles)
UPDATE alojamientos_csv 
SET Estado = 'publicado' 
WHERE Estado IS NULL OR Estado = '';

-- 3. Crear índice para mejorar el rendimiento de las consultas
CREATE INDEX IF NOT EXISTS idx_estado ON alojamientos_csv(Estado);

-- 4. Verificar los cambios
SELECT 
    COUNT(*) as total_alojamientos,
    SUM(CASE WHEN Estado = 'publicado' THEN 1 ELSE 0 END) as publicados,
    SUM(CASE WHEN Estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes
