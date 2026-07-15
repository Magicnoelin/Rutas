# Hero Image — Migración de Base de Datos

Ejecutar **UNA SOLA VEZ** en producción. Es seguro: usa `IF NOT EXISTS`.

```sql
-- 1. Campo hero_image en la tabla de eventos (prioridad máxima)
ALTER TABLE `cultural_events`
    ADD COLUMN IF NOT EXISTS `hero_image` VARCHAR(500) NULL DEFAULT NULL
    COMMENT 'Imagen hero específica del evento. NULL = heredar de categoría o fallback temático. Ruta relativa o URL absoluta.'
    AFTER `poster_image`;

-- 2. Campo hero_image en la tabla de categorías (prioridad media)
ALTER TABLE `categories_events`
    ADD COLUMN IF NOT EXISTS `hero_image` VARCHAR(500) NULL DEFAULT NULL
    COMMENT 'Imagen hero por defecto para todos los eventos de esta categoría. NULL = usar fallback temático del filtro activo.'
    AFTER `name`;
```

## Cómo ejecutar

```bash
mysql -u usuario -p nombre_base_datos < HERO_IMAGE_BD.sql
```

O en phpMyAdmin → pestaña SQL → pegar y ejecutar.

## Verificar que funcionó

```sql
SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('cultural_events', 'categories_events')
  AND COLUMN_NAME = 'hero_image';
```

## Lógica de prioridad (implementada en PHP)

```
evento.hero_image      → si tiene valor, se usa ESTA
  └→ categoria.hero_image  → si no, la de la categoría
       └→ filtro activo PHP  → si no, la del filtro/temática (hardcoded)
            └→ fallback global  → /menu_images/turismo_rural.webp
```

## Cómo personalizar una imagen

- **Para un evento concreto**: `UPDATE cultural_events SET hero_image = '/cultural_events_images/mi-imagen.webp' WHERE slug = 'mi-slug';`
- **Para una categoría**: `UPDATE categories_events SET hero_image = '/hero-images/eventos/musica.webp' WHERE id = 7;`
- **Los placeholders** son URLs de Unsplash. Cuando tengas tus propias imágenes `.webp`, actualiza el mapa `$filterHeroMap` en `eventos-landing/api/landing-data.php`.
