<?php
/**
 * ============================================================
 * HELPER DE TRADUCCIONES Y HREFLANG
 * ============================================================
 * Proporciona funciones para generar URLs de idioma correctamente,
 * verificando si existen traducciones antes de generar los enlaces.
 * 
 * Evita errores 404 en:
 * - Etiquetas hreflang
 * - Selector de idioma
 * - Sitemaps
 * 
 * Uso:
 *   require_once __DIR__ . '/includes/i18n_helper.php';
 */

/**
 * Verifica si existe traducción para un lugar en un idioma dado
 */
function get_lugar_translation_slug(PDO $pdo, int $place_id, string $language_code): ?string {
    try {
        $stmt = $pdo->prepare("
            SELECT slug FROM places_of_interest_trads 
            WHERE place_id = ? AND language_code = ? 
            AND slug IS NOT NULL AND slug != ''
            LIMIT 1
        ");
        $stmt->execute([$place_id, $language_code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['slug'] : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Verifica si existe traducción para un evento en un idioma dado
 */
function get_event_translation_slug(PDO $pdo, int $event_id, string $language_code): ?string {
    try {
        $stmt = $pdo->prepare("
            SELECT slug FROM cultural_events_trads 
            WHERE event_id = ? AND language_code = ? 
            AND slug IS NOT NULL AND slug != ''
            LIMIT 1
        ");
        $stmt->execute([$event_id, $language_code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['slug'] : null;
    } catch (Exception $e) {
        return null;
    }
}
/**
 * Genera el mapa de hreflang para un lugar de interés
 * SOLO incluye idiomas que tienen traducción real en la BD
 */
function generar_hreflang_lugar(PDO $pdo, int $place_id, string $slug_es, string $lang_actual = 'es'): array {
    $base_domain = 'https://rutasrurales.io';
    $idiomas = ['es', 'en', 'fr', 'de', 'zh'];
    
    $result = [];
    
    // Siempre incluir español (canónico)
    $result['es'] = [
        'url' => $base_domain . '/lugar/' . $slug_es,
        'exists' => true,
        'slug' => $slug_es,
        'is_default' => true
    ];
    
    foreach ($idiomas as $lang) {
        if ($lang === 'es') continue;
        
        $trad_slug = get_lugar_translation_slug($pdo, $place_id, $lang);
        
        if ($trad_slug) {
            $prefix = ($lang === 'es') ? '' : '/' . $lang;
            $result[$lang] = [
                'url' => $base_domain . $prefix . '/lugar/' . $trad_slug,
                'exists' => true,
                'slug' => $trad_slug,
                'is_default' => false
            ];
        }
    }
    
    return $result;
}

/**
 * Genera el mapa de hreflang para un evento cultural
 */
function generar_hreflang_evento(PDO $pdo, int $event_id, string $slug_es, string $lang_actual = 'es'): array {
    $base_domain = 'https://rutasrurales.io';
    $idiomas = ['es', 'en', 'fr', 'de', 'zh'];
    
    $result = [];
    
    // Siempre incluir español (canónico)
    $result['es'] = [
        'url' => $base_domain . '/evento/' . $slug_es,
        'exists' => true,
        'slug' => $slug_es,
        'is_default' => true
    ];
    
    foreach ($idiomas as $lang) {
        if ($lang === 'es') continue;
        
        $trad_slug = get_event_translation_slug($pdo, $event_id, $lang);
        
        if ($trad_slug) {
            $prefix = ($lang === 'es') ? '' : '/' . $lang;
            $result[$lang] = [
                'url' => $base_domain . $prefix . '/evento/' . $trad_slug,
                'exists' => true,
                'slug' => $trad_slug,
                'is_default' => false
            ];
        }
    }
    
    return $result;
}

/**
 * Renderiza etiquetas hreflang HTML para lugares
 */
function render_hreflang_html(array $hreflang_map): string {
    $html = '';
    
    foreach ($hreflang_map as $lang => $data) {
        $lang_attr = ($lang === 'zh') ? 'zh-Hans' : $lang;
        $html .= '<link rel="alternate" hreflang="' . htmlspecialchars($lang_attr) . '" href="' . htmlspecialchars($data['url']) . '">' . "\n";
    }
    
    if (isset($hreflang_map['es'])) {
        $html .= '<link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($hreflang_map['es']['url']) . '">' . "\n";
    }
    
    return $html;
}

/**
 * Verifica si existe traducción para un alojamiento en un idioma dado
 */
function get_alojamiento_translation_slug(PDO $pdo, int $alojamiento_id, string $language_code): ?string {
    try {
        $stmt = $pdo->prepare("
            SELECT slug FROM accommodations_trads 
            WHERE accommodation_id = ? AND language_code = ? 
            AND slug IS NOT NULL AND slug != ''
            LIMIT 1
        ");
        $stmt->execute([$alojamiento_id, $language_code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['slug'] : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Genera el mapa de hreflang para un alojamiento
 * SOLO incluye idiomas que tienen traducción real en la BD
 */
function generar_hreflang_alojamiento(PDO $pdo, int $alojamiento_id, string $slug_es, string $lang_actual = 'es'): array {
    $base_domain = 'https://rutasrurales.io';
    $idiomas = ['es', 'en', 'fr', 'de', 'zh'];
    
    $result = [];
    
    // Siempre incluir español (canónico)
    $result['es'] = [
        'url' => $base_domain . '/alojamiento/' . $slug_es,
        'exists' => true,
        'slug' => $slug_es,
        'is_default' => true
    ];
    
    foreach ($idiomas as $lang) {
        if ($lang === 'es') continue;
        
        $trad_slug = get_alojamiento_translation_slug($pdo, $alojamiento_id, $lang);
        
        if ($trad_slug) {
            $prefix = ($lang === 'es') ? '' : '/' . $lang;
            $result[$lang] = [
                'url' => $base_domain . $prefix . '/alojamiento/' . $trad_slug,
                'exists' => true,
                'slug' => $trad_slug,
                'is_default' => false
            ];
        }
    }
    
    return $result;
}