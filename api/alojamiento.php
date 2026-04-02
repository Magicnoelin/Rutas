<?php
/**
 * API Endpoint: Obtener Un Alojamiento Específico
 * GET /api/alojamiento.php?id=XXX
 */

require_once 'config.php';

// Solo permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Método no permitido', 405);
}

// Verificar que se proporcione el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    jsonError('ID de alojamiento requerido', 400);
}

try {
    $pdo = getDBConnection();
    $id = sanitizeInput($_GET['id']);
    
    // Obtener alojamiento por ID
    $sql = "SELECT * FROM " . DB_TABLE . " WHERE ID = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    
    $alojamiento = $stmt->fetch();
    
    if (!$alojamiento) {
        jsonError('Alojamiento no encontrado', 404);
    }
    
    // Procesar datos
    $alojamiento['Plazas'] = intval($alojamiento['Plazas']);
    
    if (!empty($alojamiento['Precio'])) {
        $alojamiento['Precio'] = floatval($alojamiento['Precio']);
    }
    
    // Crear array de fotos
    $fotos = [];
    for ($i = 1; $i <= 4; $i++) {
        $fotoKey = 'Foto' . $i;
        if (!empty($alojamiento[$fotoKey])) {
            $fotos[] = $alojamiento[$fotoKey];
        }
    }
    $alojamiento['Fotos'] = $fotos;
    
    // Obtener eventos vinculados
    $alojamiento['eventos_vinculados'] = [];
    try {
        $sqlEvents = "SELECT e.* FROM cultural_events e
                      JOIN accommodation_event_links link ON e.id = link.event_id
                      WHERE link.accommodation_id = :acc_id
                      AND e.is_active = 1
                      AND COALESCE(e.end_date, DATE_ADD(e.start_date, INTERVAL 1 DAY)) >= CURDATE()
                      ORDER BY e.start_date ASC";
        $stmtEvents = $pdo->prepare($sqlEvents);
        $stmtEvents->bindValue(':acc_id', $alojamiento['ID']);
        $stmtEvents->execute();
        $eventos = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($eventos as &$evento) {
            // Formatear imagen
            $imagen = $evento['poster_image'] ?: $evento['photo1'];
            if (empty($imagen)) {
                $imagen = "/cultural_events_images/" . $evento['slug'] . ".webp";
            }
            $evento['imagen'] = $imagen;
            
            // Formatear fecha
            if (!empty($evento['start_date'])) {
                $fecha = new DateTime($evento['start_date']);
                $evento['fecha_formateada'] = $fecha->format('d/m/Y');
            }
        }
        $alojamiento['eventos_vinculados'] = $eventos;
    } catch (Exception $e) {
        // No bloqueamos si fallan los eventos
    }

    // Extraer localidad y provincia
    if (!empty($alojamiento['Direccion'])) {
        $partes = explode(' ', $alojamiento['Direccion']);
        $alojamiento['Localidad'] = '';
        $alojamiento['Provincia'] = '';
        
        if (count($partes) > 2) {
            $alojamiento['Provincia'] = $partes[count($partes) - 1];
            if (count($partes) > 3) {
                $alojamiento['Localidad'] = $partes[count($partes) - 2];
            }
        }
    }
    
    jsonSuccess($alojamiento, 'Alojamiento obtenido correctamente');
    
} catch (PDOException $e) {
    jsonError('Error al obtener alojamiento: ' . $e->getMessage(), 500);
