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

    <!-- ── CTA TURÍSTICO ── -->
    <?php
    $lugName  = htmlspecialchars($lugar['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $lugProv  = htmlspecialchars($lugar['province'] ?? '', ENT_QUOTES, 'UTF-8');
    // Textos multiidioma del CTA de turista
    $ctaLang = $lang ?? 'es';
    $ctaTxt = [
        'es' => [
            'titulo'   => '🏕️ ¿Quieres visitar este lugar?',
            'subtitulo'=> 'Dinos tus fechas y cuántos sois — te buscamos alojamiento cerca',
            'lbl_lleg' => 'Llegada',
            'lbl_sal'  => 'Salida',
            'lbl_per'  => 'Personas',
            'btn'      => '🔍 Ver alojamientos cerca',
            'oferta'   => '¿Tienes cuenta? Guárdalo en favoritos',
            'ya_cuenta'=> 'Acceder →',
            'register' => '✨ Registrarme gratis',
        ],
        'en' => [
            'titulo'   => '🏕️ Want to visit this place?',
            'subtitulo'=> 'Tell us your dates and group size — we find accommodation nearby',
            'lbl_lleg' => 'Check-in',
            'lbl_sal'  => 'Check-out',
            'lbl_per'  => 'Guests',
            'btn'      => '🔍 See nearby stays',
            'oferta'   => 'Have an account? Save to favourites',
            'ya_cuenta'=> 'Log in →',
            'register' => '✨ Sign up free',
        ],
        'fr' => [
            'titulo'   => '🏕️ Vous voulez visiter ce lieu ?',
            'subtitulo'=> 'Indiquez vos dates et le nombre de voyageurs — on trouve un hébergement près',
            'lbl_lleg' => 'Arrivée',
            'lbl_sal'  => 'Départ',
            'lbl_per'  => 'Voyageurs',
            'btn'      => '🔍 Voir les hébergements proches',
            'oferta'   => 'Vous avez un compte ? Sauvegardez-le',
            'ya_cuenta'=> 'Se connecter →',
            'register' => '✨ Inscription gratuite',
        ],
        'de' => [
            'titulo'   => '🏕️ Möchten Sie diesen Ort besuchen?',
            'subtitulo'=> 'Nennen Sie uns Ihre Daten und Personenzahl — wir finden eine Unterkunft',
            'lbl_lleg' => 'Anreise',
            'lbl_sal'  => 'Abreise',
            'lbl_per'  => 'Personen',
            'btn'      => '🔍 Unterkünfte in der Nähe',
            'oferta'   => 'Haben Sie ein Konto? Speichern',
            'ya_cuenta'=> 'Anmelden →',
            'register' => '✨ Kostenlos registrieren',
        ],
        'zh' => [
            'titulo'   => '🏕️ 想参观此地？',
            'subtitulo'=> '告诉我们您的日期和人数——我们为您找附近住宿',
            'lbl_lleg' => '入住',
            'lbl_sal'  => '退房',
            'lbl_per'  => '人数',
            'btn'      => '🔍 查看附近住宿',
            'oferta'   => '已有账户？收藏此地',
            'ya_cuenta'=> '登录 →',
            'register' => '✨ 免费注册',
        ],
    ];
    $c = $ctaTxt[$ctaLang] ?? $ctaTxt['es'];
    $langPfx = ($ctaLang !== 'es') ? '/' . $ctaLang : '';
    ?>
    <div class="lug-cta-turista" id="lug-cta-sidebar">
        <div class="lug-cta-icon" aria-hidden="true">🗺️</div>
        <h3 class="lug-cta-titulo"><?php echo $c['titulo']; ?></h3>
        <p class="lug-cta-sub"><?php echo $c['subtitulo']; ?></p>

        <!-- Mini formulario de búsqueda de alojamiento -->
        <form class="lug-cta-form" id="lug-cta-form-sidebar"
              onsubmit="lugBuscarAloj(event,'sidebar')" novalidate>
            <div class="lug-cta-fields">
                <div class="lug-cta-field">
                    <label><?php echo $c['lbl_lleg']; ?></label>
                    <input type="date" id="lug-llegada-sb" name="llegada"
                           min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="lug-cta-field">
                    <label><?php echo $c['lbl_sal']; ?></label>
                    <input type="date" id="lug-salida-sb" name="salida"
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                </div>
                <div class="lug-cta-field lug-cta-field--per">
                    <label><?php echo $c['lbl_per']; ?></label>
                    <select id="lug-personas-sb" name="personas">
                        <?php for ($i=1; $i<=8; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                        <option value="9">9+</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="lug-cta-btn-main">
                <?php echo $c['btn']; ?>
            </button>
        </form>

        <!-- Separador -->
        <div class="lug-cta-divider">
            <span><?php echo htmlspecialchars($c['oferta'], ENT_QUOTES, 'UTF-8'); ?></span>
        </div>

        <!-- Botones registro / login -->
        <div class="lug-cta-btns-row">
            <a href="<?php echo $langPfx; ?>/register.html?ref=lugar&amp;slug=<?php echo urlencode($slug ?? ''); ?>"
               class="lug-cta-btn-reg">
                <?php echo $c['register']; ?>
            </a>
            <a href="<?php echo $langPfx; ?>/login.html?ref=lugar&amp;slug=<?php echo urlencode($slug ?? ''); ?>"
               class="lug-cta-btn-login">
                <?php echo $c['ya_cuenta']; ?>
            </a>
        </div>
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
