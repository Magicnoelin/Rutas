<?php
/**
 * Script FINAL para actualizar get_nearby_content.php
 * Incluye TODAS las correcciones:
 * 1. Parámetros SQL posicionales
 * 2. start_date/end_date en lugar de event_date
 * 3. Eliminación de columna price que no existe
 * 
 * Sube este archivo al servidor y ejecútalo desde el navegador
 */

$targetFile = __DIR__ . '/api/get_nearby_content.php';

$newContent = <<<'PHP'
<?php
/**
 * API Endpoint: Obtener contenido cercano a un alojamiento
 * Devuelve lugares de interés, actividades turísticas y eventos culturales
 * de la misma localidad/provincia que el alojamiento
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDBConnection();
    
    // Obtener parámetros
    $accommodationId = $_GET['accommodation_id'] ?? null;
    $municipality = $_GET['municipality'] ?? null;
    $province = $_GET['province'] ?? null;
    
    // Log para debugging
    error_log("get_nearby_content.php - Parámetros recibidos: accommodation_id=$accommodationId, municipality=$municipality, province=$province");
    
    // Si se proporciona ID de alojamiento, obtener su ubicación
    if ($accommodationId) {
        $stmt = $pdo->prepare("SELECT municipality, province FROM accommodations WHERE id = ?");
        $stmt->execute([$accommodationId]);
        $accommodation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($accommodation) {
            $municipality = $accommodation['municipality'];
            $province = $accommodation['province'];
            error_log("get_nearby_content.php - Ubicación desde BD: municipality=$municipality, province=$province");
        } else {
            error_log("get_nearby_content.php - No se encontró alojamiento con ID: $accommodationId");
        }
    }
    
    // Validar que tengamos al menos uno de los dos
    if (empty($municipality) && empty($province)) {
        error_log("get_nearby_content.php - ERROR: No hay municipality ni province");
        jsonError('Se requiere municipality, province o accommodation_id válido', 400);
    }
    
    error_log("get_nearby_content.php - Buscando contenido para: municipality=$municipality, province=$province");
    
    // Obtener lugares de interés
    $places = getPlacesOfInterest($pdo, $municipality, $province);
    
    // Obtener actividades turísticas
    $activities = getTouristActivities($pdo, $municipality, $province);
    
    // Obtener eventos culturales (próximos)
    $events = getCulturalEvents($pdo, $municipality, $province);
    
    jsonSuccess([
        'places_of_interest' => $places,
        'tourist_activities' => $activities,
        'cultural_events' => $events,
        'location' => [
            'municipality' => $municipality,
            'province' => $province
        ]
    ]);
    
} catch (Exception $e) {
    jsonError('Error al obtener contenido cercano: ' . $e->getMessage(), 500);
}

// --------------------------------------------------------------------------
// FUNCIONES
// --------------------------------------------------------------------------

function getPlacesOfInterest($pdo, $municipality, $province) {
    $sql = "SELECT id, name, slug, short_description, description, municipality, province, 
                   category_id, latitude, longitude, photo1, photo2, photo3, photo4,
                   opening_hours, phone, website
            FROM places_of_interest 
            WHERE is_active = 1 
            AND (municipality = ? OR province = ?)
            ORDER BY 
                CASE WHEN municipality = ? THEN 0 ELSE 1 END,
                name
            LIMIT 6";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$municipality, $province, $municipality]);
    
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    foreach ($places as $place) {
        $result[] = [
            'id' => $place['id'],
            'name' => $place['name'],
            'slug' => $place['slug'],
            'short_description' => $place['short_description'],
            'description' => $place['description'],
            'municipality' => $place['municipality'],
            'province' => $place['province'],
            'category_id' => $place['category_id'],
            'latitude' => $place['latitude'],
            'longitude' => $place['longitude'],
            'opening_hours' => $place['opening_hours'],
            'contact_phone' => $place['phone'],
            'website' => $place['website'],
            'photos' => array_values(array_filter([
                $place['photo1'],
                $place['photo2'],
                $place['photo3'],
                $place['photo4']
            ])),
            'main_photo' => $place['photo1'] ?: '/menu_images/image_not_found.webp'
        ];
    }
    
    return $result;
}

function getTouristActivities($pdo, $municipality, $province) {
    $sql = "SELECT id, name, slug, short_description, description, municipality, province,
                   category_id, latitude, longitude, photo1, photo2, photo3, photo4,
                   duration, difficulty_level, phone, website
            FROM tourist_activities 
            WHERE is_active = 1 
            AND (municipality = ? OR province = ?)
            ORDER BY 
                CASE WHEN municipality = ? THEN 0 ELSE 1 END,
                name
            LIMIT 6";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$municipality, $province, $municipality]);
    
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    foreach ($activities as $activity) {
        $result[] = [
            'id' => $activity['id'],
            'name' => $activity['name'],
            'slug' => $activity['slug'],
            'short_description' => $activity['short_description'],
            'description' => $activity['description'],
            'municipality' => $activity['municipality'],
            'province' => $activity['province'],
            'category_id' => $activity['category_id'],
            'latitude' => $activity['latitude'],
            'longitude' => $activity['longitude'],
            'duration' => $activity['duration'],
            'difficulty_level' => $activity['difficulty_level'],
            'price' => 'Consultar',
            'contact_phone' => $activity['phone'],
            'website' => $activity['website'],
            'photos' => array_values(array_filter([
                $activity['photo1'],
                $activity['photo2'],
                $activity['photo3'],
                $activity['photo4']
            ])),
            'main_photo' => $activity['photo1'] ?: '/menu_images/image_not_found.webp'
        ];
    }
    
    return $result;
}

function getCulturalEvents($pdo, $municipality, $province) {
    // Solo eventos futuros o del día actual
    $today = date('Y-m-d');
    
    $sql = "SELECT id, title, slug, short_description, description, municipality, province,
                   category_id, start_date, end_date, event_time, venue, address,
                   latitude, longitude, photo1, photo2, photo3, photo4,
                   organizer, phone, email, website
            FROM cultural_events 
            WHERE is_active = 1 
            AND start_date >= ?
            AND (municipality = ? OR province = ?)
            ORDER BY 
                start_date ASC,
                CASE WHEN municipality = ? THEN 0 ELSE 1 END,
                title
            LIMIT 6";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$today, $municipality, $province, $municipality]);
    
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    foreach ($events as $event) {
        // Formatear fecha
        $startDate = new DateTime($event['start_date']);
        $formattedDate = $startDate->format('d/m/Y');
        $dayName = getDayName($startDate->format('N'));
        
        // Formatear end_date si existe
        $endDateFormatted = null;
        if (!empty($event['end_date'])) {
            $endDate = new DateTime($event['end_date']);
            $endDateFormatted = $endDate->format('d/m/Y');
        }
        
        // Usar poster_image o photo1 como imagen principal, con fallback al slug
        $mainPhoto = $event['poster_image'] ?? $event['photo1'] ?? null;
        if (empty($mainPhoto)) {
            $mainPhoto = "/cultural_events_images/" . $event['slug'] . ".webp";
        }

        $result[] = [
            'id' => $event['id'],
            'title' => $event['title'],
            'slug' => $event['slug'],
            'short_description' => $event['short_description'],
            'description' => $event['description'],
            'municipality' => $event['municipality'],
            'province' => $event['province'],
            'category_id' => $event['category_id'],
            'start_date' => $event['start_date'],
            'end_date' => $event['end_date'],
            'start_date_formatted' => $formattedDate,
            'end_date_formatted' => $endDateFormatted,
            'day_name' => $dayName,
            'event_time' => $event['event_time'],
            'venue' => $event['venue'],
            'address' => $event['address'],
            'latitude' => $event['latitude'],
            'longitude' => $event['longitude'],
            'price' => 'Consultar',
            'organizer' => $event['organizer'],
            'contact_phone' => $event['phone'],
            'contact_email' => $event['email'],
            'website' => $event['website'],
            'photos' => array_values(array_filter([
                $event['photo1'],
                $event['photo2'],
                $event['photo3'],
                $event['photo4']
            ])),
            'main_photo' => $mainPhoto
        ];
    }
    
    return $result;
}

function getDayName($dayNumber) {
    $days = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo'
    ];
    return $days[$dayNumber] ?? '';
}
?>
PHP;

// Intentar escribir el archivo
$result = file_put_contents($targetFile, $newContent);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✅ Actualización FINAL - get_nearby_content.php</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success {
            color: #28a745;
            padding: 20px;
            background: #d4edda;
            border: 2px solid #c3e6cb;
            border-radius: 8px;
            margin: 20px 0;
        }
        .error {
            color: #dc3545;
            padding: 20px;
            background: #f8d7da;
            border: 2px solid #f5c6cb;
            border-radius: 8px;
            margin: 20px 0;
        }
        .info {
            color: #004085;
            padding: 20px;
            background: #cce5ff;
            border: 2px solid #b8daff;
            border-radius: 8px;
            margin: 20px 0;
        }
        code {
            background: #f4f4f4;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 15px;
            margin-right: 10px;
            font-weight: bold;
        }
        .btn:hover {
            background: #218838;
        }
        .btn-secondary {
            background: #007bff;
        }
        .btn-secondary:hover {
            background: #0056b3;
        }
        ul {
            line-height: 2;
        }
        h1 {
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Actualización FINAL - get_nearby_content.php</h1>
        
        <?php if ($result !== false): ?>
            <div class="success">
                <h2>🎉 ¡Archivo actualizado correctamente!</h2>
                <p>El archivo <code>api/get_nearby_content.php</code> ha sido actualizado con TODAS las correcciones.</p>
                <p><strong>Bytes escritos:</strong> <?php echo number_format($result); ?></p>
            </div>
            
            <div class="info">
                <h3>✅ Correcciones Aplicadas:</h3>
                <ul>
                    <li><strong>✅ Parámetros SQL:</strong> Cambiados de nombrados a posicionales</li>
                    <li><strong>✅ Fechas de eventos:</strong> Usa <code>start_date</code> y <code>end_date</code></li>
                    <li><strong>✅ Columna price:</strong> Eliminada de SELECT (no existe en BD)</li>
                    <li><strong>✅ Precio por defecto:</strong> Muestra "Consultar" en actividades y eventos</li>
                </ul>
                
                <h3>🧪 Prueba la API ahora:</h3>
                <p>Con el alojamiento ID=50 (Alamarza, Soria):</p>
                <a href="/api/get_nearby_content.php?accommodation_id=50" class="btn" target="_blank">
                    🚀 Probar API con ID=50
                </a>
                
                <p style="margin-top: 20px;">O prueba con parámetros directos:</p>
                <a href="/api/get_nearby_content.php?municipality=Alamarza&province=Soria" class="btn btn-secondary" target="_blank">
                    🗺️ Probar con Alamarza, Soria
                </a>
            </div>
            
            <div class="info" style="margin-top: 30px;">
                <h3>📊 Próximos Pasos:</h3>
                <ol style="line-height: 2;">
                    <li>Verifica que la API devuelve datos correctamente (usa los botones arriba)</li>
                    <li>Prueba tu página en <strong>Google Rich Results Test</strong>:
                        <br><a href="https://search.google.com/test/rich-results" target="_blank" style="color: #007bff;">https://search.google.com/test/rich-results</a>
                    </li>
                    <li>Ingresa la URL: <code>https://rutasrurales.io/alojamiento-detalle.html?id=50</code></li>
                    <li>Verifica que Google puede cargar los resultados enriquecidos</li>
                </ol>
            </div>
            
        <?php else: ?>
            <div class="error">
                <h2>❌ Error al actualizar el archivo</h2>
                <p>No se pudo escribir el archivo. Posibles causas:</p>
                <ul>
                    <li>Permisos insuficientes en el directorio <code>api/</code></li>
                    <li>El archivo está protegido contra escritura</li>
                    <li>Problema con el sistema de archivos del servidor</li>
                </ul>
                
                <h3>💡 Solución alternativa:</h3>
                <p>Sube manualmente el archivo <code>api/get_nearby_content.php</code> por FTP desde tu ordenador local.</p>
                <p>El archivo corregido está en tu carpeta local del proyecto.</p>
            </div>
        <?php endif; ?>
        
        <hr style="margin: 40px 0; border: none; border-top: 2px solid #ddd;">
        <p style="color: #666; font-size: 13px; text-align: center;">
            <strong>Nota de seguridad:</strong> Después de verificar que todo funciona correctamente, 
            elimina este archivo (<code>actualizar_nearby_FINAL.php</code>) del servidor.
        </p>
    </div>
</body>
</html>
