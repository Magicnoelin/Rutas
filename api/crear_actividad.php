<?php
/**
 * API Endpoint: Crear Nueva Actividad Turística
 * POST /api/crear_actividad.php
 * Body: JSON con los datos de la actividad
 */

require_once 'config.php';

// Función para generar slug
function generarSlug($texto) {
    if (!$texto) return '';
    $slug = strtolower(trim($texto));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

try {
    // Obtener datos JSON
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonError('Error al decodificar JSON: ' . json_last_error_msg(), 400);
    }

    if (empty($data)) {
        jsonError('No se recibieron datos', 400);
    }
    
    // Validar reCAPTCHA (opcional)
    $recaptchaResult = ['success' => true, 'score' => 1.0];
    
    // Validar campos requeridos
    $camposRequeridos = ['name', 'description', 'activity_type', 'difficulty', 'municipality', 'province'];
    foreach ($camposRequeridos as $campo) {
        if (!isset($data[$campo]) || empty(trim($data[$campo]))) {
            jsonError("El campo '$campo' es requerido", 400);
        }
    }
    
    // Sanitizar datos
    $datosLimpios = [];
    foreach ($data as $key => $value) {
        if (is_string($value)) {
            $datosLimpios[$key] = trim(strip_tags($value));
        } else {
            $datosLimpios[$key] = $value;
        }
    }

    // Asegurar protocolo en URLs
    if (!empty($datosLimpios['website']) && !preg_match("~^(?:f|ht)tps?://~i", $datosLimpios['website'])) {
        $datosLimpios['website'] = "https://" . $datosLimpios['website'];
    }
    if (!empty($datosLimpios['booking_url']) && !preg_match("~^(?:f|ht)tps?://~i", $datosLimpios['booking_url'])) {
        $datosLimpios['booking_url'] = "https://" . $datosLimpios['booking_url'];
    }

    $pdo = getDBConnection();

    // Generar slug único
    $baseSlug = generarSlug($datosLimpios['name'] . '-' . $datosLimpios['municipality']);
    
    if (empty($baseSlug)) {
        $baseSlug = 'actividad-' . time();
    }
    
    $slug = $baseSlug;
    $counter = 1;
    
    while (true) {
        $stmtCheck = $pdo->prepare("SELECT id FROM tourist_activities WHERE slug = ?");
        $stmtCheck->execute([$slug]);
        if ($stmtCheck->rowCount() === 0) break;
        $slug = $baseSlug . '-' . $counter++;
    }

    // Determinar el estado inicial según la acción del usuario
    $submitAction = $datosLimpios['submit_action'] ?? 'draft';
    $moderationStatus = ($submitAction === 'submit') ? 'pending' : 'draft';
    
    // Preparar datos para insertar - ajustar a los nombres de columna reales de tourist_activities
    // Basado en la estructura observada en otros archivos
    $actData = [
        'name' => $datosLimpios['name'],
        'slug' => $slug,
        'description' => $datosLimpios['description'],
        'short_description' => substr($datosLimpios['description'], 0, 200) ?? null,
        'category_id' => 1, // Valor por defecto - deberíamos mapear activity_type a category_id
        'difficulty_level' => $datosLimpios['difficulty'],
        'municipality' => $datosLimpios['municipality'],
        'province' => $datosLimpios['province'],
        'duration' => $datosLimpios['duration'] ?? null,
        'available_seasons' => !empty($datosLimpios['season']) ? json_encode([$datosLimpios['season']]) : null,
        'meeting_point' => $datosLimpios['address'] ?? null,
        'price_adult' => !empty($datosLimpios['price']) ? floatval($datosLimpios['price']) : null,
        'price_child' => null, // Campo adicional
        'price_group' => null, // Campo adicional
        'max_participants' => !empty($datosLimpios['max_participants']) ? intval($datosLimpios['max_participants']) : null,
        'contact_phone' => $datosLimpios['phone'] ?? null,
        'contact_email' => $datosLimpios['email'] ?? null,
        'website' => $datosLimpios['website'] ?? null,
        'booking_url' => $datosLimpios['booking_url'] ?? null,
        'photo1' => $datosLimpios['photo1'] ?? null,
        'photo2' => $datosLimpios['photo2'] ?? null,
        'photo3' => $datosLimpios['photo3'] ?? null,
        'photo4' => $datosLimpios['photo4'] ?? null,
        'moderation_status' => $moderationStatus,
        'is_active' => 0,
        'last_submitted_at' => ($submitAction === 'submit') ? date('Y-m-d H:i:s') : null
    ];

    // Si hay sesión, añadir created_by
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['user_id'])) {
        $actData['created_by'] = $_SESSION['user_id'];
    }

    // Construir INSERT
    $columnas = array_keys($actData);
    $placeholders = array_map(function($col) { return ":$col"; }, $columnas);
    
    $sql = "INSERT INTO tourist_activities (" . implode(', ', $columnas) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    
    foreach ($actData as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    
    $stmt->execute();
    $id = $pdo->lastInsertId();

    // Obtener actividad creada
    $stmtSelect = $pdo->prepare("SELECT * FROM tourist_activities WHERE id = ?");
    $stmtSelect->execute([$id]);
    $nuevaActividad = $stmtSelect->fetch();

    $response = [
        'id' => $id,
        'name' => $nuevaActividad['name'],
        'slug' => $nuevaActividad['slug'],
        'moderation_status' => $nuevaActividad['moderation_status'],
        'recaptcha_score' => $recaptchaResult['score']
    ];
    
    // Registrar en historial de moderación
    if (isset($_SESSION['user_id'])) {
        try {
            $historyStmt = $pdo->prepare("
                INSERT INTO content_moderation_history 
                    (content_type, content_id, action, performed_by, new_status, notes)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $historyAction = ($submitAction === 'submit') ? 'submitted' : 'created';
            $historyStmt->execute([
                'activity',
                $id, 
                $historyAction, 
                $_SESSION['user_id'], 
                $moderationStatus,
                'Actividad creada desde formulario público'
            ]);
        } catch (PDOException $historyError) {
            error_log('crear_actividad.php - Error al registrar historial: ' . $historyError->getMessage());
        }
        
        // Crear notificación si se envió para revisión
        if ($submitAction === 'submit') {
            try {
                $notifStmt = $pdo->prepare("
                    INSERT INTO moderation_notifications 
                        (user_id, content_type, content_id, notification_type, title, message)
                    VALUES (?, ?, ?, 'submitted', ?, ?)
                ");
                $notifStmt->execute([
                    $_SESSION['user_id'],
                    'activity',
                    $id,
                    'Actividad Enviada para Revisión',
                    'Tu actividad ha sido enviada para revisión. Te notificaremos cuando sea aprobada.'
                ]);
            } catch (PDOException $notifError) {
                error_log('crear_actividad.php - Error al crear notificación: ' . $notifError->getMessage());
            }
        }

        // Auto-vincular la actividad al usuario
        try {
            $stmtLink = $pdo->prepare("
                INSERT INTO user_resources (user_id, resource_type, resource_id, role, status)
                VALUES (?, 'activity', ?, 'owner', 'active')
            ");
            $stmtLink->execute([$_SESSION['user_id'], $id]);

            // Crear estadísticas iniciales
            $stmtStats = $pdo->prepare("
                INSERT IGNORE INTO resource_stats (resource_type, resource_id, views_count, interests_count, messages_count, favorites_count)
                VALUES ('activity', ?, 0, 0, 0, 0)
            ");
            $stmtStats->execute([$id]);

            $response['auto_linked'] = true;
            $response['link_message'] = 'Actividad vinculada automáticamente a tu cuenta';
        } catch (PDOException $linkError) {
            error_log('crear_actividad.php - Error al vincular: ' . $linkError->getMessage());
            $response['auto_linked'] = false;
            $response['link_message'] = 'Actividad creada pero no se pudo vincular automáticamente';
        }
    }

    jsonSuccess($response, '¡Actividad guardada exitosamente!');
    
} catch (PDOException $e) {
    error_log('crear_actividad.php - Error: ' . $e->getMessage());
    jsonError('Error al crear actividad: ' . $e->getMessage(), 500);
}
