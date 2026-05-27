<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  HERO SECTION — Landing de Alojamientos
 * ════════════════════════════════════════════════════════════════════════════
 *
 *  Renderiza: breadcrumb > badges > H1 > stats > filtros activos
 *
 *  $ctx esperado:
 *    h1, province_label, filter_icons, filter_labels,
 *    stats (total, avg_price, towns), t (array traducciones),
 *    canonical, lang, slug, parsed (province, filters)
 */

function renderLandingHero(array $ctx): void
{
    $h1           = $ctx['h1']            ?? 'Alojamientos rurales';
    $province     = $ctx['province_label']?? '';
    $stats        = $ctx['stats']         ?? [];
    $t            = $ctx['t']             ?? [];
    $canonical    = $ctx['canonical']     ?? '#';
    $lang         = $ctx['lang']          ?? 'es';
    $parsed       = $ctx['parsed']        ?? ['province'=>null,'filters'=>[]];
    $filter_icons = $ctx['filter_icons']  ?? [];
    $filter_labs  = $ctx['filter_labels'] ?? [];

    $base_url = 'https://rutasrurales.io';
    $list_url = $lang !== 'es' ? "$base_url/$lang/alojamientos-turisticos" : "$base_url/alojamientos-turisticos";

    // Enlace "ver todos en provincia"
    $prov_url = '';
    if (!empty($parsed['province'])) {
        $prov_slug = $parsed['province'];
        $prov_url  = $lang !== 'es'
            ? "$base_url/$lang/alojamientos/turismo-rural-$prov_slug"
            : "$base_url/alojamientos/turismo-rural-$prov_slug";
    }
?>
<!-- ══════════════════════════════════════════════════════════ HERO ══ -->
<section class="lnd-hero" aria-label="Cabecera de búsqueda">

    <!-- Breadcrumb semántico (también en Schema, esto es para usuarios) -->
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
                    <span itemprop="name"><?= htmlspecialchars($t['bc_listings'] ?? 'Alojamientos rurales') ?></span>
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
    <dl class="lnd-hero__stats" aria-label="Estadísticas de alojamientos">
        <div class="lnd-stat">
            <dt class="lnd-stat__value"><?= $stats['total'] ?></dt>
            <dd class="lnd-stat__label"><?= htmlspecialchars($t['stat_count'] ?? 'alojamientos') ?></dd>
        </div>
        <?php if (!empty($stats['avg_price'])): ?>
        <div class="lnd-stat">
            <dt class="lnd-stat__value"><?= $stats['avg_price'] ?> €</dt>
            <dd class="lnd-stat__label"><?= htmlspecialchars($t['stat_price'] ?? 'precio medio/noche') ?></dd>
        </div>
        <?php endif; ?>
        <?php if (!empty($stats['towns'])): ?>
        <div class="lnd-stat">
            <dt class="lnd-stat__value"><?= $stats['towns'] ?></dt>
            <dd class="lnd-stat__label"><?= htmlspecialchars($t['stat_towns'] ?? 'municipios') ?></dd>
        </div>
        <?php endif; ?>
    </dl>
    <?php endif; ?>

    <!-- Enlace rápido a todos los alojamientos de la provincia -->
    <?php if (!empty($prov_url) && !empty($parsed['filters'])): ?>
    <p class="lnd-hero__sublink">
        <a href="<?= htmlspecialchars($prov_url) ?>">
            Ver todos los alojamientos en <?= htmlspecialchars($province) ?> →
        </a>
    </p>
    <?php endif; ?>

</section>
<!-- ════════════════════════════════════════════════════════ /HERO ══ -->
<?php
}
