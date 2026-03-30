<?php
/**
 * API Endpoint: Crear Lugar de Interés
 * POST /api/crear_lugar_interes.php
 * Inserta un nuevo registro en la tabla places_of_interest
 */

require_once 'config.php';

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

try {
    $pdo = getDBConnection();

    // Sanitizar y validar datos de entrada
    $data = [
        'name' => sanitizeInput($_POST['name'] ?? ''),
        'slug' => sanitizeInput($_POST['slug'] ?? ''),
        'category_id' => intval($_POST['category_id'] ?? 0),
        'subcategory_id' => !empty($_POST['subcategory_id']) ? intval($_POST['subcategory_id']) : null,
        'description' => sanitizeInput($_POST['description'] ?? ''),
        'short_description' => sanitizeInput($_POST['short_description'] ?? ''),
        'address' => sanitizeInput($_POST['address'] ?? ''),
        'municipality' => sanitizeInput($_POST['municipality'] ?? ''),
        'province' => sanitizeInput($_POST['province'] ?? 'Soria'),
        'postal_code' => sanitizeInput($_POST['postal_code'] ?? ''),
        'latitude' => !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null,
        'longitude' => !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null,
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'website' => sanitizeInput($_POST['website'] ?? ''),
        'opening_hours' => sanitizeInput($_POST['opening_hours'] ?? ''),
        'best_season' => sanitizeInput($_POST['best_season'] ?? ''),
        'visit_duration' => !empty($_POST['visit_duration']) ? intval($_POST['visit_duration']) : null,
        'entry_fee' => floatval($_POST['entry_fee'] ?? 0.00),
        'entry_fee_details' => sanitizeInput($_POST['entry_fee_details'] ?? ''),
        'accessibility' => sanitizeInput($_POST['accessibility'] ?? ''),
        'facilities' => sanitizeInput($_POST['facilities'] ?? ''),
        'languages_available' => sanitizeInput($_POST['languages_available'] ?? ''),
        'pet_friendly' => isset($_POST['pet_friendly']) ? 1 : 0,
        'suitable_for_children' => isset($_POST['suitable_for_children']) ? 1 : 0,
        'photo1' => sanitizeInput($_POST['photo1'] ?? ''),
        'photo2' => sanitizeInput($_POST['photo2'] ?? ''),
        'photo3' => sanitizeInput($_POST['photo3'] ?? ''),
        'photo4' => sanitizeInput($_POST['photo4'] ?? ''),
        'gallery' => sanitizeInput($_POST['gallery'] ?? ''),
        'video_url' => sanitizeInput($_POST['video_url'] ?? ''),
        'virtual_tour_url' => sanitizeInput($_POST['virtual_tour_url'] ?? ''),
        'meta_title' => sanitizeInput($_POST['meta_title'] ?? ''),
        'meta_description' => sanitizeInput($_POST['meta_description'] ?? ''),
        'keywords' => sanitizeInput($_POST['keywords'] ?? ''),
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'is_active' => 1, // Por defecto activo
        'views_count' => 0,
        'rating_avg' => 0.00,
        'reviews_count' => 0,
        'created_by' => null, // TODO: implementar sistema de usuarios
        'verified' => 0,
        'verified_at' => null
    ];

    // Validaciones básicas
    if (empty($data['name'])) {
        jsonError('El nombre es obligatorio', 400);
    }

    if (empty($data['category_id'])) {
        jsonError('La categoría es obligatoria', 400);
    }

    if (empty($data['municipality'])) {
        jsonError('El municipio es obligatorio', 400);
    }

    // Generar slug si no se proporcionó
    if (empty($data['slug'])) {
        $data['slug'] = generateSlug($data['name']);
    }

    // Verificar que el slug sea único
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM places_of_interest WHERE slug = ?");
    $stmt->execute([$data['slug']]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        // Agregar sufijo numérico si el slug ya existe
        $counter = 1;
        $originalSlug = $data['slug'];
        do {
            $data['slug'] = $originalSlug . '-' . $counter;
            $stmt->execute([$data['slug']]);
            $result = $stmt->fetch();
            $counter++;
        } while ($result['count'] > 0);
    }

    // Preparar consulta de inserción
    $sql = "INSERT INTO places_of_interest (
        name, slug, category_id, subcategory_id, description, short_description,
        address, municipality, province, postal_code, latitude, longitude,
        phone, email, website, opening_hours, best_season, visit_duration,
        entry_fee, entry_fee_details, accessibility, facilities, languages_available,
        pet_friendly, suitable_for_children, photo1, photo2, photo3, photo4,
        gallery, video_url, virtual_tour_url, meta_title, meta_description,
        keywords, is_featured, is_active, views_count, rating_avg, reviews_count,
        created_by, verified, verified_at, created_at, updated_at
    ) VALUES (
        :name, :slug, :category_id, :subcategory_id, :description, :short_description,
        :address, :municipality, :province, :postal_code, :latitude, :longitude,
        :phone, :email, :website, :opening_hours, :best_season, :visit_duration,
        :entry_fee, :entry_fee_details, :accessibility, :facilities, :languages_available,
        :pet_friendly, :suitable_for_children, :photo1, :photo2, :photo3, :photo4,
        :gallery, :video_url, :virtual_tour_url, :meta_title, :meta_description,
        :keywords, :is_featured, :is_active, :views_count, :rating_avg, :reviews_count,
        :created_by, :verified, :verified_at, NOW(), NOW()
    )";

    $stmt = $pdo->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':slug', $data['slug']);
    $stmt->bindParam(':category_id', $data['category_id']);
    $stmt->bindParam(':subcategory_id', $data['subcategory_id'], PDO::PARAM_INT);
    $stmt->bindParam(':description', $data['description']);
    $stmt->bindParam(':short_description', $data['short_description']);
    $stmt->bindParam(':address', $data['address']);
    $stmt->bindParam(':municipality', $data['municipality']);
    $stmt->bindParam(':province', $data['province']);
    $stmt->bindParam(':postal_code', $data['postal_code']);
    $stmt->bindParam(':latitude', $data['latitude']);
    $stmt->bindParam(':longitude', $data['longitude']);
    $stmt->bindParam(':phone', $data['phone']);
    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':website', $data['website']);
    $stmt->bindParam(':opening_hours', $data['opening_hours']);
    $stmt->bindParam(':best_season', $data['best_season']);
    $stmt->bindParam(':visit_duration', $data['visit_duration'], PDO::PARAM_INT);
    $stmt->bindParam(':entry_fee', $data['entry_fee']);
    $stmt->bindParam(':entry_fee_details', $data['entry_fee_details']);
    $stmt->bindParam(':accessibility', $data['accessibility']);
    $stmt->bindParam(':facilities', $data['facilities']);
    $stmt->bindParam(':languages_available', $data['languages_available']);
    $stmt->bindParam(':pet_friendly', $data['pet_friendly'], PDO::PARAM_INT);
    $stmt->bindParam(':suitable_for_children', $data['suitable_for_children'], PDO::PARAM_INT);
    $stmt->bindParam(':photo1', $data['photo1']);
    $stmt->bindParam(':photo2', $data['photo2']);
    $stmt->bindParam(':photo3', $data['photo3']);
    $stmt->bindParam(':photo4', $data['photo4']);
    $stmt->bindParam(':gallery', $data['gallery']);
    $stmt->bindParam(':video_url', $data['video_url']);
    $stmt->bindParam(':virtual_tour_url', $data['virtual_tour_url']);
    $stmt->bindParam(':meta_title', $data['meta_title']);
    $stmt->bindParam(':meta_description', $data['meta_description']);
    $stmt->bindParam(':keywords', $data['keywords']);
    $stmt->bindParam(':is_featured', $data['is_featured'], PDO::PARAM_INT);
    $stmt->bindParam(':is_active', $data['is_active'], PDO::PARAM_INT);
    $stmt->bindParam(':views_count', $data['views_count'], PDO::PARAM_INT);
    $stmt->bindParam(':rating_avg', $data['rating_avg']);
    $stmt->bindParam(':reviews_count', $data['reviews_count'], PDO::PARAM_INT);
    $stmt->bindParam(':created_by', $data['created_by'], PDO::PARAM_INT);
    $stmt->bindParam(':verified', $data['verified'], PDO::PARAM_INT);
    $stmt->bindParam(':verified_at', $data['verified_at']);

    // Ejecutar consulta
    $stmt->execute();

    // Obtener ID del registro insertado
    $placeId = $pdo->lastInsertId();

    // Respuesta exitosa
    jsonSuccess([
        'id' => $placeId,
        'slug' => $data['slug'],
        'message' => 'Lugar de interés creado correctamente'
    ], 'Lugar de interés guardado correctamente');

} catch (PDOException $e) {
    jsonError('Error al guardar el lugar de interés: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    jsonError('Error inesperado: ' . $e->getMessage(), 500);
}

/**
 * Genera un slug a partir de un texto
 */
function generateSlug($text) {
    // Convertir a minúsculas
    $slug = strtolower($text);

    // Reemplazar caracteres especiales
    $slug = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
        ['a', 'e', 'i', 'o', 'u', 'n', 'u'],
        $slug
    );

    // Remover caracteres no alfanuméricos excepto espacios y guiones
    $slug = preg_replace('/[^\w\s-]/', '', $slug);

    // Reemplazar espacios y guiones múltiples con un solo guion
    $slug = preg_replace('/[\s_-]+/', '-', $slug);

    // Remover guiones al inicio y final
    $slug = trim($slug, '-');

    return $slug;
}
?>
