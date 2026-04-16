<?php
require_once 'api/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Checking places of interest database</h1>";
    
    // Check if places_of_interest table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'places_of_interest'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "Table 'places_of_interest' does not exist!<br>";
        // List all tables
        $stmt = $pdo->query("SHOW TABLES");
        echo "Available tables:<br>";
        while($row = $stmt->fetch(PDO::FETCH_NUM)) {
            echo "- " . $row[0] . "<br>";
        }
        exit;
    }
    
    // Count total places
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM places_of_interest WHERE is_active = 1");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total active places: " . $count['total'] . "<br><br>";
    
    // Search for places with "Collado" in name
    $stmt = $pdo->prepare("SELECT id, slug, name, municipality, province, is_active FROM places_of_interest WHERE name LIKE ?");
    $stmt->execute(['%Collado%']);
    $colladoPlaces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Places with 'Collado' in name:</h2>";
    if (empty($colladoPlaces)) {
        echo "No places found with 'Collado' in name.<br>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Slug</th><th>Name</th><th>Municipality</th><th>Province</th><th>Active</th></tr>";
        foreach ($colladoPlaces as $place) {
            echo "<tr>";
            echo "<td>" . $place['id'] . "</td>";
            echo "<td>" . htmlspecialchars($place['slug']) . "</td>";
            echo "<td>" . htmlspecialchars($place['name']) . "</td>";
            echo "<td>" . htmlspecialchars($place['municipality']) . "</td>";
            echo "<td>" . htmlspecialchars($place['province']) . "</td>";
            echo "<td>" . ($place['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<br><h2>Search for 'ermita-de-nuestra-señora-del-collado-berninches' slug:</h2>";
    $slug = 'ermita-de-nuestra-señora-del-collado-berninches';
    $stmt = $pdo->prepare("SELECT id, slug, name, municipality, province, is_active FROM places_of_interest WHERE slug = ?");
    $stmt->execute([$slug]);
    $place = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($place) {
        echo "Found place with slug '$slug':<br>";
        echo "ID: " . $place['id'] . "<br>";
        echo "Name: " . htmlspecialchars($place['name']) . "<br>";
        echo "Municipality: " . htmlspecialchars($place['municipality']) . "<br>";
        echo "Province: " . htmlspecialchars($place['province']) . "<br>";
        echo "Active: " . ($place['is_active'] ? 'Yes' : 'No') . "<br>";
    } else {
        echo "No place found with slug '$slug'.<br>";
        
        // Try URL-encoded version
        $urlEncodedSlug = 'ermita-de-nuestra-se%C3%B1ora-del-collado-berninches';
        echo "<br><h3>Trying URL-encoded slug: '$urlEncodedSlug'</h3>";
        $stmt->execute([$urlEncodedSlug]);
        $place = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($place) {
            echo "Found place with URL-encoded slug!<br>";
            echo "ID: " . $place['id'] . "<br>";
            echo "Name: " . htmlspecialchars($place['name']) . "<br>";
            echo "Municipality: " . htmlspecialchars($place['municipality']) . "<br>";
            echo "Province: " . htmlspecialchars($place['province']) . "<br>";
            echo "Active: " . ($place['is_active'] ? 'Yes' : 'No') . "<br>";
        } else {
            echo "No place found with URL-encoded slug either.<br>";
            
            // Try searching for "Berninches" in municipality
            echo "<br><h3>Searching for 'Berninches' in municipality:</h3>";
            $stmt = $pdo->prepare("SELECT id, slug, name, municipality, province, is_active FROM places_of_interest WHERE municipality LIKE ?");
            $stmt->execute(['%Berninches%']);
            $berninchesPlaces = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($berninchesPlaces)) {
                echo "No places found in municipality 'Berninches'.<br>";
            } else {
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID</th><th>Slug</th><th>Name</th><th>Municipality</th><th>Province</th><th>Active</th></tr>";
                foreach ($berninchesPlaces as $place) {
                    echo "<tr>";
                    echo "<td>" . $place['id'] . "</td>";
                    echo "<td>" . htmlspecialchars($place['slug']) . "</td>";
                    echo "<td>" . htmlspecialchars($place['name']) . "</td>";
                    echo "<td>" . htmlspecialchars($place['municipality']) . "</td>";
                    echo "<td>" . htmlspecialchars($place['province']) . "</td>";
                    echo "<td>" . ($place['is_active'] ? 'Yes' : 'No') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
    }
    
    // Check the lugares-interes.php API endpoint
    echo "<br><h2>Checking API endpoint /api/lugares-interes.php</h2>";
    echo "This endpoint should return all active places. Let's see what slugs are being returned...<br>";
    
    $stmt = $pdo->query("SELECT slug, name FROM places_of_interest WHERE is_active = 1 ORDER BY name LIMIT 20");
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "First 20 active places from database:<br>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Slug</th><th>Name</th></tr>";
    foreach ($places as $place) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($place['slug']) . "</td>";
        echo "<td>" . htmlspecialchars($place['name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>