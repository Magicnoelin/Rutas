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

        // ─── INBOUND LINKS: generar description_linked ───────────────────────
        $description_raw    = $_POST['description'] ?? '';
        $description_linked = procesarInboundLinks($description_raw, $pdo);
        // ─────────────────────────────────────────────────────────────────────

        // Procesar switches / checkboxes de Bootstrap (si no se marcan, no vienen en el POST)
        $is_active             = isset($_POST['is_active']) ? 1 : 0;
        $is_featured           = isset($_POST['is_featured']) ? 1 : 0;
        $is_premium            = isset($_POST['is_premium']) ? 1 : 0;
        $is_verified           = isset($_POST['is_verified']) ? 1 : 0;

        // --- PROCESAMIENTO SEGURO DE AMENITIES (Ajuste para Constraint JSON) ---
        $raw_amenities = trim($_POST['amenities'] ?? '');
        if ($raw_amenities === '') {
            $amenities_value = null;
        } elseif (json_decode($raw_amenities) !== null && json_last_error() === JSON_ERROR_NONE) {
            $amenities_value = $raw_amenities;
        } else {
            $items = array_filter(array_map('trim', explode(',', $raw_amenities)));
            $amenities_value = json_encode(array_values($items), JSON_UNESCAPED_UNICODE);
        }

        // --- PROCESAMIENTO SEGURO DE GALLERY (Ajuste para Constraint JSON) ---
        $raw_gallery = trim($_POST['gallery'] ?? '');
        if ($raw_gallery === '') {
            $gallery_value = null;
        } elseif (json_decode($raw_gallery) !== null && json_last_error() === JSON_ERROR_NONE) {
            $gallery_value = $raw_gallery;
        } else {
            $items = array_filter(array_map('trim', preg_split('/[\n,]+/', $raw_gallery)));
            $gallery_value = json_encode(array_values($items), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        // Construir consulta SQL para actualizar todos los campos de la tabla accommodations
        $sql = "UPDATE accommodations SET 
                    name = :name,
                    slug = :slug,
                    registration_number = :registration_number,
                    category_id = :category_id,
                    subcategory_id = :subcategory_id,
                    accommodation_type = :accommodation_type,
                    description = :description,
                    short_description = :short_description,
                    description_linked = :description_linked,
                    address = :address,
                    municipality = :municipality,
                    province = :province,
                    postal_code = :postal_code,
                    latitude = :latitude,
                    longitude = :longitude,
                    google_maps_url = :google_maps_url,
                    capacity = :capacity,
                    bedrooms = :bedrooms,
                    bathrooms = :bathrooms,
                    min_nights = :min_nights,
                    price_per_night = :price_per_night,
                    price_weekend = :price_weekend,
                    price_week = :price_week,
                    price_details = :price_details,
                    amenities = :amenities,
                    pet_friendly = :pet_friendly,
                    smoking_allowed = :smoking_allowed,
                    suitable_for_children = :suitable_for_children,
                    kitchen_available = :kitchen_available,
                    has_accessibility = :has_accessibility,
                    accessibility_details = :accessibility_details,
                    manager_name = :manager_name,
                    manager_nickname = :manager_nickname,
                    phone = :phone,
                    phone2 = :phone2,
                    email = :email,
                    website = :website,
                    facebook_url = :facebook_url,
                    instagram_url = :instagram_url,
                    instagram_user = :instagram_user,
                    airbnb_url = :airbnb_url,
                    booking_url = :booking_url,
                    booking = :booking,
                    video_url = :video_url,
                    virtual_tour_url = :virtual_tour_url,
                    gallery = :gallery,
                    public_notes = :public_notes,
                    private_notes = :private_notes,
                    meta_title = :meta_title,
                    meta_description = :meta_description,
                    keywords = :keywords,
                    near_events = :near_events,
                    moderation_status = :moderation_status,
                    has_pending_changes = :has_pending_changes,
                    rejection_reason = :rejection_reason,
                    reviewed_by = :reviewed_by,
                    is_active = :is_active,
                    is_featured = :is_featured,
                    is_premium = :is_premium,
                    is_verified = :is_verified,
                    suscripcion_nivel = :suscripcion_nivel,
                    created_by = :created_by,";

        // Añadir dinámicamente el bloque de parámetros para las 20 fotos
        for ($i = 1; $i <= 20; $i++) {
            $sql .= " photo$i = :photo$i,";
        }
        
        // Retirar la última coma que sobra y cerrar con el WHERE id
        $sql = rtrim($sql, ',');
        $sql .= " WHERE id = :id";

        $stmt = $pdo->prepare($sql);

        // Mapear los datos comunes en los parámetros
        $params = [
            ':id'                     => $id,
            ':name'                   => $_POST['name'] ?? '',
            ':slug'                   => $_POST['slug'] ?? '',
            ':registration_number'   => $_POST['registration_number'] ?? null,
            ':category_id'            => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : 0,
            ':subcategory_id'         => !empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null,
            ':accommodation_type'     => $_POST['accommodation_type'] ?? null,
            ':description'            => $_POST['description'] ?? null,
            ':short_description'      => $_POST['short_description'] ?? null,
            ':description_linked'     => $description_linked,
            ':address'                => $_POST['address'] ?? null,
            ':municipality'           => $_POST['municipality'] ?? null,
            ':province'               => $_POST['province'] ?? null,
            ':postal_code'            => $_POST['postal_code'] ?? null,
            ':latitude'               => !empty($_POST['latitude']) ? $_POST['latitude'] : null,
            ':longitude'              => !empty($_POST['longitude']) ? $_POST['longitude'] : null,
            ':google_maps_url'        => $_POST['google_maps_url'] ?? null,
            ':capacity'               => !empty($_POST['capacity']) ? (int)$_POST['capacity'] : 0,
            ':bedrooms'               => !empty($_POST['bedrooms']) ? (int)$_POST['bedrooms'] : 0,
            ':bathrooms'              => !empty($_POST['bathrooms']) ? (int)$_POST['bathrooms'] : 0,
            ':min_nights'             => !empty($_POST['min_nights']) ? (int)$_POST['min_nights'] : 1,
            ':price_per_night'        => !empty($_POST['price_per_night']) ? $_POST['price_per_night'] : null,
            ':price_weekend'          => !empty($_POST['price_weekend']) ? $_POST['price_weekend'] : null,
            ':price_week'             => !empty($_POST['price_week']) ? $_POST['price_week'] : null,
            ':price_details'          => $_POST['price_details'] ?? null,
            ':amenities'              => $amenities_value, // <--- Variable limpia procesada arriba
            ':pet_friendly'           => isset($_POST['pet_friendly']) ? (int)$_POST['pet_friendly'] : 0,
            ':smoking_allowed'        => isset($_POST['smoking_allowed']) ? (int)$_POST['smoking_allowed'] : 0,
            ':suitable_for_children'  => isset($_POST['suitable_for_children']) ? (int)$_POST['suitable_for_children'] : 1,
            ':kitchen_available'      => isset($_POST['kitchen_available']) ? (int)$_POST['kitchen_available'] : 1,
            ':has_accessibility'      => isset($_POST['has_accessibility']) ? (int)$_POST['has_accessibility'] : 0,
            ':accessibility_details'  => $_POST['accessibility_details'] ?? null,
            ':manager_name'           => $_POST['manager_name'] ?? null,
            ':manager_nickname'       => $_POST['manager_nickname'] ?? null,
            ':phone'                  => $_POST['phone'] ?? null,
            ':phone2'                 => $_POST['phone2'] ?? null,
            ':email'                  => $_POST['email'] ?? null,
            ':website'                => $_POST['website'] ?? null,
            ':facebook_url'           => $_POST['facebook_url'] ?? null,
            ':instagram_url'          => $_POST['instagram_url'] ?? null,
            ':instagram_user'         => $_POST['instagram_user'] ?? null,
            ':airbnb_url'             => $_POST['airbnb_url'] ?? null,
            ':booking_url'            => $_POST['booking_url'] ?? null,
            ':booking'                => $_POST['booking'] ?? null,
            ':video_url'              => $_POST['video_url'] ?? null,
            ':virtual_tour_url'       => $_POST['virtual_tour_url'] ?? null,
            ':gallery'                => $gallery_value, // <--- Variable limpia procesada arriba
            ':public_notes'           => $_POST['public_notes'] ?? null,
            ':private_notes'          => $_POST['private_notes'] ?? null,
            ':meta_title'             => $_POST['meta_title'] ?? null,
            ':meta_description'       => $_POST['meta_description'] ?? null,
            ':keywords'               => $_POST['keywords'] ?? null,
            ':near_events'            => $_POST['near_events'] ?? null,
            ':moderation_status'      => $_POST['moderation_status'] ?? 'draft',
            ':has_pending_changes'    => !empty($_POST['has_pending_changes']) ? (int)$_POST['has_pending_changes'] : 0,
            ':rejection_reason'       => $_POST['rejection_reason'] ?? null,
            ':reviewed_by'            => !empty($_POST['reviewed_by']) ? (int)$_POST['reviewed_by'] : null,
            ':is_active'              => $is_active,
            ':is_featured'            => $is_featured,
            ':is_premium'             => $is_premium,
            ':is_verified'            => $is_verified,
            ':suscripcion_nivel'      => !empty($_POST['suscripcion_nivel']) ? (int)$_POST['suscripcion_nivel'] : 1,
            ':created_by'             => !empty($_POST['created_by']) ? (int)$_POST['created_by'] : null,
        ];

        // Mapear dinámicamente las 20 fotos en el arreglo de parámetros
        for ($i = 1; $i <= 20; $i++) {
            $params[":photo$i"] = $_POST["photo$i"] ?? null;
        }

        // Ejecutar la actualización en la BD
        $stmt->execute($params);

        // Éxito: Volvemos al index con estatus success
        header("Location: index.php?status=success");
        exit;

    } catch (PDOException $e) {
        die("Error crítico en Base de Datos: " . $e->getMessage());
    }
}