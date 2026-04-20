<?php 
// Verificar que todas las variables necesarias existan
if (isset($alojamiento) && $alojamiento && isset($t)):
?>
<div class="alojamiento-cta">
    <div class="cta-card">
        <h3><?php echo isset($t['cta_titulo']) ? $t['cta_titulo'] : '¿Te gusta este alojamiento?'; ?></h3>
        <p><?php echo isset($t['cta_desc']) ? $t['cta_desc'] : 'Regístrate gratis para guardarlo en tus favoritos y recibir ofertas similares'; ?></p>
        
        <div class="cta-buttons">
            <a href="/register.html" class="btn-cta btn-register">
                <i class="fas fa-user-plus"></i>
                <?php echo isset($t['cta_register']) ? $t['cta_register'] : '✨ Registrarme gratis'; ?>
            </a>
            <a href="/login.html" class="btn-cta btn-login">
                <i class="fas fa-sign-in-alt"></i>
                <?php echo isset($t['cta_login']) ? $t['cta_login'] : 'Ya tengo cuenta'; ?>
            </a>
        </div>
    </div>
</div>
<?php endif; ?>
