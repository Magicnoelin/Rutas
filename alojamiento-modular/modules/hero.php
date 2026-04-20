<div class="alojamiento-hero">
    <div class="hero-breadcrumb">
        <nav class="breadcrumb-nav">
            <a href="/index.html"><?php echo $t['alojamiento']; ?></a> / 
            <a href="/alojamientos-turisticos.html"><?php echo $t['alojamiento']; ?>s</a> / 
            <span><?php echo htmlspecialchars($alojamiento['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
        </nav>
    </div>
    
    <div class="hero-content">
        <div class="hero-badge"><?php echo $tipo_display; ?></div>
        <h1 class="hero-title"><?php echo htmlspecialchars($alojamiento['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>
        
        <div class="hero-meta">
            <?php if (!empty($alojamiento['municipality']) || !empty($alojamiento['province'])): ?>
            <span class="hero-location">
                <i class="fas fa-map-marker-alt"></i>
                <?php echo htmlspecialchars($alojamiento['municipality'] ?? '', ENT_QUOTES, 'UTF-8'); ?><?php echo !empty($alojamiento['province']) ? ', ' . htmlspecialchars($alojamiento['province'], ENT_QUOTES, 'UTF-8') : ''; ?>
            </span>
            <?php endif; ?>
            
            <?php if (!empty($capacidad_display)): ?>
            <span class="hero-capacity">
                <i class="fas fa-users"></i>
                <?php echo $capacidad_display; ?>
            </span>
            <?php endif; ?>
            
            <?php if (!empty($precio_display)): ?>
            <span class="hero-price">
                <i class="fas fa-euro-sign"></i>
                <?php echo $precio_display; ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
</div>