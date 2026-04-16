<?php
/**
 * Script para analizar traducciones faltantes de eventos culturales
 * Compara eventos en sitemap-eventos.xml con traducciones en cultural_events_trads
 */

require_once 'api/config.php';

// Conectar a la base de datos
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    echo "=== ANÁLISIS DE TRADUCCIONES FALTANTES ===\n\n";
    
    // 1. Obtener todos los eventos activos del sitemap (aproximación)
    // Leer slugs del sitemap-eventos.xml
    $sitemapContent = file_get_contents('sitemap-eventos.xml');
    preg_match_all('/evento\/([^<"]+)/', $sitemapContent, $matches);
    $slugsSitemap = array_unique($matches[1]);
    
    echo "Total slugs en sitemap-eventos.xml: " . count($slugsSitemap) . "\n";
    
    // 2. Para cada slug, obtener el ID del evento
    $eventosConIds = [];
    $eventosSinIds = [];
    
    foreach ($slugsSitemap as $slug) {
        $slug = trim($slug);
        $sql = "SELECT id, name, slug, start_date, province FROM cultural_events WHERE slug = ? AND is_active = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $eventosConIds[$row['id']] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
                'start_date' => $row['start_date'],
                'province' => $row['province']
            ];
        } else {
            $eventosSinIds[] = $slug;
        }
        $stmt->close();
    }
    
    echo "Eventos encontrados en BD: " . count($eventosConIds) . "\n";
    echo "Eventos no encontrados en BD: " . count($eventosSinIds) . "\n";
    
    if (!empty($eventosSinIds)) {
        echo "\nSlugs no encontrados en BD:\n";
        foreach ($eventosSinIds as $slug) {
            echo "  - $slug\n";
        }
    }
    
    // 3. Verificar traducciones para cada evento encontrado
    $idiomas = ['en', 'fr', 'de', 'zh'];
    $resultados = [];
    
    foreach ($eventosConIds as $eventId => $evento) {
        $resultados[$eventId] = [
            'evento' => $evento,
            'traducciones' => []
        ];
        
        foreach ($idiomas as $idioma) {
            $sql = "SELECT COUNT(*) as count FROM cultural_events_trads WHERE event_id = ? AND language_code = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $eventId, $idioma);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            $resultados[$eventId]['traducciones'][$idioma] = ($row['count'] > 0);
            $stmt->close();
        }
    }
    
    // 4. Analizar resultados
    $eventosCompletos = 0;
    $eventosIncompletos = 0;
    $eventosSinTraducciones = 0;
    
    $eventosFaltantesPorIdioma = [
        'en' => 0,
        'fr' => 0,
        'de' => 0,
        'zh' => 0
    ];
    
    foreach ($resultados as $eventId => $data) {
        $traducciones = $data['traducciones'];
        $countTraducciones = array_sum($traducciones);
        
        if ($countTraducciones == 4) {
            $eventosCompletos++;
        } elseif ($countTraducciones > 0) {
            $eventosIncompletos++;
        } else {
            $eventosSinTraducciones++;
        }
        
        // Contar faltantes por idioma
        foreach ($idiomas as $idioma) {
            if (!$traducciones[$idioma]) {
                $eventosFaltantesPorIdioma[$idioma]++;
            }
        }
    }
    
    echo "\n=== RESUMEN DE TRADUCCIONES ===\n";
    echo "Total eventos analizados: " . count($resultados) . "\n";
    echo "Eventos con TODAS las traducciones (en, fr, de, zh): $eventosCompletos\n";
    echo "Eventos con ALGUNAS traducciones: $eventosIncompletos\n";
    echo "Eventos SIN traducciones: $eventosSinTraducciones\n";
    
    echo "\n=== TRADUCCIONES FALTANTES POR IDIOMA ===\n";
    foreach ($idiomas as $idioma) {
        echo strtoupper($idioma) . ": {$eventosFaltantesPorIdioma[$idioma]} eventos faltantes\n";
    }
    
    // 5. Mostrar eventos que necesitan traducciones
    echo "\n=== EVENTOS QUE NECESITAN TRADUCCIONES ===\n";
    $eventosParaTraducir = [];
    
    foreach ($resultados as $eventId => $data) {
        $traducciones = $data['traducciones'];
        $faltantes = [];
        
        foreach ($idiomas as $idioma) {
            if (!$traducciones[$idioma]) {
                $faltantes[] = $idioma;
            }
        }
        
        if (!empty($faltantes)) {
            $eventosParaTraducir[] = [
                'id' => $eventId,
                'name' => $data['evento']['name'],
                'slug' => $data['evento']['slug'],
                'start_date' => $data['evento']['start_date'],
                'province' => $data['evento']['province'],
                'faltantes' => $faltantes
            ];
        }
    }
    
    // Ordenar por fecha
    usort($eventosParaTraducir, function($a, $b) {
        return strtotime($a['start_date']) - strtotime($b['start_date']);
    });
    
    echo "\nTotal eventos que necesitan traducciones: " . count($eventosParaTraducir) . "\n\n";
    
    foreach ($eventosParaTraducir as $evento) {
        echo "ID: {$evento['id']}\n";
        echo "Nombre: {$evento['name']}\n";
        echo "Slug: {$evento['slug']}\n";
        echo "Fecha: {$evento['start_date']}\n";
        echo "Provincia: {$evento['province']}\n";
        echo "Idiomas faltantes: " . implode(', ', $evento['faltantes']) . "\n";
        echo "---\n";
    }
    
    // 6. Generar SQL para insertar traducciones faltantes
    echo "\n=== SQL PARA INSERTAR TRADUCCIONES FALTANTES ===\n";
    generarSQLTraducciones($eventosParaTraducir, $conn);
    
    $conn->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

/**
 * Generar SQL para insertar traducciones faltantes
 */
function generarSQLTraducciones($eventosParaTraducir, $conn) {
    $idiomas = ['en', 'fr', 'de', 'zh'];
    
    foreach ($idiomas as $idioma) {
        echo "\n-- TRADUCCIONES PARA $idioma\n";
        
        $eventosFaltantesIdioma = array_filter($eventosParaTraducir, function($evento) use ($idioma) {
            return in_array($idioma, $evento['faltantes']);
        });
        
        if (empty($eventosFaltantesIdioma)) {
            echo "-- No hay eventos faltantes para $idioma\n";
            continue;
        }
        
        foreach ($eventosFaltantesIdioma as $evento) {
            // Obtener datos del evento para generar traducción
            $eventId = $evento['id'];
            $sql = "SELECT name, slug, venue_name, province, start_date, end_date FROM cultural_events WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $eventId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $nombre = $conn->real_escape_string($row['name']);
                $slugBase = $conn->real_escape_string($row['slug']);
                $lugar = $conn->real_escape_string($row['venue_name']);
                $provincia = $conn->real_escape_string($row['province']);
                $fechaInicio = $row['start_date'];
                $fechaFin = $row['end_date'];
                
                // Generar slug traducido
                $sufijo = getSufijoSlug($idioma);
                $slugTraducido = $slugBase . $sufijo;
                
                // Generar contenido traducido
                $contenido = generarContenidoTraducido($idioma, $nombre, $lugar, $provincia, $fechaInicio, $fechaFin);
                
                echo "INSERT INTO cultural_events_trads (event_id, language_code, name, slug, short_description, description, program, target_audience, accessibility, meta_title, meta_description) VALUES ";
                echo "($eventId, '$idioma', '$nombre', '$slugTraducido', '{$contenido['short_description']}', '{$contenido['description']}', '{$contenido['program']}', '{$contenido['target_audience']}', '{$contenido['accessibility']}', '{$contenido['meta_title']}', '{$contenido['meta_description']}');\n";
            }
            
            $stmt->close();
        }
    }
}

/**
 * Obtener sufijo para slug según idioma
 */
function getSufijoSlug($idioma) {
    $sufijos = [
        'en' => '-traditional-festival-spain',
        'fr' => '-fete-traditionnelle-espagne',
        'de' => '-traditionelles-fest-spanien',
        'zh' => '-chuantongjieri-xibanya'
    ];
    
    return $sufijos[$idioma] ?? '';
}

/**
 * Generar contenido traducido según idioma
 */
function generarContenidoTraducido($idioma, $nombre, $lugar, $provincia, $fechaInicio, $fechaFin) {
    $fechaTexto = $fechaInicio;
    if ($fechaFin && $fechaFin != $fechaInicio) {
        $fechaTexto .= " to $fechaFin";
    }
    
    $contenidos = [
        'en' => [
            'short_description' => "Traditional festival in $lugar, $provincia featuring local culture, music, and traditions.",
            'description' => "<p>The $nombre is one of the most important traditional festivals in $provincia, Spain. This annual celebration brings together locals and visitors to experience authentic Spanish culture.</p>
<p>Highlights include:</p>
<ul>
<li>Traditional music and dance performances</li>
<li>Local gastronomy and food stalls</li>
<li>Cultural exhibitions and workshops</li>
<li>Family-friendly activities</li>
<li>Religious processions (if applicable)</li>
</ul>
<p>Dates: $fechaTexto</p>
<p>Location: $lugar, $provincia, Spain</p>",
            'program' => 'Daily schedule includes morning activities, afternoon cultural events, and evening celebrations with music and traditional performances.',
            'target_audience' => 'International tourists, culture enthusiasts, families',
            'accessibility' => 'Wheelchair accessible, family-friendly, multilingual information available',
            'meta_title' => "$nombre | Traditional Festival in Spain",
            'meta_description' => "Experience the $nombre in $lugar, $provincia. Traditional Spanish festival with cultural activities, local food, and authentic celebrations. Perfect for international tourists."
        ],
        'fr' => [
            'short_description' => "Fête traditionnelle à $lugar, $provincia mettant en valeur la culture locale, la musique et les traditions.",
            'description' => "<p>Le $nombre est l'une des fêtes traditionnelles les plus importantes de $provincia, Espagne. Cette célébration annuelle réunit habitants et visiteurs pour vivre une expérience authentique de la culture espagnole.</p>
<p>Points forts :</p>
<ul>
<li>Spectacles de musique et danse traditionnelles</li>
<li>Gastronomie locale et stands de nourriture</li>
<li>Expositions et ateliers culturels</li>
<li>Activités familiales</li>
<li>Processions religieuses (le cas échéant)</li>
</ul>
<p>Dates : $fechaTexto</p>
<p>Lieu : $lugar, $provincia, Espagne</p>",
            'program' => "Programme quotidien incluant activités matinales, événements culturels l'après-midi et célébrations nocturnes avec musique et spectacles traditionnels.",
            'target_audience' => 'Touristes internationaux, amateurs de culture, familles',
            'accessibility' => 'Accessible aux fauteuils roulants, adapté aux familles, informations multilingues disponibles',
            'meta_title' => "$nombre | Fête Traditionnelle en Espagne",
            'meta_description' => "Vivez le $nombre à $lugar, $provincia. Fête traditionnelle espagnole avec activités culturelles, nourriture locale et célébrations authentiques. Parfait pour les touristes internationaux."
        ],
        'de' => [
            'short_description' => "Traditionelles Fest in $lugar, $provincia mit lokaler Kultur, Musik und Traditionen.",
            'description' => "<p>Das $nombre ist eines der wichtigsten traditionellen Feste in $provincia, Spanien. Diese jährliche Feier bringt Einheimische und Besucher zusammen, um authentische spanische Kultur zu erleben.</p>
<p>Höhepunkte:</p>
<ul>
<li>Traditionelle Musik- und Tanzvorführungen</li>
<li>Lokale Gastronomie und Essensstände</li>
<li>Kulturausstellungen und Workshops</li>
<li>Familienfreundliche Aktivitäten</li>
<li>Religiöse Prozessionen (falls zutreffend)</li>
</ul>
<p>Daten: $fechaTexto</p>
<p>Ort: $lugar, $provincia, Spanien</p>",
            'program' => 'Tagesprogramm beinhaltet morgendliche Aktivitäten, nachmittägliche Kulturveranstaltungen und abendliche Feiern mit Musik und traditionellen Darbietungen.',
            'target_audience' => 'Internationale Touristen, Kulturliebhaber, Familien',
            'accessibility' => 'Rollstuhlgerecht, familienfreundlich, mehrsprachige Informationen verfügbar',
            'meta_title' => "$nombre | Traditionelles Fest in Spanien",
            'meta_description' => "Erleben Sie das $nombre in $lugar, $provincia. Traditionelles spanisches Fest mit kulturellen Aktivitäten, lokaler Küche und authentischen Feiern. Perfekt für internationale Touristen."
        ],
        'zh' => [
            'short_description' => "西班牙$provincia $lugar的传统节日，展示当地文化、音乐和传统。",
            'description' => "<p>$nombre是西班牙$provincia最重要的传统节日之一。这个年度庆典汇聚了当地居民和游客，共同体验地道的西班牙文化。</p>
<p>亮点包括：</p>
<ul>
<li>传统音乐和舞蹈表演</li>
<li>当地美食和小吃摊</li>
<li>文化展览和工作坊</li>
<li>适合家庭的活动</li>
<li>宗教游行（如适用）</li>
</ul>
<p>日期：$fechaTexto</p>
<p>地点：西班牙$provincia $lugar</p>",
            'program' => '每日行程包括上午活动、下午文化活动和晚间庆祝活动，配有音乐和传统表演。',
            'target_audience' => '国际游客, 文化爱好者, 家庭',
            'accessibility' => '轮椅通道, 适合家庭, 提供多语言信息',
            'meta_title' => "$nombre | 西班牙传统节日",
            'meta_description' => "体验西班牙$provincia $lugar的$nombre。西班牙传统节日，包含文化活动、当地美食和地道庆祝。非常适合国际游客。"
        ]
    ];
    
    return $contenidos[$idioma] ?? $contenidos['en'];
}
?>