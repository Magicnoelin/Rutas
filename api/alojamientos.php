<?php
/**
 * API Endpoint: Obtener Todos los Alojamientos
 * GET /api/alojamientos.php
 * Parámetros opcionales:
 * - page: número de página (default: 1)
 * - limit: items por página (default: 20)
 * - tipo: filtrar por tipo de alojamiento
 * - provincia: filtrar por provincia
 */

require_once 'config.php';

// Solo permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Método no permitido', 405);
}

try {
    $pdo = getDBConnection();

    // Permitir especificar tabla diferente (para accommodations)
    $tableName = isset($_GET['table']) && !empty($_GET['table']) ? sanitizeInput($_GET['table']) : DB_TABLE;

    // Obtener parámetros de paginación
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;

    // Construir query base
    $where = [];
    $params = [];

    // IMPORTANTE: Solo mostrar alojamientos publicados
    try {
        $columns = $pdo->query("DESCRIBE $tableName")->fetchAll(PDO::FETCH_COLUMN);
        if ($tableName === 'accommodations') {
            // Tabla accommodations usa columna 'is_active'
            if (in_array('is_active', $columns)) {
                $where[] = "is_active = :is_active";
                $params[':is_active'] = 1;
            }
        } else {
            // Tabla alojamientos_csv usa columna 'Estado'
            if (in_array('Estado', $columns)) {
                $where[] = "Estado = :estado";
                $params[':estado'] = 'publicado';
            }
        }
    } catch (Exception $e) {
        // Si no se puede verificar columnas, continuar sin filtro
    }

    // Filtro por tipo
    if (isset($_GET['tipo']) && !empty($_GET['tipo'])) {
        $tipoCol = ($tableName === 'accommodations') ? 'accommodation_type' : 'Tipo';
        $where[] = "$tipoCol = :tipo";
        $params[':tipo'] = sanitizeInput($_GET['tipo']);
    }

    // Filtro por búsqueda general
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . sanitizeInput($_GET['search']) . '%';
        if ($tableName === 'accommodations') {
            $where[] = "(name LIKE :search OR address LIKE :search OR description LIKE :search)";
        } else {
            $where[] = "(Nombre LIKE :search OR Direccion LIKE :search OR Responsable LIKE :search)";
        }
        $params[':search'] = $search;
    }
    
    // Construir WHERE clause
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Contar total de registros
    $countSql = "SELECT COUNT(*) as total FROM $tableName " . $whereClause;
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch()['total'];

    // Obtener registros paginados
    $orderBy = ($tableName === 'accommodations') ? 'name ASC' : 'Nombre ASC';
    $sql = "SELECT * FROM $tableName " . $whereClause . " ORDER BY $orderBy LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    
    // Bind parámetros
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $alojamientos = $stmt->fetchAll();
    
    // Procesar datos para el frontend
    $alojamientosProcesados = array_map(function($alojamiento) use ($tableName) {
        // Detectar si es tabla accommodations (columnas en inglés)
        $isAccommodations = ($tableName === 'accommodations');

        if ($isAccommodations) {
            // Convertir plazas a número
            $alojamiento['Plazas'] = intval($alojamiento['capacity'] ?? $alojamiento['Plazas'] ?? 0);

            // Procesar precio
            if (!empty($alojamiento['price_per_night'])) {
                $alojamiento['Precio'] = floatval($alojamiento['price_per_night']);
            }

            // Crear array de fotos con URLs completas
            $fotos = [];
            for ($i = 1; $i <= 4; $i++) {
                $fotoKey = 'photo' . $i;
                if (!empty($alojamiento[$fotoKey])) {
                    $fotoValue = $alojamiento[$fotoKey];

                    // Verificar si contiene múltiples URLs separadas por comas
                    if (strpos($fotoValue, ',') !== false) {
                        // Separar las URLs por coma
                        $fotoUrls = array_map('trim', explode(',', $fotoValue));
                        foreach ($fotoUrls as $fotoUrl) {
                            if (!empty($fotoUrl)) {
                                // Si no es una URL completa, construirla
                                if (!preg_match('/^https?:\/\//', $fotoUrl)) {
                                    $fotoUrl = 'https://rutasrurales.io/Alojamientos_Images/' . $fotoUrl;
                                }
                                $fotos[] = $fotoUrl;
                            }
                        }
                    } else {
                        // URL única
                        if (!preg_match('/^https?:\/\//', $fotoValue)) {
                            $fotoValue = 'https://rutasrurales.io/Alojamientos_Images/' . $fotoValue;
                        }
                        $fotos[] = $fotoValue;
                    }
                }
            }
            $alojamiento['Fotos'] = $fotos;

            // Usar campos municipality y province directamente de la base de datos
            $alojamiento['Localidad'] = $alojamiento['municipality'] ?? '';
            $alojamiento['Provincia'] = $alojamiento['province'] ?? '';

            // Mapear campos para compatibilidad con frontend
            $alojamiento['Nombre'] = $alojamiento['name'] ?? '';
            $alojamiento['Tipo'] = $alojamiento['accommodation_type'] ?? '';
            $alojamiento['Direccion'] = $alojamiento['address'] ?? '';
            $alojamiento['Telefono1'] = $alojamiento['phone'] ?? '';
            $alojamiento['Email'] = $alojamiento['email'] ?? '';
            $alojamiento['Web'] = $alojamiento['website'] ?? '';
            $alojamiento['Notaspublicas'] = $alojamiento['description'] ?? '';

        } else {
            // Tabla alojamientos_csv (columnas en español)
            // Convertir plazas a número
            $alojamiento['Plazas'] = intval($alojamiento['Plazas']);

            // Procesar precio
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

            // Extraer localidad y provincia de la dirección
            if (!empty($alojamiento['Direccion'])) {
                $partes = explode(' ', $alojamiento['Direccion']);
                $alojamiento['Localidad'] = '';
                $alojamiento['Provincia'] = '';

                // Intentar extraer provincia (última palabra antes del código postal)
                if (count($partes) > 2) {
                    $alojamiento['Provincia'] = $partes[count($partes) - 1];
                    if (count($partes) > 3) {
                        $alojamiento['Localidad'] = $partes[count($partes) - 2];
                    }
                }
            }
        }

        return $alojamiento;
    }, $alojamientos);
    
    // Calcular información de paginación
    $totalPages = ceil($totalRecords / $limit);
    
    // Respuesta exitosa
    jsonSuccess([
        'alojamientos' => $alojamientosProcesados,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ]
    ], 'Alojamientos obtenidos correctamente');
    
} catch (PDOException $e) {
    jsonError('Error al obtener alojamientos: ' . $e->getMessage(), 500);
}
