-- ============================================================
-- PASO 3: TRIGGER en users (bienvenida al registrarse)
--
-- INSTRUCCIONES phpMyAdmin Hostinger:
--   1. Pega SOLO este archivo en la pestaña SQL
--   2. Clic en Ejecutar
--   ⚠️ Un solo CREATE TRIGGER por ejecución
--   (El DROP ya se hizo en el PASO2a)
-- ============================================================

CREATE TRIGGER trg_users_after_insert
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    DECLARE v_done INT DEFAULT 0;
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

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;

    OPEN cur;

    loop_reglas: LOOP
        FETCH cur INTO v_regla_id, v_tipo_tarea, v_plantilla_id, v_requiere_mod, v_prioridad;

        IF v_done = 1 THEN
            LEAVE loop_reglas;
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
END
