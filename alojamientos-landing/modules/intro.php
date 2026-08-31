<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  BLOQUE INTRO SEO — Landing de Alojamientos
 * ════════════════════════════════════════════════════════════════════════════
 *
 *  Genera el bloque de texto dinámico introductorio:
 *    H2 "¿Por qué alojarse en {province}?"
 *    Párrafo 1 (descripción de provincia/filtro)
 *    Párrafo 2 (diferenciador vs Booking)
 *    Consejo local
 *    Atractivos cercanos en pills
 *
 *  $ctx esperado:
 *    t, province_label, province_key, province_data (attractions, vibe),
 *    filter_labels, filter_icons, lang, stats
 */

function renderLandingIntro(array $ctx): void
{
    $t            = $ctx['t']              ?? [];
    $province     = $ctx['province_label'] ?? '';
    $prov_key     = $ctx['province_key']   ?? '';
    $prov_data    = $ctx['province_data']  ?? [];
    $filter_labels= $ctx['filter_labels']  ?? [];
    $filter_icons = $ctx['filter_icons']   ?? [];
    $lang         = $ctx['lang']           ?? 'es';
    $stats        = $ctx['stats']          ?? [];
    $pdo          = $ctx['pdo']            ?? null;

    // No renderizar si no hay provincia ni filtros
    if (empty($province) && empty($filter_labels)) return;

    // Texto de vibe de la provincia en el idioma correcto
    $vibe = $prov_data['vibe'][$lang] ?? $prov_data['vibe']['es'] ?? '';

    // Etiqueta del filtro principal en minúsculas (para insertar en texto)
    $filter_label_lower = !empty($filter_labels) ? mb_strtolower($filter_labels[0]) : ($lang === 'es' ? 'alojamientos rurales' : 'rural accommodation');

    // Si hay provincia, usa la lista de atractivos. Si no, una lista de provincias para interlinking.
    $interlinking_list_str = '';
    if (!empty($province)) {
        $attractions = $prov_data['attractions'] ?? [];
        $interlinking_list_str = implode(', ', $attractions);
    } else {
        // Si no hay provincia, creamos una lista de provincias para interlinking
        $all_provinces = LANDING_PROVINCIAS ?? [];
        $province_links = [];
        $base_path = ($lang === 'es') ? '/alojamientos/turismo-rural-' : "/{$lang}/alojamientos/turismo-rural-";

        foreach ($all_provinces as $key => $p_data) {
            $url = $base_path . $key;
            $province_links[] = '<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($p_data['label']) . '</a>';
        }
        // Unimos los enlaces con comas
        $interlinking_list_str = implode(', ', $province_links);
    }

    // Interpolar variables en las cadenas
    $vars = [
        'FILTER_LABEL_LOWER' => $filter_label_lower,
        'PROVINCE'           => !empty($province) ? $province : ($lang === 'es' ? 'España' : 'Spain'),
        'PROVINCE_VIBE'      => $vibe,
        'INTERLINKING_LIST'  => $interlinking_list_str ?: ($lang === 'es' ? 'Castilla y León' : 'Castile and León'),
        'FILTER_FEATURE'     => !empty($filter_labels[1]) ? mb_strtolower($filter_labels[1]) : ($lang === 'es' ? 'entorno natural único' : 'unique natural setting'),
        'FILTER_LABEL'       => !empty($filter_labels[0]) ? $filter_labels[0] : ($lang === 'es' ? 'Alojamientos rurales' : 'Rural accommodation'),
    ];

    $p1  = t($t['intro_p1']  ?? '', $vars);
    $p2  = t($t['intro_p2']  ?? '', $vars);
    $tip = t($t['intro_tip'] ?? '', $vars);
    $h2  = t($t['h2_porque'] ?? '¿Por qué alojarse en {PROVINCE}?', $vars);

    // ── Inbound links: enriquecer párrafos con links internos ────────────
    if ($pdo !== null) {
        $p1  = procesarInboundLinks($p1,  $pdo);
        $p2  = procesarInboundLinks($p2,  $pdo);
        $tip = procesarInboundLinks($tip, $pdo);
    }
?>
<!-- ══════════════════════════════════════════════════════════ INTRO ══ -->
<section class="lnd-intro" aria-label="Descripción del destino">
    <div class="lnd-intro__inner">

        <!-- H2 — Segundo encabezado más importante de la página -->
        <h2 class="lnd-intro__h2"><?= htmlspecialchars($h2) ?></h2>

        <!-- Párrafo 1: provincia + vibe -->
        <?php if (!empty($p1)): ?>
        <p class="lnd-intro__p"><?= $p1 // Permite HTML de procesarInboundLinks ?></p>
        <?php endif; ?>

        <!-- Párrafo 2: diferenciador rutasrurales.io -->
        <?php if (!empty($p2)): ?>
        <p class="lnd-intro__p"><?= $p2 ?></p>
        <?php endif; ?>

        <!-- Consejo local (HTML permitido para <strong>) -->
        <?php if (!empty($tip)): ?>
        <div class="lnd-intro__tip" role="note">
            <?= $tip ?>
        </div>
        <?php endif; ?>

        <!-- Pills de atractivos de la provincia -->
        <?php if (!empty($prov_data['attractions'])): ?>
        <div class="lnd-intro__attractions" aria-label="Principales atractivos">
            <?php foreach ($prov_data['attractions'] as $attraction): ?>
            <span class="lnd-pill lnd-pill--attraction">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                </svg>
                <?php
                // Procesar inbound links en el atractivo (ej: "Arribes del Duero")
                $attraction_linked = ($pdo !== null)
                    ? procesarInboundLinks($attraction, $pdo)
                    : $attraction;
                echo $attraction_linked; // Permite HTML del enlace
                ?>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Stats rápidos en línea (complementan el hero sin repetirlo) -->
        <?php if (!empty($stats['total']) && $stats['total'] > 0 && !empty($province)): ?>
        <p class="lnd-intro__stat-inline">
            <?php
            $statText = match($lang) {
                'en' => "We have <strong>{$stats['total']}</strong> verified accommodations in {$province}" . (!empty($stats['avg_price']) ? ", from <strong>{$stats['avg_price']} €/night</strong>." : "."),
                'fr' => "Nous avons <strong>{$stats['total']}</strong> hébergements vérifiés en {$province}" . (!empty($stats['avg_price']) ? ", à partir de <strong>{$stats['avg_price']} €/nuit</strong>." : "."),
                'de' => "Wir haben <strong>{$stats['total']}</strong> verifizierte Unterkünfte in {$province}" . (!empty($stats['avg_price']) ? ", ab <strong>{$stats['avg_price']} €/Nacht</strong>." : "."),
                'zh' => "我们在{$province}有 <strong>{$stats['total']}</strong> 处经过验证的住宿" . (!empty($stats['avg_price']) ? "，起价 <strong>{$stats['avg_price']} €/晚</strong>。" : "。"),
                default => "Tenemos <strong>{$stats['total']}</strong> alojamientos verificados en {$province}" . (!empty($stats['avg_price']) ? ", desde <strong>{$stats['avg_price']} €/noche</strong>." : "."),
            };
            echo $statText;
            ?>
        </p>
        <?php endif; ?>

    </div>
</section>
<!-- ════════════════════════════════════════════════════════ /INTRO ══ -->
<?php
}
