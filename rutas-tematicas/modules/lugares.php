<?php
/**
 * Módulo: Lugares de interés de la ruta
 */

function renderLugares(array $lugares, array $ruta): void
{
    if (empty($lugares)) return;
    $provincia = htmlspecialchars($ruta['province'] ?? 'Soria');
?>
<section class="rt-section" id="lugares">
    <div class="rt-container">
        <div class="rt-section__header">
            <h2 class="rt-section__title">
                <span class="rt-section__icon" aria-hidden="true">🏛️</span>
                Qué ver en <?= $provincia ?>
            </h2>
            <p class="rt-section__subtitle">
                Los lugares imprescindibles que no puedes perderte en esta escapada
            </p>
        </div>

        <div class="rt-grid rt-grid--2">
            <?php foreach ($lugares as $l):
                $nombre    = htmlspecialchars($l['name'] ?? '');
                $localidad = htmlspecialchars($l['municipality'] ?? '');
                $prov      = htmlspecialchars($l['province'] ?? $provincia);
                $nota      = htmlspecialchars($l['editorial_note'] ?? '');
                $desc      = $nota ?: htmlspecialchars(substr(strip_tags($l['short_description'] ?? $l['description'] ?? ''), 0, 160));
                $precio    = htmlspecialchars($l['precio_entrada'] ?? 'Entrada gratuita');
                $horario   = htmlspecialchars($l['opening_hours'] ?? '');
                $url       = $l['url'] ?? '#';
                $foto      = $l['fotos'][0] ?? null;
                $highlight = !empty($l['is_highlight']);
                $timeSlot  = $l['time_slot'] ?? null;
                $slotLabel = [
                    'mañana'      => '🌅 Mejor por la mañana',
                    'tarde'       => '🌇 Mejor por la tarde',
                    'noche'       => '🌙 Visita nocturna',
                    'todo-el-dia' => '📅 Todo el día',
                ][$timeSlot] ?? null;
            ?>
            <article class="rt-lugar <?= $highlight ? 'rt-lugar--highlight' : '' ?>">
                <?php if ($foto): ?>
                <div class="rt-lugar__img-wrap">
                    <img
                        src="<?= htmlspecialchars($foto) ?>"
                        alt="<?= $nombre ?> — <?= $localidad ?>, <?= $prov ?>"
                        width="560" height="300"
                        loading="lazy"
                        decoding="async"
                        class="rt-lugar__img"
                        onerror="this.closest('.rt-lugar__img-wrap').style.display='none'"
                    >
                    <?php if ($highlight): ?>
                    <span class="rt-lugar__badge">⭐ Imprescindible</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="rt-lugar__body">
                    <div class="rt-lugar__meta">
                        <span class="rt-lugar__location">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <?= $localidad ?>, <?= $prov ?>
                        </span>
                        <?php if ($slotLabel): ?>
                        <span class="rt-lugar__slot"><?= $slotLabel ?></span>
                        <?php endif; ?>
                    </div>

                    <h3 class="rt-lugar__title">
                        <a href="<?= htmlspecialchars($url) ?>"><?= $nombre ?></a>
                    </h3>

                    <p class="rt-lugar__desc"><?= $desc ?>…</p>

                    <div class="rt-lugar__info">
                        <span class="rt-info-item">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            <?= $precio ?>
                        </span>
                        <?php if ($horario): ?>
                        <span class="rt-info-item">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <?= $horario ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <a href="<?= htmlspecialchars($url) ?>" class="rt-lugar__link">
                        Más información
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <div class="rt-section__cta">
            <a href="https://rutasrurales.io/lugares-interes-paginacion.html?provincia=<?= urlencode($provincia) ?>" class="rt-btn rt-btn--outline">
                Ver todos los lugares en <?= $provincia ?>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </div>
</section>
<?php
}
