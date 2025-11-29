<?php
/**
 * API Endpoint: Obtener Alojamientos Turísticos (Tabla accommodations)
 * GET /api/accommodations.php
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

    // Usar tabla accommodations en lugar de la tabla por defecto
    $tableName = 'accommodations';

    // Verificar si la tabla existe
    $tableCheck = $pdo->query("SHOW TABLES LIKE '$tableName'");
    if ($tableCheck->rowCount() == 0) {
        // Si la tabla no existe, devolver datos vacíos
        jsonSuccess([
            'data' => [],
            'pagination' => [
                'current_page' => 1,
                'total_pages' => 0,
                'total_records' => 0,
                'per_page' => 20,
                'has_next' => false,
                'has_prev' => false
            ]
        ], 'Tabla accommodations no encontrada, devolviendo datos vacíos');
        exit();
    }

    // Obtener parámetros de paginación
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;

    // Construir query base
    $where = [];
    $params = [];

    // IMPORTANTE: Solo mostrar alojamientos publicados (si existe columna Estado)
    try {
        $columns = $pdo->query("DESCRIBE $tableName")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('Estado', $columns)) {
            $where[] = "Estado = :estado";
            $params[':estado'] = 'publicado';
        }
    } catch (Exception $e) {
        // Si no se puede verificar columnas, continuar sin filtro de estado
    }

    // Filtro por tipo
    if (isset($_GET['tipo']) && !empty($_GET['tipo'])) {
        $where[] = "Tipo = :tipo OR tipo = :tipo";
        $params[':tipo'] = sanitizeInput($_GET['tipo']);
    }

    // Filtro por búsqueda general
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . sanitizeInput($_GET['search']) . '%';
        $where[] = "(Nombre LIKE :search OR name LIKE :search OR Direccion LIKE :search OR address LIKE :search)";
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
    $accommodations = $stmt->fetchAll();

    // Procesar datos para el frontend - manejar diferentes formatos de columna
    $accommodationsProcesados = array_map(function($accommodation) {
        // Convertir plazas a número (manejar diferentes formatos)
        $accommodation['Plazas'] = intval($accommodation['Plazas'] ?? $accommodation['plazas'] ?? $accommodation['capacity'] ?? 0);
        $accommodation['plazas'] = $accommodation['Plazas']; // Para compatibilidad

        // Procesar precio (manejar diferentes formatos)
        $precio = $accommodation['Precio'] ?? $accommodation['precio'] ?? $accommodation['price'] ?? null;
        if ($precio !== null) {
            $accommodation['Precio'] = floatval($precio);
            $accommodation['precio'] = $accommodation['Precio'];
        }

        // Nombre (manejar diferentes formatos)
        $accommodation['Nombre'] = $accommodation['Nombre'] ?? $accommodation['name'] ?? '';
        $accommodation['nombre'] = $accommodation['Nombre'];

        // Tipo (manejar diferentes formatos)
        $accommodation['Tipo'] = $accommodation['Tipo'] ?? $accommodation['tipo'] ?? $accommodation['type'] ?? '';
        $accommodation['tipo'] = $accommodation['Tipo'];

        // Dirección/Localidad/Provincia (manejar diferentes formatos)
        $accommodation['Direccion'] = $accommodation['Direccion'] ?? $accommodation['address'] ?? '';
        $accommodation['direccion'] = $accommodation['Direccion'];

        // Extraer localidad y provincia de la dirección si no existen
        if (empty($accommodation['Localidad']) && empty($accommodation['localidad'])) {
            if (!empty($accommodation['Direccion']) || !empty($accommodation['address'])) {
                $address = $accommodation['Direccion'] ?: $accommodation['address'];
                $partes = explode(',', $address);
                if (count($partes) >= 2) {
                    $accommodation['Localidad'] = trim($partes[count($partes) - 2]); // Penúltima parte (localidad)
                    $accommodation['Provincia'] = trim($partes[count($partes) - 1]); // Última parte (provincia)
                } else {
                    $accommodation['Localidad'] = trim($partes[0]);
                    $accommodation['Provincia'] = 'Soria';
                }
            } else {
                $accommodation['Localidad'] = 'Soria';
                $accommodation['Provincia'] = 'Soria';
            }
        }
        $accommodation['localidad'] = $accommodation['Localidad'] ?? $accommodation['localidad'] ?? 'Soria';
        $accommodation['provincia'] = $accommodation['Provincia'] ?? $accommodation['provincia'] ?? 'Soria';

        // Descripción (manejar diferentes formatos)
        $accommodation['Notaspublicas'] = $accommodation['Notaspublicas'] ?? $accommodation['descripcion'] ?? $accommodation['description'] ?? '';
        $accommodation['descripcion'] = $accommodation['Notaspublicas'];

        // Teléfonos (manejar diferentes formatos)
        $accommodation['Telefono1'] = $accommodation['Telefono1'] ?? $accommodation['telefono'] ?? $accommodation['phone'] ?? '';
        $accommodation['telefono'] = $accommodation['Telefono1'];

        // Email
        $accommodation['Email'] = $accommodation['Email'] ?? $accommodation['email'] ?? '';
        $accommodation['email'] = $accommodation['Email'];

        // Web
        $accommodation['Web'] = $accommodation['Web'] ?? $accommodation['web'] ?? $accommodation['website'] ?? '';
        $accommodation['web'] = $accommodation['Web'];

        // Crear array de fotos (manejar diferentes formatos)
        $fotos = [];
        // Buscar fotos en diferentes formatos de la tabla accommodations
        for ($i = 1; $i <= 4; $i++) {
            $fotoKey = 'Foto' . $i;
            $imageKey = 'image' . $i;
            if (!empty($accommodation[$fotoKey])) {
                $fotos[] = $accommodation[$fotoKey];
            } elseif (!empty($accommodation[$imageKey])) {
                $fotos[] = $accommodation[$imageKey];
            }
        }
        // También buscar en campos alternativos
        if (!empty($accommodation['fotos']) && is_array($accommodation['fotos'])) {
            $fotos = array_merge($fotos, $accommodation['fotos']);
        }
        if (!empty($accommodation['images']) && is_array($accommodation['images'])) {
            $fotos = array_merge($fotos, $accommodation['images']);
        }
        $accommodation['Fotos'] = array_unique($fotos);
        $accommodation['fotos'] = $accommodation['Fotos'];

        return $accommodation;
    }, $accommodations);

    // Calcular información de paginación
    $totalPages = ceil($totalRecords / $limit);

    // Respuesta exitosa
    jsonSuccess([
        'data' => $accommodationsProcesados,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ]
    ], 'Alojamientos turísticos obtenidos correctamente');

} catch (PDOException $e) {
    jsonError('Error al obtener alojamientos turísticos: ' . $e->getMessage(), 500);
}
