<?php
/**
 * footer.php — Footer, lightbox, toasts y scripts
 * Variables requeridas: $lugar, $t
 */
?>

<!-- ══════════════════════════════════════════════════════
     CTA MÓVIL TURISTA — Barra fija inferior (solo móvil)
     ══════════════════════════════════════════════════════ -->
<?php if (!empty($lugar)): ?>
<?php
// Reutilizamos las traducciones del CTA turista definidas en sidebar.php
// (que ya se ejecutó antes en el ciclo include del index.php)
// Para evitar redefinir variables PHP, las generamos de nuevo de forma compacta
$_cta_mob_lang = $lang ?? 'es';
$_mob_txt = [
    'es' => ['bar'=>'🏕️ ¿Buscas alojamiento cerca?','btn'=>'Ver fechas y precios','title'=>'Buscar alojamiento cerca','lbl_lleg'=>'Llegada','lbl_sal'=>'Salida','lbl_per'=>'Personas','btn2'=>'🔍 Ver alojamientos','register'=>'✨ Registrarme gratis','login'=>'Ya tengo cuenta →'],
    'en' => ['bar'=>'🏕️ Looking for accommodation nearby?','btn'=>'See dates & prices','title'=>'Find accommodation nearby','lbl_lleg'=>'Check-in','lbl_sal'=>'Check-out','lbl_per'=>'Guests','btn2'=>'🔍 See nearby stays','register'=>'✨ Sign up free','login'=>'I have an account →'],
    'fr' => ['bar'=>'🏕️ Cherchez un hébergement proche ?','btn'=>'Voir les disponibilités','title'=>'Hébergements à proximité','lbl_lleg'=>'Arrivée','lbl_sal'=>'Départ','lbl_per'=>'Voyageurs','btn2'=>'🔍 Voir hébergements','register'=>'✨ Inscription gratuite','login'=>'J\'ai un compte →'],
    'de' => ['bar'=>'🏕️ Unterkunft in der Nähe gesucht?','btn'=>'Verfügbarkeit prüfen','title'=>'Unterkünfte in der Nähe','lbl_lleg'=>'Anreise','lbl_sal'=>'Abreise','lbl_per'=>'Personen','btn2'=>'🔍 Unterkünfte anzeigen','register'=>'✨ Kostenlos registrieren','login'=>'Ich habe ein Konto →'],
    'zh' => ['bar'=>'🏕️ 需要附近住宿？','btn'=>'查看日期与价格','title'=>'查找附近住宿','lbl_lleg'=>'入住','lbl_sal'=>'退房','lbl_per'=>'人数','btn2'=>'🔍 查看住宿','register'=>'✨ 免费注册','login'=>'我已有账户 →'],
];
$_cm = $_mob_txt[$_cta_mob_lang] ?? $_mob_txt['es'];
$_mob_slug = $slug ?? '';
$_mob_prov = $lugar['province'] ?? '';
$_mob_lpfx = ($_cta_mob_lang !== 'es') ? '/' . $_cta_mob_lang : '';
?>

<!-- Barra inferior fija -->
<div id="lug-mob-bar" role="complementary" aria-label="Buscar alojamiento">
    <div class="lmb-text">
        <strong><?php echo $_cm['bar']; ?></strong>
        <?php if ($_mob_prov): ?><span><?php echo htmlspecialchars($_mob_prov, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
    </div>
    <button class="lmb-btn"
            onclick="document.getElementById('lug-mob-overlay').classList.add('open');document.body.style.overflow='hidden';"
            type="button">
        <?php echo $_cm['btn']; ?>
    </button>
    <button class="lmb-close" onclick="document.getElementById('lug-mob-bar').style.display='none';" aria-label="Cerrar" type="button">✕</button>
</div>

<!-- Bottom sheet con el formulario completo -->
<div id="lug-mob-overlay" role="dialog" aria-modal="true" aria-label="Buscar alojamiento"
     onclick="if(event.target===this){this.classList.remove('open');document.body.style.overflow='';}">
    <div class="lmo-box">
        <div class="lmo-header">
            <span class="lmo-title"><?php echo $_cm['title']; ?></span>
            <button class="lmo-close" type="button"
                    onclick="document.getElementById('lug-mob-overlay').classList.remove('open');document.body.style.overflow='';"
                    aria-label="Cerrar">✕</button>
        </div>
        <form class="lug-cta-form" onsubmit="lugBuscarAloj(event,'mobile')" novalidate style="margin-bottom:12px;">
            <div class="lug-cta-fields">
                <div class="lug-cta-field">
                    <label><?php echo $_cm['lbl_lleg']; ?></label>
                    <input type="date" id="lug-llegada-mob" name="llegada"
                           min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="lug-cta-field">
                    <label><?php echo $_cm['lbl_sal']; ?></label>
                    <input type="date" id="lug-salida-mob" name="salida"
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                </div>
                <div class="lug-cta-field lug-cta-field--per">
                    <label><?php echo $_cm['lbl_per']; ?></label>
                    <select id="lug-personas-mob" name="personas">
                        <?php for ($i=1;$i<=8;$i++): ?><option value="<?php echo $i; ?>"><?php echo $i; ?></option><?php endfor; ?>
                        <option value="9">9+</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="lug-cta-btn-main"><?php echo $_cm['btn2']; ?></button>
        </form>
        <div style="display:flex;gap:8px;margin-top:4px;">
            <a href="<?php echo $_mob_lpfx; ?>/register.html?ref=lugar&slug=<?php echo urlencode($_mob_slug); ?>"
               style="flex:1;text-align:center;background:rgba(255,255,255,0.15);border:1.5px solid rgba(255,255,255,0.4);color:#fff;padding:10px;border-radius:8px;font-size:0.82rem;font-weight:700;text-decoration:none;">
                <?php echo $_cm['register']; ?>
            </a>
            <a href="<?php echo $_mob_lpfx; ?>/login.html?ref=lugar&slug=<?php echo urlencode($_mob_slug); ?>"
               style="flex:1;text-align:center;color:rgba(255,255,255,0.7);padding:10px;border-radius:8px;font-size:0.82rem;font-weight:600;text-decoration:none;background:transparent;border:1.5px solid rgba(255,255,255,0.2);">
                <?php echo $_cm['login']; ?>
            </a>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- ══════════════════════════════════════════════════════
     LIGHTBOX — Overlay de galería de fotos
     ══════════════════════════════════════════════════════ -->
<div class="lbox-overlay" id="lightbox" onclick="closeLightboxOnOverlay(event)" role="dialog" aria-modal="true" aria-label="Galería de fotos">
    <button class="lbox-close" onclick="closeLightbox()" type="button" aria-label="Cerrar galería">✕</button>
    <button class="lbox-nav lbox-prev" onclick="lightboxNav(-1)" type="button" aria-label="Foto anterior">‹</button>
    <img class="lbox-img" id="lightbox-img" src="" alt="">
    <button class="lbox-nav lbox-next" onclick="lightboxNav(1)" type="button" aria-label="Foto siguiente">›</button>
    <div class="lbox-caption" id="lightbox-caption"></div>
</div>

<!-- ══════════════════════════════════════════════════════
     TOAST — Notificaciones emergentes
     ══════════════════════════════════════════════════════ -->
<div class="toast" id="toast" role="status" aria-live="polite"></div>

<!-- ══════════════════════════════════════════════════════
     LEAFLET CSS diferido — se inyecta sólo si hay mapa
     Technique: media="print" onload="this.media='all'"
     (no bloquea renderizado inicial)
     ══════════════════════════════════════════════════════ -->
<?php if (!empty($lugar['latitude']) && !empty($lugar['longitude'])): ?>
<link rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      media="print"
      onload="this.media='all'"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin="anonymous">
<noscript>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</noscript>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════
     JS PRINCIPAL — lugar.js (defer para no bloquear render)
     ══════════════════════════════════════════════════════ -->
<script src="/lugar-modular/js/lugar.js" defer></script>

<!-- GTM noscript (debe ir justo al abrir body; se incluye aquí como fallback) -->
<noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-XXXXXXX"
            height="1" width="1" style="display:none;visibility:hidden"
            title="Google Tag Manager"></iframe>
</noscript>

</body>
</html>
