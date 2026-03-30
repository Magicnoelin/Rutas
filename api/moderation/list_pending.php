<?php
/**
 * API Endpoint: Listar Alojamientos Pendientes de Moderación
 * GET /api/moderation/list_pending.php
 * Requiere: Admin autenticado
 */

require_once '../config.php';

// La autenticación se maneja por .htaccess en la carpeta admin_tablas
// No necesitamos verificar sesión PHP aquí

// Solo permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Método no permitido', 405);
}

try {
    $pdo = getDBConnection();
    
    // Obtener filtros opcionales
    $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'all';
    $changeType = isset($_GET['change_type']) ? sanitizeInput($_GET['change_type']) : 'all';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    // Construir query base - sin subquery que puede fallar
    $sql = "SELECT 
                a.id,
                a.name,
                a.slug,
                a.municipality,
                a.province,
                a.moderation_status,
                a.has_pending_changes,
                a.last_submitted_at,
                a.created_at,
                a.rejection_reason,
                a.photo1,
                u.id as user_id,
                u.first_name,
                u.last_name,
                u.email as user_email,
                CASE 
                    WHEN a.has_pending_changes = 1 THEN 'update'
                    WHEN a.published_at IS NULL THEN 'new'
                    ELSE 'new'
                END as change_type,
                DATEDIFF(NOW(), COALESCE(a.last_submitted_at, a.created_at)) as days_pending,
                0 as pending_changes_count
            FROM accommodations a
            LEFT JOIN users u ON a.created_by = u.id
            WHERE 1=1";
    
    // Aplicar filtros
    if ($status === 'pending') {
        $sql .= " AND a.moderation_status = 'pending'";
    } elseif ($status === 'pending_changes') {
        $sql .= " AND a.has_pending_changes = 1";
    } elseif ($status === 'rejected') {
        $sql .= " AND a.moderation_status = 'rejected'";
    } elseif ($status === 'all') {
        $sql .= " AND (a.moderation_status = 'pending' OR a.has_pending_changes = 1)";
    }
    
    $sql .= " ORDER BY a.last_submitted_at ASC LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $items = $stmt->fetchAll();
    
    // Obtener estadísticas - la vista puede no existir
    $stats = ['pending_count' => 0, 'approved_count' => 0, 'rejected_count' => 0];
    try {
        $statsStmt = $pdo->query("SELECT * FROM v_moderation_stats");
        $stats = $statsStmt->fetch() ?: $stats;
    } catch (Exception $e) {
        error_log('Vista v_moderation_stats no existe: ' . $e->getMessage());
    }
    
    // Obtener total de items
    $countSql = "SELECT COUNT(*) as total FROM accommodations a 
                 WHERE a.moderation_status = 'pending' OR a.has_pending_changes = 1";
    $countStmt = $pdo->query($countSql);
    $totalCount = $countStmt->fetch()['total'];
    
    jsonSuccess([
        'items' => $items,
        'stats' => $stats,
        'pagination' => [
            'total' => $totalCount,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $totalCount
        ]
    ], 'Lista de moderación obtenida correctamente');
    
} catch (PDOException $e) {
    error_log('Error en list_pending.php: ' . $e->getMessage());
    jsonError('Error al obtener lista de moderación: ' . $e->getMessage(), 500);
}
