-- Script SQL para agregar las categorías "Gastronomía" y "Restauración" a la tabla categories_places
-- Ejecutar este script en phpMyAdmin o línea de comandos
-- Base de datos: u412199647_Rutas
-- Tabla: categories_places

-- Insertar categoría "Gastronomía"
INSERT INTO categories_places (
    name,
    slug,
    description,
    icon,
    color,
    parent_id,
    display_order,
    is_active,
    created_at,
    updated_at
) VALUES (
    'Gastronomía',
    'gastronomia',
    'Categoría dedicada a la gastronomía local, productos típicos y experiencias culinarias',
    'fas fa-utensils',
    '#e67e22',
    NULL,
    (SELECT COALESCE(MAX(display_order), 0) + 1 FROM categories_places),
    1,
    NOW(),
    NOW()
);

-- Insertar categoría "Enoturismo"
INSERT INTO categories_places (
    name,
    slug,
    description,
    icon,
    color,
    parent_id,
    display_order,
    is_active,
    created_at,
    updated_at
) VALUES (
    'Enoturismo',
    'enoturismo',
    'Categoría dedicada al turismo del vino, bodegas, catas y experiencias enológicas',
    'fas fa-wine-glass',
    '#8e44ad',
    NULL,
    (SELECT COALESCE(MAX(display_order), 0) + 1 FROM categories_places),
    1,
    NOW(),
    NOW()
);

-- Verificar que las categorías se agregaron correctamente
SELECT
    id,
    name,
    slug,
    description,
    icon,
    color,
    display_order,
    is_active
FROM categories_places
WHERE name IN ('Gastronomía', 'Enoturismo')
ORDER BY display_order;
