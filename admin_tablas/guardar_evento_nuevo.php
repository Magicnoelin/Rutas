<?php
// 1. Mostrar errores para saber qué pasa si falla
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. CONFIGURACIÓN DE CONEXIÓN
$host = 'localhost';
$db   = 'nombre_de_tu_base_de_datos'; // <--- CAMBIA ESTO
$user = 'usuario';                   // <--- CAMBIA ESTO
$pass = 'contraseña';                // <--- CAMBIA ESTO
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}

// 3. PROCESAR EL FORMULARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Mapeo de campos basado en tu tabla cultural_events
        $data = [
            ':name'                => $_POST['name'] ?? '',
            ':slug'                => $_POST['slug'] ?? '',
            ':category_id'         => (int)($_POST['category_id'] ?? 0),
            ':subcategory_id'      => !empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null,
            ':description'         => $_POST['description'] ?? null,
            ':short_description'   => $_POST['short_description'] ?? null,
            ':venue_name'          => $_POST['venue_name'] ?? null,
            ':venue_address'       => $_POST['venue_address'] ?? null,
            ':postal_code'         => $_POST['postal_code'] ?? null,
            ':municipality'        => $_POST['municipality'] ?? '',
            ':province'            => $_POST['province'] ?? 'Soria',
            ':latitude'            => !empty($_POST['latitude']) ? $_POST['latitude'] : null,
            ':longitude'           => !empty($_POST['longitude']) ? $_POST['longitude'] : null,
            ':start_date'          => $_POST['start_date'] ?? date('Y-m-d'),
            ':end_date'            => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            ':start_time'          => !empty($_POST['start_time']) ? $_POST['start_time'] : null,
            ':end_time'            => !empty($_POST['end_time']) ? $_POST['end_time'] : null,
            ':is_recurring'        => isset($_POST['is_recurring']) ? 1 : 0,
            ':recurrence_pattern'  => $_POST['recurrence_pattern'] ?? null,
            ':all_day_event'       => isset($_POST['all_day_event']) ? 1 : 0,
            ':organizer'           => $_POST['organizer'] ?? null,
            ':organizer_contact'   => $_POST['organizer_contact'] ?? null,
            ':program'             => $_POST['program'] ?? null,
            ':is_free'             => isset($_POST['is_free']) ? 1 : 0,
            ':ticket_price'        => !empty($_POST['ticket_price']) ? $_POST['ticket_price'] : 0,
            ':ticket_price_range'  => $_POST['ticket_price_range'] ?? null,
            ':ticket_url'          => $_POST['ticket_url'] ?? null,
            ':capacity'            => !empty($_POST['capacity']) ? (int)$_POST['capacity'] : null,
            ':booking_required'    => isset($_POST['booking_required']) ? 1 : 0,
            ':target_audience'     => $_POST['target_audience'] ?? null,
            ':accessibility'       => $_POST['accessibility'] ?? null,
            ':languages_available' => $_POST['languages_available'] ?? null,
            ':dress_code'          => $_POST['dress_code'] ?? null,
            ':photo1'              => $_POST['photo1'] ?? null,
            ':photo2'              => $_POST['photo2'] ?? null,
            ':photo3'              => $_POST['photo3'] ?? null,
            ':photo4'              => $_POST['photo4'] ?? null,
            ':poster_image'        => $_POST['poster_image'] ?? null,
            ':gallery'             => $_POST['gallery'] ?? null,
            ':video_url'           => $_POST['video_url'] ?? null,
            ':phone'               => $_POST['phone'] ?? null,
            ':email'               => $_POST['email'] ?? null,
            ':website'             => $_POST['website'] ?? null,
            ':social_media'        => $_POST['social_media'] ?? null,
            ':meta_title'          => $_POST['meta_title'] ?? null,
            ':meta_description'    => $_POST['meta_description'] ?? null,
            ':is_featured'         => isset($_POST['is_featured']) ? 1 : 0,
            ':is_active'           => isset($_POST['is_active']) ? 1 : 0,
            ':status'              => $_POST['status'] ?? 'scheduled',
            ':verified'            => isset($_POST['verified']) ? 1 : 0
        ];

        // SQL específico para la tabla cultural_events
        $sql = "INSERT INTO cultural_events (
            name, slug, category_id, subcategory_id, description, short_description, 
            venue_name, venue_address, postal_code, municipality, province, 
            latitude, longitude, start_date, end_date, start_time, end_time, 
            is_recurring, recurrence_pattern, all_day_event, organizer, 
            organizer_contact, program, is_free, ticket_price, ticket_price_range, 
            ticket_url, capacity, booking_required, target_audience, accessibility, 
            languages_available, dress_code, photo1, photo2, photo3, photo4, 
            poster_image, gallery, video_url, phone, email, website, social_media, 
            meta_title, meta_description, is_featured, is_active, status, verified
        ) VALUES (
            :name, :slug, :category_id, :subcategory_id, :description, :short_description, 
            :venue_name, :venue_address, :postal_code, :municipality, :province, 
            :latitude, :longitude, :start_date, :end_date, :start_time, :end_time, 
            :is_recurring, :recurrence_pattern, :all_day_event, :organizer, 
            :organizer_contact, :program, :is_free, :ticket_price, :ticket_price_range, 
            :ticket_url, :capacity, :booking_required, :target_audience, :accessibility, 
            :languages_available, :dress_code, :photo1, :photo2, :photo3, :photo4, 
            :poster_image, :gallery, :video_url, :phone, :email, :website, :social_media, 
            :meta_title, :meta_description, :is_featured, :is_active, :status, :verified
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        // Si llega aquí, es que SÍ guardó
        echo "<h2>✅ Guardado con éxito en cultural_events</h2>";
        echo "ID generado: " . $pdo->lastInsertId();
        echo "<br><a href='eventos_nuevo.php'>Insertar otro</a>";

    } catch (PDOException $e) {
        // Si hay un error de SQL, lo dirá aquí
        echo "<h2>❌ Error de SQL</h2>";
        echo "Detalles: " . $e->getMessage();
    }
} else {
    echo "No se recibió ninguna petición POST directa.";
}
?>