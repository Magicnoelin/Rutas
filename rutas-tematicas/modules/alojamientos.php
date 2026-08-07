<?php
/**
 * Módulo: Alojamientos de la ruta
 * Cards con foto, precio, localidad y CTA de reserva
 */

function renderAlojamientos(array $alojamientos, array $ruta): void
{
    if (empty($alojamientos)) return;
    $provincia = htmlspecialchars($ruta['province'] ?? 'Soria');
?>
<section class="rt-section rt-section--alt" id="alojamientos">
    <div class="rt-container">
        <div class="rt-section__header">
            <h2 class="rt-section__title">
                <span class="rt-section__icon" aria-hidden="true">🏠</span>
                Dónde dormir en <?= $provincia ?>
            </h2>
            <p class="rt-section__subtitle">
                Casas rurales y apartamentos seleccionados para esta escapada. Reserva cuanto antes, el puente se llena.
            </p>
        </div>

        <div class="rt-grid rt-grid--3">
            <?php foreach ($alojamientos as $a):
                $nombre    = htmlspecialchars($a['name'] ?? '');
                $localidad = htmlspecialchars($a['municipality'] ?? '');
                $prov      = htmlspecialchars($a['province'] ?? $provincia);
                $desc      = htmlspecialchars(substr(strip_tags($a['short_description'] ?? $a['description'] ?? ''), 0, 140));
                $precio    = $a['price_per_night'] ?? null;
                $capacidad = (int)($a['capacity'] ?? 0);
                $telefono  = $a['phone'] ?? null;
                $web       = $a['website'] ?? null;
                $url       = $a['url'] ?? '#';
                $foto      = $a['fotos'][0] ?? null;
                $highlight = !empty($a['is_highlight']);
                $nota      = htmlspecialchars($a['editorial_note'] ?? '');
                $categoria = htmlspecialchars($a['category_name'] ?? 'Casa Rural');
            ?>
            <article class="rt-card <?= $highlight ? 'rt-card--highlight' : '' ?>">
                <?php if ($highlight): ?>
                <div class="rt-card__badge">⭐ Recomendado</div>
                <?php endif; ?>

                <!-- Imagen -->
                <div class="rt-card__img-wrap">
                    <?php if ($foto): ?>
                    <img
                        src="<?= htmlspecialchars($foto) ?>"
                        alt="<?= $nombre ?> — Casa rural en <?= $localidad ?>, <?= $prov ?>"
                        width="400" height="240"
                        loading="lazy"
                        decoding="async"
                        class="rt-card__img"
                        onerror="this.src='https://rutasrurales.io/menu_images/Logo%20transparente.webp'"
                    >
                    <?php else: ?>
                    <div class="rt-card__img-placeholder" aria-hidden="true">🏠</div>
                    <?php endif; ?>
                    <span class="rt-card__tipo"><?= $categoria ?></span>
                </div>

                <!-- Contenido -->
                <div class="rt-card__body">
                    <div class="rt-card__location">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?= $localidad ?>, <?= $prov ?>
                    </div>
                    <h3 class="rt-card__title">
                        <a href="<?= htmlspecialchars($url) ?>"><?= $nombre ?></a>
                    </h3>

                    <?php if ($nota): ?>
                    <p class="rt-card__editorial"><?= $nota ?></p>
                    <?php elseif ($desc): ?>
                    <p class="rt-card__desc"><?= $desc ?>…</p>
                    <?php endif; ?>

                    <!-- Features -->
                    <div class="rt-card__features">
                        <?php if ($capacidad > 0): ?>
                        <span class="rt-feature">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <?= $capacidad ?> plazas
                        </span>
                        <?php endif; ?>
                        <?php if ($precio): ?>
                        <span class="rt-feature rt-feature--precio">
                            Desde <strong><?= number_format((float)$precio, 0) ?>€/noche</strong>
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- CTAs -->
                    <div class="rt-card__ctas">
                        <a href="<?= htmlspecialchars($url) ?>" class="rt-btn rt-btn--card-primary">
                            Ver disponibilidad
                        </a>
                        <?php if ($telefono): ?>
                        <a href="tel:<?= preg_replace('/\s+/', '', $telefono) ?>" class="rt-btn rt-btn--card-tel" aria-label="Llamar a <?= $nombre ?>">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            Llamar
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <!-- CTA ver todos -->
        <div class="rt-section__cta">
            <a href="https://rutasrurales.io/alojamientos/turismo-rural-<?= strtolower(str_replace([' ','á','é','í','ó','ú','ñ'],['-','a','e','i','o','u','n'],$provincia)) ?>" class="rt-btn rt-btn--outline">
                Ver todos los alojamientos en <?= $provincia ?>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </div>
</section>
<?php
}
