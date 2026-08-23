<?php
/**
 * hero.php — Cabecera hero: imagen LCP, H1, breadcrumb y badges
 * Variables requeridas: $lugar, $fotos, $t, $lang
 */
if (empty($lugar)) return;

if (!function_exists('fixUrlHero')) {
    function fixUrlHero(string $url): string {
        return preg_match('/^https?:\/\//', $url) ? $url : '/' . ltrim($url, '/');
    }
}

if (!function_exists('esLugarGastronomicoHero')) {
    function esLugarGastronomicoHero(string $categoryName): bool {
        if (empty($categoryName)) return false;
        $lower = mb_strtolower($categoryName, 'UTF-8');
        foreach (['restauran','gastronom','enotur','bodega','cafeter','restauraci','taberna','hosteleria','hostelería'] as $kw) {
            if (strpos($lower, $kw) !== false) return true;
        }
        return false;
    }
}

// Acceso seguro a claves de $t con fallback
$_t = [
    'inicio'  => isset($t['inicio'])  ? $t['inicio']  : 'Inicio',
    'lugares' => isset($t['lugares']) ? $t['lugares'] : 'Lugares de interés',
];

$fotoHero    = !empty($fotos[0]) ? fixUrlHero($fotos[0]) : '/menu_images/turismo_rural.webp';
$nombreLugar = isset($lugar['name']) ? $lugar['name'] : '';
$municipio   = isset($lugar['municipality']) ? $lugar['municipality'] : '';
$provincia   = isset($lugar['province']) ? $lugar['province'] : '';
$categoria   = isset($lugar['category_name']) ? $lugar['category_name'] : '';
$stars       = isset($lugar['quality_score']) ? (int)$lugar['quality_score'] : 0;
$entryFee    = isset($lugar['entry_fee']) ? $lugar['entry_fee'] : null;
$entryDet    = isset($lugar['entry_fee_details']) ? $lugar['entry_fee_details'] : '';

// Check if it's a gastronomic place
$esGastronomico = esLugarGastronomicoHero($categoria);

// For restaurants: don't show fee/gratis, show price range instead
$isGratuito  = (empty($entryFee) || (float)$entryFee === 0.0);
$entradaInfo = '';
if ($esGastronomico) {
    // For restaurants: show price range if available
    if (!empty($entryDet)) {
        $entradaInfo = '💶 ' . htmlspecialchars($entryDet, ENT_QUOTES, 'UTF-8');
    }
} elseif (!empty($entryFee) && (float)$entryFee > 0) {
    $entradaInfo = '💶 ' . number_format((float)$entryFee, 2, '.', '') . '€';
    if (!empty($entryDet)) $entradaInfo .= ' · ' . htmlspecialchars($entryDet, ENT_QUOTES, 'UTF-8');
} elseif ($isGratuito && !empty($entryDet)) {
    $entradaInfo = '🟢 0.00€ · ' . htmlspecialchars($entryDet, ENT_QUOTES, 'UTF-8');
}

// Prefijo de idioma para breadcrumb
$langPrefix = ($lang !== 'es') ? '/' . $lang : '';
?>

<!-- ══ HERO ══════════════════════════════════════════════ -->
<section class="lug-hero" role="banner" aria-label="<?php echo htmlspecialchars($nombreLugar, ENT_QUOTES, 'UTF-8'); ?>" itemscope itemtype="https://schema.org/TouristAttraction">

    <!-- Imagen hero (LCP) — fetchpriority high, no lazy -->
    <img class="lug-hero-bg-img"
         src="<?php echo htmlspecialchars($fotoHero, ENT_QUOTES, 'UTF-8'); ?>"
         alt="<?php echo htmlspecialchars($nombreLugar, ENT_QUOTES, 'UTF-8'); ?>"
         width="1200" height="500"
         fetchpriority="high"
         decoding="sync"
         loading="eager">

    <div class="lug-hero-overlay" aria-hidden="true"></div>

    <div class="lug-hero-content">

        <!-- Breadcrumb semántico (Schema.org BreadcrumbList inline) -->
        <nav class="lug-breadcrumb" aria-label="Ruta de navegación">
            <ol>
                <li><a href="<?php echo $langPrefix; ?>/">🏠 <?php echo htmlspecialchars($_t['inicio'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                <li><a href="<?php echo $langPrefix; ?>/lugares-de-interes"><?php echo htmlspecialchars($_t['lugares'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                <?php if (!empty($municipio)): ?>
                <li><a href="<?php echo $langPrefix; ?>/lugares-de-interes?municipio=<?php echo urlencode($municipio); ?>"><?php echo htmlspecialchars($municipio, ENT_QUOTES, 'UTF-8'); ?></a></li>
                <?php endif; ?>
                <li aria-current="page"><?php echo htmlspecialchars($nombreLugar, ENT_QUOTES, 'UTF-8'); ?></li>
            </ol>
        </nav>

        <!-- H1 -->
        <h1 itemprop="name"><?php echo htmlspecialchars($nombreLugar, ENT_QUOTES, 'UTF-8'); ?></h1>

        <!-- Localización -->
        <?php if (!empty($municipio)): ?>
        <p class="lug-hero-location" aria-label="Localización">
            📍 <?php echo htmlspecialchars($municipio, ENT_QUOTES, 'UTF-8'); ?>
            <?php if (!empty($provincia)): ?>, <?php echo htmlspecialchars($provincia, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>

            <?php if ($stars > 0): ?>
            <span class="lug-stars" aria-label="<?php echo $stars; ?> estrellas de calidad" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
                <meta itemprop="ratingValue" content="<?php echo $stars; ?>">
                <meta itemprop="bestRating" content="5">
                <?php for ($i = 0; $i < min($stars, 5); $i++) echo '⭐'; ?>
            </span>
            <?php endif; ?>
        </p>
        <?php endif; ?>

        <!-- Badges -->
        <div class="lug-badges" aria-label="Características del lugar">
            <?php if (!empty($categoria)): ?>
            <span class="lug-badge lug-badge-cat"><?php echo htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>

            <?php if (!$esGastronomico && $isGratuito && empty($entradaInfo)): ?>
            <span class="lug-badge lug-badge-free">✅ Gratis</span>
            <?php endif; ?>

            <?php if (!empty($lugar['pet_friendly'])): ?>
            <span class="lug-badge lug-badge-pet">🐾 Mascotas</span>
            <?php endif; ?>

            <?php if (!empty($lugar['suitable_for_children'])): ?>
            <span class="lug-badge lug-badge-kids">👶 Familias</span>
            <?php endif; ?>
        </div>

        <!-- Precio / detalle entrada -->
        <?php if (!empty($entradaInfo)): ?>
        <div class="lug-entry-badge" aria-label="Precio de entrada">
            <?php echo $entradaInfo; ?>
        </div>
        <?php endif; ?>

    </div><!-- /.lug-hero-content -->

</section>
