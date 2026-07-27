<?php
/**
 * hero.php — Hero de la ficha de lugar de interés
 * Variables requeridas: $lugar, $fotos, $t, $slug
 */
if (empty($lugar)) return;

// Detecta si un lugar es de tipo gastronómico/restaurante (no tiene "entrada")
function esLugarGastronomico(string $categoryName): bool {
    if (empty($categoryName)) return false;
    $lower = mb_strtolower($categoryName, 'UTF-8');
    foreach (['restauran', 'gastronom', 'enotur', 'bodega', 'cafeter', 'restauraci', 'taberna', 'hosteleria', 'hostelería'] as $kw) {
        if (strpos($lower, $kw) !== false) return true;
    }
    return false;
}

$foto_hero    = !empty($fotos[0]) ? (preg_match('/^https?:\/\//', $fotos[0]) ? $fotos[0] : '/' . ltrim($fotos[0], '/')) : '';
$esGratis     = empty($lugar['entry_fee']) || $lugar['entry_fee'] == 0;
$esGastronomico = esLugarGastronomico($lugar['category_name'] ?? '');
$ubicacion    = implode(', ', array_filter([$lugar['municipality'] ?? '', $lugar['province'] ?? '']));
?>

<!-- ══════════════════════════════════════════════════════
     HERO — SSR, visible inmediatamente (LCP)
     ══════════════════════════════════════════════════════ -->
<section class="lug-hero" id="lug-hero">

    <?php if ($foto_hero): ?>
    <img id="heroBg"
         class="lug-hero-bg-img"
         src="<?php echo htmlspecialchars($foto_hero, ENT_QUOTES, 'UTF-8'); ?>"
         alt="<?php echo htmlspecialchars($lugar['name'], ENT_QUOTES, 'UTF-8'); ?> — imagen principal"
         fetchpriority="high"
         loading="eager"
         decoding="async"
         width="1200" height="440">
    <?php endif; ?>

    <div class="lug-hero-overlay"></div>

    <!-- Botones flotantes: compartir y guardar -->
    <div class="lug-hero-actions">
        <button class="lug-hero-btn" id="btn-share" title="<?php echo htmlspecialchars($t['compartir'], ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($t['compartir'], ENT_QUOTES, 'UTF-8'); ?>">🔗</button>
        <button class="lug-hero-btn" id="btn-fav"   title="Guardar" aria-label="Guardar en favoritos">🤍</button>
    </div>

    <div class="lug-hero-content">

        <!-- Breadcrumb (semántico para SEO + Schema BreadcrumbList) -->
        <nav class="lug-breadcrumb" aria-label="breadcrumb">
            <a href="/"><?php echo htmlspecialchars($t['inicio'], ENT_QUOTES, 'UTF-8'); ?></a>
            <span aria-hidden="true">/</span>
            <a href="/lugares-de-interes"><?php echo htmlspecialchars($t['lugares'], ENT_QUOTES, 'UTF-8'); ?></a>
            <?php if (!empty($lugar['province'])): ?>
            <span aria-hidden="true">/</span>
            <a href="/lugares-de-interes?provincia=<?php echo urlencode($lugar['province']); ?>"><?php echo htmlspecialchars($lugar['province'], ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endif; ?>
            <span aria-hidden="true">/</span>
            <span aria-current="page"><?php echo htmlspecialchars($lugar['name'], ENT_QUOTES, 'UTF-8'); ?></span>
        </nav>

        <!-- Badge de categoría -->
        <?php if (!empty($lugar['category_name'])): ?>
        <div class="lug-hero-badge"><?php echo htmlspecialchars($lugar['category_name'], ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <!-- H1 — palabra clave principal -->
        <h1><?php echo htmlspecialchars($lugar['name'], ENT_QUOTES, 'UTF-8'); ?></h1>

        <!-- Meta: ubicación, duración, época y precio entrada -->
        <div class="lug-hero-meta">

            <?php if (!empty($ubicacion)): ?>
            <span>📍 <?php echo htmlspecialchars($ubicacion, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>

            <?php if (!empty($lugar['visit_duration'])): ?>
            <span>⏱️ <?php echo htmlspecialchars($lugar['visit_duration'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>

            <?php if (!empty($lugar['best_season'])): ?>
            <span>🌸 <?php echo htmlspecialchars($lugar['best_season'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>

            <?php if ($esGratis && empty($lugar['entry_fee_details']) && !$esGastronomico): ?>
            <span class="lug-hero-free"><?php echo htmlspecialchars($t['entrada_gratuita'], ENT_QUOTES, 'UTF-8'); ?></span>

            <?php elseif (!empty($lugar['entry_fee'])): ?>
            <span class="lug-hero-free" style="background:var(--lug-warm);color:#1a1a1a;">
                💶 <?php echo htmlspecialchars($lugar['entry_fee'], ENT_QUOTES, 'UTF-8'); ?>€<?php
                if (!empty($lugar['entry_fee_details'])): ?> · <?php echo htmlspecialchars($lugar['entry_fee_details'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
            </span>

            <?php elseif (!empty($lugar['entry_fee_details'])): ?>
            <span class="lug-hero-free" style="background:var(--lug-warm);color:#1a1a1a;">
                💶 <?php echo htmlspecialchars($lugar['entry_fee_details'], ENT_QUOTES, 'UTF-8'); ?>
            </span>
            <?php endif; ?>

        </div><!-- /.lug-hero-meta -->

    </div><!-- /.lug-hero-content -->

</section><!-- /.lug-hero -->
