<?php
/**
 * Redirige URLs con ?id=external_id a su página correspondiente
 */

require_once 'config.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: /');
    exit;
}

$externalId = $_GET['id'];
$externalId = preg_replace('/[^a-zA-Z0-9]/', '', $externalId);

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar en alojamientos
    $stmt = $pdo->prepare("SELECT slug FROM accommodations WHERE REPLACE(external_id, '-', '') = ? OR external_id = ? LIMIT 1");
    $stmt->execute([$externalId, $externalId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && !empty($result['slug'])) {
        header('Location: /alojamiento/' . $result['slug'], true, 301);
        exit;
    }
    
    // Buscar en lugares
    $stmt = $pdo->prepare("SELECT slug FROM places WHERE REPLACE(external_id, '-', '') = ? OR external_id = ? LIMIT 1");
    $stmt->execute([$externalId, $externalId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && !empty($result['slug'])) {
        header('Location: /lugar/' . $result['slug'], true, 301);
        exit;
    }
    
    // Buscar en actividades
    $stmt = $pdo->prepare("SELECT slug FROM tourist_activities WHERE REPLACE(external_id, '-', '') = ? OR external_id = ? LIMIT 1");
    $stmt->execute([$externalId, $externalId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && !empty($result['slug'])) {
        header('Location: /actividad/' . $result['slug'], true, 301);
        exit;
    }
    
    // No encontrado
    header('Location: /');
    exit;
    
} catch (Exception $e) {
    error_log("External ID redirect error: " . $e->getMessage());
    header('Location: /');
    exit;
}
