-- ============================================================
-- PASO 2: TRIGGER en resource_stats (visitas y likes)
--
-- INSTRUCCIONES phpMyAdmin Hostinger:
--   1. Ejecuta PRIMERO el PASO2a (DROP triggers)
--   2. Luego pega SOLO este archivo en la pestaña SQL
--   3. Clic en Ejecutar
--   ⚠️ Un solo CREATE TRIGGER por ejecución
-- ============================================================

CREATE TRIGGER trg_resource_stats_after_update
AFTER UPDATE ON resource_stats
FOR EACH ROW
BEGIN
    DECLARE v_done INT DEFAULT 0;
    DECLARE v_regla_id INT;
    DECLARE v_tipo_tarea VARCHAR(50);
    DECLARE v_plantilla_id INT;
    DECLARE v_requiere_mod TINYINT;
    DECLARE v_cooldown INT;
    DECLARE v_prioridad TINYINT;
    DECLARE v_campo VARCHAR(50);
    DECLARE v_umbral INT;
    DECLARE v_umbral_tipo VARCHAR(20);
    DECLARE v_valor_nuevo INT DEFAULT 0;
    DECLARE v_valor_viejo INT DEFAULT 0;
    DECLARE v_disparar TINYINT DEFAULT 0;
    DECLARE v_existe_cooldown INT DEFAULT 0;

    DECLARE cur CURSOR FOR
        SELECT id, tipo_tarea, plantilla_id, requiere_moderacion,
               cooldown_horas, prioridad, campo_umbral, umbral_valor, umbral_tipo
        FROM reglas_notificacion
        WHERE activa = 1
          AND tabla_origen = 'resource_stats'
          AND evento_tipo = 'UPDATE'
          AND (resource_type_filtro IS NULL OR resource_type_filtro = NEW.resource_type);

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;

    OPEN cur;

    loop_reglas: LOOP
        FETCH cur INTO v_regla_id, v_tipo_tarea, v_plantilla_id, v_requiere_mod,
                       v_cooldown, v_prioridad, v_campo, v_umbral, v_umbral_tipo;

        IF v_done = 1 THEN
            LEAVE loop_reglas;
        END IF;

        SET v_disparar = 0;
        SET v_valor_nuevo = 0;
        SET v_valor_viejo = 0;

        IF v_campo = 'views_count' THEN
            SET v_valor_nuevo = COALESCE(NEW.views_count, 0);
            SET v_valor_viejo = COALESCE(OLD.views_count, 0);
        ELSEIF v_campo = 'favorites_count' THEN
            SET v_valor_nuevo = COALESCE(NEW.favorites_count, 0);
            SET v_valor_viejo = COALESCE(OLD.favorites_count, 0);
        ELSEIF v_campo = 'interests_count' THEN
            SET v_valor_nuevo = COALESCE(NEW.interests_count, 0);
            SET v_valor_viejo = COALESCE(OLD.interests_count, 0);
        END IF;

        IF v_umbral_tipo = 'multiplo' AND v_umbral > 0 THEN
            IF v_valor_nuevo > 0 AND MOD(v_valor_nuevo, v_umbral) = 0 AND MOD(v_valor_viejo, v_umbral) != 0 THEN
                SET v_disparar = 1;
            END IF;
        ELSEIF v_umbral_tipo = 'mayor_igual' AND v_umbral IS NOT NULL THEN
            IF v_valor_nuevo >= v_umbral AND v_valor_viejo < v_umbral THEN
                SET v_disparar = 1;
            END IF;
        ELSEIF v_umbral_tipo = 'igual' AND v_umbral IS NOT NULL THEN
            IF v_valor_nuevo = v_umbral AND v_valor_viejo != v_umbral THEN
                SET v_disparar = 1;
            END IF;
        END IF;

        IF v_disparar = 1 AND v_cooldown > 0 THEN
            SELECT COUNT(*) INTO v_existe_cooldown
            FROM cola_tareas
            WHERE regla_id = v_regla_id
              AND entidad_tipo = NEW.resource_type
              AND entidad_id = NEW.resource_id
              AND creada_en > NOW() - INTERVAL v_cooldown HOUR
              AND estado NOT IN ('error', 'cancelada');

            IF v_existe_cooldown > 0 THEN
                SET v_disparar = 0;
            END IF;
        END IF;

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
END
