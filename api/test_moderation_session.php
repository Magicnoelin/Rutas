<?php
/**
 * Script de Diagnóstico - Verificar Sesión y Permisos de Admin
 * Acceder a: /api/test_moderation_session.php
 */

session_start();

header('Content-Type: application/json; charset=utf-8');

$diagnostico = [
    'session_active' => isset($_SESSION) && !empty($_SESSION),
    'user_id' => $_SESSION['user_id'] ?? null,
    'user_type' => $_SESSION['user_type'] ?? null,
    'user_email' => $_SESSION['email'] ?? null,
    'is_admin' => (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'),
    'all_session_data' => $_SESSION ?? []
];

echo json_encode($diagnostico, JSON_PRETTY_PRINT);
