<?php
/**
 * DIAGNÓSTICO: Inbound Links - Castillo de Zamora
 * ================================================
 * Verifica y soluciona los problemas de enlaces automáticos
 */

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/inbound_links_helper.php';

echo "<h1>🔍 Diagnóstico de Inbound Links</h1>\n";

try {
    $pdo = getDBConnection();
    
    echo "<h2>1. Keywords configuradas que incluyen 'Castillo' o 'Zamora':</h2>\n";
    $stmt = $pdo->prepare("
        SELECT keyword, url, link_title, is_active, priority 
        FROM inbound_links 
        WHERE keyword LIKE '%Castillo%' OR keyword LIKE '%Zamora%' 
        ORDER BY priority ASC, LENGTH(keyword) DESC
    ");
    $stmt->execute();
    $keywords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($keywords)) {
        echo "<p>❌ <strong>PROBLEMA ENCONTRADO:</strong> No hay keywords configuradas para 'Castillo de Zamora'</p>\n";
        
        echo "<h3>📝 Agregando keyword 'Castillo de Zamora'...</h3>\n";
        
        // Verificar si existe el slug del castillo
        $checkPlace = $pdo->prepare("SELECT slug, name FROM places_of_interest WHERE name LIKE '%Castillo%' AND name LIKE '%Zamora%' LIMIT 1");
        $checkPlace->execute();
        $place = $checkPlace->fetch(PDO::FETCH_ASSOC);
        
        if ($place) {
            $url = "/lugar/" . $place['slug'];
            $linkTitle = "Visitar " . $place['name'] . " - Información, horarios y cómo llegar";
            
            $insert = $pdo->prepare("
                INSERT INTO inbound_links (keyword, url, link_title, is_active, priority) 
                VALUES (?, ?, ?, 1, 10)
            ");
            $insert->execute([$place['name'], $url, $linkTitle]);
            
            echo "<p>✅ Keyword agregada: <strong>" . htmlspecialchars($place['name']) . "</strong></p>\n";
            echo "<p>URL: <code>" . htmlspecialchars($url) . "</code></p>\n";
            echo "<p>Título: " . htmlspecialchars($linkTitle) . "</p>\n";
        } else {
            echo "<p>⚠️ No se encontró el lugar 'Castillo de Zamora' en places_of_interest</p>\n";
            
            // Buscar lugares relacionados con Zamora
            echo "<h4>Lugares relacionados con Zamora:</h4>\n";
            $relatedPlaces = $pdo->prepare("SELECT name, slug FROM places_of_interest WHERE name LIKE '%Zamora%' OR municipality LIKE '%Zamora%' LIMIT 10");
            $relatedPlaces->execute();
            $places = $relatedPlaces->fetchAll(PDO::FETCH_ASSOC);
            
            if ($places) {
                echo "<ul>\n";
                foreach ($places as $p) {
                    echo "<li>" . htmlspecialchars($p['name']) . " (slug: " . htmlspecialchars($p['slug']) . ")</li>\n";
                }
                echo "</ul>\n";
            }
        }
    } else {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>Keyword</th><th>URL</th><th>Activa</th><th>Prioridad</th></tr>\n";
        foreach ($keywords as $kw) {
            $status = $kw['is_active'] ? '✅' : '❌';
            echo "<tr>\n";
            echo "<td>" . htmlspecialchars($kw['keyword']) . "</td>\n";
            echo "<td>" . htmlspecialchars($kw['url']) . "</td>\n";
            echo "<td>" . $status . "</td>\n";
            echo "<td>" . $kw['priority'] . "</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    echo "<h2>2. Test del procesamiento de inbound links:</h2>\n";
    
    $textoTest = "El Castillo de Zamora es una impresionante fortaleza medieval situada en el corazón de esta histórica ciudad. Visitamos Zamora para conocer más sobre esta joya arquitectónica.";
    
    echo "<h3>Texto original:</h3>\n";
    echo "<div style='background:#f5f5f5;padding:10px;border-left:3px solid #ccc;'>" . htmlspecialchars($textoTest) . "</div>\n";
    
    $textoConLinks = procesarInboundLinks($textoTest, $pdo);
    
    echo "<h3>Texto procesado con inbound links:</h3>\n";
    echo "<div style='background:#e8f5e8;padding:10px;border-left:3px solid #4CAF50;'>" . $textoConLinks . "</div>\n";
    
    if ($textoTest === $textoConLinks) {
        echo "<p>⚠️ <strong>PROBLEMA:</strong> El texto no fue modificado. Los inbound links no se están aplicando.</p>\n";
        
        // Debug más profundo
        echo "<h3>3. Debug del sistema:</h3>\n";
        $debugKeywords = getInboundLinks($pdo);
        echo "<p>Total keywords cargadas: " . count($debugKeywords) . "</p>\n";
        
        if (empty($debugKeywords)) {
            echo "<p>❌ No se pudieron cargar las keywords. Verificando estructura de tabla...</p>\n";
            
            try {
                $tableCheck = $pdo->query("DESCRIBE inbound_links");
                $columns = $tableCheck->fetchAll(PDO::FETCH_ASSOC);
                echo "<h4>Estructura de tabla inbound_links:</h4>\n";
                echo "<ul>\n";
                foreach ($columns as $col) {
                    echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>\n";
                }
                echo "</ul>\n";
            } catch (Exception $e) {
                echo "<p>❌ Error: La tabla 'inbound_links' no existe o no es accesible: " . $e->getMessage() . "</p>\n";
                
                echo "<h4>📝 Creando tabla inbound_links...</h4>\n";
                $createTable = "
                CREATE TABLE IF NOT EXISTS inbound_links (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    keyword VARCHAR(255) NOT NULL,
                    url VARCHAR(500) NOT NULL,
                    link_title VARCHAR(500) NOT NULL,
                    is_active BOOLEAN DEFAULT 1,
                    priority INT DEFAULT 10,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_keyword (keyword)
                )";
                
                try {
                    $pdo->exec($createTable);
                    echo "<p>✅ Tabla creada exitosamente</p>\n";
                } catch (Exception $e2) {
                    echo "<p>❌ Error creando tabla: " . $e2->getMessage() . "</p>\n";
                }
            }
        } else {
            echo "<p>Keywords encontradas:</p>\n";
            echo "<ul>\n";
            foreach (array_slice($debugKeywords, 0, 10) as $kw) {
                echo "<li><strong>" . htmlspecialchars($kw['keyword']) . "</strong> → " . htmlspecialchars($kw['url']) . "</li>\n";
            }
            echo "</ul>\n";
        }
    } else {
        echo "<p>✅ <strong>ÉXITO:</strong> Los inbound links se están aplicando correctamente.</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>❌ <strong>ERROR:</strong> " . $e->getMessage() . "</p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<br><hr><p><em>Diagnóstico completado a las " . date('Y-m-d H:i:s') . "</em></p>\n";
?>