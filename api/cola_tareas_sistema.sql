-- ============================================================
-- SISTEMA DE COLA DE TAREAS CON TRIGGERS MYSQL
-- Arquitectura escalable sin crons de Hostinger
-- Base de datos: u412199647_Rutas
-- ============================================================
-- 
-- ORDEN DE EJECUCIÓN:
--   1. Tablas base (este bloque)
--   2. Triggers
--   3. Datos iniciales (reglas + plantillas)
--
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- TABLA 1: plantillas_mensaje
-- Textos de emails/notificaciones editables desde admin
-- ============================================================
CREATE TABLE IF NOT EXISTS plantillas_mensaje (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL COMMENT 'Nombre interno de la plantilla',
    canal       ENUM('email','push','sms','interno') NOT NULL DEFAULT 'email',
    asunto      VARCHAR(255) NULL COMMENT 'Asunto del email (con variables {{variable}})',
    cuerpo_html TEXT NULL COMMENT 'Cuerpo HTML con variables {{nombre}}, {{visitas}}, {{url}}, etc.',
    cuerpo_txt  TEXT NULL COMMENT 'Versión texto plano',
    activa      TINYINT(1) NOT NULL DEFAULT 1,
    creada_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modificada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_canal (canal),
    INDEX idx_activa (activa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Plantillas de mensajes editables desde admin sin tocar código';


-- ============================================================
-- TABLA 2: reglas_notificacion
-- El corazón configurable: define CUÁNDO y QUÉ encolar
-- Se edita desde admin_tablas sin tocar código PHP
-- ============================================================
CREATE TABLE IF NOT EXISTS reglas_notificacion (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(150) NOT NULL COMMENT 'Descripción legible de la regla',
    activa          TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'ON/OFF desde admin',

    -- ¿Qué tabla y evento dispara esta regla?
    tabla_origen    VARCHAR(50) NOT NULL COMMENT 'Tabla MySQL que dispara: resource_stats, users, accommodations...',
    evento_tipo     ENUM('INSERT','UPDATE') NOT NULL COMMENT 'Tipo de evento MySQL',

    -- ¿Qué condición debe cumplirse? (NULL = siempre, ej: INSERT en users)
    campo_umbral    VARCHAR(50) NULL COMMENT 'Campo a evaluar: views_count, favorites_count...',
    umbral_valor    INT NULL COMMENT 'Valor del umbral: 50, 100, 20...',
    umbral_tipo     ENUM('igual','mayor_igual','multiplo') NULL DEFAULT 'multiplo'
                    COMMENT 'multiplo=cada N veces | mayor_igual=solo primera vez | igual=exacto',

    -- ¿Qué tipo de recurso aplica? (NULL = todos)
    resource_type_filtro VARCHAR(50) NULL COMMENT 'Filtrar por resource_type: accommodation, event, route... NULL=todos',

    -- ¿Qué tarea encolar?
    tipo_tarea      VARCHAR(50) NOT NULL COMMENT 'Identificador de la tarea: email_propietario, email_usuario, notif_admin...',
    plantilla_id    INT NULL COMMENT 'FK a plantillas_mensaje',

    -- ¿A quién va dirigida?
    destinatario    ENUM('propietario','admin','usuario','todos') NOT NULL DEFAULT 'propietario',

    -- ¿Requiere que tú la apruebes antes de enviar?
    requiere_moderacion TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=pasa por moderación admin antes de enviarse',

    -- Control de frecuencia (evitar spam)
    cooldown_horas  INT NOT NULL DEFAULT 24 COMMENT 'Horas mínimas entre disparos de esta regla para la misma entidad',

    -- Prioridad de procesamiento
    prioridad       TINYINT NOT NULL DEFAULT 5 COMMENT '1=urgente, 5=normal, 10=baja',

    creada_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modificada_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_activa (activa),
    INDEX idx_tabla_evento (tabla_origen, evento_tipo),
    FOREIGN KEY (plantilla_id) REFERENCES plantillas_mensaje(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Reglas configurables desde admin: cuándo y qué notificar. Sin tocar código.';


-- ============================================================
-- TABLA 3: cola_tareas
-- Las tareas pendientes generadas automáticamente por triggers
-- ============================================================
CREATE TABLE IF NOT EXISTS cola_tareas (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    regla_id        INT NULL COMMENT 'FK a reglas_notificacion (trazabilidad)',
    tipo_tarea      VARCHAR(50) NOT NULL COMMENT 'Tipo de tarea a ejecutar',
    plantilla_id    INT NULL COMMENT 'FK a plantillas_mensaje',

    -- Contexto: qué entidad generó la tarea
    entidad_tipo    VARCHAR(50) NULL COMMENT 'accommodation, event, route, usuario...',
    entidad_id      INT NULL COMMENT 'ID de la entidad',

    -- Destinatario
    destinatario_id INT NULL COMMENT 'user_id del destinatario (NULL=buscar en procesador)',
    destinatario_email VARCHAR(255) NULL COMMENT 'Email directo si se conoce en el trigger',

    -- Datos extra en JSON (flexible, sin alterar estructura)
    payload         JSON NULL COMMENT 'Datos adicionales: {"nombre":"Casa Rural","visitas":50,"url":"..."}',

    -- Estado del ciclo de vida
    estado          ENUM('pendiente','moderacion','procesando','completada','error','cancelada')
                    NOT NULL DEFAULT 'pendiente'
                    COMMENT 'pendiente=lista|moderacion=espera aprobación admin|procesando=en ejecución|completada|error|cancelada',

    requiere_moderacion TINYINT(1) NOT NULL DEFAULT 0,

    -- Control de reintentos
    intentos        INT NOT NULL DEFAULT 0,
    max_intentos    INT NOT NULL DEFAULT 3,

    -- Programación
    disponible_desde DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'No procesar antes de esta fecha',
    prioridad       TINYINT NOT NULL DEFAULT 5 COMMENT '1=urgente, 5=normal, 10=baja',

    -- Timestamps
    creada_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    procesada_en    DATETIME NULL,
    error_msg       TEXT NULL COMMENT 'Último mensaje de error',

    INDEX idx_estado (estado),
    INDEX idx_disponible (disponible_desde),
    INDEX idx_estado_prioridad (estado, prioridad, disponible_desde),
    INDEX idx_entidad (entidad_tipo, entidad_id),
    INDEX idx_regla (regla_id),
    FOREIGN KEY (regla_id) REFERENCES reglas_notificacion(id) ON DELETE SET NULL,
    FOREIGN KEY (plantilla_id) REFERENCES plantillas_mensaje(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Cola de tareas generada automáticamente por triggers MySQL';


-- ============================================================
-- TABLA 4: historial_tareas
-- Log inmutable de todo lo ejecutado (auditoría)
-- ============================================================
CREATE TABLE IF NOT EXISTS historial_tareas (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    tarea_id        INT NOT NULL COMMENT 'ID original en cola_tareas',
    regla_id        INT NULL,
    tipo_tarea      VARCHAR(50) NOT NULL,
    entidad_tipo    VARCHAR(50) NULL,
    entidad_id      INT NULL,
    destinatario_id INT NULL,
    destinatario_email VARCHAR(255) NULL,
    payload         JSON NULL,
    resultado       ENUM('completada','error','cancelada') NOT NULL,
    intentos_realizados INT NOT NULL DEFAULT 1,
    error_msg       TEXT NULL,
    ejecutada_en    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tarea (tarea_id),
    INDEX idx_resultado (resultado),
    INDEX idx_tipo (tipo_tarea),
    INDEX idx_ejecutada (ejecutada_en),
    INDEX idx_entidad (entidad_tipo, entidad_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historial inmutable de tareas ejecutadas. Solo lectura desde admin.';

SET FOREIGN_KEY_CHECKS = 1;


-- ============================================================
-- TRIGGERS
-- ============================================================

-- Eliminar triggers existentes si los hay (para poder re-ejecutar)
DROP TRIGGER IF EXISTS trg_resource_stats_after_update;
DROP TRIGGER IF EXISTS trg_users_after_insert;
DROP TRIGGER IF EXISTS trg_accommodations_after_insert;

DELIMITER $$

-- ─────────────────────────────────────────────────────────────
-- TRIGGER 1: resource_stats → UPDATE
-- Detecta cambios en visitas y likes de cualquier recurso
-- (alojamientos, eventos, rutas, actividades...)
-- ─────────────────────────────────────────────────────────────
CREATE TRIGGER trg_resource_stats_after_update
AFTER UPDATE ON resource_stats
FOR EACH ROW
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_regla_id INT;
    DECLARE v_tipo_tarea VARCHAR(50);
    DECLARE v_plantilla_id INT;
    DECLARE v_destinatario VARCHAR(50);
    DECLARE v_requiere_mod TINYINT;
    DECLARE v_cooldown INT;
    DECLARE v_prioridad TINYINT;
    DECLARE v_campo VARCHAR(50);
    DECLARE v_umbral INT;
    DECLARE v_umbral_tipo VARCHAR(20);
    DECLARE v_resource_filtro VARCHAR(50);
    DECLARE v_valor_nuevo INT;
    DECLARE v_valor_viejo INT;
    DECLARE v_disparar TINYINT DEFAULT 0;

    -- Cursor sobre reglas activas para UPDATE en resource_stats
    DECLARE cur CURSOR FOR
        SELECT id, tipo_tarea, plantilla_id, destinatario, requiere_moderacion,
               cooldown_horas, prioridad, campo_umbral, umbral_valor, umbral_tipo,
               resource_type_filtro
        FROM reglas_notificacion
        WHERE activa = 1
          AND tabla_origen = 'resource_stats'
          AND evento_tipo = 'UPDATE'
          AND (resource_type_filtro IS NULL OR resource_type_filtro = NEW.resource_type);

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO v_regla_id, v_tipo_tarea, v_plantilla_id, v_destinatario,
                       v_requiere_mod, v_cooldown, v_prioridad, v_campo,
                       v_umbral, v_umbral_tipo, v_resource_filtro;

        IF done THEN
            LEAVE read_loop;
        END IF;

        SET v_disparar = 0;

        -- Obtener valores nuevo y viejo según el campo configurado
        IF v_campo = 'views_count' THEN
            SET v_valor_nuevo = COALESCE(NEW.views_count, 0);
            SET v_valor_viejo = COALESCE(OLD.views_count, 0);
        ELSEIF v_campo = 'favorites_count' THEN
            SET v_valor_nuevo = COALESCE(NEW.favorites_count, 0);
            SET v_valor_viejo = COALESCE(OLD.favorites_count, 0);
        ELSEIF v_campo = 'interests_count' THEN
            SET v_valor_nuevo = COALESCE(NEW.interests_count, 0);
            SET v_valor_viejo = COALESCE(OLD.interests_count, 0);
        ELSE
            SET v_valor_nuevo = 0;
            SET v_valor_viejo = 0;
        END IF;

        -- Evaluar condición según tipo de umbral
        IF v_umbral_tipo = 'multiplo' AND v_umbral > 0 THEN
            -- Cada vez que cruza un múltiplo: ej. 50, 100, 150...
            IF v_valor_nuevo > 0
               AND (v_valor_nuevo MOD v_umbral) = 0
               AND (v_valor_viejo MOD v_umbral) != 0 THEN
                SET v_disparar = 1;
            END IF;

        ELSEIF v_umbral_tipo = 'mayor_igual' THEN
            -- Solo la primera vez que supera el umbral
            IF v_valor_nuevo >= v_umbral AND v_valor_viejo < v_umbral THEN
                SET v_disparar = 1;
            END IF;

        ELSEIF v_umbral_tipo = 'igual' THEN
            -- Exactamente ese valor
            IF v_valor_nuevo = v_umbral AND v_valor_viejo != v_umbral THEN
                SET v_disparar = 1;
            END IF;
        END IF;

        -- Verificar cooldown: no repetir si ya hay tarea reciente de esta regla+entidad
        IF v_disparar = 1 AND v_cooldown > 0 THEN
            IF EXISTS (
                SELECT 1 FROM cola_tareas
                WHERE regla_id = v_regla_id
                  AND entidad_tipo = NEW.resource_type
                  AND entidad_id = NEW.resource_id
                  AND creada_en > NOW() - INTERVAL v_cooldown HOUR
                  AND estado NOT IN ('error', 'cancelada')
                LIMIT 1
            ) THEN
                SET v_disparar = 0;
            END IF;
        END IF;

        -- Encolar la tarea
        IF v_disparar = 1 THEN
            INSERT INTO cola_tareas (
                regla_id, tipo_tarea, plantilla_id,
                entidad_tipo, entidad_id,
                payload,
                estado, requiere_moderacion, prioridad
            ) VALUES (
                v_regla_id, v_tipo_tarea, v_plantilla_id,
                NEW.resource_type, NEW.resource_id,
                JSON_OBJECT(
                    'campo', v_campo,
                    'valor_nuevo', v_valor_nuevo,
                    'valor_viejo', v_valor_viejo,
                    'umbral', v_umbral,
                    'resource_type', NEW.resource_type,
                    'resource_id', NEW.resource_id
                ),
                IF(v_requiere_mod = 1, 'moderacion', 'pendiente'),
                v_requiere_mod,
                v_prioridad
            );
        END IF;

    END LOOP;

    CLOSE cur;
END$$


-- ─────────────────────────────────────────────────────────────
-- TRIGGER 2: users → INSERT
-- Detecta nuevos registros de usuarios
-- ─────────────────────────────────────────────────────────────
CREATE TRIGGER trg_users_after_insert
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_regla_id INT;
    DECLARE v_tipo_tarea VARCHAR(50);
    DECLARE v_plantilla_id INT;
    DECLARE v_requiere_mod TINYINT;
    DECLARE v_prioridad TINYINT;

    DECLARE cur CURSOR FOR
        SELECT id, tipo_tarea, plantilla_id, requiere_moderacion, prioridad
        FROM reglas_notificacion
        WHERE activa = 1
          AND tabla_origen = 'users'
          AND evento_tipo = 'INSERT';

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO v_regla_id, v_tipo_tarea, v_plantilla_id, v_requiere_mod, v_prioridad;

        IF done THEN
            LEAVE read_loop;
        END IF;

        INSERT INTO cola_tareas (
            regla_id, tipo_tarea, plantilla_id,
            entidad_tipo, entidad_id,
            destinatario_id, destinatario_email,
            payload,
            estado, requiere_moderacion, prioridad
        ) VALUES (
            v_regla_id, v_tipo_tarea, v_plantilla_id,
            'usuario', NEW.id,
            NEW.id, NEW.email,
            JSON_OBJECT(
                'user_id', NEW.id,
                'email', NEW.email,
                'nombre', COALESCE(NEW.name, NEW.username, 'Viajero'),
                'user_type', COALESCE(NEW.user_type, 'tourist')
            ),
            IF(v_requiere_mod = 1, 'moderacion', 'pendiente'),
            v_requiere_mod,
            v_prioridad
        );

    END LOOP;

    CLOSE cur;
END$$


-- ─────────────────────────────────────────────────────────────
-- TRIGGER 3: accommodations → INSERT
-- Detecta nuevos alojamientos publicados
-- ─────────────────────────────────────────────────────────────
CREATE TRIGGER trg_accommodations_after_insert
AFTER INSERT ON accommodations
FOR EACH ROW
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_regla_id INT;
    DECLARE v_tipo_tarea VARCHAR(50);
    DECLARE v_plantilla_id INT;
    DECLARE v_requiere_mod TINYINT;
    DECLARE v_prioridad TINYINT;

    DECLARE cur CURSOR FOR
        SELECT id, tipo_tarea, plantilla_id, requiere_moderacion, prioridad
        FROM reglas_notificacion
        WHERE activa = 1
          AND tabla_origen = 'accommodations'
          AND evento_tipo = 'INSERT';

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO v_regla_id, v_tipo_tarea, v_plantilla_id, v_requiere_mod, v_prioridad;

        IF done THEN
            LEAVE read_loop;
        END IF;

        INSERT INTO cola_tareas (
            regla_id, tipo_tarea, plantilla_id,
            entidad_tipo, entidad_id,
            destinatario_id,
            payload,
            estado, requiere_moderacion, prioridad
        ) VALUES (
            v_regla_id, v_tipo_tarea, v_plantilla_id,
            'accommodation', NEW.id,
            NEW.user_id,
            JSON_OBJECT(
                'accommodation_id', NEW.id,
                'nombre', NEW.name,
                'user_id', NEW.user_id,
                'provincia', COALESCE(NEW.province, ''),
                'slug', COALESCE(NEW.slug, '')
            ),
            IF(v_requiere_mod = 1, 'moderacion', 'pendiente'),
            v_requiere_mod,
            v_prioridad
        );

    END LOOP;

    CLOSE cur;
END$$

DELIMITER ;


-- ============================================================
-- DATOS INICIALES: Plantillas de mensaje
-- ============================================================

INSERT INTO plantillas_mensaje (nombre, canal, asunto, cuerpo_html, cuerpo_txt) VALUES

-- Plantilla 1: Bienvenida nuevo usuario
('Bienvenida nuevo usuario', 'email',
 '¡Bienvenido/a a Rutas Rurales, {{nombre}}!',
 '<h2>¡Hola, {{nombre}}! 👋</h2>
<p>Bienvenido/a a <strong>Rutas Rurales</strong>, tu plataforma de turismo rural en Castilla y León.</p>
<p>Ya puedes explorar rutas, alojamientos y eventos culturales de toda la región.</p>
<p><a href="https://rutasrurales.io" style="background:#2d6a4f;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;">Explorar ahora</a></p>
<p>Un saludo,<br>El equipo de Rutas Rurales</p>',
 'Hola {{nombre}}, bienvenido/a a Rutas Rurales. Ya puedes explorar rutas, alojamientos y eventos en https://rutasrurales.io'),

-- Plantilla 2: Alojamiento alcanza 50 visitas
('Alojamiento - hito de visitas', 'email',
 '🎉 ¡Tu alojamiento "{{nombre_entidad}}" ha alcanzado {{valor_nuevo}} visitas!',
 '<h2>¡Enhorabuena! 🎉</h2>
<p>Tu alojamiento <strong>{{nombre_entidad}}</strong> ha alcanzado <strong>{{valor_nuevo}} visitas</strong> en Rutas Rurales.</p>
<p>Cada vez más viajeros descubren tu espacio. ¡Sigue así!</p>
<p><a href="https://rutasrurales.io/{{slug}}" style="background:#2d6a4f;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;">Ver mi alojamiento</a></p>
<p>Un saludo,<br>El equipo de Rutas Rurales</p>',
 'Enhorabuena! Tu alojamiento {{nombre_entidad}} ha alcanzado {{valor_nuevo}} visitas en Rutas Rurales.'),

-- Plantilla 3: Ruta/evento con muchos likes
('Contenido popular - likes', 'email',
 '❤️ ¡"{{nombre_entidad}}" está siendo muy popular!',
 '<h2>¡Tu contenido es tendencia! ❤️</h2>
<p><strong>{{nombre_entidad}}</strong> ha acumulado <strong>{{valor_nuevo}} me gusta</strong> en Rutas Rurales.</p>
<p>Los viajeros están enamorándose de tu contenido. ¡Gracias por compartirlo!</p>
<p><a href="https://rutasrurales.io/{{slug}}" style="background:#2d6a4f;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;">Ver el contenido</a></p>
<p>Un saludo,<br>El equipo de Rutas Rurales</p>',
 'Tu contenido {{nombre_entidad}} ha alcanzado {{valor_nuevo}} me gusta en Rutas Rurales.'),

-- Plantilla 4: Notificación interna para admin
('Notificación interna admin', 'interno',
 'Alerta sistema: {{tipo_tarea}}',
 '<p><strong>Tipo:</strong> {{tipo_tarea}}<br>
<strong>Entidad:</strong> {{entidad_tipo}} #{{entidad_id}}<br>
<strong>Valor:</strong> {{valor_nuevo}}<br>
<strong>Fecha:</strong> {{fecha}}</p>',
 'Alerta: {{tipo_tarea}} | {{entidad_tipo}} #{{entidad_id}} | Valor: {{valor_nuevo}}'),

-- Plantilla 5: Nuevo alojamiento publicado (para admin)
('Nuevo alojamiento publicado', 'email',
 '🏠 Nuevo alojamiento publicado: {{nombre_entidad}}',
 '<h2>Nuevo alojamiento en la plataforma</h2>
<p>Se ha publicado un nuevo alojamiento: <strong>{{nombre_entidad}}</strong></p>
<p><strong>Provincia:</strong> {{provincia}}<br>
<strong>Propietario ID:</strong> {{user_id}}</p>
<p><a href="https://rutasrurales.io/{{slug}}">Ver alojamiento</a></p>',
 'Nuevo alojamiento: {{nombre_entidad}} en {{provincia}}. Ver: https://rutasrurales.io/{{slug}}');


-- ============================================================
-- DATOS INICIALES: Reglas de notificación
-- ============================================================

INSERT INTO reglas_notificacion 
(nombre, activa, tabla_origen, evento_tipo, campo_umbral, umbral_valor, umbral_tipo, 
 resource_type_filtro, tipo_tarea, plantilla_id, destinatario, requiere_moderacion, cooldown_horas, prioridad)
VALUES

-- Regla 1: Email de bienvenida al registrarse (automático, sin moderación)
('Bienvenida nuevo usuario', 1, 'users', 'INSERT', 
 NULL, NULL, NULL, 
 NULL, 'email_usuario', 1, 'usuario', 0, 0, 2),

-- Regla 2: Alojamiento alcanza cada 50 visitas (múltiplo: 50, 100, 150...)
('Alerta cada 50 visitas - alojamiento', 1, 'resource_stats', 'UPDATE',
 'views_count', 50, 'multiplo',
 'accommodation', 'email_propietario', 2, 'propietario', 0, 48, 5),

-- Regla 3: Evento alcanza cada 50 visitas
('Alerta cada 50 visitas - evento', 1, 'resource_stats', 'UPDATE',
 'views_count', 50, 'multiplo',
 'event', 'email_propietario', 2, 'propietario', 0, 48, 5),

-- Regla 4: Alojamiento supera 20 likes (primera vez)
('Alojamiento popular - 20 likes', 1, 'resource_stats', 'UPDATE',
 'favorites_count', 20, 'mayor_igual',
 'accommodation', 'email_propietario', 3, 'propietario', 0, 72, 5),

-- Regla 5: Evento supera 20 likes (primera vez)
('Evento popular - 20 likes', 1, 'resource_stats', 'UPDATE',
 'favorites_count', 20, 'mayor_igual',
 'event', 'email_propietario', 3, 'propietario', 0, 72, 5),

-- Regla 6: Nuevo alojamiento publicado → notificar admin (con moderación)
('Nuevo alojamiento - notif admin', 0, 'accommodations', 'INSERT',
 NULL, NULL, NULL,
 NULL, 'notif_admin', 5, 'admin', 1, 0, 3);

-- ============================================================
-- FIN DEL SCRIPT
-- ============================================================
-- Para añadir nuevas reglas sin tocar código:
--   INSERT INTO reglas_notificacion (...) VALUES (...);
--
-- Para cambiar un umbral (ej: de 50 a 30 visitas):
--   UPDATE reglas_notificacion SET umbral_valor = 30 WHERE id = 2;
--
-- Para desactivar una regla temporalmente:
--   UPDATE reglas_notificacion SET activa = 0 WHERE id = 2;
--
-- Para ver tareas pendientes de moderación:
--   SELECT * FROM cola_tareas WHERE estado = 'moderacion' ORDER BY creada_en DESC;
--
-- Para aprobar una tarea desde admin:
--   UPDATE cola_tareas SET estado = 'pendiente' WHERE id = X;
--
-- Para cancelar una tarea:
--   UPDATE cola_tareas SET estado = 'cancelada' WHERE id = X;
-- ============================================================
