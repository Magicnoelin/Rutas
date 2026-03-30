<?php
/**
 * Listar Alojamientos Pendientes de Moderación
 * Usado por: admin_tablas/moderacion_alojamientos.php
 */

session_start();
require_once '../db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    $limit  = isset($_GET['limit'])  ? intval($_GET['limit'])  : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    // Construir query base
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
                DATEDIFF(NOW(), COALESCE(a.last_submitted_at, a.created_at)) as days_pending
            FROM accommodations a
            LEFT JOIN users u ON a.created_by = u.id
            WHERE 1=1";

    if ($status === 'pending') {
        $sql .= " AND a.moderation_status = 'pending'";
    } elseif ($status === 'pending_changes') {
        $sql .= " AND a.has_pending_changes = 1";
    } elseif ($status === 'rejected') {
        $sql .= " AND a.moderation_status = 'rejected'";
    } else {
        // 'all' = pendientes o con cambios pendientes
        $sql .= " AND (a.moderation_status = 'pending' OR a.has_pending_changes = 1)";
    }

    $sql .= " ORDER BY COALESCE(a.last_submitted_at, a.created_at) ASC LIMIT " . $limit . " OFFSET " . $offset;

    $stmt = $pdo->query($sql);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estadísticas básicas
    $stats = [
        'pending_count'         => 0,
        'approved_count'        => 0,
        'rejected_count'        => 0,
        'pending_changes_count' => 0
    ];
    try {
        $stStats = $pdo->query("
            SELECT 
                SUM(CASE WHEN moderation_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN moderation_status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN moderation_status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                SUM(CASE WHEN has_pending_changes = 1 THEN 1 ELSE 0 END) as pending_changes_count
            FROM accommodations
        ");
        $row = $stStats->fetch(PDO::FETCH_ASSOC);
        if ($row) $stats = $row;
    } catch (Exception $e) {
        // Si falla, dejamos los valores por defecto
    }

    // Total para paginación
    $countSql = "SELECT COUNT(*) as total FROM accommodations 
                 WHERE moderation_status = 'pending' OR has_pending_changes = 1";
    $totalCount = $pdo->query($countSql)->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode([
        'success' => true,
        'data' => [
            'items' => $items,
            'stats' => $stats,
            'pagination' => [
                'total'    => $totalCount,
                'limit'    => $limit,
                'offset'   => $offset,
                'has_more' => ($offset + $limit) < $totalCount
            ]
        ]
    ]);

} catch (Exception $e) {
    error_log('list_pending.php Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al obtener lista: ' . $e->getMessage()]);
}
