<?php
/**
 * API Endpoint: Estadísticas para Dashboard
 * GET /api/estadisticas.php
 */

require_once 'config.php';

// Solo permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Método no permitido', 405);
}

try {
    $pdo = getDBConnection();
    
    // Total de alojamientos
    $sqlTotal = "SELECT COUNT(*) as total FROM " . DB_TABLE;
    $stmtTotal = $pdo->query($sqlTotal);
    $totalAlojamientos = $stmtTotal->fetch()['total'];
    
    // Total de plazas
    $sqlPlazas = "SELECT SUM(Plazas) as total_plazas FROM " . DB_TABLE;
    $stmtPlazas = $pdo->query($sqlPlazas);
    $totalPlazas = $stmtPlazas->fetch()['total_plazas'] ?? 0;
    
    // Precio medio
    $sqlPrecio = "SELECT AVG(CAST(Precio AS DECIMAL(10,2))) as precio_medio 
                  FROM " . DB_TABLE . " 
                  WHERE Precio IS NOT NULL AND Precio != '' AND Precio != '0'";
    $stmtPrecio = $pdo->query($sqlPrecio);
    $precioMedio = round($stmtPrecio->fetch()['precio_medio'] ?? 0, 2);
    
    // Distribución por tipo
    $sqlTipos = "SELECT Tipo, COUNT(*) as cantidad 
                 FROM " . DB_TABLE . " 
                 WHERE Tipo IS NOT NULL AND Tipo != ''
                 GROUP BY Tipo 
                 ORDER BY cantidad DESC";
    $stmtTipos = $pdo->query($sqlTipos);
    $distribucionTipos = $stmtTipos->fetchAll();
    
    // Alojamientos por provincia (extraer de dirección)
    $sqlProvincias = "SELECT 
                        SUBSTRING_INDEX(Direccion, ' ', -1) as provincia,
                        COUNT(*) as cantidad
                      FROM " . DB_TABLE . "
                      WHERE Direccion IS NOT NULL AND Direccion != ''
                      GROUP BY provincia
                      ORDER BY cantidad DESC
                      LIMIT 10";
    $stmtProvincias = $pdo->query($sqlProvincias);
    $distribucionProvincias = $stmtProvincias->fetchAll();
    
    // Alojamientos con accesibilidad
    $sqlAccesibilidad = "SELECT COUNT(*) as total 
                         FROM " . DB_TABLE . " 
                         WHERE Discapacitados = 'Si' OR Discapacitados = 'Sí'";
    $stmtAccesibilidad = $pdo->query($sqlAccesibilidad);
    $conAccesibilidad = $stmtAccesibilidad->fetch()['total'];
    
    // Alojamientos con web
    $sqlWeb = "SELECT COUNT(*) as total 
               FROM " . DB_TABLE . " 
               WHERE Web IS NOT NULL AND Web != ''";
    $stmtWeb = $pdo->query($sqlWeb);
    $conWeb = $stmtWeb->fetch()['total'];
    
    // Alojamientos con redes sociales
    $sqlRedes = "SELECT COUNT(*) as total 
                 FROM " . DB_TABLE . " 
                 WHERE Instagram IS NOT NULL AND Instagram != ''";
    $stmtRedes = $pdo->query($sqlRedes);
    $conRedes = $stmtRedes->fetch()['total'];
    
    // Rangos de precio
    $sqlRangos = "SELECT 
                    CASE 
                        WHEN CAST(Precio AS DECIMAL(10,2)) < 100 THEN '< 100€'
                        WHEN CAST(Precio AS DECIMAL(10,2)) BETWEEN 100 AND 150 THEN '100-150€'
                        WHEN CAST(Precio AS DECIMAL(10,2)) BETWEEN 150 AND 200 THEN '150-200€'
                        WHEN CAST(Precio AS DECIMAL(10,2)) > 200 THEN '> 200€'
                    END as rango,
                    COUNT(*) as cantidad
                  FROM " . DB_TABLE . "
                  WHERE Precio IS NOT NULL AND Precio != '' AND Precio != '0'
                  GROUP BY rango
                  ORDER BY cantidad DESC";
    $stmtRangos = $pdo->query($sqlRangos);
    $rangosPrecio = $stmtRangos->fetchAll();
    
    // Últimos alojamientos añadidos (simulado por ID)
    $sqlUltimos = "SELECT ID, Nombre, Tipo, Direccion 
                   FROM " . DB_TABLE . " 
                   ORDER BY ID DESC 
                   LIMIT 5";
    $stmtUltimos = $pdo->query($sqlUltimos);
    $ultimosAlojamientos = $stmtUltimos->fetchAll();
    
    // Construir respuesta
    $estadisticas = [
        'resumen' => [
            'total_alojamientos' => intval($totalAlojamientos),
            'total_plazas' => intval($totalPlazas),
            'precio_medio' => floatval($precioMedio),
            'con_accesibilidad' => intval($conAccesibilidad),
            'con_web' => intval($conWeb),
            'con_redes_sociales' => intval($conRedes)
        ],
        'distribucion_tipos' => $distribucionTipos,
        'distribucion_provincias' => $distribucionProvincias,
        'rangos_precio' => $rangosPrecio,
        'ultimos_alojamientos' => $ultimosAlojamientos
    ];
    
    jsonSuccess($estadisticas, 'Estadísticas obtenidas correctamente');
    
} catch (PDOException $e) {
    jsonError('Error al obtener estadísticas: ' . $e->getMessage(), 500);
}
