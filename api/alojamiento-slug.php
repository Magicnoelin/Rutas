<?php
ini_set('log_errors', 1);
ini_set('display_errors', 0);
ini_set('error_log', __DIR__ . '/error_log');
error_reporting(E_ALL);


/**
 * API Endpoint: Obtener Alojamiento por Slug
 * GET /api/alojamiento-slug.php?slug=abuela-nines
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Solo permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido'], JSON_PRETTY_PRINT);
    exit();
}

// Verificar que se proporcione el slug o el ID
$slug = isset($_GET['slug']) ? sanitizeInput($_GET['slug']) : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (empty($slug) && empty($id)) {
    echo json_encode(['success' => false, 'error' => 'Slug o ID requerido'], JSON_PRETTY_PRINT);
    exit();
}

try {
    $pdo = getDBConnection();
    
    // SQL base con JOIN a categories_accommodations para obtener el nombre de categoría
    $sqlBase = "SELECT a.*, c.name as category_name
                FROM accommodations a
                LEFT JOIN categories_accommodations c ON a.category_id = c.id";

    if ($id > 0) {
        // Buscar por ID (más preciso y evita duplicados)
        $stmt = $pdo->prepare("$sqlBase WHERE a.id = :id LIMIT 1");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $alojamiento = $stmt->fetch();
    } else {
        // Primero intentar buscar por slug nativo
        $stmt = $pdo->prepare("$sqlBase WHERE a.slug = :slug ORDER BY a.id DESC LIMIT 1");
        $stmt->bindValue(':slug', $slug);
        $stmt->execute();
        $alojamiento = $stmt->fetch();
        
        // Si no se encuentra por slug, intentar generar slug desde el nombre
        if (!$alojamiento) {
            $generatedSlug = strtolower(trim($slug));
            // Quitar acentos y caracteres especiales
            $normalizedSlug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $generatedSlug);
            $normalizedSlug = preg_replace('/[^a-z0-9\-]/', '', $normalizedSlug);
            $normalizedSlug = preg_replace('/-+/', '-', $normalizedSlug);
            $normalizedSlug = trim($normalizedSlug, '-');
            
            // Buscar por nombre coincidente
            $stmt = $pdo->prepare("$sqlBase WHERE LOWER(a.name) LIKE :likeSlug ORDER BY a.id DESC LIMIT 1");
            $stmt->bindValue(':likeSlug', '%' . $normalizedSlug . '%');
            $stmt->execute();
            $alojamiento = $stmt->fetch();
            
            // Si aún no se encuentra, buscar por nombre exacto
            if (!$alojamiento) {
                $nameFromSlug = str_replace('-', ' ', $normalizedSlug);
                $stmt = $pdo->prepare("$sqlBase WHERE LOWER(a.name) = :name ORDER BY a.id DESC LIMIT 1");
                $stmt->bindValue(':name', strtolower($nameFromSlug));
                $stmt->execute();
                $alojamiento = $stmt->fetch();
            }
        }
    }

    if (!$alojamiento) {
        echo json_encode(['success' => false, 'error' => 'Alojamiento no encontrado'], JSON_PRETTY_PRINT);
        exit();
    }
    
    // Procesar datos
    $alojamiento['Plazas'] = intval($alojamiento['capacity'] ?? $alojamiento['Plazas'] ?? 0);
    
    if (!empty($alojamiento['price_per_night'])) {
        $alojamiento['Precio'] = floatval($alojamiento['price_per_night']);
    }
    
    // Crear array de fotos - buscar en TODAS las carpetas posibles
    $fotos = [];
    $slug = $alojamiento['slug'] ?? '';
    for ($i = 1; $i <= 10; $i++) { // Ampliado a 10 fotos
        $fotoKey = 'photo' . $i;
        if (!empty($alojamiento[$fotoKey])) {
            $fotoValue = $alojamiento[$fotoKey];
            
            // Limpiar espacios en blanco y caracteres extraños
            $fotoValue = trim($fotoValue);
            
            // Si ya es una URL completa (https://...), usarla directamente
            if (preg_match('/^https?:\/\//', $fotoValue)) {
                $fotos[] = $fotoValue;
            } else {
                // Es solo el nombre del archivo - buscar en TODAS las carpetas
                $fotoFilename = basename($fotoValue); // Quitar cualquier ruta previa
                
                // Rutas posibles (en orden de prioridad):
                // 1. /accommodations_images/{slug}/ (carpeta correcta según SISTEMA_FOTOS_ALOJAMIENTOS.md)
                
                $ruta1 = 'https://rutasrurales.io/accommodations_images/' . $slug . '/' . $fotoFilename;
                
                // Usar la ruta correcta (accommodations_images/) como principal
                $fotos[] = $ruta1;
                
                // Guardar alternativas para fallback
                if (!isset($alojamiento['FotosAlternativos'])) {
                    $alojamiento['FotosAlternativos'] = [];
                }
                $alojamiento['FotosAlternativos'][] = $ruta1;
            }
        }
    }
    
    // Si no hay fotos, intentar usar las alternativas
    if (count($fotos) === 0 && !empty($alojamiento['FotosAlternativos'])) {
        $fotos = $alojamiento['FotosAlternativos'];
    }
    
    $alojamiento['Fotos'] = $fotos;
    
    // Mapear campos para compatibilidad
    $alojamiento['Nombre'] = $alojamiento['name'] ?? '';
    // Usar category_name (del JOIN con categories_accommodations) como Tipo,
    // con fallback a accommodation_type y luego a 'Alojamiento Rural'
    $alojamiento['Tipo'] = $alojamiento['category_name'] ?? $alojamiento['accommodation_type'] ?? 'Alojamiento Rural';
    $alojamiento['Direccion'] = $alojamiento['address'] ?? '';
    $alojamiento['Telefono1'] = $alojamiento['phone'] ?? '';
    $alojamiento['Email'] = $alojamiento['email'] ?? '';
    $alojamiento['Web'] = $alojamiento['website'] ?? '';
    $alojamiento['Notaspublicas'] = $alojamiento['description'] ?? '';
    $alojamiento['Localidad'] = $alojamiento['municipality'] ?? '';
    $alojamiento['Provincia'] = $alojamiento['province'] ?? '';
    $alojamiento['Registro'] = $alojamiento['registration_number'] ?? '';

    // URL Canónica para SEO
    $alojamiento['canonical_url'] = 'https://rutasrurales.io/alojamiento/' . $alojamiento['slug'];
    
    // Incluir virtual_tour_url y video_url para videos de YouTube
    $alojamiento['virtual_tour_url'] = $alojamiento['virtual_tour_url'] ?? '';
    $alojamiento['video_url'] = $alojamiento['video_url'] ?? '';

    // Obtener información del anfitrión
    $alojamiento['host_info'] = null;
    if (!empty($alojamiento['created_by'])) {
        try {
            $stmtHost = $pdo->prepare("SELECT first_name, last_name, avatar_url FROM users WHERE id = :user_id LIMIT 1");
            $stmtHost->bindValue(':user_id', $alojamiento['created_by']);
            $stmtHost->execute();
            $host = $stmtHost->fetch(PDO::FETCH_ASSOC);
            if ($host) {
                $alojamiento['host_info'] = $host;
            }
        } catch (Exception $e) {
            error_log("Error al obtener info del anfitrión: " . $e->getMessage());
        }
    }
    
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
        $stmtEvents->bindValue(':acc_id', $alojamiento['id']);
        $stmtEvents->execute();
        $eventos = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($eventos as &$evento) {
            // Formatear imagen
            $imagen = $evento['poster_image'] ?: $evento['photo1'];
            if (empty($imagen)) {
                $imagen = "https://rutasrurales.io/cultural_events_images/" . $evento['slug'] . ".webp";
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
        error_log("Error al obtener eventos vinculados: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'data' => $alojamiento
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener alojamiento',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
