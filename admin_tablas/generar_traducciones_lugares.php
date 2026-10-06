<?php
// ============================================================================
// SCRIPT DE GENERACIÓN DE TRADUCCIONES DE LUGARES DE INTERÉS
// ============================================================================

// 1. CONEXIÓN A LA BASE DE DATOS Y HELPERS DE SLUGS
include 'db.php';
include 'slug_lugares_helper.php';

header('Content-Type: text/html; charset=utf-8');
set_time_limit(300);

// ── HELPER: detectar si un texto ya contiene HTML de bloque ───────────────
function tieneHtmlBloque(string $texto): bool {
    return (bool)preg_match('/<(?:p|h[1-6]|ul|ol|li|div|section|article|blockquote)\b/i', $texto);
}

// ── FUNCIÓN DE TRADUCCIÓN VÍA GOOGLE TRANSLATE (sin API key) ─────────────
// Preserva HTML completo: NO usa strip_tags, NO usa htmlspecialchars en el resultado.
// Divide en fragmentos por etiquetas de cierre de bloque usando marcador temporal
// (los lookbehind de longitud variable no están soportados en PHP).
function traducirTexto(string $texto, string $targetLang): string {
    if (empty(trim($texto))) return $texto;

    // Insertar marcador DESPUÉS de cada etiqueta de cierre de bloque
    $marcador = "\x00SPLIT\x00";
    $textoMarcado = preg_replace(
        '/(<\/(?:p|h[1-6]|li|div|ul|ol|blockquote|section|article|tr|td|th)>)/i',
        '$1' . $marcador,
        $texto
    );
    $partes = explode($marcador, $textoMarcado ?? $texto);
    if (!$partes || count($partes) === 0) $partes = [$texto];

    // Agrupar partes en fragmentos de máx. 4500 chars
    $fragmentos = [];
    $buffer = '';
    foreach ($partes as $parte) {
        if (strlen($buffer) + strlen($parte) > 4500) {
            if ($buffer !== '') $fragmentos[] = $buffer;
            $buffer = $parte;
        } else {
            $buffer .= $parte;
        }
    }
    if ($buffer !== '') $fragmentos[] = $buffer;

    $traducido = [];
    foreach ($fragmentos as $frag) {
        // &format=html indica a Google que el contenido es HTML → preserva etiquetas
        $apiUrl = 'https://translate.googleapis.com/translate_a/single'
                . '?client=gtx&sl=es&tl=' . urlencode($targetLang) . '&dt=t&dj=1&format=html';
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_USERAGENT      => 'Mozilla/5.0',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => 'q=' . urlencode($frag),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err || !$resp) { $traducido[] = $frag; continue; }
        $data = json_decode($resp, true);
        if (json_last_error() !== JSON_ERROR_NONE) { $traducido[] = $frag; continue; }

        $partesTrad = '';
        if (!empty($data['sentences'])) {
            foreach ($data['sentences'] as $seg) {
                if (!empty($seg['trans'])) $partesTrad .= $seg['trans'];
            }
        } elseif (!empty($data[0])) {
            foreach ($data[0] as $seg) {
                if (!empty($seg[0])) $partesTrad .= $seg[0];
            }
        }
        if (empty($partesTrad)) { $traducido[] = $frag; continue; }
        $traducido[] = $partesTrad;
        usleep(150000);
    }
    return implode('', $traducido);
}
// ── 2. CONFIGURACIÓN Y TEXTOS POR IDIOMA ────────────────────────────────────
$textos = [
    'es' => [
        'intro' => 'Descubre', 'in' => 'en',
        'h3a' => 'Sobre', 'h3v' => 'Qué ver',
        'acc' => 'Accesible en silla de ruedas, apto para familias',
        'msuf' => 'en España', 'mv' => 'Visita', 'mend' => 'Descubre este lugar especial en España.',
        'short_template' => fn($name, $muni, $prov) => "Descubre $name en $muni, $prov. Un lugar único para disfrutar de la cultura y el turismo rural en España.",
        'desc_template' => function($name, $desc_es, $h3a, $h3v) {
            $textoLargo = !empty(trim($desc_es)) ? $desc_es : "Un espacio excepcional lleno de encanto y tradición en España.";
            return "<section><h3>$h3a $name</h3>$textoLargo</section><section><h3>$h3v</h3><p>Disfruta de su entorno, su patrimonio y su riqueza local.</p></section>";
        },
        'meta_title' => fn($name, $cat) => "$name | " . (!empty($cat) ? $cat : 'Lugar de Interés') . " en España",
        'meta_desc' => fn($name, $muni, $prov) => "Visita $name en $muni, $prov. Descubre este lugar especial en España."
    ],
    'en' => [
        'intro' => 'Discover', 'in' => 'in',
        'h3a' => 'About', 'h3v' => 'What to See',
        'acc' => 'Wheelchair accessible, family-friendly',
        'msuf' => 'in Spain', 'mv' => 'Visit', 'mend' => 'Discover this remarkable place in Spain.',
        'short_template' => fn($name, $muni, $prov) => "Discover $name in $muni, $prov. A wonderful location to explore rural Spain and local heritage.",
        'desc_template' => function($name, $desc_es, $h3a, $h3v) {
            return "<section><h3>$h3a $name</h3><p>This remarkable location offers a unique experience for travelers seeking authentic rural tourism, full of charm and local tradition.</p></section><section><h3>$h3v</h3><p>Enjoy its surroundings, cultural heritage, and local environment.</p></section>";
        },
        'meta_title' => fn($name, $cat) => "$name | " . (!empty($cat) ? $cat : 'Place of Interest') . " in Spain",
        'meta_desc' => fn($name, $muni, $prov) => "Visit $name in $muni, $prov. Discover this remarkable place in Spain."
    ],
    'fr' => [
        'intro' => 'Découvrez', 'in' => 'à',
        'h3a' => 'À propos de', 'h3v' => 'À voir',
        'acc' => 'Accessible, adapté aux familles',
        'msuf' => 'en Espagne', 'mv' => 'Visitez', 'mend' => 'Découvrez ce lieu remarquable en Espagne.',
        'short_template' => fn($name, $muni, $prov) => "Découvrez $name à $muni, $prov. Un endroit magnifique pour explorer le tourisme rural en Espagne.",
        'desc_template' => function($name, $desc_es, $h3a, $h3v) {
            return "<section><h3>$h3a $name</h3><p>Ce lieu remarquable offre une expérience unique aux voyageurs à la recherche d'authenticité et de patrimoine rural.</p></section><section><h3>$h3v</h3><p>Profitez de ses environs, de son patrimoine culturel et de son environnement local.</p></section>";
        },
        'meta_title' => fn($name, $cat) => "$name | " . (!empty($cat) ? $cat : "Lieu d'intérêt") . " en Espagne",
        'meta_desc' => fn($name, $muni, $prov) => "Visitez $name à $muni, $prov. Découvrez ce lieu remarquable en Espagne."
    ],
    'de' => [
        'intro' => 'Entdecken Sie', 'in' => 'in',
        'h3a' => 'Über', 'h3v' => 'Sehenswürdigkeiten',
        'acc' => 'Barrierefrei, familienfreundlich',
        'msuf' => 'in Spanien', 'mv' => 'Besuchen Sie', 'mend' => 'Entdecken Sie diesen tollen Ort in Spanien.',
        'short_template' => fn($name, $muni, $prov) => "Entdecken Sie $name in $muni, $prov. Ein wunderbarer Ort, um den ländlichen Tourismus in Spanien zu erkunden.",
        'desc_template' => function($name, $desc_es, $h3a, $h3v) {
            return "<section><h3>$h3a $name</h3><p>Dieser bemerkenswerte Ort bietet Reisenden ein einzigartiges Erlebnis im ländlichen Spanien voller Tradition und Charme.</p></section><section><h3>$h3v</h3><p>Genießen Sie die Umgebung, das kulturelle Erbe und die lokale Natur.</p></section>";
        },
        'meta_title' => fn($name, $cat) => "$name | " . (!empty($cat) ? $cat : 'Sehenswürdigkeit') . " in Spanien",
        'meta_desc' => fn($name, $muni, $prov) => "Besuchen Sie $name in $muni, $prov. Entdecken Sie diesen tollen Ort in Spanien."
    ],
    'zh' => [
        'intro' => '探索', 'in' => '在',
        'h3a' => '关于', 'h3v' => '参观亮点',
        'acc' => '无障碍, 适合家庭',
        'msuf' => '西班牙', 'mv' => '参观', 'mend' => '发现西班牙的这个精彩景点。',
        'short_template' => function($name, $muni, $prov) {
            return "探索位于西班牙 " . $prov . " " . $muni . " 的 " . $name . "。体验地道乡村旅游的绝佳去处。";
        },
        'desc_template' => function($name, $desc_es, $h3a, $h3v) {
            return "<section><h3>" . $h3a . " " . $name . "</h3><p>这个出色的景点为游客提供独特的旅行体验，充满西班牙乡村的传统与魅力。</p></section><section><h3>" . $h3v . "</h3><p>尽情欣赏周边环境、文化遗产和当地风情。</p></section>";
        },
        'meta_title' => function($name, $cat) {
            return $name . " | " . (!empty($cat) ? $cat : '景点') . " 西班牙";
        },
        'meta_desc' => function($name, $muni, $prov) {
            return "参观位于西班牙 " . $prov . " " . $muni . " 的 " . $name . "。";
        }
    ],
];

// ── 3. PREPARACIÓN DE LA SENTENCIA DE INSERCIÓN ─────────────────────────────
$insert = $pdo->prepare("
    INSERT INTO places_of_interest_trads (
        place_id, language_code, name, slug, short_description, description,
        address, municipality, province, opening_hours, accessibility,
        meta_title, meta_description, entry_fee, entry_fee_details, facilities
    ) VALUES (
        :place_id, :lang, :name, :slug, :short_desc, :description,
        :address, :municipality, :province, :opening_hours, :accessibility,
        :meta_title, :meta_description, :entry_fee, :entry_fee_details, :facilities
    ) ON DUPLICATE KEY UPDATE
        name = VALUES(name),
        slug = VALUES(slug),
        short_description = VALUES(short_description),
        description = VALUES(description),
        address = VALUES(address),
        municipality = VALUES(municipality),
        province = VALUES(province),
        opening_hours = VALUES(opening_hours),
        accessibility = VALUES(accessibility),
        meta_title = VALUES(meta_title),
        meta_description = VALUES(meta_description),
        entry_fee = VALUES(entry_fee),
        entry_fee_details = VALUES(entry_fee_details),
        facilities = VALUES(facilities)
");

$totalInsertados = 0;
$insertadosEs = 0;

// ── 4. FASE 1 — Idioma base 'es' ─────────────────────────────────────────────
$stmtEs = $pdo->query("
    SELECT p.id,
           p.name,
           p.slug        AS slug_original,
           p.municipality, p.province, p.address,
           p.short_description, p.description,
           p.opening_hours, p.entry_fee, p.entry_fee_details, p.facilities,
           COALESCE(c.name,'') AS categoria
    FROM   places_of_interest p
    LEFT JOIN categories_places c ON p.category_id = c.id
    WHERE  p.is_active = 1
      AND  p.id NOT IN (
               SELECT place_id FROM places_of_interest_trads WHERE language_code = 'es'
           )
    ORDER BY p.id
");
$lugaresParaEs = $stmtEs->fetchAll(PDO::FETCH_ASSOC);
$t = $textos['es'];

foreach ($lugaresParaEs as $lugar) {
    $slugEs    = generarSlugLugar($lugar['name'], $lugar['categoria'], $lugar['municipality'], 'es');
    $shortDesc = ($t['short_template'])($lugar['name'], $lugar['municipality'], $lugar['province']);
    $desc      = ($t['desc_template'])($lugar['name'], $lugar['description'], $t['h3a'], $t['h3v']);
    $metaTitle = ($t['meta_title'])($lugar['name'], $lugar['categoria']);
    $metaDesc  = ($t['meta_desc'])($lugar['name'], $lugar['municipality'], $lugar['province']);

    $insert->execute([
        ':place_id'         => $lugar['id'],
        ':lang'             => 'es',
        ':name'             => $lugar['name'],
        ':slug'             => $slugEs,
        ':short_desc'       => $shortDesc,
        ':description'      => $desc,
        ':address'          => $lugar['address']            ?? '',
        ':municipality'     => $lugar['municipality'],
        ':province'         => $lugar['province'],
        ':opening_hours'    => $lugar['opening_hours']      ?? '',
        ':accessibility'    => $t['acc'],
        ':meta_title'       => $metaTitle,
        ':meta_description' => $metaDesc,
        ':entry_fee'        => $lugar['entry_fee']          ?? '',
        ':entry_fee_details'=> $lugar['entry_fee_details'] ?? '',
        ':facilities'       => $lugar['facilities']         ?? '',
    ]);
    $insertadosEs++;
    $totalInsertados++;
}

// ── 5. FASE 2 — Traducciones secundarias (en, fr, de, zh) con plantillas nativas ──
foreach (['en', 'fr', 'de', 'zh'] as $lang) {
    $t = $textos[$lang];

    $stmtLang = $pdo->prepare("
        SELECT
            pt_es.place_id          AS id,
            pt_es.name,
            pt_es.municipality,
            pt_es.province,
            pt_es.address,
            pt_es.opening_hours,
            p.short_description     AS short_original,
            p.description           AS desc_original,
            p.entry_fee,
            p.entry_fee_details,
            p.facilities,
            COALESCE(c.name, '')    AS categoria
        FROM   places_of_interest_trads pt_es
        INNER JOIN places_of_interest p  ON p.id  = pt_es.place_id
        LEFT  JOIN categories_places  c  ON c.id  = p.category_id
        WHERE  pt_es.language_code = 'es'
          AND  p.is_active = 1
          AND  pt_es.place_id NOT IN (
                    SELECT place_id FROM places_of_interest_trads WHERE language_code = :lang
                )
        ORDER BY pt_es.place_id
    ");
    $stmtLang->execute([':lang' => $lang]);
    $lugares = $stmtLang->fetchAll(PDO::FETCH_ASSOC);

    // Textos de fallback genérico por idioma (cuando description real está vacía)
    $fallbackIntro = [
        'en' => 'This remarkable location offers a unique experience for travelers seeking authentic rural tourism, full of charm and local tradition.',
        'fr' => "Ce lieu remarquable offre une expérience unique aux voyageurs à la recherche d'authenticité et de patrimoine rural.",
        'de' => 'Dieser bemerkenswerte Ort bietet Reisenden ein einzigartiges Erlebnis im ländlichen Spanien voller Tradition und Charme.',
        'zh' => '这个出色的景点为游客提供独特的旅行体验，充满西班牙乡村的传统与魅力。',
    ];
    $fallbackWhatToSee = [
        'en' => 'Enjoy its surroundings, cultural heritage, and local environment.',
        'fr' => 'Profitez de ses environs, de son patrimoine culturel et de son environnement local.',
        'de' => 'Genießen Sie die Umgebung, das kulturelle Erbe und die lokale Natur.',
        'zh' => '尽情欣赏周边环境、文化遗产和当地风情。',
    ];

    foreach ($lugares as $lugar) {
        $slug      = generarSlugLugar($lugar['name'], $lugar['categoria'], $lugar['municipality'], $lang);
        $shortDesc = ($t['short_template'])($lugar['name'], $lugar['municipality'], $lugar['province']);

        // Usar contenido real del lugar (en español) para las descripciones.
        // IMPORTANTE: NO usar strip_tags → preservar HTML original (<p>, <ul>, <li>…)
        // IMPORTANTE: NO usar htmlspecialchars en el resultado de traducirTexto → ya es HTML
        $shortOriginal = trim($lugar['short_original'] ?? '');
        $descOriginal  = trim($lugar['desc_original']  ?? '');

        if (!empty($shortOriginal)) {
            $introContent = traducirTexto($shortOriginal, $lang);
        } else {
            $introContent = $fallbackIntro[$lang];
        }
        if (!empty($descOriginal)) {
            $bodyContent = traducirTexto($descOriginal, $lang);
        } else {
            $bodyContent = $fallbackWhatToSee[$lang];
        }

        $h3a = $t['h3a'] ?? 'About';
        $h3v = $t['h3v'] ?? 'What to See';

        // Si la descripción original ya tiene HTML de bloque, usarla directamente sin añadir <p> extra
        if (tieneHtmlBloque($bodyContent)) {
            $desc = '<section><h3>'.$h3a.' '.htmlspecialchars($lugar['name']).'</h3>'
                  . '<p>'.$introContent.'</p></section>'
                  . '<section><h3>'.$h3v.'</h3>'.$bodyContent.'</section>';
        } else {
            $desc = '<section><h3>'.$h3a.' '.htmlspecialchars($lugar['name']).'</h3>'
                  . '<p>'.$introContent.'</p></section>'
                  . '<section><h3>'.$h3v.'</h3><p>'.$bodyContent.'</p></section>';
        }

        $metaTitle = ($t['meta_title'])($lugar['name'], $lugar['categoria']);
        $metaDesc  = ($t['meta_desc'])($lugar['name'], $lugar['municipality'], $lugar['province']);

        $insert->execute([
            ':place_id'         => $lugar['id'],
            ':lang'             => $lang,
            ':name'             => $lugar['name'],
            ':slug'             => $slug,
            ':short_desc'       => $shortDesc,
            ':description'      => $desc,
            ':address'          => $lugar['address']            ?? '',
            ':municipality'     => $lugar['municipality'],
            ':province'         => $lugar['province'],
            ':opening_hours'    => $lugar['opening_hours']      ?? '',
            ':accessibility'    => $t['acc'],
            ':meta_title'       => $metaTitle,
            ':meta_description' => $metaDesc,
            ':entry_fee'        => $lugar['entry_fee']          ?? '',
            ':entry_fee_details'=> $lugar['entry_fee_details'] ?? '',
            ':facilities'       => $lugar['facilities']         ?? '',
        ]);
        $totalInsertados++;
    }
}

echo "<h3>Traducciones generadas correctamente. Total procesadas: $totalInsertados</h3>";