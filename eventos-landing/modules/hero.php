<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  HERO SECTION — Landing de Eventos Culturales
 * ════════════════════════════════════════════════════════════════════════════
 *
 *  Renderiza: breadcrumb > badges de filtros > H1 > stats
 *
 *  $ctx esperado:
 *    h1, province_label, filter_icons, filter_labels,
 *    stats (total, free_count, towns), t (array traducciones),
 *    canonical, lang, slug, parsed (province, filters)
 */

function renderEventosLandingHero(array $ctx): void
{
    $h1           = $ctx['h1']             ?? 'Eventos culturales';
    $province     = $ctx['province_label'] ?? '';
    $stats        = $ctx['stats']          ?? [];
    $t            = $ctx['t']              ?? [];
    $canonical    = $ctx['canonical']      ?? '#';
    $lang         = $ctx['lang']           ?? 'es';
    $parsed       = $ctx['parsed']         ?? ['province' => null, 'filters' => []];
    $filter_icons = $ctx['filter_icons']   ?? [];
    $filter_labs  = $ctx['filter_labels']  ?? [];

    $base_url = 'https://rutasrurales.io';
    $list_url = $lang !== 'es' ? "$base_url/$lang/eventos-culturales" : "$base_url/eventos-culturales";

    // Enlace "ver todos los eventos en provincia"
    $prov_url = '';
    if (!empty($parsed['province'])) {
        $prov_slug = $parsed['province'];
        $prov_url  = $lang !== 'es'
            ? "$base_url/$lang/eventos/$prov_slug"
            : "$base_url/eventos/$prov_slug";
    }
?>
<!-- ══════════════════════════════════════════════════════════ HERO ══ -->
<section class="lnd-hero" aria-label="Cabecera de búsqueda de eventos">

    <!-- Breadcrumb semántico -->
    <nav class="lnd-breadcrumb" aria-label="Breadcrumb">
        <ol itemscope itemtype="https://schema.org/BreadcrumbList">
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a href="<?= $base_url ?>/" itemprop="item">
                    <span itemprop="name"><?= htmlspecialchars($t['bc_home'] ?? 'Inicio') ?></span>
                </a>
                <meta itemprop="position" content="1">
                <span class="lnd-bc-sep" aria-hidden="true">›</span>
            </li>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a href="<?= $list_url ?>" itemprop="item">
                    <span itemprop="name"><?= htmlspecialchars($t['bc_listings'] ?? 'Eventos culturales') ?></span>
                </a>
                <meta itemprop="position" content="2">
                <?php if (!empty($province) || !empty($filter_labs)): ?>
                <span class="lnd-bc-sep" aria-hidden="true">›</span>
                <?php endif; ?>
            </li>
            <?php if (!empty($province) || !empty($filter_labs)): ?>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" aria-current="page">
                <span itemprop="name"><?= htmlspecialchars($h1) ?></span>
                <meta itemprop="position" content="3">
            </li>
            <?php endif; ?>
        </ol>
    </nav>

    <!-- Badges de filtros activos -->
    <?php if (!empty($filter_icons)): ?>
    <div class="lnd-hero__badges" aria-label="Filtros activos">
        <?php foreach ($filter_icons as $i => $icon): ?>
        <span class="lnd-badge lnd-badge--filter">
            <?= $icon ?> <?= htmlspecialchars($filter_labs[$i] ?? '') ?>
        </span>
        <?php endforeach; ?>
        <?php if (!empty($province)): ?>
        <span class="lnd-badge lnd-badge--province">
            📍 <?= htmlspecialchars($province) ?>
        </span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- H1 — único, explícito, con keyword principal -->
    <h1 class="lnd-hero__h1"><?= htmlspecialchars($h1) ?></h1>

    <!-- Stats en tiempo real (de BD) -->
    <?php if (!empty($stats['total']) && $stats['total'] > 0): ?>
    <dl class="lnd-hero__stats" aria-label="Estadísticas de eventos">
        <div class="lnd-stat">
            <dt class="lnd-stat__value"><?= $stats['total'] ?></dt>
            <dd class="lnd-stat__label"><?= htmlspecialchars($t['stat_count'] ?? 'eventos') ?></dd>
        </div>
        <?php if (!empty($stats['free_count']) && $stats['free_count'] > 0): ?>
        <div class="lnd-stat">
            <dt class="lnd-stat__value"><?= $stats['free_count'] ?></dt>
            <dd class="lnd-stat__label"><?= htmlspecialchars($t['stat_free'] ?? 'gratuitos') ?></dd>
        </div>
        <?php endif; ?>
        <?php if (!empty($stats['towns']) && $stats['towns'] > 0): ?>
        <div class="lnd-stat">
            <dt class="lnd-stat__value"><?= $stats['towns'] ?></dt>
            <dd class="lnd-stat__label"><?= htmlspecialchars($t['stat_towns'] ?? 'municipios') ?></dd>
        </div>
        <?php endif; ?>
    </dl>
    <?php endif; ?>

    <!-- Enlace rápido a todos los eventos de la provincia -->
    <?php if (!empty($prov_url) && !empty($parsed['filters'])): ?>
    <p class="lnd-hero__sublink">
        <a href="<?= htmlspecialchars($prov_url) ?>">
            <?= htmlspecialchars($lang === 'es' ? "Ver todos los eventos en $province →" : "All events in $province →") ?>
        </a>
    </p>
    <?php endif; ?>

</section>
<!-- ════════════════════════════════════════════════════════ /HERO ══ -->
<?php
}
