<?php 
// Verificar que todas las variables necesarias existan
if (isset($alojamiento) && $alojamiento && isset($t) && isset($tipo_display) && isset($capacidad_display)):
?>
<div class="alojamiento-description">
    <h2 class="section-title"><i class="fas fa-align-left"></i> <?php echo isset($t['descripcion']) ? $t['descripcion'] : 'Descripción'; ?></h2>
    
    <?php if (!empty($alojamiento['description'])): ?>
    <div class="description-content">
        <?php echo nl2br(htmlspecialchars($alojamiento['description'], ENT_QUOTES, 'UTF-8')); ?>
    </div>
    <?php else: ?>
    <p class="no-description">No hay descripción disponible.</p>
    <?php endif; ?>
    
    <div class="features-grid">
        <?php if (!empty($tipo_display)): ?>
        <div class="feature-item">
            <i class="fas fa-home"></i>
            <div>
                <strong><?php echo isset($t['tipo']) ? $t['tipo'] : 'Tipo'; ?></strong>
                <p><?php echo $tipo_display; ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($capacidad_display)): ?>
        <div class="feature-item">
            <i class="fas fa-users"></i>
            <div>
                <strong><?php echo isset($t['capacidad']) ? $t['capacidad'] : 'Capacidad'; ?></strong>
                <p><?php echo $capacidad_display; ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($alojamiento['check_in_time'])): ?>
        <div class="feature-item">
            <i class="fas fa-sign-in-alt"></i>
            <div>
                <strong>Check-in</strong>
                <p><?php echo htmlspecialchars($alojamiento['check_in_time'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($alojamiento['check_out_time'])): ?>
        <div class="feature-item">
            <i class="fas fa-sign-out-alt"></i>
            <div>
                <strong>Check-out</strong>
                <p><?php echo htmlspecialchars($alojamiento['check_out_time'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($alojamiento['services'])): ?>
        <div class="feature-item">
            <i class="fas fa-concierge-bell"></i>
            <div>
                <strong><?php echo isset($t['servicios']) ? $t['servicios'] : 'Servicios'; ?></strong>
                <p><?php echo htmlspecialchars($alojamiento['services'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
