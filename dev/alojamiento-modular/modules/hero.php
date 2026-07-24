<?php
/**
 * MODULE: hero.php — REVISADO (sin cambios de interlinking necesarios)
 *
 * Este módulo ya usaba <a href> correctamente en la versión original.
 * Se mantiene limpio y se actualiza el breadcrumb para usar las URLs
 * canónicas actuales del proyecto (sin .html en alojamientos).
 */
if (isset($alojamiento) && $alojamiento && isset($t) && isset($tipo_display) && isset($capacidad_display) && isset($precio_display)):
?>
<section class="alojamiento-hero" id="alo-hero">
    <div class="hero-content">
        <!-- Breadcrumb: todos <a href> nativos ✅ -->
        <nav class="hero-breadcrumb" aria-label="breadcrumb">
            <a href="/">🏠 Inicio</a>
            <span>/</span>
            <a href="/alojamientos-turisticos"><?php echo isset($t['alojamientos']) ? $t['alojamientos'] : 'Alojamientos'; ?></a>
            <span>/</span>
            <span aria-current="page"><?php echo htmlspecialchars($alojamiento['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
        </nav>

        <div class="hero-badge"><?php echo htmlspecialchars($tipo_display); ?></div>
        <h1 class="hero-title"><?php echo htmlspecialchars($alojamiento['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>

        <div class="hero-meta">
            <?php if (!empty($alojamiento['municipality']) || !empty($alojamiento['province'])): ?>
            <span class="hero-location">
                📍
                <?php echo htmlspecialchars($alojamiento['municipality'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                <?php echo !empty($alojamiento['province']) ? ', ' . htmlspecialchars($alojamiento['province'], ENT_QUOTES, 'UTF-8') : ''; ?>
            </span>
            <?php endif; ?>

            <?php if (!empty($capacidad_display)): ?>
            <span class="hero-capacity">👥 <?php echo htmlspecialchars($capacidad_display); ?></span>
            <?php endif; ?>

            <?php if (!empty($precio_display)): ?>
            <span class="hero-price">
                <?php echo isset($t['precio_desde']) ? $t['precio_desde'] : 'Desde'; ?>
                <?php echo htmlspecialchars($precio_display); ?>
                / <?php echo isset($t['noche']) ? $t['noche'] : 'noche'; ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>
