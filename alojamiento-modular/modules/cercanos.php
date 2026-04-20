<div class="alojamiento-nearby">
    <h2 class="section-title"><i class="fas fa-compass"></i> <?php echo $t['cercanos']; ?></h2>
    
    <div class="nearby-sections">
        <div class="nearby-section">
            <h3><?php echo $t['alojamientos_cercanos']; ?></h3>
            <div id="nearby-accommodations" class="nearby-grid">
                <div class="loading-placeholder">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Cargando alojamientos cercanos...</p>
                </div>
            </div>
        </div>
        
        <div class="nearby-section">
            <h3><?php echo $t['lugares_cercanos']; ?></h3>
            <div id="nearby-places" class="nearby-grid">
                <div class="loading-placeholder">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Cargando lugares de interés...</p>
                </div>
            </div>
        </div>
        
        <div class="nearby-section">
            <h3><?php echo $t['eventos_cercanos']; ?></h3>
            <div id="nearby-events" class="nearby-grid">
                <div class="loading-placeholder">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Cargando eventos culturales...</p>
                </div>
            </div>
        </div>
        
        <div class="nearby-section">
            <h3><?php echo $t['actividades_cercanas']; ?></h3>
            <div id="nearby-activities" class="nearby-grid">
                <div class="loading-placeholder">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Cargando actividades turísticas...</p>
                </div>
            </div>
        </div>
    </div>
</div>