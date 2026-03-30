<?php
/**
 * Test script to debug pagination API issues
 */

require_once 'api/config.php';

try {
    $pdo = getDBConnection();

    echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Debug API Paginación</title>";
    echo "<style>body{font-family:Arial;padding:2rem;background:#f5f5f5}table{border-collapse:collapse;width:100%;background:white;margin:1rem 0}th,td{border:1px solid #ddd;padding:8px;text-align:left}th{background:#f2f2f2}tr:nth-child(even){background:#f9f9f9}.error{background:#ffe6e6;color:#d9534f;padding:1rem;border-radius:5px;margin:1rem 0}.success{background:#e6ffe6;color:#5cb85c;padding:1rem;border-radius:5px;margin:1rem 0}</style>";
    echo "</head><body><h1>🔍 Debug: API de Paginación de Alojamientos</h1>";

    // Test the same query as the pagination
    echo "<h2>1. Query directa a la base de datos</h2>";
    $stmt = $pdo->prepare("SELECT id, name, province, municipality, is_active FROM accommodations WHERE is_active = 1 ORDER BY name LIMIT 25");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p><strong>Total de alojamientos activos encontrados:</strong> " . count($results) . "</p>";

    if (count($results) > 0) {
        echo "<table><tr><th>ID</th><th>Nombre</th><th>Provincia</th><th>Municipio</th><th>Activo</th></tr>";
        foreach ($results as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['name']}</td>";
            echo "<td>{$row['province']}</td>";
            echo "<td>{$row['municipality']}</td>";
            echo "<td>" . ($row['is_active'] ? '✅ Sí' : '❌ No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Check if Casa de Aldea is in the results
    $casaAldeaFound = false;
    foreach ($results as $row) {
        if (stripos($row['name'], 'Casa de Aldea') !== false) {
            $casaAldeaFound = true;
            break;
        }
    }

    if ($casaAldeaFound) {
        echo "<div class='success'>✅ Casa de Aldea SÍ está en los resultados de la query</div>";
    } else {
        echo "<div class='error'>❌ Casa de Aldea NO está en los resultados de la query</div>";
    }

    // Test API call simulation
    echo "<h2>2. Simulación de llamada a API</h2>";
    echo "<p>Probando la misma lógica que usa alojamientos-turisticos-paginacion.html...</p>";

    // Simulate API call
    $page = 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $sql = "SELECT * FROM accommodations WHERE is_active = 1 ORDER BY name ASC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $apiResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p><strong>Resultados de la API simulada (página 1, límite 20):</strong> " . count($apiResults) . " alojamientos</p>";

    if (count($apiResults) > 0) {
        echo "<table><tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Provincia</th><th>Municipio</th></tr>";
        foreach ($apiResults as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['name']}</td>";
            echo "<td>{$row['accommodation_type']}</td>";
            echo "<td>{$row['province']}</td>";
            echo "<td>{$row['municipality']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Check data processing
    echo "<h2>3. Verificación del procesamiento de datos</h2>";
    if (count($apiResults) > 0) {
        echo "<p>Procesando el primer alojamiento como lo haría la API...</p>";

        $firstAlojamiento = $apiResults[0];
        $processed = [
            'Nombre' => $firstAlojamiento['name'] ?? '',
            'Tipo' => $firstAlojamiento['accommodation_type'] ?? '',
            'Provincia' => $firstAlojamiento['province'] ?? '',
            'Localidad' => $firstAlojamiento['municipality'] ?? '',
            'Plazas' => intval($firstAlojamiento['capacity'] ?? 0),
            'Precio' => $firstAlojamiento['price_per_night'] ?? null,
        ];

        echo "<pre>" . json_encode($processed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    }

    echo "<h2>4. Diagnóstico final</h2>";

    $issues = [];

    if (count($results) === 0) {
        $issues[] = "No hay alojamientos activos en la base de datos";
    }

    if (!$casaAldeaFound) {
        $issues[] = "Casa de Aldea no aparece en los resultados activos";
    }

    if (count($apiResults) === 0) {
        $issues[] = "La simulación de API no devuelve resultados";
    }

    // Check for data quality issues
    $dataIssues = [];
    foreach ($results as $row) {
        if (empty($row['name'])) $dataIssues[] = "Alojamiento ID {$row['id']} sin nombre";
        if (empty($row['province'])) $dataIssues[] = "Alojamiento ID {$row['id']} sin provincia";
        if (empty($row['municipality'])) $dataIssues[] = "Alojamiento ID {$row['id']} sin municipio";
    }

    if (count($issues) === 0 && count($dataIssues) === 0) {
        echo "<div class='success'>✅ No se encontraron problemas obvios. El problema podría estar en el JavaScript del frontend.</div>";
    } else {
        echo "<div class='error'>";
        echo "<strong>Problemas encontrados:</strong><br>";
        foreach ($issues as $issue) {
            echo "• $issue<br>";
        }
        if (count($dataIssues) > 0) {
            echo "<br><strong>Problemas de calidad de datos:</strong><br>";
            foreach (array_slice($dataIssues, 0, 5) as $issue) {
                echo "• $issue<br>";
            }
            if (count($dataIssues) > 5) {
                echo "• ... y " . (count($dataIssues) - 5) . " más<br>";
            }
        }
        echo "</div>";
    }

    echo "<p style='margin-top:2rem;'>";
    echo "<a href='alojamientos-turisticos-paginacion.html' style='background:#2c5f2d;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-right:10px;'>Ver paginación</a>";
    echo "<a href='listar-slugs.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-right:10px;'>Ver todos los alojamientos</a>";
    echo "<a href='activar-casa-aldea.php' style='background:#ffc107;color:black;padding:10px 20px;text-decoration:none;border-radius:5px;'>Volver a activar Casa de Aldea</a>";
    echo "</p>";

} catch (PDOException $e) {
    echo "<div style='background:#ffe6e6;color:#d9534f;padding:1rem;border-radius:5px;margin:1rem 0;'>";
    echo "<strong>Error de conexión:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>

</body></html>
