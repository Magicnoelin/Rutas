<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  BLOQUE INTRO SEO — Landing de Eventos Culturales
 * ════════════════════════════════════════════════════════════════════════════
 *
 *  Genera el bloque de texto dinámico introductorio:
 *    H2 "¿Por qué visitar {province} para sus eventos?"
 *    Párrafo 1 (descripción de provincia/categoría)
 *    Párrafo 2 (diferenciador rutasrurales.io)
 *    Consejo local
 *    Pills de atractivos / eventos de la provincia
 *
 *  $ctx esperado:
 *    t, province_label, province_key, province_data (attractions, vibe),
 *    filter_labels, filter_icons, lang, stats
 */

function renderEventosLandingIntro(array $ctx): void
{
    $t             = $ctx['t']              ?? [];
    $province      = $ctx['province_label'] ?? '';
    $prov_key      = $ctx['province_key']   ?? '';
    $prov_data     = $ctx['province_data']  ?? [];
    $filter_labels = $ctx['filter_labels']  ?? [];
    $filter_icons  = $ctx['filter_icons']   ?? [];
    $lang          = $ctx['lang']           ?? 'es';
    $stats         = $ctx['stats']          ?? [];

    // No renderizar si no hay provincia ni filtros
    if (empty($province) && empty($filter_labels)) return;

    // Texto de vibe de la provincia en el idioma correcto
    $vibe = $prov_data['vibe'][$lang] ?? $prov_data['vibe']['es'] ?? '';

    // Etiqueta del filtro principal en minúsculas
    $filter_label_lower = !empty($filter_labels)
        ? mb_strtolower($filter_labels[0])
        : ($lang === 'es' ? 'eventos culturales' : 'cultural events');

    // Atractivos como lista legible
    $attractions     = $prov_data['attractions'] ?? [];
    $attractions_str = '';
    if (!empty($attractions)) {
        if ($lang === 'zh') {
            $attractions_str = implode('、', $attractions);
        } else {
            $last = array_pop($attractions);
            $sep  = ['es' => ' y ', 'en' => ' and ', 'fr' => ' et ', 'de' => ' und '][$lang] ?? ' y ';
            $attractions_str = empty($attractions)
                ? $last
                : implode(', ', $attractions) . $sep . $last;
        }
    }

    // Interpolar variables
    $vars = [
        'FILTER_LABEL_LOWER' => $filter_label_lower,
        'PROVINCE'           => $province,
        'PROVINCE_VIBE'      => $vibe,
        'ATTRACTIONS_LIST'   => $attractions_str ?: $province,
        'FILTER_FEATURE'     => !empty($filter_labels[1]) ? mb_strtolower($filter_labels[1]) : ($lang === 'es' ? 'entrada gratuita' : 'free entry'),
        'FILTER_LABEL'       => !empty($filter_labels[0]) ? $filter_labels[0] : ($lang === 'es' ? 'Eventos culturales' : 'Cultural events'),
    ];

    $p1  = t($t['intro_p1']  ?? '', $vars);
    $p2  = t($t['intro_p2']  ?? '', $vars);
    $tip = t($t['intro_tip'] ?? '', $vars);
    $h2  = t($t['h2_porque'] ?? '¿Por qué visitar {PROVINCE} para sus eventos?', $vars);
?>
<!-- ══════════════════════════════════════════════════════════ INTRO ══ -->
<section class="lnd-intro" aria-label="Descripción de la agenda cultural">
    <div class="lnd-intro__inner">

        <!-- H2 -->
        <h2 class="lnd-intro__h2"><?= htmlspecialchars($h2) ?></h2>

        <!-- Párrafo 1: provincia + vibe cultural -->
        <?php if (!empty($p1)): ?>
        <p class="lnd-intro__p"><?= htmlspecialchars($p1) ?></p>
        <?php endif; ?>

        <!-- Párrafo 2: diferenciador rutasrurales.io -->
        <?php if (!empty($p2)): ?>
        <p class="lnd-intro__p"><?= htmlspecialchars($p2) ?></p>
        <?php endif; ?>

        <!-- Consejo local (HTML permitido para <strong>) -->
        <?php if (!empty($tip)): ?>
        <div class="lnd-intro__tip" role="note">
            <?= $tip ?>
        </div>
        <?php endif; ?>

        <!-- Pills de atractivos de la provincia -->
        <?php if (!empty($prov_data['attractions'])): ?>
        <div class="lnd-intro__attractions" aria-label="Principales atractivos de la zona">
            <?php foreach ($prov_data['attractions'] as $attraction): ?>
            <span class="lnd-pill lnd-pill--attraction">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                </svg>
                <?= htmlspecialchars($attraction) ?>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Stats rápidos en línea -->
        <?php if (!empty($stats['total']) && $stats['total'] > 0 && !empty($province)): ?>
        <p class="lnd-intro__stat-inline">
            <?php
            $freeText = !empty($stats['free_count']) && $stats['free_count'] > 0
                ? ', ' . ($lang === 'es' ? "de los cuales <strong>{$stats['free_count']}</strong> son gratuitos"
                    : ($lang === 'en' ? "of which <strong>{$stats['free_count']}</strong> are free"
                    : ($lang === 'fr' ? "dont <strong>{$stats['free_count']}</strong> gratuits"
                    : ($lang === 'de' ? "davon <strong>{$stats['free_count']}</strong> kostenlos"
                    : "其中<strong>{$stats['free_count']}</strong>项免费"))))
                : '';

            $statText = match($lang) {
                'en' => "There are <strong>{$stats['total']}</strong> upcoming events in {$province}{$freeText}.",
                'fr' => "Il y a <strong>{$stats['total']}</strong> événements à venir en {$province}{$freeText}.",
                'de' => "Es gibt <strong>{$stats['total']}</strong> bevorstehende Veranstaltungen in {$province}{$freeText}.",
                'zh' => "{$province}有 <strong>{$stats['total']}</strong> 项即将到来的活动{$freeText}。",
                default => "Hay <strong>{$stats['total']}</strong> eventos próximos en {$province}{$freeText}.",
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
