<?php
/**
 * MODULE: cta.php — REFACTORIZADO (SEO interlinking)
 *
 * CAMBIOS vs versión legacy:
 * - Los dos botones del CTA ya eran <a href> en la versión legacy, pero apuntaban
 *   a /register.html y /login.html sin parámetros de contexto.
 * - Ahora incluyen parámetros ?ref=alojamiento&slug=... para que al registrarse
 *   o iniciar sesión el sistema sepa de qué alojamiento viene el usuario.
 * - Clases CSS btn-white y btn-outline-white ya están definidas en index.php.
 * - NO hay onclick, NO hay JS, 100% HTML nativo.
 */
if (isset($alojamiento) && $alojamiento && isset($t) && isset($slug)):
?>
<div class="alojamiento-cta">
    <div class="cta-card">
        <div style="font-size:1.8rem;margin-bottom:8px;line-height:1;">🌿</div>
        <h3><?php echo isset($t['cta_titulo']) ? htmlspecialchars($t['cta_titulo']) : '¿Te gusta este alojamiento?'; ?></h3>
        <p><?php echo isset($t['cta_desc']) ? htmlspecialchars($t['cta_desc']) : 'Regístrate gratis para guardarlo en tus favoritos y recibir ofertas similares'; ?></p>

        <div class="cta-buttons">
            <!-- ✅ <a href> nativo — Google lo sigue, accesible con teclado -->
            <a href="/login.html?action=register&ref=alojamiento&slug=<?php echo urlencode($slug); ?>"
               class="btn-white">
                <?php echo isset($t['cta_register']) ? $t['cta_register'] : '✨ Registrarme gratis'; ?>
            </a>
            <!-- ✅ <a href> nativo -->
            <a href="/login.html?ref=alojamiento&slug=<?php echo urlencode($slug); ?>"
               class="btn-outline-white">
                <?php echo isset($t['cta_login']) ? $t['cta_login'] : 'Ya tengo cuenta'; ?>
            </a>
        </div>
    </div>
</div><!-- /.alojamiento-cta -->
<?php endif; ?>
