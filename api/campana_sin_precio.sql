-- ============================================================
-- CAMPAÑA: Email a propietarios de alojamientos sin precio
-- ============================================================
-- Ejecutar en phpMyAdmin en este orden:
--   1. Este archivo completo (una sola vez)
--   2. Ir a admin_tablas/cola_tareas.php → pestaña Moderación
--   3. Revisar las tareas generadas y aprobar las que quieras
-- ============================================================


-- ── PASO A: Crear la plantilla de email ─────────────────────
-- (Si ya existe con ese nombre, actualiza el contenido)

INSERT INTO plantillas_mensaje (nombre, canal, asunto, cuerpo_html, cuerpo_txt)
VALUES (
  'Alojamiento sin precio - recordatorio',
  'email',
  'Completa tu alojamiento "{{nombre_entidad}}" — falta el precio',
  '
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;">
  <div style="background:#2d6a4f;padding:20px;border-radius:8px 8px 0 0;text-align:center;">
    <h1 style="color:white;margin:0;font-size:22px;">Rutas Rurales</h1>
  </div>
  <div style="background:#ffffff;padding:30px;border:1px solid #e0e0e0;border-top:none;">
    <h2 style="color:#2d6a4f;">Hola, {{nombre}}!</h2>
    <p>Hemos revisado tu alojamiento <strong>{{nombre_entidad}}</strong> y hemos visto que aún no tiene precio configurado.</p>
    <p>Los viajeros que visitan tu página no pueden ver cuánto cuesta alojarse contigo, lo que puede hacer que se vayan sin contactarte.</p>
    <div style="background:#f8f9fa;border-left:4px solid #2d6a4f;padding:15px;margin:20px 0;border-radius:0 4px 4px 0;">
      <strong>¿Por qué es importante añadir el precio?</strong>
      <ul style="margin:10px 0 0 0;padding-left:20px;">
        <li>Los viajeros filtran por precio al buscar alojamiento</li>
        <li>Genera más confianza y más contactos directos</li>
        <li>Mejora tu posición en los resultados de búsqueda</li>
      </ul>
    </div>
    <p>Solo te llevará 2 minutos. Entra en tu panel y actualiza el precio:</p>
    <div style="text-align:center;margin:25px 0;">
      <a href="https://rutasrurales.io/editar-mi-alojamiento.php?id={{entidad_id}}"
         style="background:#2d6a4f;color:white;padding:12px 30px;text-decoration:none;border-radius:6px;font-size:16px;display:inline-block;">
        ✏️ Añadir precio ahora
      </a>
    </div>
    <p style="color:#666;font-size:14px;">Si ya has añadido el precio recientemente, ignora este mensaje. Puede que tardemos unos días en actualizar nuestros registros.</p>
    <hr style="border:none;border-top:1px solid #e0e0e0;margin:20px 0;">
    <p style="color:#999;font-size:12px;text-align:center;">
      Rutas Rurales · Castilla y León<br>
      <a href="https://rutasrurales.io" style="color:#2d6a4f;">rutasrurales.io</a>
    </p>
  </div>
</div>
  ',
  'Hola {{nombre}}, tu alojamiento "{{nombre_entidad}}" no tiene precio configurado. Los viajeros no pueden ver cuánto cuesta alojarse contigo. Añade el precio en: https://rutasrurales.io/editar-mi-alojamiento.php?id={{entidad_id}}'
)
ON DUPLICATE KEY UPDATE
  asunto    = VALUES(asunto),
  cuerpo_html = VALUES(cuerpo_html),
  cuerpo_txt  = VALUES(cuerpo_txt);


-- ── PASO B: Insertar tareas en moderación ───────────────────
-- Una tarea por cada alojamiento activo sin precio
-- Estado = 'moderacion' → tú decides cuáles aprobar y cuándo

INSERT INTO cola_tareas (
    tipo_tarea,
    plantilla_id,
    entidad_tipo,
    entidad_id,
    destinatario_id,
    destinatario_email,
    payload,
    estado,
    requiere_moderacion,
    prioridad
)
SELECT
    'email_propietario'                          AS tipo_tarea,
    (SELECT id FROM plantillas_mensaje
     WHERE nombre = 'Alojamiento sin precio - recordatorio'
     LIMIT 1)                                    AS plantilla_id,
    'accommodation'                              AS entidad_tipo,
    a.id                                         AS entidad_id,
    a.user_id                                    AS destinatario_id,
    u.email                                      AS destinatario_email,
    JSON_OBJECT(
        'accommodation_id', a.id,
        'nombre',           COALESCE(a.name, 'Tu alojamiento'),
        'user_id',          a.user_id,
        'email',            u.email,
        'nombre_usuario',   COALESCE(u.name, u.username, 'Propietario'),
        'slug',             COALESCE(a.slug, ''),
        'provincia',        COALESCE(a.province, '')
    )                                            AS payload,
    'moderacion'                                 AS estado,
    1                                            AS requiere_moderacion,
    5                                            AS prioridad
FROM accommodations a
JOIN users u ON u.id = a.user_id
WHERE
    a.status = 'active'
    AND (
        a.price IS NULL
        OR a.price = 0
        OR a.price = ''
        OR TRIM(a.price) = ''
    )
    -- Evitar duplicados: no insertar si ya hay una tarea pendiente/moderacion para este alojamiento
    AND NOT EXISTS (
        SELECT 1 FROM cola_tareas ct
        WHERE ct.entidad_tipo = 'accommodation'
          AND ct.entidad_id = a.id
          AND ct.tipo_tarea = 'email_propietario'
          AND ct.estado IN ('moderacion', 'pendiente', 'procesando')
    );


-- ── Verificación ─────────────────────────────────────────────
SELECT
    'Alojamientos sin precio (activos)' AS descripcion,
    COUNT(*) AS total
FROM accommodations
WHERE status = 'active'
  AND (price IS NULL OR price = 0 OR TRIM(price) = '')

UNION ALL

SELECT
    'Tareas en moderación generadas',
    COUNT(*)
FROM cola_tareas
WHERE tipo_tarea = 'email_propietario'
  AND estado = 'moderacion'
  AND entidad_tipo = 'accommodation';
