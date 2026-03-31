<?php
/**
 * Script to generate SQL statements for completing cultural events translations
 * Focus on tourism-oriented slugs for foreign audiences
 */

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
    
    echo "=== GENERATING TRANSLATION SQL STATEMENTS ===\n\n";
    
    // 1. Get active events after April 1, 2026
    $query = "SELECT * FROM cultural_events 
              WHERE is_active = 1 
              AND (start_date >= '2026-04-01' OR end_date >= '2026-04-01')
              ORDER BY start_date ASC";
    $stmt = $pdo->query($query);
    $events = $stmt->fetchAll();
    
    echo "Found " . count($events) . " active events after April 1, 2026\n\n";
    
    // Language configurations
    $languages = [
        'en' => [
            'name' => 'English',
            'slug_suffix' => '-traditional-festival-spain',
            'meta_title_suffix' => ' | Traditional Festival in Spain',
            'target_audience' => 'International tourists, culture enthusiasts, families'
        ],
        'fr' => [
            'name' => 'French',
            'slug_suffix' => '-fete-traditionnelle-espagne',
            'meta_title_suffix' => ' | Fête Traditionnelle en Espagne',
            'target_audience' => 'Touristes internationaux, amateurs de culture, familles'
        ],
        'de' => [
            'name' => 'German',
            'slug_suffix' => '-traditionelles-fest-spanien',
            'meta_title_suffix' => ' | Traditionelles Fest in Spanien',
            'target_audience' => 'Internationale Touristen, Kulturliebhaber, Familien'
        ],
        'zh' => [
            'name' => 'Chinese',
            'slug_suffix' => '-chuantongjieri-xibanya',
            'meta_title_suffix' => ' | 西班牙传统节日',
            'target_audience' => '国际游客, 文化爱好者, 家庭'
        ]
    ];
    
    $sql_statements = [];
    
    foreach ($events as $event) {
        $event_id = $event['id'];
        $original_name = $event['name'];
        $original_slug = $event['slug'];
        $location = $event['location'];
        $province = $event['province'];
        $start_date = $event['start_date'];
        $end_date = $event['end_date'];
        
        echo "Processing Event ID {$event_id}: {$original_name}\n";
        echo "  Location: {$location}, {$province}\n";
        echo "  Dates: {$start_date} to {$end_date}\n";
        
        // Check existing translations
        $query = "SELECT language_code FROM cultural_events_trads WHERE event_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$event_id]);
        $existing_langs = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($languages as $lang_code => $lang_config) {
            if (in_array($lang_code, $existing_langs)) {
                // Check if translation is complete
                $query = "SELECT * FROM cultural_events_trads 
                          WHERE event_id = ? AND language_code = ?";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$event_id, $lang_code]);
                $translation = $stmt->fetch();
                
                if ($translation) {
                    $needs_update = false;
                    $update_fields = [];
                    
                    // Check each field
                    if (empty($translation['slug']) || !str_contains($translation['slug'], $lang_config['slug_suffix'])) {
                        $needs_update = true;
                        $update_fields[] = 'slug';
                    }
                    if (empty($translation['short_description'])) {
                        $needs_update = true;
                        $update_fields[] = 'short_description';
                    }
                    if (empty($translation['description'])) {
                        $needs_update = true;
                        $update_fields[] = 'description';
                    }
                    if (empty($translation['meta_title'])) {
                        $needs_update = true;
                        $update_fields[] = 'meta_title';
                    }
                    if (empty($translation['meta_description'])) {
                        $needs_update = true;
                        $update_fields[] = 'meta_description';
                    }
                    if (empty($translation['target_audience'])) {
                        $needs_update = true;
                        $update_fields[] = 'target_audience';
                    }
                    
                    if ($needs_update) {
                        echo "  [{$lang_code}] Needs update: " . implode(', ', $update_fields) . "\n";
                        
                        // Generate update SQL
                        $slug = $original_slug . $lang_config['slug_suffix'];
                        $meta_title = $original_name . $lang_config['meta_title_suffix'];
                        $meta_description = "Experience the {$original_name} in {$location}, {$province}. Traditional Spanish festival with cultural activities, local food, and authentic celebrations. Perfect for international tourists.";
                        
                        $short_description = "Traditional festival in {$location}, {$province} featuring local culture, music, and traditions.";
                        $description = "<p>The {$original_name} is one of the most important traditional festivals in {$province}, Spain. This annual celebration brings together locals and visitors to experience authentic Spanish culture.</p>
<p>Highlights include:</p>
<ul>
<li>Traditional music and dance performances</li>
<li>Local gastronomy and food stalls</li>
<li>Cultural exhibitions and workshops</li>
<li>Family-friendly activities</li>
<li>Religious processions (if applicable)</li>
</ul>
<p>Dates: {$start_date}" . ($end_date ? " to {$end_date}" : "") . "</p>
<p>Location: {$location}, {$province}, Spain</p>";
                        
                        $program = "Daily schedule includes morning activities, afternoon cultural events, and evening celebrations with music and traditional performances.";
                        
                        $sql = "UPDATE cultural_events_trads SET 
                                name = '" . addslashes($original_name) . "',
                                slug = '" . addslashes($slug) . "',
                                short_description = '" . addslashes($short_description) . "',
                                description = '" . addslashes($description) . "',
                                program = '" . addslashes($program) . "',
                                target_audience = '" . addslashes($lang_config['target_audience']) . "',
                                meta_title = '" . addslashes($meta_title) . "',
                                meta_description = '" . addslashes($meta_description) . "'
                                WHERE event_id = {$event_id} AND language_code = '{$lang_code}';";
                        
                        $sql_statements[] = $sql;
                    } else {
                        echo "  [{$lang_code}] Complete ✓\n";
                    }
                }
            } else {
                // Missing translation entirely - generate INSERT statement
                echo "  [{$lang_code}] Missing - will create new translation\n";
                
                $slug = $original_slug . $lang_config['slug_suffix'];
                $meta_title = $original_name . $lang_config['meta_title_suffix'];
                $meta_description = "Experience the {$original_name} in {$location}, {$province}. Traditional Spanish festival with cultural activities, local food, and authentic celebrations. Perfect for international tourists.";
                
                $short_description = "Traditional festival in {$location}, {$province} featuring local culture, music, and traditions.";
                $description = "<p>The {$original_name} is one of the most important traditional festivals in {$province}, Spain. This annual celebration brings together locals and visitors to experience authentic Spanish culture.</p>
<p>Highlights include:</p>
<ul>
<li>Traditional music and dance performances</li>
<li>Local gastronomy and food stalls</li>
<li>Cultural exhibitions and workshops</li>
<li>Family-friendly activities</li>
<li>Religious processions (if applicable)</li>
</ul>
<p>Dates: {$start_date}" . ($end_date ? " to {$end_date}" : "") . "</p>
<p>Location: {$location}, {$province}, Spain</p>";
                
                $program = "Daily schedule includes morning activities, afternoon cultural events, and evening celebrations with music and traditional performances.";
                
                $sql = "INSERT INTO cultural_events_trads 
                        (event_id, language_code, name, slug, short_description, description, program, target_audience, meta_title, meta_description, created_at, updated_at)
                        VALUES (
                            {$event_id},
                            '{$lang_code}',
                            '" . addslashes($original_name) . "',
                            '" . addslashes($slug) . "',
                            '" . addslashes($short_description) . "',
                            '" . addslashes($description) . "',
                            '" . addslashes($program) . "',
                            '" . addslashes($lang_config['target_audience']) . "',
                            '" . addslashes($meta_title) . "',
                            '" . addslashes($meta_description) . "',
                            NOW(),
                            NOW()
                        );";
                
                $sql_statements[] = $sql;
            }
        }
        echo "\n";
    }
    
    // Write SQL statements to file
    if (!empty($sql_statements)) {
        $sql_file = "complete_translations_" . date('Ymd_His') . ".sql";
        file_put_contents($sql_file, implode("\n\n", $sql_statements));
        echo "=== GENERATED " . count($sql_statements) . " SQL STATEMENTS ===\n";
        echo "Saved to: {$sql_file}\n";
        
        // Also output first few statements as example
        echo "\n=== SAMPLE SQL STATEMENTS ===\n";
        for ($i = 0; $i < min(3, count($sql_statements)); $i++) {
            echo $sql_statements[$i] . "\n\n";
        }
    } else {
        echo "=== NO SQL STATEMENTS NEEDED ===\n";
        echo "All translations appear to be complete.\n";
    }
    
} catch (\PDOException $e) {
    echo "Database connection error: " . $e->getMessage() . "\n";
    echo "Please check your database credentials and connection.\n";
    
    // Provide template SQL for manual execution
    echo "\n=== TEMPLATE SQL FOR MANUAL EXECUTION ===\n";
    echo "You can use these templates to create translations manually:\n\n";
    
    $template_sql = <<<SQL
-- Example INSERT statement for English translation
INSERT INTO cultural_events_trads 
(event_id, language_code, name, slug, short_description, description, program, target_audience, meta_title, meta_description, created_at, updated_at)
VALUES (
    [EVENT_ID],
    'en',
    '[EVENT_NAME]',
    '[EVENT_SLUG]-traditional-festival-spain',
    'Traditional festival in [LOCATION], [PROVINCE] featuring local culture, music, and traditions.',
    '<p>The [EVENT_NAME] is one of the most important traditional festivals in [PROVINCE], Spain. This annual celebration brings together locals and visitors to experience authentic Spanish culture.</p>
<p>Highlights include traditional music, local gastronomy, cultural exhibitions, and family-friendly activities.</p>
<p>Dates: [START_DATE] to [END_DATE]</p>
<p>Location: [LOCATION], [PROVINCE], Spain</p>',
    'Daily schedule includes morning activities, afternoon cultural events, and evening celebrations with music and traditional performances.',
    'International tourists, culture enthusiasts, families',
    '[EVENT_NAME] | Traditional Festival in Spain',
    'Experience the [EVENT_NAME] in [LOCATION], [PROVINCE]. Traditional Spanish festival with cultural activities, local food, and authentic celebrations. Perfect for international tourists.',
    NOW(),
    NOW()
);

-- Example UPDATE statement to fix incomplete translations
UPDATE cultural_events_trads SET
    slug = CONCAT(slug, '-traditional-festival-spain'),
    meta_title = CONCAT(name, ' | Traditional Festival in Spain'),
    meta_description = 'Experience this traditional Spanish festival with cultural activities, local food, and authentic celebrations. Perfect for international tourists.',
    target_audience = 'International tourists, culture enthusiasts, families'
WHERE language_code = 'en' AND (slug = '' OR meta_title = '' OR meta_description = '' OR target_audience = '');
SQL;
    
    echo $template_sql . "\n";
}
?>