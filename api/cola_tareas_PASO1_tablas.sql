-- ============================================================
-- PASO 1: TABLAS DEL SISTEMA DE COLA DE TAREAS
-- Ejecutar en phpMyAdmin → pestaña SQL
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── Tabla 1: plantillas_mensaje ──────────────────────────────
CREATE TABLE IF NOT EXISTS plantillas_mensaje (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100) NOT NULL,
    canal         ENUM('email','push','sms','interno') NOT NULL DEFAULT 'email',
    asunto        VARCHAR(255) NULL,
    cuerpo_html   TEXT NULL,
    cuerpo_txt    TEXT NULL,
    activa        TINYINT(1) NOT NULL DEFAULT 1,
    creada_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modificada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_canal (canal),
    INDEX idx_activa (activa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabla 2: reglas_notificacion ─────────────────────────────
CREATE TABLE IF NOT EXISTS reglas_notificacion (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    nombre               VARCHAR(150) NOT NULL,
    activa               TINYINT(1) NOT NULL DEFAULT 1,
    tabla_origen         VARCHAR(50) NOT NULL,
    evento_tipo          ENUM('INSERT','UPDATE') NOT NULL,
    campo_umbral         VARCHAR(50) NULL,
    umbral_valor         INT NULL,
    umbral_tipo          ENUM('igual','mayor_igual','multiplo') NULL DEFAULT 'multiplo',
    resource_type_filtro VARCHAR(50) NULL,
    tipo_tarea           VARCHAR(50) NOT NULL,
    plantilla_id         INT NULL,
    destinatario         ENUM('propietario','admin','usuario','todos') NOT NULL DEFAULT 'propietario',
    requiere_moderacion  TINYINT(1) NOT NULL DEFAULT 0,
    cooldown_horas       INT NOT NULL DEFAULT 24,
    prioridad            TINYINT NOT NULL DEFAULT 5,
    creada_en            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modificada_en        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activa (activa),
    INDEX idx_tabla_evento (tabla_origen, evento_tipo),
    FOREIGN KEY (plantilla_id) REFERENCES plantillas_mensaje(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabla 3: cola_tareas ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS cola_tareas (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    regla_id            INT NULL,
    tipo_tarea          VARCHAR(50) NOT NULL,
    plantilla_id        INT NULL,
    entidad_tipo        VARCHAR(50) NULL,
    entidad_id          INT NULL,
    destinatario_id     INT NULL,
    destinatario_email  VARCHAR(255) NULL,
    payload             JSON NULL,
    estado              ENUM('pendiente','moderacion','procesando','completada','error','cancelada') NOT NULL DEFAULT 'pendiente',
    requiere_moderacion TINYINT(1) NOT NULL DEFAULT 0,
    intentos            INT NOT NULL DEFAULT 0,
    max_intentos        INT NOT NULL DEFAULT 3,
    disponible_desde    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    prioridad           TINYINT NOT NULL DEFAULT 5,
    creada_en           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    procesada_en        DATETIME NULL,
    error_msg           TEXT NULL,
    INDEX idx_estado (estado),
    INDEX idx_disponible (disponible_desde),
    INDEX idx_estado_prioridad (estado, prioridad, disponible_desde),
    INDEX idx_entidad (entidad_tipo, entidad_id),
    INDEX idx_regla (regla_id),
    FOREIGN KEY (regla_id) REFERENCES reglas_notificacion(id) ON DELETE SET NULL,
    FOREIGN KEY (plantilla_id) REFERENCES plantillas_mensaje(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabla 4: historial_tareas ────────────────────────────────
CREATE TABLE IF NOT EXISTS historial_tareas (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    tarea_id            INT NOT NULL,
    regla_id            INT NULL,
    tipo_tarea          VARCHAR(50) NOT NULL,
    entidad_tipo        VARCHAR(50) NULL,
    entidad_id          INT NULL,
    destinatario_id     INT NULL,
    destinatario_email  VARCHAR(255) NULL,
    payload             JSON NULL,
    resultado           ENUM('completada','error','cancelada') NOT NULL,
    intentos_realizados INT NOT NULL DEFAULT 1,
    error_msg           TEXT NULL,
    ejecutada_en        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tarea (tarea_id),
    INDEX idx_resultado (resultado),
    INDEX idx_tipo (tipo_tarea),
    INDEX idx_ejecutada (ejecutada_en),
    INDEX idx_entidad (entidad_tipo, entidad_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ✅ PASO 1 completado: 4 tablas creadas
-- Continúa con PASO 2, PASO 3 y PASO 4 (triggers)
-- Luego PASO 5 (datos iniciales)
