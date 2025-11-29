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
    $tableName = isset($_GET['table']) && !empty($_GET['table']) ? $_GET['table'] : DB_TABLE;

    // Obtener parámetros de paginación
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;

    // Construir query base
    $where = [];
    $params = [];

    // IMPORTANTE: Solo mostrar alojamientos publicados (si la tabla tiene columna Estado)
    try {
        $columns = $pdo->query("DESCRIBE $tableName")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('Estado', $columns)) {
            $where[] = "Estado = :estado";
            $params[':estado'] = 'publicado';
        }
    } catch (Exception $e) {
        // Si no se puede verificar columnas, continuar sin filtro
    }
    
    // Filtro por tipo
    if (isset($_GET['tipo']) && !empty($_GET['tipo'])) {
        $where[] = "Tipo = :tipo";
        $params[':tipo'] = sanitizeInput($_GET['tipo']);
    }
    
    // Filtro por búsqueda general
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . sanitizeInput($_GET['search']) . '%';
        $where[] = "(Nombre LIKE :search OR Direccion LIKE :search OR Responsable LIKE :search)";
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
    $sql = "SELECT * FROM $tableName " . $whereClause . " ORDER BY Nombre ASC, name ASC LIMIT :limit OFFSET :offset";
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
    $alojamientosProcesados = array_map(function($alojamiento) {
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
