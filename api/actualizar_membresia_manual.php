<?php
/**
 * Script Manual para Actualizar Membresía
 * Usar SOLO para pruebas cuando el webhook no está configurado
 * 
 * USO: https://rutasrurales.io/api/actualizar_membresia_manual.php?user_id=TU_ID&plan=premium&cycle=monthly
 */

require_once 'config.php';

// Verificar parámetros
if (!isset($_GET['user_id']) || !isset($_GET['plan']) || !isset($_GET['cycle'])) {
    jsonError('Parámetros requeridos: user_id, plan (premium/free), cycle (monthly/yearly)', 400);
}

$userId = (int)$_GET['user_id'];
$planName = strtolower($_GET['plan']);
$billingCycle = strtolower($_GET['cycle']);

// Validar parámetros
if (!in_array($planName, ['premium', 'free'])) {
    jsonError('Plan debe ser "premium" o "free"', 400);
}

if (!in_array($billingCycle, ['monthly', 'yearly'])) {
    jsonError('Cycle debe ser "monthly" o "yearly"', 400);
}

try {
    $pdo = getDBConnection();

    // Verificar que el usuario existe
    $stmtCheck = $pdo->prepare("SELECT id, email, first_name FROM users WHERE id = ?");
    $stmtCheck->execute([$userId]);
    $user = $stmtCheck->fetch();

    if (!$user) {
        jsonError('Usuario no encontrado', 404);
    }

    // Calcular fechas
    $startDate = date('Y-m-d H:i:s');
    $endDate = ($billingCycle === 'monthly') 
        ? date('Y-m-d H:i:s', strtotime('+1 month'))
        : date('Y-m-d H:i:s', strtotime('+1 year'));

    // Actualizar membresía
    $stmt = $pdo->prepare("
        UPDATE users
        SET membership_type = ?,
            membership_start_date = ?,
            membership_end_date = ?,
            membership_updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $stmt->execute([
        ucfirst($planName),
        $startDate,
        $endDate,
        $userId
    ]);

    // Crear registro en membership_upgrade_intents para historial
    $stmtIntent = $pdo->prepare("
        INSERT INTO membership_upgrade_intents
        (user_id, plan_id, plan_name, billing_cycle, price, currency, status, completed_at, created_at)
        VALUES (?, 2, ?, ?, 9.99, 'EUR', 'completed', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ");
    $stmtIntent->execute([
        $userId,
        ucfirst($planName),
        $billingCycle
    ]);

    jsonSuccess([
        'user_id' => $userId,
        'email' => $user['email'],
        'name' => $user['first_name'],
        'membership_type' => ucfirst($planName),
        'start_date' => $startDate,
        'end_date' => $endDate,
        'billing_cycle' => $billingCycle,
        'days_valid' => ($billingCycle === 'monthly') ? 30 : 365
    ], 'Membresía actualizada correctamente');

} catch (PDOException $e) {
    error_log('Error actualizando membresía: ' . $e->getMessage());
    jsonError('Error de base de datos: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    jsonError('Error inesperado: ' . $e->getMessage(), 500);
}
