<?php
/**
 * API Endpoint: Crear Nuevo Alojamiento
 * POST /api/crear.php
 * Body: FormData con los datos del alojamiento
 */

require_once 'config.php';

// Función para generar slug
function generarSlug($texto) {
    if (!$texto) return '';
    return strtolower(trim(preg_replace('/[^a-zA-Z0-9-]/', '', preg_replace('/\s+/', '-', $texto)), '-'));
}

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

try {
    // Obtener datos - puede ser JSON o FormData
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

    if (strpos($contentType, 'application/json') !== false) {
        // Datos en formato JSON
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            jsonError('Error al decodificar JSON: ' . json_last_error_msg(), 400);
        }

        error_log('Crear.php - Received JSON data: ' . $jsonData);
    } else {
        // Datos en formato FormData
        $data = $_POST;
        error_log('Crear.php - Received FormData: ' . json_encode($data));
    }

    if (empty($data)) {
        jsonError('No se recibieron datos', 400);
    }
    
    // Validar reCAPTCHA (temporalmente deshabilitado)
    $recaptchaResult = ['success' => true, 'score' => 1.0];
    
    // Validar campos requeridos
    $camposRequeridos = ['Nombre', 'Tipo', 'Direccion'];
    foreach ($camposRequeridos as $campo) {
        if (!isset($data[$campo]) || empty(trim($data[$campo]))) {
            jsonError("El campo '$campo' es requerido", 400);
        }
    }
    
    // Validar email si se proporciona
    if (!empty($data['Email']) && !filter_var($data['Email'], FILTER_VALIDATE_EMAIL)) {
        jsonError('Email inválido', 400);
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
    if (!empty($datosLimpios['Web']) && !preg_match("~^(?:f|ht)tps?://~i", $datosLimpios['Web'])) {
        $datosLimpios['Web'] = "https://" . $datosLimpios['Web'];
    }

    $pdo = getDBConnection();

    // Verificar límites de membresía si el usuario está autenticado
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $userId = $_SESSION['user_id'] ?? null;
    $membershipType = 'free'; // Por defecto
    
    if ($userId) {
        // Obtener información de membresía del usuario
        $stmtUser = $pdo->prepare("
            SELECT membership_type, membership_status 
            FROM users 
            WHERE id = ?
        ");
        $stmtUser->execute([$userId]);
        $user = $stmtUser->fetch();
        
        if ($user) {
            $membershipType = strtolower($user['membership_type'] ?? 'free');
            $membershipStatus = $user['membership_status'] ?? 'active';
            
            // Verificar límites según membresía
            if ($membershipType === 'free' || $membershipType === 'basic') {
                // Contar alojamientos existentes del usuario (solo pendientes/aprobados, EXCLUIR borradores draft)
                $stmtCount = $pdo->prepare("
                    SELECT COUNT(*) as total_alojamientos, 
                           COALESCE(SUM(a.capacity), 0) as total_plazas
                    FROM accommodations a
                    INNER JOIN user_resources ur ON ur.resource_id = a.id AND ur.resource_type = 'accommodation'
                    WHERE ur.user_id = ? AND ur.role = 'owner'
                    AND a.moderation_status != 'draft'
                ");
                $stmtCount->execute([$userId]);
                $counts = $stmtCount->fetch();
                
                $totalAlojamientos = intval($counts['total_alojamientos'] ?? 0);
                $totalPlazas = intval($counts['total_plazas'] ?? 0);
                $nuevasPlazas = intval($datosLimpios['Plazas'] ?? 0);
                
                error_log("Crear.php - Membership check: user=$userId, type=$membershipType, totalAloj=$totalAlojamientos, totalPlazas=$totalPlazas, nuevasPlazas=$nuevasPlazas");
                
                // Límites para membresía básica: 2 alojamientos o 15 plazas
                if ($totalAlojamientos >= 2) {
                    jsonError('Límite alcanzado: La membresía básica permite máximo 2 alojamientos. Actualiza a Premium para añadir más.', 403);
                }
                
                // Solo bloquear si las nuevas plazas POR SÍ SOLAS superan el límite de 15
                // O si la suma total supera el límite
                if ($nuevasPlazas > 15) {
                    jsonError('Límite alcanzado: La membresía básica permite máximo 15 plazas por alojamiento. Actualiza a Premium para añadir más plazas.', 403);
                }
                
                if ($totalPlazas > 0 && ($totalPlazas + $nuevasPlazas) > 15) {
                    jsonError('Límite alcanzado: La membresía básica permite máximo 15 plazas totales (ya tienes ' . $totalPlazas . '). Actualiza a Premium para añadir más plazas.', 403);
                }
            }
        }
    }

    // Obtener category_id
    $categoryId = 1;
    try {
        $stmtCategory = $pdo->query("SELECT id FROM categories_accommodations LIMIT 1");
        $categoryResult = $stmtCategory->fetch();
        if ($categoryResult) {
            $categoryId = $categoryResult['id'];
        }
    } catch (PDOException $e) {
        error_log('Crear.php - Category error: ' . $e->getMessage());
    }

    // Generar slug único
    $baseSlug = !empty($datosLimpios['Slug']) 
        ? $datosLimpios['Slug'] 
        : generarSlug(($datosLimpios['Nombre'] ?? '') . '-' . ($datosLimpios['Municipality'] ?? ''));
    
    if (empty($baseSlug)) {
        $baseSlug = 'alojamiento-' . time();
    }
    
    $slug = $baseSlug;
    $counter = 1;
    
    while (true) {
        $stmtCheck = $pdo->prepare("SELECT id FROM accommodations WHERE slug = ?");
        $stmtCheck->execute([$slug]);
        if ($stmtCheck->rowCount() === 0) break;
        $slug = $baseSlug . '-' . $counter++;
    }

    // Preparar datos
    // Determinar el estado inicial según la acción del usuario
    $submitAction = $datosLimpios['submit_action'] ?? 'draft'; // 'draft' o 'submit'
    $moderationStatus = ($submitAction === 'submit') ? 'pending' : 'draft';
    
    $accData = [
        'name' => $datosLimpios['Nombre'] ?? '',
        'slug' => $slug,
        'category_id' => $categoryId,
        'accommodation_type' => $datosLimpios['Tipo'] ?? 'casa',
        'address' => $datosLimpios['Direccion'] ?? '',
        'municipality' => $datosLimpios['Municipality'] ?? '',
        'province' => $datosLimpios['Province'] ?? '',
        'postal_code' => $datosLimpios['postal_code'] ?? '',
        'registration_number' => $datosLimpios['registration_number'] ?? '',
        'capacity' => intval($datosLimpios['Plazas'] ?? 0),
        'price_per_night' => !empty($datosLimpios['Precio']) ? floatval($datosLimpios['Precio']) : null,
        'description' => $datosLimpios['Notaspublicas'] ?? '',
        'phone' => $datosLimpios['Telefono1'] ?? '',
        'email' => $datosLimpios['Email'] ?? '',
        'website' => $datosLimpios['Web'] ?? '',
        'instagram' => $datosLimpios['Instagram'] ?? '',
        'booking' => $datosLimpios['Booking'] ?? '',
        'photo1' => $datosLimpios['foto1'] ?? $datosLimpios['Foto1'] ?? '',
        'photo2' => $datosLimpios['foto2'] ?? $datosLimpios['Foto2'] ?? '',
        'photo3' => $datosLimpios['foto3'] ?? $datosLimpios['Foto3'] ?? '',
        'photo4' => $datosLimpios['foto4'] ?? $datosLimpios['Foto4'] ?? '',
        'is_active' => 0,
        'moderation_status' => $moderationStatus,
        'last_submitted_at' => ($submitAction === 'submit') ? date('Y-m-d H:i:s') : null
    ];

    // Construir INSERT
    $columnas = array_keys($accData);
    $placeholders = array_map(function($col) { return ":$col"; }, $columnas);
    
    $sql = "INSERT INTO accommodations (" . implode(', ', $columnas) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    
    foreach ($accData as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    
    $stmt->execute();
    $id = $pdo->lastInsertId();

    // Obtener alojamiento creado
    $stmtSelect = $pdo->prepare("SELECT * FROM accommodations WHERE id = ?");
    $stmtSelect->execute([$id]);
    $nuevoAlojamiento = $stmtSelect->fetch();

    $response = [
        'id' => $id,
        'nombre' => $nuevoAlojamiento['name'],
        'tipo' => $nuevoAlojamiento['accommodation_type'],
        'estado' => $nuevoAlojamiento['is_active'] ? 'activo' : 'inactivo',
        'moderation_status' => $nuevoAlojamiento['moderation_status'],
        'recaptcha_score' => $recaptchaResult['score'],
        'slug' => $slug
    ];
    
    // Registrar en historial de moderación
    try {
        $historyStmt = $pdo->prepare("
            INSERT INTO accommodation_moderation_history 
                (accommodation_id, action, performed_by, new_status, notes)
            VALUES (?, ?, ?, ?, ?)
        ");
        $historyAction = ($submitAction === 'submit') ? 'submitted' : 'created';
        $historyStmt->execute([
            $id, 
            $historyAction, 
            $_SESSION['user_id'] ?? null, 
            $moderationStatus,
            'Alojamiento creado desde formulario público'
        ]);
    } catch (PDOException $historyError) {
        error_log('Crear.php - Error al registrar historial: ' . $historyError->getMessage());
    }
    
    // Crear notificación si se envió para revisión
    if ($submitAction === 'submit' && isset($_SESSION['user_id'])) {
        try {
            $notifStmt = $pdo->prepare("
                INSERT INTO moderation_notifications 
                    (user_id, accommodation_id, notification_type, title, message)
                VALUES (?, ?, 'submitted', ?, ?)
            ");
            $notifStmt->execute([
                $_SESSION['user_id'],
                $id,
                'Alojamiento Enviado para Revisión',
                'Tu alojamiento ha sido enviado para revisión. Te notificaremos cuando sea aprobado.'
            ]);
        } catch (PDOException $notifError) {
            error_log('Crear.php - Error al crear notificación: ' . $notifError->getMessage());
        }
    }

    // Auto-vincular el alojamiento al usuario si está autenticado
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];

        try {
            // Verificar si ya existe la vinculación
            $stmtCheckLink = $pdo->prepare("
                SELECT id FROM user_resources
                WHERE user_id = ? AND resource_type = 'accommodation' AND resource_id = ?
            ");
            $stmtCheckLink->execute([$userId, $id]);
            $existingLink = $stmtCheckLink->fetch();

            if (!$existingLink) {
                // Crear vinculación automática
                $stmtLink = $pdo->prepare("
                    INSERT INTO user_resources (user_id, resource_type, resource_id, role, status)
                    VALUES (?, 'accommodation', ?, 'owner', 'active')
                ");
                $stmtLink->execute([$userId, $id]);

                // Actualizar created_by en el alojamiento
                $stmtUpdate = $pdo->prepare("UPDATE accommodations SET created_by = ? WHERE id = ?");
                $stmtUpdate->execute([$userId, $id]);

                // Crear estadísticas iniciales
                $stmtStats = $pdo->prepare("
                    INSERT IGNORE INTO resource_stats (resource_type, resource_id, views_count, interests_count, messages_count, favorites_count)
                    VALUES ('accommodation', ?, 0, 0, 0, 0)
                ");
                $stmtStats->execute([$id]);

                $response['auto_linked'] = true;
                $response['link_message'] = 'Alojamiento vinculado automáticamente a tu cuenta';
            } else {
                $response['auto_linked'] = false;
                $response['link_message'] = 'Alojamiento ya estaba vinculado a tu cuenta';
            }
        } catch (PDOException $linkError) {
            error_log('Crear.php - Error al vincular automáticamente: ' . $linkError->getMessage());
            $response['auto_linked'] = false;
            $response['link_message'] = 'Alojamiento creado pero no se pudo vincular automáticamente';
        }
    } else {
        $response['auto_linked'] = false;
        $response['link_message'] = 'Alojamiento creado. Inicia sesión para vincularlo a tu cuenta';
    }

    jsonSuccess($response, '¡Alojamiento guardado exitosamente!');
    
} catch (PDOException $e) {
    error_log('Crear.php - Error: ' . $e->getMessage());
    jsonError('Error al crear alojamiento: ' . $e->getMessage(), 500);
}
