<?php
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    echo json_encode(['success'=>false, 'message'=>'Slug no proporcionado']);
    exit;
}

$slug = $_GET['slug'];

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $stmt = $pdo->prepare("SELECT * FROM tourist_activities WHERE slug = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$slug]);
    $actividad = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$actividad) {
        echo json_encode(['success'=>false, 'message'=>'Actividad no encontrada']);
        exit;
    }

    // Procesar datos
    $fotos = array_filter([$actividad['photo1'], $actividad['photo2'], $actividad['photo3'], $actividad['photo4']]);
    $precioAdulto = !empty($actividad['price_adult']) && $actividad['price_adult'] > 0 ? $actividad['price_adult'].'€' : 'Gratis';
    // Si el precio del niño es negativo o 0, no mostrar nada (en lugar de "Gratis")
    $precioNino = !empty($actividad['price_child']) && $actividad['price_child'] > 0 ? $actividad['price_child'].'€' : '';
    // Si el precio del grupo es negativo o 0, no mostrar nada (en lugar de "Gratis")
    $precioGrupo = !empty($actividad['price_group']) && $actividad['price_group'] > 0 ? $actividad['price_group'].'€' : '';

    // Procesar JSON fields
    $temporadas = json_decode($actividad['available_seasons'], true);
    $dias = json_decode($actividad['available_days'], true);
    $horario = json_decode($actividad['schedule'], true);
    $requisitos = json_decode($actividad['requirements'], true);
    $queLlevar = json_decode($actividad['what_to_bring'], true);
    $equipoProporcionado = json_decode($actividad['provided_equipment'], true);
    $idiomas = json_decode($actividad['languages_available'], true);
    $accesibilidad = json_decode($actividad['accessibility'], true);

    $data = [
        'id' => $actividad['id'],
        'nombre' => $actividad['name'],
        'slug' => $actividad['slug'],
        'descripcion' => $actividad['description'],
        'descripcion_corta' => $actividad['short_description'],
        'localidad' => $actividad['municipality'],
        'provincia' => $actividad['province'],
        'punto_encuentro' => $actividad['meeting_point'],
        'latitud' => $actividad['latitude'],
        'longitud' => $actividad['longitude'],
        'duracion' => $actividad['duration'],
        'dificultad' => $actividad['difficulty_level'],
        'edad_minima' => $actividad['min_age'],
        'edad_maxima' => $actividad['max_age'],
        'participantes_min' => $actividad['min_participants'],
        'participantes_max' => $actividad['max_participants'],
        'precio_adulto' => $precioAdulto,
        'precio_nino' => $precioNino,
        'precio_grupo' => $precioGrupo,
        'detalles_precio' => $actividad['price_details'],
        'incluye' => $actividad['includes'],
        'no_incluye' => $actividad['not_includes'],
        'temporadas' => $temporadas,
        'dias_disponibles' => $dias,
        'horario' => $horario,
        'reserva_requerida' => $actividad['booking_required'],
        'dias_antelacion' => $actividad['advance_booking_days'],
        'requisitos' => $requisitos,
        'que_llevar' => $queLlevar,
        'equipo_proporcionado' => $equipoProporcionado,
        'proveedor' => $actividad['provider_name'],
        'proveedor_id' => $actividad['provider_id'],
        'telefono' => $actividad['contact_phone'],
        'email' => $actividad['contact_email'],
        'web' => $actividad['website'],
        'url_reserva' => $actividad['booking_url'],
        'idiomas' => $idiomas,
        'aptos_para_familias' => $actividad['suitable_for_families'],
        'admite_mascotas' => $actividad['pet_friendly'],
        'interior_exterior' => $actividad['indoor_outdoor'],
        'depende_clima' => $actividad['weather_dependent'],
        'politica_cancelacion' => $actividad['cancellation_policy'],
        'fotos' => $fotos,
        'galeria' => json_decode($actividad['gallery'], true),
        'video' => $actividad['video_url'],
        'meta_titulo' => $actividad['meta_title'],
        'meta_descripcion' => $actividad['meta_description'],
        'categoria' => $actividad['category_id'],
        'subcategoria' => $actividad['subcategory_id']
    ];

    echo json_encode(['success'=>true, 'data'=>$data]);
} catch(Exception $e) {
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
?>
