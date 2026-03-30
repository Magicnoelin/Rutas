-- ============================================================
-- SISTEMA DE ROLES Y PERFILES DE USUARIO
-- RutasRurales.io
-- ============================================================
-- IMPORTANTE: Este script es ADITIVO. No modifica ni elimina
-- ninguna tabla existente (users, billing_*, subscriptions, etc.)
-- Solo AÑADE nuevas tablas y datos.
-- ============================================================

-- ============================================================
-- 1. TABLA ROLES (catálogo maestro de tipos de usuario)
-- ============================================================
CREATE TABLE IF NOT EXISTS roles (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    slug   VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar roles base (idempotente)
INSERT INTO roles (id, nombre, slug, descripcion) VALUES
-- ── Turistas y visitantes ──────────────────────────────────────────────────
(1,  'Turista',               'turista',               'Viajero que busca alojamientos, rutas y actividades turísticas'),
(2,  'Senderista',            'senderista',            'Aficionado al senderismo y rutas de montaña'),

-- ── Alojamientos y hostelería ─────────────────────────────────────────────
(3,  'Alojamiento',           'alojamiento',           'Propietario o gestor de alojamientos turísticos (casas rurales, hoteles, campings...)'),
(4,  'Restaurante',           'restaurante',           'Propietario o gestor de restaurante con oferta gastronómica local'),
(5,  'Bodega',                'bodega',                'Bodega o productor de vino que ofrece visitas y enoturismo'),

-- ── Eventos y cultura ─────────────────────────────────────────────────────
(6,  'Organizador de Eventos','organizador_eventos',   'Organiza y gestiona eventos culturales, ferias, festivales y actividades'),
(7,  'Actividad Cultural',    'actividad_cultural',    'Ofrece actividades culturales, talleres o experiencias de ocio'),

-- ── Instituciones y organismos ────────────────────────────────────────────
(8,  'Ayuntamiento',          'ayuntamiento',          'Representante de un ayuntamiento o entidad municipal'),
(9,  'Organismo Oficial',     'organismo_oficial',     'Organismo público: diputación, consejería, patronato de turismo...'),
(10, 'Asociación',            'asociacion',            'Asociación cultural, deportiva, vecinal o de desarrollo rural'),

-- ── Creadores y colaboradores ─────────────────────────────────────────────
(11, 'Fotógrafo',             'fotografo',             'Fotógrafo que documenta rutas, paisajes y patrimonio rural'),
(12, 'Creador de Contenido',  'creador_contenido',     'Blogger, youtuber, influencer o periodista de turismo rural'),
(13, 'Colaborador',           'colaborador',           'Persona que colabora con el proyecto de forma general'),

-- ── Servicios complementarios ─────────────────────────────────────────────
(14, 'Guía Turístico',        'guia_turistico',        'Guía oficial o local que ofrece visitas guiadas y rutas'),
(15, 'Artesano',              'artesano',              'Artesano local que vende o expone productos tradicionales'),
(16, 'Agricultor/Ganadero',   'agricultor_ganadero',   'Productor agrícola o ganadero con turismo rural o venta directa'),
(17, 'Empresa de Actividades','empresa_actividades',   'Empresa de turismo activo: senderismo, escalada, kayak, BTT...'),
(18, 'Transporte Turístico',  'transporte_turistico',  'Empresa de transporte turístico, transfers o alquiler de vehículos'),
(19, 'Tienda/Comercio Local', 'comercio_local',        'Tienda o comercio local con productos típicos de la zona'),
(21, 'Cafetería',             'cafeteria',             'Propietario de cafetería o bar de desayunos'),
(22, 'Panadería / Pastelería','panaderia',             'Propietario de panadería, pastelería o confitería'),

-- ── Sistema ───────────────────────────────────────────────────────────────
(20, 'Administrador',         'admin',                 'Administrador del sistema con acceso completo')

ON DUPLICATE KEY UPDATE
    nombre      = VALUES(nombre),
    descripcion = VALUES(descripcion);

-- ============================================================
-- 2. TABLA PIVOT role_user (un usuario puede tener varios roles)
-- ============================================================
CREATE TABLE IF NOT EXISTS role_user (
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id)  ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_role_id (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. PERFIL ALOJAMIENTO (campos específicos de propietarios)
-- ============================================================
CREATE TABLE IF NOT EXISTS profile_alojamientos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL UNIQUE,
    nif         VARCHAR(20)  NULL COMMENT 'NIF/CIF del propietario o empresa',
    razon_social VARCHAR(255) NULL COMMENT 'Nombre legal o razón social',
    direccion   TEXT         NULL,
    municipio   VARCHAR(150) NULL,
    provincia   VARCHAR(100) NULL,
    codigo_postal VARCHAR(10) NULL,
    telefono_negocio VARCHAR(30) NULL,
    web         VARCHAR(255) NULL,
    capacidad_total INT       NULL COMMENT 'Plazas totales gestionadas',
    num_alojamientos INT DEFAULT 0 COMMENT 'Número de alojamientos registrados',
    descripcion_negocio TEXT  NULL,
    logo_url    VARCHAR(500) NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_municipio (municipio),
    INDEX idx_provincia (provincia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. PERFIL TURISTA (campos específicos de turistas)
-- ============================================================
CREATE TABLE IF NOT EXISTS profile_turistas (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL UNIQUE,
    intereses_json  JSON NULL COMMENT 'Array de intereses: naturaleza, cultura, aventura, etc.',
    presupuesto     ENUM('bajo','medio','alto','sin_limite') NULL COMMENT 'Rango de presupuesto preferido',
    duracion_viaje  ENUM('fin_semana','puente','semana','mas_semana') NULL,
    viaja_con       ENUM('solo','pareja','familia','amigos','grupo') NULL,
    provincia_origen VARCHAR(100) NULL,
    pais_origen     VARCHAR(100) DEFAULT 'España',
    idioma_preferido VARCHAR(10) DEFAULT 'es',
    notas           TEXT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_presupuesto (presupuesto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. MIGRAR DATOS EXISTENTES: poblar role_user desde user_type
--    (Solo inserta si no existe ya la relación)
-- ============================================================

-- Turistas existentes
INSERT IGNORE INTO role_user (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN roles r ON r.slug = 'turista'
WHERE u.user_type IN ('turista', 'tourist', 'user')
  AND NOT EXISTS (
      SELECT 1 FROM role_user ru WHERE ru.user_id = u.id AND ru.role_id = r.id
  );

-- Alojamientos existentes
INSERT IGNORE INTO role_user (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN roles r ON r.slug = 'alojamiento'
WHERE u.user_type IN ('alojamiento', 'accommodation', 'host')
  AND NOT EXISTS (
      SELECT 1 FROM role_user ru WHERE ru.user_id = u.id AND ru.role_id = r.id
  );

-- Promotores de eventos existentes
INSERT IGNORE INTO role_user (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN roles r ON r.slug = 'promotor_eventos'
WHERE u.user_type IN ('promotor_eventos', 'promotor')
  AND NOT EXISTS (
      SELECT 1 FROM role_user ru WHERE ru.user_id = u.id AND ru.role_id = r.id
  );

-- Actividades culturales existentes
INSERT IGNORE INTO role_user (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN roles r ON r.slug = 'actividad_cultural'
WHERE u.user_type IN ('actividad_cultural', 'actividad')
  AND NOT EXISTS (
      SELECT 1 FROM role_user ru WHERE ru.user_id = u.id AND ru.role_id = r.id
  );

-- Admins existentes
INSERT IGNORE INTO role_user (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN roles r ON r.slug = 'admin'
WHERE u.user_type IN ('admin', 'administrator', 'superadmin')
  AND NOT EXISTS (
      SELECT 1 FROM role_user ru WHERE ru.user_id = u.id AND ru.role_id = r.id
  );

-- ============================================================
-- 6. MIGRAR PREFERENCIAS EXISTENTES A profile_turistas
--    (Solo si la columna preferences_json existe en users)
-- ============================================================
INSERT IGNORE INTO profile_turistas (user_id, intereses_json)
SELECT u.id, u.preferences_json
FROM users u
WHERE u.preferences_json IS NOT NULL
  AND u.preferences_json != ''
  AND u.preferences_json != 'null'
  AND NOT EXISTS (
      SELECT 1 FROM profile_turistas pt WHERE pt.user_id = u.id
  );

-- ============================================================
-- VERIFICACIÓN FINAL
-- ============================================================
SELECT 'Sistema de Roles creado correctamente' AS resultado;

SELECT 
    r.nombre AS rol,
    COUNT(ru.user_id) AS usuarios_asignados
FROM roles r
LEFT JOIN role_user ru ON ru.role_id = r.id
GROUP BY r.id, r.nombre
ORDER BY r.id;
