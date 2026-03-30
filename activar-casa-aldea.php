<?php
/**
 * Script para verificar y activar "Casa de Aldea" en la base de datos
 * Si no existe, lo importa desde accommodations.json
 */

require_once 'api/config.php';

try {
    $pdo = getDBConnection();

    echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Solucionar Casa de Aldea</title>";
    echo "<style>body{font-family:Arial;padding:2rem;background:#f5f5f5}h1{color:#2c5f2d;text-align:center}.success{background:#d4edda;color:#155724;padding:1rem;border-radius:5px;margin:1rem 0}.warning{background:#fff3cd;color:#856404;padding:1rem;border-radius:5px;margin:1rem 0}.error{background:#f8d7da;color:#721c24;padding:1rem;border-radius:5px;margin:1rem 0}.info{background:#d1ecf1;color:#0c5460;padding:1rem;border-radius:5px;margin:1rem 0}</style>";
    echo "</head><body><h1>🔧 Solucionar problema con Casa de Aldea</h1>";

    // Buscar "Casa de Aldea" en la base de datos
    $stmt = $pdo->prepare("SELECT id, name, is_active, municipality, province FROM accommodations WHERE name LIKE '%Casa de Aldea%' OR name LIKE '%casa de aldea%'");
    $stmt->execute();
    $alojamiento = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($alojamiento) {
        echo "<div class='info'><h3>📋 Alojamiento encontrado en base de datos:</h3>";
        echo "<p><strong>ID:</strong> {$alojamiento['id']}</p>";
        echo "<p><strong>Nombre:</strong> {$alojamiento['name']}</p>";
        echo "<p><strong>Ubicación:</strong> {$alojamiento['municipality']}, {$alojamiento['province']}</p>";
        echo "<p><strong>Estado:</strong> " . ($alojamiento['is_active'] ? '✅ Activo' : '❌ Inactivo') . "</p></div>";

        if (!$alojamiento['is_active']) {
            // Activar el alojamiento
            $updateStmt = $pdo->prepare("UPDATE accommodations SET is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->execute([$alojamiento['id']]);

            echo "<div class='success'><h3>✅ ¡Casa de Aldea activada!</h3>";
            echo "<p>El alojamiento ahora está activo y debería aparecer en la paginación.</p></div>";
        } else {
            echo "<div class='success'><h3>ℹ️ Casa de Aldea ya está activa</h3>";
            echo "<p>El alojamiento ya está activo en la base de datos. Si no aparece en la paginación, podría haber otro problema.</p></div>";
        }
    } else {
        echo "<div class='warning'><h3>⚠️ Casa de Aldea NO encontrada en base de datos</h3>";
        echo "<p>El alojamiento existe en accommodations.json pero no en la base de datos.</p>";
        echo "<p>Intentando importarlo...</p></div>";

        // Buscar en accommodations.json
        $jsonFile = __DIR__ . '/accommodations.json';
        if (file_exists($jsonFile)) {
            $jsonData = json_decode(file_get_contents($jsonFile), true);

            $casaAldea = null;
            foreach ($jsonData as $item) {
                if (stripos($item['name'] ?? $item['Nombre'] ?? '', 'Casa de Aldea') !== false) {
                    $casaAldea = $item;
                    break;
                }
            }

            if ($casaAldea) {
                echo "<div class='info'><h4>📄 Datos encontrados en JSON:</h4>";
                echo "<p><strong>Nombre:</strong> " . ($casaAldea['name'] ?? $casaAldea['Nombre']) . "</p>";
                echo "<p><strong>ID:</strong> " . ($casaAldea['id']) . "</p>";
                echo "<p><strong>Ubicación:</strong> " . ($casaAldea['localidad'] ?? $casaAldea['municipality'] ?? 'N/A') . "</p></div>";

                // Preparar datos para inserción
                $insertData = [
                    'id' => $casaAldea['id'],
                    'name' => $casaAldea['name'] ?? $casaAldea['Nombre'],
                    'accommodation_type' => $casaAldea['type'] ?? $casaAldea['Tipo'] ?? $casaAldea['accommodation_type'],
                    'capacity' => $casaAldea['capacity'] ?? $casaAldea['Plazas'] ?? $casaAldea['plazas'],
                    'price_per_night' => $casaAldea['price'] ?? $casaAldea['Precio'] ?? null,
                    'province' => $casaAldea['province'] ?? $casaAldea['provincia'] ?? $casaAldea['Province'],
                    'municipality' => $casaAldea['municipality'] ?? $casaAldea['localidad'] ?? $casaAldea['Localidad'],
                    'address' => $casaAldea['address'] ?? $casaAldea['Direccion'] ?? $casaAldea['Address'],
                    'phone' => $casaAldea['phone'] ?? $casaAldea['Telefono1'] ?? $casaAldea['Phone'],
                    'email' => $casaAldea['email'] ?? $casaAldea['Email'],
                    'website' => $casaAldea['website'] ?? $casaAldea['Web'] ?? $casaAldea['Website'],
                    'description' => $casaAldea['description'] ?? $casaAldea['Notaspublicas'] ?? $casaAldea['Description'],
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                // Insertar en base de datos
                $columns = implode(', ', array_keys($insertData));
                $placeholders = ':' . implode(', :', array_keys($insertData));

                $insertStmt = $pdo->prepare("INSERT INTO accommodations ($columns) VALUES ($placeholders)");

                foreach ($insertData as $key => $value) {
                    $insertStmt->bindValue(":$key", $value);
                }

                if ($insertStmt->execute()) {
                    echo "<div class='success'><h3>✅ ¡Casa de Aldea importada exitosamente!</h3>";
                    echo "<p>El alojamiento ha sido añadido a la base de datos y está activo.</p></div>";
                } else {
                    echo "<div class='error'><h3>❌ Error al importar</h3><p>No se pudo insertar el alojamiento en la base de datos.</p></div>";
                }
            } else {
                echo "<div class='error'><h3>❌ No encontrado en JSON</h3><p>Casa de Aldea no existe ni en la base de datos ni en accommodations.json</p></div>";
            }
        } else {
            echo "<div class='error'><h3>❌ Archivo JSON no encontrado</h3><p>No se pudo acceder a accommodations.json</p></div>";
        }
    }

    echo "<hr><p style='text-align:center;margin-top:2rem;'>";
    echo "<a href='alojamientos-turisticos-paginacion.html' style='background:#2c5f2d;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-right:10px;'>Ver paginación</a>";
    echo "<a href='listar-slugs.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Ver todos los alojamientos</a>";
    echo "</p>";

    echo "</body></html>";

} catch (PDOException $e) {
    echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Error</title>";
    echo "<style>body{font-family:Arial;padding:2rem;background:#f5f5f5;text-align:center}h1{color:#e74c3c}.error{background:#f8d7da;color:#721c24;padding:2rem;border-radius:10px;margin:2rem auto;max-width:600px}</style>";
    echo "</head><body>";

    echo "<h1>❌ Error de conexión</h1>";
    echo "<div class='error'>";
    echo "<p>Error al conectar con la base de datos:</p>";
    echo "<p><strong>" . htmlspecialchars($e->getMessage()) . "</strong></p>";
    echo "</div>";

    echo "</body></html>";
}
?>
