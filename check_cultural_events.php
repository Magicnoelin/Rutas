<?php
// Database configuration
$host = "localhost";
$db   = "u412199647_Rutas";
$user = "u412199647_olgamarin";
$pass = "Rutas5Rurales7$";
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

echo "<h1>Cultural Events Analysis</h1>";

// 1. First, let's check the structure of cultural_events table
echo "<h2>1. Structure of cultural_events table</h2>";
$stmt = $pdo->query("DESCRIBE cultural_events");
$columns = $stmt->fetchAll();
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $col) {
    echo "<tr>";
    echo "<td>{$col['Field']}</td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>{$col['Key']}</td>";
    echo "<td>{$col['Default']}</td>";
    echo "<td>{$col['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Get active events after April 1st
echo "<h2>2. Active Cultural Events (is_active=1) after April 1, 2026</h2>";
$query = "SELECT * FROM cultural_events 
          WHERE is_active = 1 
          AND (start_date >= '2026-04-01' OR end_date >= '2026-04-01')
          ORDER BY start_date ASC";
$stmt = $pdo->query($query);
$events = $stmt->fetchAll();

echo "<p>Found " . count($events) . " active events after April 1, 2026</p>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Start Date</th><th>End Date</th><th>Location</th><th>Slug</th></tr>";
foreach ($events as $event) {
    echo "<tr>";
    echo "<td>{$event['id']}</td>";
    echo "<td>{$event['name']}</td>";
    echo "<td>{$event['start_date']}</td>";
    echo "<td>{$event['end_date']}</td>";
    echo "<td>{$event['location']}</td>";
    echo "<td>{$event['slug']}</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Check existing translations for these events
echo "<h2>3. Existing Translations for Active Events</h2>";
foreach ($events as $event) {
    $event_id = $event['id'];
    $query = "SELECT language_code, COUNT(*) as count FROM cultural_events_trads 
              WHERE event_id = $event_id 
              GROUP BY language_code";
    $stmt = $pdo->query($query);
    $translations = $stmt->fetchAll();
    
    echo "<h3>Event ID {$event_id}: {$event['name']}</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Language</th><th>Translation Count</th></tr>";
    foreach ($translations as $trans) {
        echo "<tr>";
        echo "<td>{$trans['language_code']}</td>";
        echo "<td>{$trans['count']}</td>";
        echo "</tr>";
    }
    
    // Check which languages are missing
    $all_languages = ['es', 'en', 'fr', 'de', 'zh'];
    $existing_languages = array_column($translations, 'language_code');
    $missing_languages = array_diff($all_languages, $existing_languages);
    
    if (!empty($missing_languages)) {
        echo "<tr><td colspan='2'><strong>Missing languages:</strong> " . implode(', ', $missing_languages) . "</td></tr>";
    }
    echo "</table>";
}

// 4. Check for empty or incomplete translations
echo "<h2>4. Incomplete Translations (Empty Fields)</h2>";
$query = "SELECT cet.*, ce.name as original_name, ce.slug as original_slug 
          FROM cultural_events_trads cet
          JOIN cultural_events ce ON cet.event_id = ce.id
          WHERE ce.is_active = 1 
          AND (ce.start_date >= '2026-04-01' OR ce.end_date >= '2026-04-01')
          AND (cet.name = '' OR cet.slug = '' OR cet.short_description = '' OR cet.description = '' 
               OR cet.program = '' OR cet.target_audience = '' OR cet.meta_title = '' OR cet.meta_description = '')";
$stmt = $pdo->query($query);
$incomplete = $stmt->fetchAll();

echo "<p>Found " . count($incomplete) . " incomplete translations</p>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Event ID</th><th>Language</th><th>Original Name</th><th>Empty Fields</th></tr>";
foreach ($incomplete as $row) {
    $empty_fields = [];
    if (empty($row['name'])) $empty_fields[] = 'name';
    if (empty($row['slug'])) $empty_fields[] = 'slug';
    if (empty($row['short_description'])) $empty_fields[] = 'short_description';
    if (empty($row['description'])) $empty_fields[] = 'description';
    if (empty($row['program'])) $empty_fields[] = 'program';
    if (empty($row['target_audience'])) $empty_fields[] = 'target_audience';
    if (empty($row['meta_title'])) $empty_fields[] = 'meta_title';
    if (empty($row['meta_description'])) $empty_fields[] = 'meta_description';
    
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['event_id']}</td>";
    echo "<td>{$row['language_code']}</td>";
    echo "<td>{$row['original_name']}</td>";
    echo "<td>" . implode(', ', $empty_fields) . "</td>";
    echo "</tr>";
}
echo "</table>";

?>