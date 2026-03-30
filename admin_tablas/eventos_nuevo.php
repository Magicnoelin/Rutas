<?php
// 1. Configuración de la base de datos (RELLENA CON TUS DATOS)
$host = "localhost";
$db   = "u412199647_Rutas";
$user = "u412199647_olgamarin";
$pass = "Rutas5Rurales7$";
$charset = 'utf8mb4';

$mensaje = "";

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// 2. PROCESAMIENTO DEL FORMULARIO
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $sql = "INSERT INTO cultural_events (
            name, slug, category_id, subcategory_id, description, short_description, 
            venue_name, venue_address, postal_code, municipality, province, 
            latitude, longitude, start_date, end_date, start_time, end_time, 
            is_recurring, recurrence_pattern, all_day_event, organizer, 
            organizer_contact, program, is_free, ticket_price, ticket_price_range, 
            ticket_url, capacity, booking_required, target_audience, accessibility, 
            languages_available, dress_code, photo1, photo2, photo3, photo4, 
            poster_image, video_url, phone, email, website, social_media, 
            meta_title, meta_description, is_featured, is_active, status, verified
        ) VALUES (
            :name, :slug, :category_id, :subcategory_id, :description, :short_description, 
            :venue_name, :venue_address, :postal_code, :municipality, :province, 
            :latitude, :longitude, :start_date, :end_date, :start_time, :end_time, 
            :is_recurring, :recurrence_pattern, :all_day_event, :organizer, 
            :organizer_contact, :program, :is_free, :ticket_price, :ticket_price_range, 
            :ticket_url, :capacity, :booking_required, :target_audience, :accessibility, 
            :languages_available, :dress_code, :photo1, :photo2, :photo3, :photo4, 
            :poster_image, :video_url, :phone, :email, :website, :social_media, 
            :meta_title, :meta_description, :is_featured, :is_active, :status, :verified
        )";

        $stmt = $pdo->prepare($sql);
        
        // Ejecución vinculando los campos del POST
        $stmt->execute([
            ':name'                => $_POST['name'],
            ':slug'                => $_POST['slug'],
            ':category_id'         => (int)$_POST['category_id'],
            ':subcategory_id'      => !empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null,
            ':description'         => $_POST['description'] ?? null,
            ':short_description'   => $_POST['short_description'] ?? null,
            ':venue_name'          => $_POST['venue_name'] ?? null,
            ':venue_address'       => $_POST['venue_address'] ?? null,
            ':postal_code'         => $_POST['postal_code'] ?? null,
            ':municipality'        => $_POST['municipality'],
            ':province'            => $_POST['province'] ?? 'Soria',
            ':latitude'            => !empty($_POST['latitude']) ? $_POST['latitude'] : null,
            ':longitude'           => !empty($_POST['longitude']) ? $_POST['longitude'] : null,
            ':start_date'          => $_POST['start_date'],
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
        ]);

        $mensaje = "<div class='alert alert-success'>✅ ¡Evento guardado con éxito en cultural_events! ID: " . $pdo->lastInsertId() . "</div>";
    } catch (PDOException $e) {
        $mensaje = "<div class='alert alert-danger'>❌ Error al guardar: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Evento - RutasRurales.io</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: #f4f7f6; padding: 20px; }
        .form-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 50px; }
        h2 { border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 25px; color: #2c3e50; }
        .section-title { background: #e9ecef; padding: 10px; border-radius: 4px; margin: 20px 0; font-weight: bold; color: #495057; }
    </style>
</head>
<body>

<div class="container form-container">
    <h2>Añadir Nuevo Evento</h2>
    
    <?php echo $mensaje; ?>
    
    <form action="" method="POST">
        
        <div class="mt-5 mb-5 border-top pt-4 text-end">
            <div class="form-check form-check-inline text-start float-start">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                <label class="form-check-label">Publicar inmediatamente</label>
            </div>
            
            <button type="submit" class="btn btn-success btn-lg px-5 shadow-sm">
                <i class="bi bi-save"></i> Guardar Evento en cultural_events
            </button>
        </div>
        
        <div class="section-title">Información Principal</div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nombre del Evento *</label>
                <input type="text" name="name" class="form-control" required placeholder="Ej: Fiestas de San Juan">
            </div>
            <div class="col-md-3">
                <label class="form-label">Slug (URL)</label>
                <input type="text" name="slug" class="form-control" placeholder="ej-fiestas-san-juan">
            </div>
            <div class="col-md-3">
                <label class="form-label">Estado</label>
                <select name="status" class="form-select">
                    <option value="scheduled">Programado</option>
                    <option value="ongoing">En curso</option>
                    <option value="completed">Finalizado</option>
                    <option value="cancelled">Cancelado</option>
                </select>
            </div>
            
            <div class="col-md-6">
                <label class="form-label">Categoría Principal</label>
                <select name="category_id" class="form-select" required>
                    <option value="1">Fiestas Populares</option>
                    <option value="6">Cultura y Espectáculos</option>
                    <option value="12">Gastronomía y Ferias</option>
                    <option value="17">Deportes</option>
                    <option value="22">Religión y Tradición</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Subcategoría</label>
                <select name="subcategory_id" class="form-select">
                    <option value="">Ninguna</option>
                    <optgroup label="Fiestas Populares">
                        <option value="2">Fiestas Patronales</option>
                        <option value="3">Fiestas Tradicionales</option>
                        <option value="4">Romerías</option>
                        <option value="5">Carnavales</option>
                    </optgroup>
                    <optgroup label="Cultura">
                        <option value="7">Conciertos</option>
                        <option value="8">Teatro</option>
                        <option value="10">Festivales de Música</option>
                    </optgroup>
                    <optgroup label="Gastronomía">
                        <option value="13">Ferias Gastronómicas</option>
                        <option value="15">Mercados Tradicionales</option>
                    </optgroup>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Descripción Corta</label>
                <textarea name="short_description" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Descripción Detallada</label>
                <textarea name="description" class="form-control" rows="5"></textarea>
            </div>
        </div>

        <div class="section-title">Ubicación y Localización</div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Lugar (Venue)</label>
                <input type="text" name="venue_name" class="form-control" placeholder="Plaza Mayor">
            </div>
            <div class="col-md-6">
                <label class="form-label">Dirección</label>
                <input type="text" name="venue_address" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Municipio *</label>
                <input type="text" name="municipality" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Provincia</label>
                <input type="text" name="province" class="form-control" value="Soria">
            </div>
            <div class="col-md-2">
                <label class="form-label">C. Postal</label>
                <input type="text" name="postal_code" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Latitud</label>
                <input type="number" step="any" name="latitude" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Longitud</label>
                <input type="number" step="any" name="longitude" class="form-control">
            </div>
        </div>

        <div class="section-title">Fechas y Horarios</div>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Fecha Inicio *</label>
                <input type="date" name="start_date" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha Fin</label>
                <input type="date" name="end_date" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Hora Inicio</label>
                <input type="time" name="start_time" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Hora Fin</label>
                <input type="time" name="end_time" class="form-control">
            </div>
            <div class="col-md-4">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="all_day_event" value="1">
                    <label class="form-check-label">Evento de todo el día</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="is_recurring" value="1">
                    <label class="form-check-label">Es recurrente</label>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Patrón Recurrencia</label>
                <input type="text" name="recurrence_pattern" class="form-control">
            </div>
        </div>

        <div class="section-title">Entradas y Acceso</div>
        <div class="row g-3">
            <div class="col-md-3">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="is_free" value="1" checked>
                    <label class="form-check-label">Es gratuito</label>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Precio (€)</label>
                <input type="number" step="0.01" name="ticket_price" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">URL Venta</label>
                <input type="url" name="ticket_url" class="form-control">
            </div>
        </div>

        <div class="section-title">Multimedia y Contacto</div>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Portada (URL)</label>
                <input type="text" name="poster_image" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Foto 1 (URL)</label>
                <input type="text" name="photo1" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Teléfono</label>
                <input type="text" name="phone" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="