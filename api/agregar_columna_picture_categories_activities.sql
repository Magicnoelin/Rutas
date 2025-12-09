-- Script SQL para agregar la columna Picture a la tabla categories_activities
-- Ejecutar este script en phpMyAdmin o línea de comandos

USE u412199647_Rutas;

-- Agregar columna Picture si no existe
ALTER TABLE categories_activities
ADD COLUMN IF NOT EXISTS Picture VARCHAR(500) DEFAULT NULL;

-- Crear índice para mejorar rendimiento (opcional)
CREATE INDEX IF NOT EXISTS idx_picture ON categories_activities(Picture);

-- Verificar que la columna se agregó correctamente
DESCRIBE categories_activities;
