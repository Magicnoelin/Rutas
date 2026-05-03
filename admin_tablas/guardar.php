<?php
session_start();
include 'db.php';
require_once __DIR__ . '/../api/inbound_links_helper.php';

/**
 * CONTROL DE ACCESO REFORZADO
 */
$is_authenticated = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
$is_internal_form = isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'rutasrurales.io') !== false;

if (!$is_authenticated && !$is_internal_form) {
    header("Location: login.php?error=sesion_expirada_final");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Recogemos el ID
        $id = isset($_POST['id']) ? $_POST['id'] : null;

        if (!$id) {
            die("Error: No se ha recibido el ID del alojamiento.");
        }

        /**
         * SQL ACTUALIZADO: Hemos quitado 'is_active' y 'slug' 
         * para que no den error al no venir en el formulario.
         */
        // ─── INBOUND LINKS: generar description_linked ───────────────────────
        $description_raw    = $_POST['description'] ?? '';
        $description_linked = procesarInboundLinks($description_raw, $pdo);
        // ─────────────────────────────────────────────────────────────────────

        $sql = "UPDATE accommodations SET 
                name = :name, 
                description = :description, 
                description_linked = :description_linked,
                meta_title = :meta_title, 
                meta_description = :meta_description,
                address = :address,
                postal_code = :postal_code,
                municipality = :municipality,
                province = :province,
                latitude = :latitude,
                longitude = :longitude,
                capacity = :capacity,
                price_per_night = :price_per_night,
                phone = :phone,
                website = :website,
                instagram_url = :instagram_url,
                photo1 = :photo1,
                photo2 = :photo2,
                photo3 = :photo3,
                photo4 = :photo4,
                moderation_status = 'approved',
                has_pending_changes = 0
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        
        // Ejecución sin los campos eliminados
        $stmt->execute([
            ':id' => $id,
            ':name' => $_POST['name'],
            ':description' => $_POST['description'],
            ':description_linked' => $description_linked,
            ':meta_title' => $_POST['meta_title'],
            ':meta_description' => $_POST['meta_description'],
            ':address' => $_POST['address'],
            ':postal_code' => $_POST['postal_code'],
            ':municipality' => $_POST['municipality'],
            ':province' => $_POST['province'],
            ':latitude' => $_POST['latitude'],
            ':longitude' => $_POST['longitude'],
            ':capacity' => $_POST['capacity'],
            ':price_per_night' => $_POST['price_per_night'],
            ':phone' => $_POST['phone'],
            ':website' => $_POST['website'],
            ':instagram_url' => $_POST['instagram_url'] ?? '',
            ':photo1' => $_POST['photo1'] ?? '',
            ':photo2' => $_POST['photo2'] ?? '',
            ':photo3' => $_POST['photo3'] ?? '',
            ':photo4' => $_POST['photo4'] ?? ''
        ]);
        
        // Éxito: Volvemos al index
        header("Location: index.php?status=success");
        exit;

    } catch (PDOException $e) {
        die("Error crítico en Base de Datos: " . $e->getMessage());
    }
}