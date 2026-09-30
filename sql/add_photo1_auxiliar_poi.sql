-- ============================================================
-- MIGRACIÓN: Añadir campo photo1 a la tabla auxiliar_poi
-- Base de datos : u412199647_Rutas (Hostinger)
-- Archivo       : sql/add_photo1_auxiliar_poi.sql
-- Ejecutar en   : phpMyAdmin → pestaña SQL → pegar y ejecutar
-- SEGURO: No borra ni modifica datos existentes.
-- ============================================================

ALTER TABLE `auxiliar_poi`
    ADD COLUMN IF NOT EXISTS `photo1` VARCHAR(500) NULL
        COMMENT 'Ruta de la foto principal del POI auxiliar (ej: /img/auxiliar_poi/slug/foto.webp)'
        AFTER `description`;

-- Verificación
SELECT 'auxiliar_poi' AS tabla,
       COUNT(*) AS total_registros,
       SUM(photo1 IS NOT NULL) AS con_foto
FROM auxiliar_poi;
