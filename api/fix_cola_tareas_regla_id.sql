-- ============================================================
-- FIX COMPLETO: Añadir todas las columnas faltantes a cola_tareas
-- Error: 1054 Unknown column 'regla_id' / 'tipo_tarea' / etc.
--
-- INSTRUCCIONES:
--   1. Abre phpMyAdmin en Hostinger
--   2. Selecciona la base de datos correcta
--   3. Pega este script en la pestaña SQL
--   4. Clic en Ejecutar
-- ============================================================

ALTER TABLE cola_tareas
    ADD COLUMN IF NOT EXISTS regla_id            INT NULL          AFTER id,
    ADD COLUMN IF NOT EXISTS tipo_tarea          VARCHAR(50) NULL  AFTER regla_id,
    ADD COLUMN IF NOT EXISTS plantilla_id        INT NULL          AFTER tipo_tarea,
    ADD COLUMN IF NOT EXISTS entidad_tipo        VARCHAR(50) NULL  AFTER plantilla_id,
    ADD COLUMN IF NOT EXISTS entidad_id          INT NULL          AFTER entidad_tipo,
    ADD COLUMN IF NOT EXISTS destinatario_id     INT NULL          AFTER entidad_id,
    ADD COLUMN IF NOT EXISTS destinatario_email  VARCHAR(255) NULL AFTER destinatario_id,
    ADD COLUMN IF NOT EXISTS payload             JSON NULL         AFTER destinatario_email,
    ADD COLUMN IF NOT EXISTS estado              VARCHAR(20) NOT NULL DEFAULT 'pendiente' AFTER payload,
    ADD COLUMN IF NOT EXISTS requiere_moderacion TINYINT(1) NOT NULL DEFAULT 0 AFTER estado,
    ADD COLUMN IF NOT EXISTS intentos            INT NOT NULL DEFAULT 0 AFTER requiere_moderacion,
    ADD COLUMN IF NOT EXISTS max_intentos        INT NOT NULL DEFAULT 3 AFTER intentos,
    ADD COLUMN IF NOT EXISTS disponible_desde    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER max_intentos,
    ADD COLUMN IF NOT EXISTS prioridad           TINYINT NOT NULL DEFAULT 5 AFTER disponible_desde,
    ADD COLUMN IF NOT EXISTS creada_en           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER prioridad,
    ADD COLUMN IF NOT EXISTS procesada_en        DATETIME NULL AFTER creada_en,
    ADD COLUMN IF NOT EXISTS error_msg           TEXT NULL AFTER procesada_en;

-- Índices (se ignoran si ya existen)
ALTER TABLE cola_tareas
    ADD INDEX IF NOT EXISTS idx_regla    (regla_id),
    ADD INDEX IF NOT EXISTS idx_estado   (estado),
    ADD INDEX IF NOT EXISTS idx_entidad  (entidad_tipo, entidad_id);

-- ✅ Verificación: muestra la estructura completa
DESCRIBE cola_tareas;
