-- ============================================================
-- PASO 4: TRIGGER en accommodations (nuevo alojamiento)
--
-- INSTRUCCIONES phpMyAdmin Hostinger:
--   1. Pega SOLO este archivo en la pestaña SQL
--   2. Clic en Ejecutar
--   ⚠️ Un solo CREATE TRIGGER por ejecución
--   (El DROP ya se hizo en el PASO2a)
-- ============================================================

CREATE TRIGGER trg_accommodations_after_insert
AFTER INSERT ON accommodations
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
          AND tabla_origen = 'accommodations'
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
END
