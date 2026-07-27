<?php
/**
 * sidebar.php — Columna lateral de la ficha de lugar de interés
 * Variables requeridas: $lugar, $t, $slug
 */
if (empty($lugar)) return;

if (!function_exists('esLugarGastronomico')) {
    function esLugarGastronomico(string $categoryName): bool {
        if (empty($categoryName)) return false;
        $lower = mb_strtolower($categoryName, 'UTF-8');
        foreach (['restauran','gastronom','enotur','bodega','cafeter','restauraci','taberna','hosteleria','hostelería'] as $kw) {
            if (strpos($lower, $kw) !== false) return true;
        }
        return false;
    }
}

$esGastronomico = esLugarGastronomico($lugar['category_name'] ?? '');

// Acceso seguro a claves de $t con fallback
$_t = [
    'en_un_vistazo'    => isset($t['en_un_vistazo'])    ? $t['en_un_vistazo']    : '📌 En un vistazo',
    'entrada_gratuita' => isset($t['entrada_gratuita']) ? $t['entrada_gratuita'] : '✅ Entrada gratuita',
    'duracion_visita'  => isset($t['duracion_visita'])  ? $t['duracion_visita']  : 'Duración visita',
    'mejor_epoca'      => isset($t['mejor_epoca'])      ? $t['mejor_epoca']      : 'Mejor época',
    'mascotas'         => isset($t['mascotas'])         ? $t['mascotas']         : 'Mascotas',
    'admite_mascotas'  => isset($t['admite_mascotas'])  ? $t['admite_mascotas']  : 'Admitidas',
    'apto_ninos'       => isset($t['apto_ninos'])       ? $t['apto_ninos']       : 'Apto para niños',
    'web_oficial'      => isset($t['web_oficial'])      ? $t['web_oficial']      : '🌐 Web oficial',
    'como_llegar'      => isset($t['como_llegar'])      ? $t['como_llegar']      : '🗺️ Cómo llegar (Google Maps)',
    'te_gusta'         => isset($t['te_gusta'])         ? $t['te_gusta']         : '¿Te gusta este lugar?',
    'cta_desc'         => isset($t['cta_desc'])         ? $t['cta_desc']         : 'Guárdalo en favoritos y recibe alertas de eventos y actividades cercanas',
    'registrarme'      => isset($t['registrarme'])      ? $t['registrarme']      : '✨ Registrarme gratis',
    'ya_cuenta'        => isset($t['ya_cuenta'])        ? $t['ya_cuenta']        : 'Ya tengo cuenta',
    'compartir'        => isset($t['compartir'])        ? $t['compartir']        : 'Compartir este lugar',
];
?>

<aside class="lug-sidebar" aria-label="Información rápida">

    <!-- ── Tarjeta: información rápida ── -->
    <div class="info-card">
        <div class="info-card-title"><?php echo htmlspecialchars($_t['en_un_vistazo'], ENT_QUOTES, 'UTF-8'); ?></div>
        <ul class="info-list">

            <?php if (!empty($lugar['category_name'])): ?>
            <li>
                <span class="li-icon" aria-hidden="true">🏷️</span>
                <span><?php echo htmlspecialchars($lugar['category_name'], ENT_QUOTES, 'UTF-8'); ?></span>
            </li>
            <?php endif; ?>

            <?php if (!empty($lugar['municipality'])): ?>
            <li>
                <span class="li-icon" aria-hidden="true">📍</span>
                <span>
                    <a href="/lugares-de-interes?provincia=<?php echo urlencode($lugar['province'] ?? ''); ?>">
                        <?php echo htmlspecialchars($lugar['municipality'], ENT_QUOTES, 'UTF-8'); ?>
                        <?php if (!empty($lugar['province'])): ?>, <?php echo htmlspecialchars($lugar['province'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                    </a>
                </span>
            </li>
            <?php endif; ?>

            <?php if (!empty($lugar['entry_fee']) || !empty($lugar['entry_fee_details']) || (!$esGastronomico && isset($lugar['entry_fee']))): ?>
            <li>
                <span class="li-icon" aria-hidden="true">🎫</span>
                <span>
                    <?php if (!empty($lugar['entry_fee'])): ?>
                        💶 <?php echo htmlspecialchars($lugar['entry_fee'], ENT_QUOTES, 'UTF-8'); ?>€
                    <?php elseif (!empty($lugar['entry_fee_details'])): ?>
                        💶 <?php echo htmlspecialchars($lugar['entry_fee_details'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php else: ?>
                        <?php echo htmlspecialchars($_t['entrada_gratuita'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>
                    <?php if (!empty($lugar['entry_fee']) && !empty($lugar['entry_fee_details'])): ?>
                        <br><small style="color:var(--lug-text-l);font-weight:400;"><?php echo htmlspecialchars($lugar['entry_fee_details'], ENT_QUOTES, 'UTF-8'); ?></small>
                    <?php endif; ?>
                </span>
            </li>
            <?php endif; ?>

            <?php if (!empty($lugar['opening_hours'])): ?>
            <li>
                <span class="li-icon" aria-hidden="true">🕐</span>
                <span><?php echo htmlspecialchars($lugar['opening_hours'], ENT_QUOTES, 'UTF-8'); ?></span>
            </li>
            <?php endif; ?>

            <?php if (!empty($lugar['visit_duration'])): ?>
            <li>
                <span class="li-icon" aria-hidden="true">⏱️</span>
                <span><?php echo htmlspecialchars($_t['duracion_visita'], ENT_QUOTES, 'UTF-8'); ?>: <?php echo htmlspecialchars($lugar['visit_duration'], ENT_QUOTES, 'UTF-8'); ?></span>
            </li>
            <?php endif; ?>

            <?php if (!empty($lugar['best_season'])): ?>
            <li>
                <span class="li-icon" aria-hidden="true">🌸</span>
                <span><?php echo htmlspecialchars($_t['mejor_epoca'], ENT_QUOTES, 'UTF-8'); ?>: <?php echo htmlspecialchars($lugar['best_season'], ENT_QUOTES, 'UTF-8'); ?></span>
            </li>
            <?php endif; ?>

            <?php if (!empty($lugar['pet_friendly'])): ?>
            <li>
                <span class="li-icon" aria-hidden="true">🐾</span>
                <span><?php echo htmlspecialchars($_t['mascotas'], ENT_QUOTES, 'UTF-8'); ?>: <?php echo htmlspecialchars($_t['admite_mascotas'], ENT_QUOTES, 'UTF-8'); ?></span>
            </li>
            <?php endif; ?>

            <?php if (!empty($lugar['suitable_for_children'])): ?>
            <li>
                <span class="li-icon" aria-hidden="true">👶</span>
                <span><?php echo htmlspecialchars($_t['apto_ninos'], ENT_QUOTES, 'UTF-8'); ?></span>
            </li>
            <?php endif; ?>

            <?php if (!empty($lugar['phone'])): ?>
            <li>
                <span class="li-icon" aria-hidden="true">📞</span>
                <a href="tel:<?php echo htmlspecialchars($lugar['phone'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($lugar['phone'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </li>
            <?php endif; ?>

            <?php if (!empty($lugar['website'])): ?>
            <li>
                <span class="li-icon" aria-hidden="true">🌐</span>
                <a href="<?php echo htmlspecialchars($lugar['website'], ENT_QUOTES, 'UTF-8'); ?>"
                   target="_blank" rel="noopener noreferrer">
                    <?php echo htmlspecialchars($_t['web_oficial'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </li>
            <?php endif; ?>

        </ul>

        <?php if (!empty($lugar['latitude']) && !empty($lugar['longitude'])): ?>
        <a href="https://www.google.com/maps?q=<?php echo htmlspecialchars($lugar['latitude'], ENT_QUOTES, 'UTF-8'); ?>,<?php echo htmlspecialchars($lugar['longitude'], ENT_QUOTES, 'UTF-8'); ?>"
           target="_blank" rel="noopener noreferrer"
           style="display:flex;align-items:center;justify-content:center;gap:8px;background:#2F5233;color:#fff;padding:10px 16px;border-radius:8px;font-weight:700;font-size:0.88rem;text-decoration:none;margin-top:16px;width:100%;">
            <?php echo htmlspecialchars($_t['como_llegar'], ENT_QUOTES, 'UTF-8'); ?>
        </a>
        <?php endif; ?>

    </div><!-- /.info-card -->

    <!-- ── CTA ── -->
    <div class="cta-card">
        <div style="font-size:1.8rem;margin-bottom:8px;line-height:1;" aria-hidden="true">🌿</div>
        <h3><?php echo htmlspecialchars($_t['te_gusta'], ENT_QUOTES, 'UTF-8'); ?></h3>
        <p><?php echo htmlspecialchars($_t['cta_desc'], ENT_QUOTES, 'UTF-8'); ?></p>
        <a href="/login.html?action=register&amp;ref=lugar&amp;slug=<?php echo urlencode($slug ?? ''); ?>" class="btn-cta-primary">
            <?php echo htmlspecialchars($_t['registrarme'], ENT_QUOTES, 'UTF-8'); ?>
        </a>
        <a href="/login.html?ref=lugar&amp;slug=<?php echo urlencode($slug ?? ''); ?>" class="btn-cta-secondary">
            <?php echo htmlspecialchars($_t['ya_cuenta'], ENT_QUOTES, 'UTF-8'); ?>
        </a>
    </div>

    <!-- ── Compartir ── -->
    <div class="share-card">
        <p class="share-label"><?php echo htmlspecialchars($_t['compartir'], ENT_QUOTES, 'UTF-8'); ?></p>
        <div class="share-btns">
            <button onclick="shareLug('whatsapp')" title="WhatsApp" aria-label="Compartir por WhatsApp">💬</button>
            <button onclick="shareLug('facebook')" title="Facebook" aria-label="Compartir en Facebook">📘</button>
            <button onclick="shareLug('twitter')"  title="X / Twitter" aria-label="Compartir en X">🐦</button>
            <button onclick="shareLug('copy')"     title="Copiar enlace" aria-label="Copiar enlace">🔗</button>
        </div>
    </div>

</aside>
