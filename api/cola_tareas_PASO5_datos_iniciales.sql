-- ============================================================
-- PASO 5: DATOS INICIALES (plantillas y reglas)
-- Ejecutar en phpMyAdmin → pestaña SQL
-- ============================================================

-- ── Plantillas de mensaje ────────────────────────────────────

INSERT INTO plantillas_mensaje (nombre, canal, asunto, cuerpo_html, cuerpo_txt) VALUES

('Bienvenida nuevo usuario', 'email',
 'Bienvenido/a a Rutas Rurales, {{nombre}}!',
 '<h2>Hola, {{nombre}}!</h2><p>Bienvenido/a a <strong>Rutas Rurales</strong>, tu plataforma de turismo rural en Castilla y Leon.</p><p>Ya puedes explorar rutas, alojamientos y eventos culturales de toda la region.</p><p><a href="https://rutasrurales.io" style="background:#2d6a4f;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;">Explorar ahora</a></p><p>Un saludo,<br>El equipo de Rutas Rurales</p>',
 'Hola {{nombre}}, bienvenido/a a Rutas Rurales. Ya puedes explorar rutas, alojamientos y eventos en https://rutasrurales.io'),

('Alojamiento - hito de visitas', 'email',
 'Tu alojamiento {{nombre_entidad}} ha alcanzado {{valor_nuevo}} visitas!',
 '<h2>Enhorabuena!</h2><p>Tu alojamiento <strong>{{nombre_entidad}}</strong> ha alcanzado <strong>{{valor_nuevo}} visitas</strong> en Rutas Rurales.</p><p>Cada vez mas viajeros descubren tu espacio. Sigue asi!</p><p><a href="https://rutasrurales.io/{{slug}}" style="background:#2d6a4f;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;">Ver mi alojamiento</a></p><p>Un saludo,<br>El equipo de Rutas Rurales</p>',
 'Enhorabuena! Tu alojamiento {{nombre_entidad}} ha alcanzado {{valor_nuevo}} visitas en Rutas Rurales.'),

('Contenido popular - likes', 'email',
 '{{nombre_entidad}} esta siendo muy popular!',
 '<h2>Tu contenido es tendencia!</h2><p><strong>{{nombre_entidad}}</strong> ha acumulado <strong>{{valor_nuevo}} me gusta</strong> en Rutas Rurales.</p><p>Los viajeros estan enamorandose de tu contenido. Gracias por compartirlo!</p><p><a href="https://rutasrurales.io/{{slug}}" style="background:#2d6a4f;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;">Ver el contenido</a></p><p>Un saludo,<br>El equipo de Rutas Rurales</p>',
 'Tu contenido {{nombre_entidad}} ha alcanzado {{valor_nuevo}} me gusta en Rutas Rurales.'),

('Notificacion interna admin', 'interno',
 'Alerta sistema: {{tipo_tarea}}',
 '<p><strong>Tipo:</strong> {{tipo_tarea}}<br><strong>Entidad:</strong> {{entidad_tipo}} #{{entidad_id}}<br><strong>Valor:</strong> {{valor_nuevo}}<br><strong>Fecha:</strong> {{fecha}}</p>',
 'Alerta: {{tipo_tarea}} | {{entidad_tipo}} #{{entidad_id}} | Valor: {{valor_nuevo}}'),

('Nuevo alojamiento publicado', 'email',
 'Nuevo alojamiento publicado: {{nombre_entidad}}',
 '<h2>Nuevo alojamiento en la plataforma</h2><p>Se ha publicado un nuevo alojamiento: <strong>{{nombre_entidad}}</strong></p><p><strong>Provincia:</strong> {{provincia}}<br><strong>Propietario ID:</strong> {{user_id}}</p><p><a href="https://rutasrurales.io/{{slug}}">Ver alojamiento</a></p>',
 'Nuevo alojamiento: {{nombre_entidad}} en {{provincia}}. Ver: https://rutasrurales.io/{{slug}}');


-- ── Reglas de notificacion ───────────────────────────────────

INSERT INTO reglas_notificacion
(nombre, activa, tabla_origen, evento_tipo, campo_umbral, umbral_valor, umbral_tipo,
 resource_type_filtro, tipo_tarea, plantilla_id, destinatario, requiere_moderacion, cooldown_horas, prioridad)
VALUES

-- Regla 1: Bienvenida al registrarse (automatico)
('Bienvenida nuevo usuario', 1, 'users', 'INSERT',
 NULL, NULL, NULL,
 NULL, 'email_usuario', 1, 'usuario', 0, 0, 2),

-- Regla 2: Alojamiento cada 50 visitas (multiplo: 50, 100, 150...)
('Alerta cada 50 visitas - alojamiento', 1, 'resource_stats', 'UPDATE',
 'views_count', 50, 'multiplo',
 'accommodation', 'email_propietario', 2, 'propietario', 0, 48, 5),

-- Regla 3: Evento cada 50 visitas
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

-- Regla 6: Nuevo alojamiento -> notif admin (desactivada, con moderacion)
('Nuevo alojamiento - notif admin', 0, 'accommodations', 'INSERT',
 NULL, NULL, NULL,
 NULL, 'notif_admin', 5, 'admin', 1, 0, 3);


-- ── Verificacion final ───────────────────────────────────────
SELECT 'plantillas_mensaje' AS tabla, COUNT(*) AS registros FROM plantillas_mensaje
UNION ALL
SELECT 'reglas_notificacion', COUNT(*) FROM reglas_notificacion
UNION ALL
SELECT 'cola_tareas', COUNT(*) FROM cola_tareas
UNION ALL
SELECT 'historial_tareas', COUNT(*) FROM historial_tareas;

-- ✅ PASO 5 completado. Sistema listo.
-- Verifica los triggers con: SHOW TRIGGERS;
