-- ============================================================
-- CORRECCIÓN DE ROLES EN BASE DE DATOS
-- Problema detectado: id=2 y id=3 tienen datos cruzados/incorrectos
-- ============================================================

-- Ver estado actual antes de corregir
SELECT id, nombre, slug, descripcion FROM roles ORDER BY id LIMIT 10;

-- ── Corrección de registros con datos incorrectos ─────────────────────────

-- Corregir id=2: debe ser Senderista (según el SQL original)
-- Pero si ya existe un registro con slug='alojamiento' en otro id, ajustar
-- Primero: limpiar duplicados y corregir

-- Paso 1: Actualizar id=2 para que sea correcto (Senderista)
UPDATE roles SET
    nombre      = 'Senderista',
    slug        = 'senderista',
    descripcion = 'Aficionado al senderismo y rutas de montaña'
WHERE id = 2 AND slug IN ('alojamiento', 'senderista');

-- Paso 2: Actualizar id=3 para que sea correcto (Alojamiento)
UPDATE roles SET
    nombre      = 'Alojamiento',
    slug        = 'alojamiento',
    descripcion = 'Propietario o gestor de alojamientos turísticos (casas rurales, hoteles, campings...)'
WHERE id = 3 AND slug IN ('promotor_eventos', 'alojamiento');

-- Paso 3: Asegurarse de que todos los roles base están correctos
-- (usa INSERT ... ON DUPLICATE KEY UPDATE para no romper nada)

INSERT INTO roles (id, nombre, slug, descripcion) VALUES
(1,  'Turista',               'turista',               'Viajero que busca alojamientos, rutas y actividades turísticas'),
(2,  'Senderista',            'senderista',            'Aficionado al senderismo y rutas de montaña'),
(3,  'Alojamiento',           'alojamiento',           'Propietario o gestor de alojamientos turísticos (casas rurales, hoteles, campings...)'),
(4,  'Restaurante',           'restaurante',           'Propietario o gestor de restaurante con oferta gastronómica local'),
(5,  'Bodega',                'bodega',                'Bodega o productor de vino que ofrece visitas y enoturismo'),
(6,  'Organizador de Eventos','organizador_eventos',   'Organiza y gestiona eventos culturales, ferias, festivales y actividades'),
(7,  'Actividad Cultural',    'actividad_cultural',    'Ofrece actividades culturales, talleres o experiencias de ocio'),
(8,  'Ayuntamiento',          'ayuntamiento',          'Representante de un ayuntamiento o entidad municipal'),
(9,  'Organismo Oficial',     'organismo_oficial',     'Organismo público: diputación, consejería, patronato de turismo...'),
(10, 'Asociación',            'asociacion',            'Asociación cultural, deportiva, vecinal o de desarrollo rural'),
(11, 'Fotógrafo',             'fotografo',             'Fotógrafo que documenta rutas, paisajes y patrimonio rural'),
(12, 'Creador de Contenido',  'creador_contenido',     'Blogger, youtuber, influencer o periodista de turismo rural'),
(13, 'Colaborador',           'colaborador',           'Persona que colabora con el proyecto de forma general'),
(14, 'Guía Turístico',        'guia_turistico',        'Guía oficial o local que ofrece visitas guiadas y rutas'),
(15, 'Artesano',              'artesano',              'Artesano local que vende o expone productos tradicionales'),
(16, 'Agricultor/Ganadero',   'agricultor_ganadero',   'Productor agrícola o ganadero con turismo rural o venta directa'),
(17, 'Empresa de Actividades','empresa_actividades',   'Empresa de turismo activo: senderismo, escalada, kayak, BTT...'),
(18, 'Transporte Turístico',  'transporte_turistico',  'Empresa de transporte turístico, transfers o alquiler de vehículos'),
(19, 'Tienda/Comercio Local', 'comercio_local',        'Tienda o comercio local con productos típicos de la zona'),
(20, 'Administrador',         'admin',                 'Administrador del sistema con acceso completo'),
(21, 'Cafetería',             'cafeteria',             'Propietario de cafetería o bar de desayunos'),
(22, 'Panadería / Pastelería','panaderia',             'Propietario de panadería, pastelería o confitería'),
(23, 'Bar',                   'bar',                   'Propietario de bar o taberna con oferta de tapas y bebidas')
ON DUPLICATE KEY UPDATE
    nombre      = VALUES(nombre),
    slug        = VALUES(slug),
    descripcion = VALUES(descripcion);

-- ── Verificación final ────────────────────────────────────────────────────
SELECT id, nombre, slug FROM roles ORDER BY id;
