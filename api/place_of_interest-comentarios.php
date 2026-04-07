<?php
/**
 * API: Comentarios de Lugares de Interés
 * 
 * GET  /api/place_of_interest-comentarios.php?place_id=123          → Listar comentarios
 * GET  /api/place_of_interest-comentarios.php?place_id=123&count=1  → Solo contar comentarios
 * POST /api/place_of_interest-comentarios.php                        → Crear comentario
 *   Body: { place_id, author_name, author_email?, comment_text, rating?, parent_id? }
 */

require_once 'config.php';

// ── Crear tabla si no existe (auto-migración) ──
function ensurePlaceCommentsTable($pdo) {
    try {
        $pdo->query("SELECT 1 FROM place_of_interest_comments LIMIT 1");
    } catch (PDOException $e) {
        // La tabla no existe, crearla
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS place_of_interest_comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                place_id INT NOT NULL,
                parent_id INT DEFAULT NULL COMMENT 'Para respuestas a comentarios',
                author_name VARCHAR(100) NOT NULL,
                author_email VARCHAR(255) DEFAULT NULL,
                author_avatar VARCHAR(500) DEFAULT NULL,
                comment_text TEXT NOT NULL,
                rating TINYINT DEFAULT NULL COMMENT 'Valoración 1-5 estrellas',
                is_approved TINYINT(1) DEFAULT 1 COMMENT '1=aprobado, 0=pendiente',
                is_deleted TINYINT(1) DEFAULT 0,
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent VARCHAR(500) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_place_id (place_id),
                INDEX idx_parent_id (parent_id),
                INDEX idx_approved (is_approved),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}

// ── GET: Listar comentarios ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $placeId = isset($_GET['place_id']) ? intval($_GET['place_id']) : 0;
    $countOnly = isset($_GET['count']);
    
    if ($placeId <= 0) {
        jsonError('place_id es requerido', 400);
    }
    
    try {
        $pdo = getDBConnection();
        ensurePlaceCommentsTable($pdo);
        
        // Solo contar
        if ($countOnly) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total 
                FROM place_of_interest_comments 
                WHERE place_id = :place_id AND is_approved = 1 AND is_deleted = 0
            ");
            $stmt->execute(['place_id' => $placeId]);
            $count = $stmt->fetch()['total'];
            
            // Calcular media de valoraciones
            $stmtAvg = $pdo->prepare("
                SELECT AVG(rating) as avg_rating, COUNT(rating) as rating_count
                FROM place_of_interest_comments 
                WHERE place_id = :place_id AND is_approved = 1 AND is_deleted = 0 AND rating IS NOT NULL
            ");
            $stmtAvg->execute(['place_id' => $placeId]);
            $avgData = $stmtAvg->fetch();
            
            jsonSuccess([
                'place_id'     => $placeId,
                'total'        => (int)$count,
                'avg_rating'   => $avgData['avg_rating'] ? round((float)$avgData['avg_rating'], 1) : null,
                'rating_count' => (int)$avgData['rating_count']
            ]);
        }
        
        // Listar comentarios con paginación
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(5, intval($_GET['limit']))) : 20;
        $offset = ($page - 1) * $limit;
        
        // Total de comentarios
        $stmtCount = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM place_of_interest_comments 
            WHERE place_id = :place_id AND is_approved = 1 AND is_deleted = 0 AND parent_id IS NULL
        ");
        $stmtCount->execute(['place_id' => $placeId]);
        $total = (int)$stmtCount->fetch()['total'];
        
        // Comentarios principales (sin parent_id)
        $stmt = $pdo->prepare("
            SELECT id, place_id, author_name, author_avatar, comment_text, rating, created_at
            FROM place_of_interest_comments 
            WHERE place_id = :place_id AND is_approved = 1 AND is_deleted = 0 AND parent_id IS NULL
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue('place_id', $placeId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $comments = $stmt->fetchAll();
        
        // Para cada comentario, obtener sus respuestas
        $commentIds = array_column($comments, 'id');
        $replies = [];
        
        if (!empty($commentIds)) {
            $placeholders = implode(',', array_fill(0, count($commentIds), '?'));
            $stmtReplies = $pdo->prepare("
                SELECT id, place_id, parent_id, author_name, author_avatar, comment_text, created_at
                FROM place_of_interest_comments 
                WHERE parent_id IN ($placeholders) AND is_approved = 1 AND is_deleted = 0
                ORDER BY created_at ASC
            ");
            $stmtReplies->execute($commentIds);
            $allReplies = $stmtReplies->fetchAll();
            
            foreach ($allReplies as $reply) {
                $replies[$reply['parent_id']][] = $reply;
            }
        }
        
        // Adjuntar respuestas a cada comentario
        foreach ($comments as &$comment) {
            $comment['replies'] = $replies[$comment['id']] ?? [];
        }
        
        // Media de valoraciones
        $stmtAvg = $pdo->prepare("
            SELECT AVG(rating) as avg_rating, COUNT(rating) as rating_count
            FROM place_of_interest_comments 
            WHERE place_id = :place_id AND is_approved = 1 AND is_deleted = 0 AND rating IS NOT NULL
        ");
        $stmtAvg->execute(['place_id' => $placeId]);
        $avgData = $stmtAvg->fetch();
        
        jsonSuccess([
            'place_id'     => $placeId,
            'comments'     => $comments,
            'total'        => $total,
            'page'         => $page,
            'limit'        => $limit,
            'total_pages'  => ceil($total / $limit),
            'avg_rating'   => $avgData['avg_rating'] ? round((float)$avgData['avg_rating'], 1) : null,
            'rating_count' => (int)$avgData['rating_count']
        ]);
        
    } catch (PDOException $e) {
        error_log('place_of_interest-comentarios.php GET Error: ' . $e->getMessage());
        jsonError('Error al obtener comentarios', 500);
    }
}

// ── POST: Crear comentario ──
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        jsonError('Datos JSON inválidos', 400);
    }
    
    // Validar campos requeridos
    $placeId     = isset($data['place_id']) ? intval($data['place_id']) : 0;
    $authorName  = isset($data['author_name']) ? trim($data['author_name']) : '';
    $authorEmail = isset($data['author_email']) ? trim($data['author_email']) : null;
    $commentText = isset($data['comment_text']) ? trim($data['comment_text']) : '';
    $rating      = isset($data['rating']) ? intval($data['rating']) : null;
    $parentId    = isset($data['parent_id']) ? intval($data['parent_id']) : null;
    
    // Validaciones
    if ($placeId <= 0) {
        jsonError('place_id es requerido', 400);
    }
    if (empty($authorName) || mb_strlen($authorName) < 2) {
        jsonError('El nombre debe tener al menos 2 caracteres', 400);
    }
    if (mb_strlen($authorName) > 100) {
        jsonError('El nombre no puede superar los 100 caracteres', 400);
    }
    if (empty($commentText) || mb_strlen($commentText) < 5) {
        jsonError('El comentario debe tener al menos 5 caracteres', 400);
    }
    if (mb_strlen($commentText) > 2000) {
        jsonError('El comentario no puede superar los 2000 caracteres', 400);
    }
    if ($rating !== null && ($rating < 1 || $rating > 5)) {
        jsonError('La valoración debe estar entre 1 y 5', 400);
    }
    if ($authorEmail && !filter_var($authorEmail, FILTER_VALIDATE_EMAIL)) {
        jsonError('Email no válido', 400);
    }
    
    // Sanitizar
    $authorName  = sanitizeInput($authorName);
    $commentText = sanitizeInput($commentText);
    
    // Anti-spam básico: verificar que no haya enviado otro comentario en los últimos 60 segundos
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
    
    try {
        $pdo = getDBConnection();
        ensurePlaceCommentsTable($pdo);
        
        // Anti-spam: máximo 1 comentario por IP cada 60 segundos
        $stmtSpam = $pdo->prepare("
            SELECT COUNT(*) as recent 
            FROM place_of_interest_comments 
            WHERE ip_address = :ip AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)
        ");
        $stmtSpam->execute(['ip' => $ipAddress]);
        if ($stmtSpam->fetch()['recent'] > 0) {
            jsonError('Por favor, espera un momento antes de enviar otro comentario', 429);
        }
        
        // Anti-spam: máximo 10 comentarios por IP al día
        $stmtDailySpam = $pdo->prepare("
            SELECT COUNT(*) as daily 
            FROM place_of_interest_comments 
            WHERE ip_address = :ip AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
        ");
        $stmtDailySpam->execute(['ip' => $ipAddress]);
        if ($stmtDailySpam->fetch()['daily'] >= 10) {
            jsonError('Has alcanzado el límite de comentarios por hoy', 429);
        }
        
        // Generar avatar con iniciales
        $initials = mb_strtoupper(mb_substr($authorName, 0, 1));
        $colors = ['#2F5233', '#6B8E6B', '#B8956A', '#e67e22', '#3498db', '#9b59b6', '#e74c3c', '#1abc9c'];
        $colorIndex = crc32($authorName) % count($colors);
        $avatarColor = $colors[abs($colorIndex)];
        
        // Insertar comentario
        $stmt = $pdo->prepare("
            INSERT INTO place_of_interest_comments 
                (place_id, parent_id, author_name, author_email, author_avatar, comment_text, rating, ip_address, user_agent, is_approved)
            VALUES 
                (:place_id, :parent_id, :author_name, :author_email, :author_avatar, :comment_text, :rating, :ip_address, :user_agent, 1)
        ");
        
        $stmt->execute([
            'place_id'      => $placeId,
            'parent_id'     => $parentId ?: null,
            'author_name'   => $authorName,
            'author_email'  => $authorEmail,
            'author_avatar' => $avatarColor,
            'comment_text'  => $commentText,
            'rating'        => $rating,
            'ip_address'    => $ipAddress,
            'user_agent'    => $userAgent
        ]);
        
        $newId = $pdo->lastInsertId();
        
        // Devolver el comentario creado
        $stmtNew = $pdo->prepare("
            SELECT id, place_id, parent_id, author_name, author_avatar, comment_text, rating, created_at
            FROM place_of_interest_comments WHERE id = :id
        ");
        $stmtNew->execute(['id' => $newId]);
        $newComment = $stmtNew->fetch();
        
        jsonSuccess([
            'comment' => $newComment
        ], 'Comentario publicado correctamente');
        
    } catch (PDOException $e) {
        error_log('place_of_interest-comentarios.php POST Error: ' . $e->getMessage());
        jsonError('Error al guardar el comentario', 500);
    }
}

else {
    jsonError('Método no permitido', 405);
}