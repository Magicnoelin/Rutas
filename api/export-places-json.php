<?php
header('Content-Type: application/json');

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");

    $stmt = $pdo->query("
        SELECT id, slug, name, description, short_description, address, municipality, province,
               postal_code, latitude, longitude, phone, email, website, opening_hours,
               best_season, visit_duration, entry_fee, entry_fee_details, accessibility,
               facilities, languages_available, pet_friendly, suitable_for_children,
               photo1, photo2, photo3, photo4, gallery, video_url, virtual_tour_url,
               category_id, subcategory_id, is_featured, is_active, views_count,
               rating_avg, reviews_count, meta_title, meta_description, created_at, updated_at
        FROM places_of_interest 
        WHERE is_active = 1
        ORDER BY name
    ");

    $lugares = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Guardar en archivo
    $json = json_encode(['places_of_interest' => $lugares], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents('../places_of_interest.json', $json);

    echo "✅生成完成！Guardado como places_of_interest.json con " . count($lugares) . " lugares.\n";
    echo "📁 Archivo: " . realpath('../places_of_interest.json') . "\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
