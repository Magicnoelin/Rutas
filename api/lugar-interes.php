<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once 'config.php';

$response = ['success' => false, 'data' => null, 'message' => ''];

try {
    $slug = isset($_GET['slug']) ? $_GET['slug'] : '';
    
    if (empty($slug)) {
        $response['message'] = 'Slug no proporcionado';
        echo json_encode($response);
        exit;
    }

    // Conectar a la base de datos
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");

    // Sanitize slug - permitir guiones bajos también
    $slug = strtolower(trim($slug));
    $slug = preg_replace('/[^a-z0-9\-_]/', '', $slug);

    // Buscar por slug
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.slug,
            p.name,
            p.description,
            p.short_description,
            p.address,
            p.municipality,
            p.province,
            p.postal_code,
            p.latitude,
            p.longitude,
            p.phone,
            p.email,
            p.website,
            p.opening_hours,
            p.best_season,
            p.visit_duration,
            p.entry_fee,
            p.entry_fee_details,
            p.accessibility,
            p.facilities,
            p.languages_available,
            p.pet_friendly,
            p.suitable_for_children,
            p.photo1,
            p.photo2,
            p.photo3,
            p.photo4,
            p.gallery,
            p.video_url,
            p.virtual_tour_url,
            p.category_id,
            p.subcategory_id,
            p.is_featured,
            p.is_active,
            p.views_count,
            p.rating_avg,
            p.reviews_count,
            p.meta_title,
            p.meta_description,
            c.name as category_name
        FROM places_of_interest p
        LEFT JOIN categories_places c ON p.category_id = c.id
        WHERE p.slug = :slug AND p.is_active = 1
        LIMIT 1
    ");
    
    $stmt->execute(['slug' => $slug]);
    $lugar = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lugar) {
        $response['message'] = 'Lugar de interés no encontrado';
        echo json_encode($response);
        exit;
    }

    // Obtener fotos con créditos desde entity_photos
    $fotos = [];
    $fotoCreditos = [];
    $primerCredito = null;
    
    try {
        $stmtFotos = $pdo->prepare("
            SELECT file_url, author_name, author_instagram
            FROM entity_photos 
            WHERE entity_type = 'places_of_interest' 
            AND entity_id = ? 
            AND permission_status = 'approved' 
            AND status = 'active'
            ORDER BY is_cover DESC, featured DESC, uploaded_at DESC
        ");
        $stmtFotos->execute([$lugar['id']]);
        $fotosEntity = $stmtFotos->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($fotosEntity)) {
            // Usar fotos de entity_photos
            foreach ($fotosEntity as $foto) {
                if (!empty($foto['file_url'])) {
                    $fotoUrl = '/' . ltrim(str_replace('\\', '/', $foto['file_url']), '/');
                    $fotos[] = $fotoUrl;
                    $fotoCreditos[$fotoUrl] = [
                        'author' => $foto['author_name'],
                        'instagram' => $foto['author_instagram']
                    ];
                    if (!$primerCredito) {
                        $primerCredito = [
                            'author' => $foto['author_name'],
                            'instagram' => $foto['author_instagram']
                        ];
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Si falla, continuar sin créditos de entity_photos
    }
    
    // Si no hay fotos de entity_photos, usar las legacy
    if (empty($fotos)) {
        if (!empty($lugar['photo1'])) $fotos[] = $lugar['photo1'];
        if (!empty($lugar['photo2'])) $fotos[] = $lugar['photo2'];
        if (!empty($lugar['photo3'])) $fotos[] = $lugar['photo3'];
        if (!empty($lugar['photo4'])) $fotos[] = $lugar['photo4'];
        
        // Procesar gallery JSON si existe
        if (!empty($lugar['gallery'])) {
            $gallery = json_decode($lugar['gallery'], true);
            if (is_array($gallery)) {
                $fotos = array_merge($fotos, $gallery);
            }
        }
    }
    
    // Si aún no hay fotos, usar la imagen por defecto
    if (empty($fotos)) {
        $fotos = ['interest_places_images/Patrocinio.webp'];
    }

    // Procesar características
    $facilities = [];
    if (!empty($lugar['facilities'])) {
        $facilities = json_decode($lugar['facilities'], true);
        if (!is_array($facilities)) {
            $facilities = [];
        }
    }

    // Procesar idiomas
    $languages = [];
    if (!empty($lugar['languages_available'])) {
        $languages = json_decode($lugar['languages_available'], true);
        if (!is_array($languages)) {
            $languages = [];
        }
    }

    // Formatear precio
    $precio = !empty($lugar['entry_fee']) && $lugar['entry_fee'] > 0 
        ? $lugar['entry_fee'] . '€' 
        : 'Entrada gratuita';

    // Canonical URL
    $canonicalUrl = 'https://rutasurales.io/lugar-interes.html?slug=' . $lugar['slug'];

    // Construir respuesta
    $response['success'] = true;
    $response['data'] = [
        'id' => $lugar['id'],
        'slug' => $lugar['slug'],
        'nombre' => $lugar['name'],
        'descripcion' => $lugar['description'],
        'descripcion_corta' => $lugar['short_description'],
        'direccion' => $lugar['address'],
        'localidad' => $lugar['municipality'],
        'provincia' => $lugar['province'],
        'codigo_postal' => $lugar['postal_code'],
        'latitud' => $lugar['latitude'],
        'longitud' => $lugar['longitude'],
        'telefono' => $lugar['phone'],
        'email' => $lugar['email'],
        'web' => $lugar['website'],
        'horario' => $lugar['opening_hours'],
        'temporada_mejor' => $lugar['best_season'],
        'duracion_visita' => $lugar['visit_duration'],
        'precio' => $precio,
        'precio_detalles' => $lugar['entry_fee_details'],
        'accesibilidad' => $lugar['accessibility'],
        'instalaciones' => $facilities,
        'idiomas' => $languages,
        'mascotas' => $lugar['pet_friendly'],
        'ninos_adaptado' => $lugar['suitable_for_children'],
        'fotos' => $fotos,
        'foto_creditos' => $fotoCreditos,
        'primer_credito' => $primerCredito,
        'video_url' => $lugar['video_url'],
        'tour_virtual' => $lugar['virtual_tour_url'],
        'category_id' => $lugar['category_id'],
        'subcategory_id' => $lugar['subcategory_id'],
        'category_name' => $lugar['category_name'],
        'subcategory_name' => null,
        'destacado' => $lugar['is_featured'],
        'visitas' => $lugar['views_count'],
        'rating' => $lugar['rating_avg'],
        'reviews' => $lugar['reviews_count'],
        'meta_title' => $lugar['meta_title'],
        'meta_description' => $lugar['meta_description'],
        'canonical_url' => $canonicalUrl
    ];

} catch (PDOException $e) {
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    error_log("Error en lugar-interes.php: " . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log("Error en lugar-interes.php: " . $e->getMessage());
}

echo json_encode($response);
