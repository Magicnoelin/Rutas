<?php
/**
 * PROCESADOR DE COLA DE TAREAS
 * ============================================================
 * Ejecuta las tareas pendientes de la tabla cola_tareas.
 * 
 * CÓMO LLAMARLO:
 *   - Desde admin_tablas: botón "Procesar cola ahora"
 *   - URL directa (con token): /api/procesar_cola.php?token=TU_TOKEN
 *   - Desde CLI en servidor: php procesar_cola.php
 * 
 * NO necesita cron. Se llama manualmente o desde admin.
 * 
 * SEGURIDAD: Requiere token secreto o sesión admin activa.
 * ============================================================
 */

define('API_NO_HEADERS', true);
require_once __DIR__ . '/config.php';

// ─── Configuración ────────────────────────────────────────────
define('COLA_TOKEN',        'RutasRurales_Cola_2026_$ecret');  // Cambiar en producción
define('COLA_MAX_TAREAS',   10);    // Máximo de tareas por ejecución
define('COLA_TIMEOUT',      30);    // Segundos máximos de ejecución
define('COLA_EMAIL_FROM',   'noreply@rutasrurales.io');
define('COLA_EMAIL_NAME',   'Rutas Rurales');
define('COLA_ADMIN_EMAIL',  'hola@rutasrurales.io');

// ─── Seguridad: verificar token o sesión admin ────────────────
$esCLI = (php_sapi_name() === 'cli');

if (!$esCLI) {
    header('Content-Type: application/json; charset=utf-8');
    
    $tokenRecibido = $_GET['token'] ?? '';
    $esAdmin = false;
    
    // Verificar token en URL
    if ($tokenRecibido === COLA_TOKEN) {
        $esAdmin = true;
    }
    
    // Verificar sesión admin (si viene desde admin_tablas)
    if (!$esAdmin) {
        session_start();
        if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $esAdmin = true;
        }
    }
    
    if (!$esAdmin) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado']);
        exit;
    }
}

// ─── Límite de tiempo de ejecución ───────────────────────────
set_time_limit(COLA_TIMEOUT + 10);
$tiempoInicio = microtime(true);

// ─── Conectar a BD ───────────────────────────────────────────
$pdo = getDBConnection();

// ─── Resultado acumulado ──────────────────────────────────────
$resultado = [
    'procesadas'  => 0,
    'completadas' => 0,
    'errores'     => 0,
    'omitidas'    => 0,
    'detalle'     => []
];

// ─── Obtener y bloquear tareas pendientes ────────────────────
// UPDATE atómico para evitar que dos procesos cojan la misma tarea
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("
        SELECT id FROM cola_tareas
        WHERE estado = 'pendiente'
          AND disponible_desde <= NOW()
          AND intentos < max_intentos
        ORDER BY prioridad ASC, creada_en ASC
        LIMIT " . COLA_MAX_TAREAS . "
        FOR UPDATE
    ");
    $stmt->execute();
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("
            UPDATE cola_tareas 
            SET estado = 'procesando', intentos = intentos + 1
            WHERE id IN ($placeholders)
        ")->execute($ids);
    }
    
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    salir_con_error('Error al bloquear tareas: ' . $e->getMessage());
}

if (empty($ids)) {
    salir(['mensaje' => 'No hay tareas pendientes', 'procesadas' => 0]);
}

// ─── Cargar tareas completas ──────────────────────────────────
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("
    SELECT ct.*, 
           pm.asunto, pm.cuerpo_html, pm.cuerpo_txt, pm.canal
    FROM cola_tareas ct
    LEFT JOIN plantillas_mensaje pm ON pm.id = ct.plantilla_id AND pm.activa = 1
    WHERE ct.id IN ($placeholders)
    ORDER BY ct.prioridad ASC, ct.creada_en ASC
");
$stmt->execute($ids);
$tareas = $stmt->fetchAll();

// ─── Procesar cada tarea ──────────────────────────────────────
foreach ($tareas as $tarea) {
    
    // Verificar timeout global
    if ((microtime(true) - $tiempoInicio) > COLA_TIMEOUT) {
        // Devolver a pendiente las que no se procesaron
        $pdo->prepare("
            UPDATE cola_tareas SET estado = 'pendiente', intentos = intentos - 1
            WHERE id = ?
        ")->execute([$tarea['id']]);
        $resultado['omitidas']++;
        continue;
    }
    
    $resultado['procesadas']++;
    $payload = json_decode($tarea['payload'] ?? '{}', true) ?: [];
    
    try {
        // Resolver destinatario si no está en la tarea
        $emailDestinatario = $tarea['destinatario_email'];
        $nombreDestinatario = $payload['nombre'] ?? 'Viajero';
        
        if (empty($emailDestinatario) && !empty($tarea['destinatario_id'])) {
            $u = $pdo->prepare("SELECT email, name, username FROM users WHERE id = ?");
            $u->execute([$tarea['destinatario_id']]);
            $usuario = $u->fetch();
            if ($usuario) {
                $emailDestinatario = $usuario['email'];
                $nombreDestinatario = $usuario['name'] ?? $usuario['username'] ?? 'Viajero';
            }
        }
        
        // Si es email_propietario, buscar el propietario de la entidad
        if ($tarea['tipo_tarea'] === 'email_propietario' && empty($emailDestinatario)) {
            $emailDestinatario = buscar_email_propietario($pdo, $tarea['entidad_tipo'], $tarea['entidad_id'], $payload);
            $nombreDestinatario = $payload['nombre_propietario'] ?? 'Propietario';
        }
        
        // Si es notif_admin, usar email de admin
        if ($tarea['tipo_tarea'] === 'notif_admin') {
            $emailDestinatario = COLA_ADMIN_EMAIL;
            $nombreDestinatario = 'Admin';
        }
        
        // Enriquecer payload con datos de la entidad
        $payload = enriquecer_payload($pdo, $tarea['entidad_tipo'], $tarea['entidad_id'], $payload);
        $payload['nombre_destinatario'] = $nombreDestinatario;
        $payload['fecha'] = date('d/m/Y H:i');
        $payload['tipo_tarea'] = $tarea['tipo_tarea'];
        $payload['entidad_tipo'] = $tarea['entidad_tipo'] ?? '';
        $payload['entidad_id'] = $tarea['entidad_id'] ?? '';
        
        // Ejecutar según canal
        $canal = $tarea['canal'] ?? 'email';
        $exito = false;
        $mensajeResultado = '';
        
        switch ($canal) {
            case 'email':
                if (empty($emailDestinatario)) {
                    throw new Exception('No se encontró email del destinatario');
                }
                $asunto  = sustituir_variables($tarea['asunto'] ?? 'Notificación de Rutas Rurales', $payload);
                $cuerpoH = sustituir_variables($tarea['cuerpo_html'] ?? '', $payload);
                $cuerpoT = sustituir_variables($tarea['cuerpo_txt'] ?? strip_tags($cuerpoH), $payload);
                
                $exito = enviar_email($emailDestinatario, $nombreDestinatario, $asunto, $cuerpoH, $cuerpoT);
                $mensajeResultado = "Email enviado a $emailDestinatario";
                break;
                
            case 'interno':
                // Guardar como notificación interna en la tabla notifications si existe
                $exito = guardar_notificacion_interna($pdo, $tarea, $payload);
                $mensajeResultado = 'Notificación interna guardada';
                break;
                
            default:
                // Canal desconocido: marcar como completada igualmente (no bloquear la cola)
                $exito = true;
                $mensajeResultado = "Canal '$canal' no implementado, tarea omitida";
        }
        
        if ($exito) {
            // Marcar como completada
            $pdo->prepare("
                UPDATE cola_tareas 
                SET estado = 'completada', procesada_en = NOW()
                WHERE id = ?
            ")->execute([$tarea['id']]);
            
            // Guardar en historial
            guardar_historial($pdo, $tarea, 'completada', null);
            
            $resultado['completadas']++;
            $resultado['detalle'][] = ['id' => $tarea['id'], 'estado' => 'ok', 'msg' => $mensajeResultado];
        } else {
            throw new Exception('La ejecución de la tarea devolvió false');
        }
        
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        
        // ¿Quedan reintentos?
        if ($tarea['intentos'] >= $tarea['max_intentos']) {
            // Sin más reintentos: marcar como error definitivo
            $pdo->prepare("
                UPDATE cola_tareas 
                SET estado = 'error', procesada_en = NOW(), error_msg = ?
                WHERE id = ?
            ")->execute([$errorMsg, $tarea['id']]);
            
            guardar_historial($pdo, $tarea, 'error', $errorMsg);
        } else {
            // Volver a pendiente con retraso exponencial
            $retrasoMinutos = pow(2, $tarea['intentos']) * 5; // 5, 10, 20 min...
            $pdo->prepare("
                UPDATE cola_tareas 
                SET estado = 'pendiente',
                    disponible_desde = NOW() + INTERVAL ? MINUTE,
                    error_msg = ?
                WHERE id = ?
            ")->execute([$retrasoMinutos, $errorMsg, $tarea['id']]);
        }
        
        $resultado['errores']++;
        $resultado['detalle'][] = ['id' => $tarea['id'], 'estado' => 'error', 'msg' => $errorMsg];
        
        error_log("[procesar_cola] Error tarea #{$tarea['id']} ({$tarea['tipo_tarea']}): $errorMsg");
    }
}

$resultado['tiempo_ms'] = round((microtime(true) - $tiempoInicio) * 1000);
salir($resultado);


// ============================================================
// FUNCIONES AUXILIARES
// ============================================================

/**
 * Sustituye variables {{variable}} en una plantilla con los valores del payload
 */
function sustituir_variables(string $texto, array $payload): string {
    foreach ($payload as $clave => $valor) {
        if (!is_array($valor) && !is_object($valor)) {
            $texto = str_replace('{{' . $clave . '}}', (string)$valor, $texto);
        }
    }
    // Limpiar variables no sustituidas
    $texto = preg_replace('/\{\{[^}]+\}\}/', '', $texto);
    return $texto;
}

/**
 * Enriquece el payload con datos reales de la entidad (nombre, slug, etc.)
 */
function enriquecer_payload(PDO $pdo, ?string $tipo, ?int $id, array $payload): array {
    if (empty($tipo) || empty($id)) return $payload;
    
    try {
        switch ($tipo) {
            case 'accommodation':
                $stmt = $pdo->prepare("SELECT name, slug, province, user_id FROM accommodations WHERE id = ?");
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                if ($row) {
                    $payload['nombre_entidad'] = $row['name'] ?? '';
                    $payload['slug']           = $row['slug'] ?? '';
                    $payload['provincia']      = $row['province'] ?? '';
                    $payload['user_id']        = $row['user_id'] ?? '';
                    $payload['url']            = 'https://rutasrurales.io/' . ($row['slug'] ?? '');
                    // Rellenar nombre del propietario si no viene ya en el payload
                    if (empty($payload['nombre']) && !empty($row['user_id'])) {
                        $stmtU = $pdo->prepare("SELECT name, username FROM users WHERE id = ?");
                        $stmtU->execute([$row['user_id']]);
                        $usr = $stmtU->fetch();
                        if ($usr) {
                            $payload['nombre'] = $usr['name'] ?? $usr['username'] ?? 'Propietario';
                        }
                    }
                    // Alias: nombre_usuario → nombre (si viene del payload de campaña)
                    if (empty($payload['nombre']) && !empty($payload['nombre_usuario'])) {
                        $payload['nombre'] = $payload['nombre_usuario'];
                    }
                }
                break;
                
            case 'event':
                $stmt = $pdo->prepare("SELECT title, slug, user_id FROM cultural_events WHERE id = ?");
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                if ($row) {
                    $payload['nombre_entidad'] = $row['title'] ?? '';
                    $payload['slug']           = $row['slug'] ?? '';
                    $payload['user_id']        = $row['user_id'] ?? '';
                    $payload['url']            = 'https://rutasrurales.io/evento/' . ($row['slug'] ?? '');
                }
                break;
                
            case 'route':
                $stmt = $pdo->prepare("SELECT name, slug, user_id FROM routes WHERE id = ?");
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                if ($row) {
                    $payload['nombre_entidad'] = $row['name'] ?? '';
                    $payload['slug']           = $row['slug'] ?? '';
                    $payload['user_id']        = $row['user_id'] ?? '';
                    $payload['url']            = 'https://rutasrurales.io/ruta/' . ($row['slug'] ?? '');
                }
                break;
                
            case 'usuario':
                $stmt = $pdo->prepare("SELECT name, username, email FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                if ($row) {
                    $payload['nombre']  = $row['name'] ?? $row['username'] ?? 'Viajero';
                    $payload['email']   = $row['email'] ?? '';
                }
                break;
        }
    } catch (Exception $e) {
        // No bloquear por error de enriquecimiento
        error_log("[procesar_cola] Error enriqueciendo payload: " . $e->getMessage());
    }
    
    return $payload;
}

/**
 * Busca el email del propietario de una entidad
 */
function buscar_email_propietario(PDO $pdo, ?string $tipo, ?int $id, array $payload): string {
    if (empty($tipo) || empty($id)) return '';
    
    try {
        $userId = null;
        
        switch ($tipo) {
            case 'accommodation':
                $stmt = $pdo->prepare("SELECT user_id FROM accommodations WHERE id = ?");
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                $userId = $row['user_id'] ?? null;
                break;
                
            case 'event':
                $stmt = $pdo->prepare("SELECT user_id FROM cultural_events WHERE id = ?");
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                $userId = $row['user_id'] ?? null;
                break;
                
            case 'route':
                $stmt = $pdo->prepare("SELECT user_id FROM routes WHERE id = ?");
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                $userId = $row['user_id'] ?? null;
                break;
        }
        
        if ($userId) {
            $stmt = $pdo->prepare("SELECT email, name FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $usuario = $stmt->fetch();
            return $usuario['email'] ?? '';
        }
    } catch (Exception $e) {
        error_log("[procesar_cola] Error buscando propietario: " . $e->getMessage());
    }
    
    return '';
}

/**
 * Envía un email usando mail() nativo de PHP
 * (Compatible con Hostinger sin configuración extra)
 */
function enviar_email(string $para, string $nombrePara, string $asunto, string $html, string $texto): bool {
    $boundary = md5(uniqid());
    
    $headers  = "From: " . COLA_EMAIL_NAME . " <" . COLA_EMAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . COLA_EMAIL_FROM . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    $headers .= "X-Mailer: RutasRurales-Cola/1.0\r\n";
    
    $body  = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($texto)) . "\r\n";
    
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($html)) . "\r\n";
    
    $body .= "--$boundary--";
    
    $paraFormateado = "=?UTF-8?B?" . base64_encode($nombrePara) . "?= <$para>";
    $asuntoFormateado = "=?UTF-8?B?" . base64_encode($asunto) . "?=";
    
    return mail($paraFormateado, $asuntoFormateado, $body, $headers);
}

/**
 * Guarda una notificación interna en la tabla notifications (si existe)
 */
function guardar_notificacion_interna(PDO $pdo, array $tarea, array $payload): bool {
    try {
        // Verificar si existe la tabla notifications
        $existe = $pdo->query("SHOW TABLES LIKE 'notifications'")->rowCount() > 0;
        if (!$existe) return true; // No hay tabla, pero no es un error
        
        $userId = $tarea['destinatario_id'] ?? null;
        if (!$userId) return true;
        
        $titulo  = sustituir_variables($tarea['asunto'] ?? 'Notificación del sistema', $payload);
        $mensaje = sustituir_variables($tarea['cuerpo_txt'] ?? '', $payload);
        
        $pdo->prepare("
            INSERT INTO notifications (user_id, notification_type, title, message)
            VALUES (?, 'system', ?, ?)
        ")->execute([$userId, $titulo, $mensaje]);
        
        return true;
    } catch (Exception $e) {
        error_log("[procesar_cola] Error notificación interna: " . $e->getMessage());
        return false;
    }
}

/**
 * Guarda el resultado en historial_tareas
 */
function guardar_historial(PDO $pdo, array $tarea, string $resultado, ?string $errorMsg): void {
    try {
        $pdo->prepare("
            INSERT INTO historial_tareas 
            (tarea_id, regla_id, tipo_tarea, entidad_tipo, entidad_id, 
             destinatario_id, destinatario_email, payload, resultado, 
             intentos_realizados, error_msg)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $tarea['id'],
            $tarea['regla_id'],
            $tarea['tipo_tarea'],
            $tarea['entidad_tipo'],
            $tarea['entidad_id'],
            $tarea['destinatario_id'],
            $tarea['destinatario_email'],
            $tarea['payload'],
            $resultado,
            $tarea['intentos'],
            $errorMsg
        ]);
    } catch (Exception $e) {
        error_log("[procesar_cola] Error guardando historial: " . $e->getMessage());
    }
}

/**
 * Salida JSON o texto según contexto
 */
function salir(array $datos): void {
    if (php_sapi_name() === 'cli') {
        echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    }
    exit;
}

function salir_con_error(string $msg): void {
    error_log("[procesar_cola] $msg");
    salir(['error' => $msg]);
}
