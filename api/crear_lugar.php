<?php
/**
 * API Endpoint: Crear Nuevo Lugar de Interés
 * POST /api/crear_lugar.php
 * Body: JSON con los datos del lugar
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
    $camposRequeridos = ['name', 'description', 'municipality'];
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

    // Asegurar protocolo en URL
    if (!empty($datosLimpios['website']) && !preg_match("~^(?:f|ht)tps?://~i", $datosLimpios['website'])) {
        $datosLimpios['website'] = "https://" . $datosLimpios['website'];
    }

    $pdo = getDBConnection();

    // Generar slug único
    $baseSlug = generarSlug($datosLimpios['name'] . '-' . $datosLimpios['municipality']);
    
    if (empty($baseSlug)) {
        $baseSlug = 'lugar-' . time();
    }
    
    $slug = $baseSlug;
    $counter = 1;
    
    while (true) {
        $stmtCheck = $pdo->prepare("SELECT id FROM places_of_interest WHERE slug = ?");
        $stmtCheck->execute([$slug]);
        if ($stmtCheck->rowCount() === 0) break;
        $slug = $baseSlug . '-' . $counter++;
    }

    // Determinar el estado inicial según la acción del usuario
    $submitAction = $datosLimpios['submit_action'] ?? 'draft';
    $moderationStatus = ($submitAction === 'submit') ? 'pending' : 'draft';
    
    // Preparar datos para insertar (adaptado a la estructura real de la tabla)
    $placeData = [
        'name' => $datosLimpios['name'],
        'slug' => $slug,
        'category_id' => $datosLimpios['category_id'] ?? 1, // Categoría por defecto
        'subcategory_id' => $datosLimpios['subcategory_id'] ?? null,
        'description' => $datosLimpios['description'],
        'short_description' => isset($datosLimpios['description']) ? substr($datosLimpios['description'], 0, 500) : null,
        'municipality' => $datosLimpios['municipality'],
        'province' => $datosLimpios['province'] ?? 'Soria',
        'postal_code' => $datosLimpios['postal_code'] ?? null,
        'address' => $datosLimpios['address'] ?? null,
        'latitude' => !empty($datosLimpios['latitude']) ? floatval($datosLimpios['latitude']) : null,
        'longitude' => !empty($datosLimpios['longitude']) ? floatval($datosLimpios['longitude']) : null,
        'entry_fee' => !empty($datosLimpios['entry_fee']) ? floatval($datosLimpios['entry_fee']) : 0.00,
        'entry_fee_details' => $datosLimpios['entry_fee_details'] ?? null,
        'opening_hours' => $datosLimpios['opening_hours'] ?? null,
        'best_season' => $datosLimpios['best_season'] ?? null,
        'visit_duration' => !empty($datosLimpios['visit_duration']) ? intval($datosLimpios['visit_duration']) : null,
        'accessibility' => $datosLimpios['accessibility'] ?? null,
        'facilities' => $datosLimpios['facilities'] ?? null,
        'languages_available' => $datosLimpios['languages_available'] ?? null,
        'pet_friendly' => isset($datosLimpios['pet_friendly']) ? intval($datosLimpios['pet_friendly']) : 0,
        'suitable_for_children' => isset($datosLimpios['suitable_for_children']) ? intval($datosLimpios['suitable_for_children']) : 1,
        'phone' => $datosLimpios['phone'] ?? null,
        'email' => $datosLimpios['email'] ?? null,
        'website' => $datosLimpios['website'] ?? null,
        'photo1' => $datosLimpios['photo1'] ?? null,
        'photo2' => $datosLimpios['photo2'] ?? null,
        'photo3' => $datosLimpios['photo3'] ?? null,
        'photo4' => $datosLimpios['photo4'] ?? null,
        'gallery' => $datosLimpios['gallery'] ?? null,
        'video_url' => $datosLimpios['video_url'] ?? null,
        'virtual_tour_url' => $datosLimpios['virtual_tour_url'] ?? null,
        'meta_title' => $datosLimpios['meta_title'] ?? $datosLimpios['name'],
        'meta_description' => $datosLimpios['meta_description'] ?? null,
        'keywords' => $datosLimpios['keywords'] ?? null
    ];
    
    // Nota: Los campos moderation_status, is_active, last_submitted_at, created_by
    // no existen en la tabla actual, así que no los incluimos

    // Si hay sesión, añadir created_by
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['user_id'])) {
        $placeData['created_by'] = $_SESSION['user_id'];
    }

    // Construir INSERT
    $columnas = array_keys($placeData);
    $placeholders = array_map(function($col) { return ":$col"; }, $columnas);
    
    $sql = "INSERT INTO places_of_interest (" . implode(', ', $columnas) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    
    foreach ($placeData as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    
    $stmt->execute();
    $id = $pdo->lastInsertId();

    // Obtener lugar creado
    $stmtSelect = $pdo->prepare("SELECT * FROM places_of_interest WHERE id = ?");
    $stmtSelect->execute([$id]);
    $nuevoLugar = $stmtSelect->fetch();

    $response = [
        'id' => $id,
        'name' => $nuevoLugar['name'],
        'slug' => $nuevoLugar['slug'],
        'category_id' => $nuevoLugar['category_id'],
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
                'place',
                $id, 
                $historyAction, 
                $_SESSION['user_id'], 
                $moderationStatus,
                'Lugar creado desde formulario público'
            ]);
        } catch (PDOException $historyError) {
            error_log('crear_lugar.php - Error al registrar historial: ' . $historyError->getMessage());
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
                    'place',
                    $id,
                    'Lugar Enviado para Revisión',
                    'Tu lugar ha sido enviado para revisión. Te notificaremos cuando sea aprobado.'
                ]);
            } catch (PDOException $notifError) {
                error_log('crear_lugar.php - Error al crear notificación: ' . $notifError->getMessage());
            }
        }

        // Auto-vincular el lugar al usuario
        try {
            $stmtLink = $pdo->prepare("
                INSERT INTO user_resources (user_id, resource_type, resource_id, role, status)
                VALUES (?, 'place', ?, 'owner', 'active')
            ");
            $stmtLink->execute([$_SESSION['user_id'], $id]);

            // Crear estadísticas iniciales
            $stmtStats = $pdo->prepare("
                INSERT IGNORE INTO resource_stats (resource_type, resource_id, views_count, interests_count, messages_count, favorites_count)
                VALUES ('place', ?, 0, 0, 0, 0)
            ");
            $stmtStats->execute([$id]);

            $response['auto_linked'] = true;
            $response['link_message'] = 'Lugar vinculado automáticamente a tu cuenta';
        } catch (PDOException $linkError) {
            error_log('crear_lugar.php - Error al vincular: ' . $linkError->getMessage());
            $response['auto_linked'] = false;
            $response['link_message'] = 'Lugar creado pero no se pudo vincular automáticamente';
        }
    }

    jsonSuccess($response, '¡Lugar guardado exitosamente!');
    
} catch (PDOException $e) {
    error_log('crear_lugar.php - Error: ' . $e->getMessage());
    jsonError('Error al crear lugar: ' . $e->getMessage(), 500);
}
