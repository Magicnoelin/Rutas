<?php
require_once 'api/config_updated.php';
initSecurity();

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: /alojamientos-turisticos.html');
    exit();
}

$alojamiento = null;
$tituloPagina = 'Alojamiento no encontrado';
$descripcion = 'El alojamiento que buscas no existe.';
$imagenDefecto = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&h=600&fit=crop';

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM accommodations WHERE slug = ? AND is_active = 1");
    $stmt->execute([$slug]);
    $alojamiento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($alojamiento) {
        $tituloPagina = $alojamiento['name'] . ' - ' . $alojamiento['municipality'];
        $descripcion = $alojamiento['description'] ?? 'Alojamiento turistico';
    }
} catch (PDOException $e) {
    error_log('Error: ' . $e->getMessage());
}

$fotos = [];
if ($alojamiento) {
    if (!empty($alojamiento['photos'])) {
        $arr = json_decode($alojamiento['photos'], true);
        if (is_array($arr)) $fotos = $arr;
    }
    if (empty($fotos)) {
        if (!empty($alojamiento['photo1'])) $fotos[] = $alojamiento['photo1'];
        if (!empty($alojamiento['photo2'])) $fotos[] = $alojamiento['photo2'];
        if (!empty($alojamiento['photo3'])) $fotos[] = $alojamiento['photo3'];
        if (!empty($alojamiento['photo4'])) $fotos[] = $alojamiento['photo4'];
    }
}
if (empty($fotos)) $fotos[] = $imagenDefecto;

$nombre = $alojamiento ? htmlspecialchars($alojamiento['name'], ENT_QUOTES, 'UTF-8') : 'No encontrado';
$municipio = $alojamiento ? htmlspecialchars($alojamiento['municipality'], ENT_QUOTES, 'UTF-8') : '';
$provincia = $alojamiento ? htmlspecialchars($alojamiento['province'], ENT_QUOTES, 'UTF-8') : '';
$capacidad = $alojamiento['capacity'] ?? 'N/A';
$tipo = $alojamiento ? htmlspecialchars($alojamiento['accommodation_type'] ?? 'Alojamiento', ENT_QUOTES, 'UTF-8') : '';
$precio = !empty($alojamiento['price_per_night']) ? number_format($alojamiento['price_per_night'], 0, ',', '.') . ' EUR/noche' : 'Consultar';
$telefono = $alojamiento['phone'] ?? '';
$email = $alojamiento['email'] ?? '';
$direccion = $alojamiento ? htmlspecialchars($alojamiento['address'] ?? '', ENT_QUOTES, 'UTF-8') : '';
$descripcionTexto = $alojamiento ? htmlspecialchars($alojamiento['description'] ?? '', ENT_QUOTES, 'UTF-8') : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8'); ?></title>
<meta name="description" content="<?php echo htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8'); ?>">
<link rel="canonical" href="https://rutasrurales.io/alojamiento/<?php echo $slug; ?>">
<link rel="icon" href="/favicon.png" type="image/png">
<link rel="stylesheet" href="/styles.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.breadcrumb{background:#f8f9fa;padding:1rem 0;margin-top:80px}
.breadcrumb-nav{max-width:1200px;margin:0 auto;padding:0 1rem}
.breadcrumb-nav a{color:var(--primary-color);text-decoration:none}
.detalle-container{max-width:1200px;margin:2rem auto;padding:0 1rem}
.detalle-header{background:white;border-radius:15px;padding:2rem;box-shadow:0 2px 10px rgba(0,0,0,0.1);margin-bottom:2rem}
.detalle-titulo{font-size:2.5rem;color:var(--primary-color);margin-bottom:1rem}
.detalle-ubicacion{font-size:1.2rem;color:var(--secondary-color);margin-bottom:1rem}
.detalle-precio{font-size:1.8rem;color:var(--accent-color);font-weight:bold}
.fotos-galeria{background:white;border-radius:15px;padding:2rem;box-shadow:0 2px 10px rgba(0,0,0,0.1);margin-bottom:2rem}
.foto-principal{width:100%;height:400px;object-fit:cover;border-radius:10px;margin-bottom:1rem}
.fotos-secundarias{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem}
.foto-secundaria{width:100%;height:120px;object-fit:cover;border-radius:8px;cursor:pointer}
.info-card{background:white;border-radius:15px;padding:2rem;box-shadow:0 2px 10px rgba(0,0,0,0.1);margin-bottom:2rem}
.info-card h3{color:var(--primary-color);margin-bottom:1rem}
.contacto-botones{display:flex;gap:1rem;flex-wrap:wrap;margin-top:1rem}
.btn-contacto{padding:1rem 2rem;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-flex;align-items:center;gap:0.5rem}
.btn-whatsapp{background:#25D366;color:white}
.btn-telefono{background:var(--primary-color);color:white}
.btn-email{background:var(--secondary-color);color:white}
.volver-lista{text-align:center;margin:3rem 0}
.error-container{text-align:center;padding:4rem 2rem}
@media(max-width:768px){.detalle-titulo{font-size:1.8rem}.foto-principal{height:250px}}
</style>
</head>
<body>
<header class="header">
<nav class="navbar">
<div class="container">
<div class="logo"><img src="/logo_990x1076_verde.png" alt="Rutas Logo" style="height:50px"></div>
<ul class="nav-menu">
<li><a href="/index.html">Inicio</a></li>
<li><a href="/alojamientos-turisticos.html">Alojamientos</a></li>
<li><a href="/rutas-turisticas.html">Rutas</a></li>
<li><a href="/compromiso-social.html">Compromiso</a></li>
</ul>
</div>
</nav>
</header>

<div class="breadcrumb">
<nav class="breadcrumb-nav">
<a href="/index.html">Inicio</a> / <a href="/alojamientos-turisticos.html">Alojamientos</a> / <span><?php echo $nombre; ?></span>
</nav>
</div>

<div class="detalle-container">
<?php if ($alojamiento): ?>
<div class="detalle-header">
<h1 class="detalle-titulo"><?php echo $nombre; ?></h1>
<p class="detalle-ubicacion"><i class="fas fa-map-marker-alt"></i> <?php echo $municipio; ?>, <?php echo $provincia; ?></p>
<div class="detalle-precio"><?php echo $precio; ?></div>
</div>

<div class="fotos-galeria">
<h2><i class="fas fa-camera"></i> Fotos</h2>
<img id="fotoPrincipal" src="<?php echo $fotos[0]; ?>" alt="<?php echo $nombre; ?>" class="foto-principal">
<?php if (count($fotos) > 1): ?>
<div class="fotos-secundarias">
<?php foreach ($fotos as $i => $foto): ?>
<img src="<?php echo $foto; ?>" alt="Foto <?php echo $i+1; ?>" class="foto-secundaria" onclick="document.getElementById('fotoPrincipal').src='<?php echo $foto; ?>'">
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<div class="info-card">
<h3><i class="fas fa-info-circle"></i> Caracteristicas</h3>
<ul style="list-style:none;padding:0">
<li style="margin-bottom:0.5rem"><i class="fas fa-users" style="color:var(--primary-color);margin-right:0.5rem"></i> Capacidad: <?php echo $capacidad; ?> personas</li>
<li style="margin-bottom:0.5rem"><i class="fas fa-home" style="color:var(--primary-color);margin-right:0.5rem"></i> Tipo: <?php echo $tipo; ?></li>
<?php if ($direccion): ?>
<li style="margin-bottom:0.5rem"><i class="fas fa-map-pin" style="color:var(--primary-color);margin-right:0.5rem"></i> <?php echo $direccion; ?></li>
<?php endif; ?>
</ul>
</div>

<div class="info-card">
<h3><i class="fas fa-phone-alt"></i> Contacto</h3>
<div class="contacto-botones">
<?php if ($telefono): ?>
<a href="tel:<?php echo $telefono; ?>" class="btn-contacto btn-telefono"><i class="fas fa-phone"></i> Llamar</a>
<a href="https://wa.me/34<?php echo preg_replace('/[^0-9]/', '', $telefono); ?>" target="_blank" class="btn-contacto btn-whatsapp"><i class="fab fa-whatsapp"></i> WhatsApp</a>
<?php endif; ?>
<?php if ($email): ?>
<a href="mailto:<?php echo $email; ?>" class="btn-contacto btn-email"><i class="fas fa-envelope"></i> Email</a>
<?php endif; ?>
</div>
</div>

<?php if ($descripcionTexto): ?>
<div class="info-card">
<h3><i class="fas fa-align-left"></i> Descripcion</h3>
<p style="line-height:1.8"><?php echo nl2br($descripcionTexto); ?></p>
</div>
<?php endif; ?>

<?php else: ?>
<div class="error-container">
<i class="fas fa-exclamation-triangle" style="font-size:4rem;color:orange"></i>
<h2>Alojamiento no encontrado</h2>
<p>El alojamiento que buscas no existe o ha sido eliminado.</p>
</div>
<?php endif; ?>

<div class="volver-lista">
<a href="/alojamientos-turisticos.html" class="btn-primary" style="text-decoration:none"><i class="fas fa-arrow-left"></i> Volver a la lista</a>
</div>
</div>

<footer class="footer">
<div class="container">
<div class="footer-content-simple">
<div class="footer-info">
<span><i class="fas fa-envelope"></i> olgamarin@rutasrurales.io</span>
<span><i class="fas fa-phone"></i> +34 605 249 696</span>
</div>
</div>
<div class="footer-copyright"><p>2025 rutasrurales.io</p></div>
</div>
</footer>

<!-- ── TRACKING DE VISTAS DEL ALOJAMIENTO ── -->
<script>
(function() {
    'use strict';
    
    <?php if ($alojamiento && !empty($alojamiento['id'])): ?>
    var accommodationId = <?php echo (int)$alojamiento['id']; ?>;
    
    // Función para trackear vista del alojamiento
    function trackAccommodationView() {
        try {
            var trackingData = {
                resource_type: 'accommodation',
                resource_id: accommodationId,
                stat_type: 'view'
            };
            
            // Usar la API de tracking de recursos
            fetch('/api/track_resource_stat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(trackingData)
            }).then(function(response) {
                if (response.ok) {
                    console.log('✅ Vista registrada para alojamiento ID:', accommodationId);
                }
            }).catch(function(error) {
                console.warn('⚠️ Error al trackear vista:', error);
            });
        } catch (error) {
            console.warn('⚠️ Error en trackAccommodationView:', error);
        }
    }
    
    // Trackear vista después de 3 segundos (indica interés real)
    setTimeout(trackAccommodationView, 3000);
    
    // También trackear al hacer scroll hasta la mitad de la página
    var scrollTracked = false;
    window.addEventListener('scroll', function() {
        if (!scrollTracked && window.scrollY > window.innerHeight / 2) {
            scrollTracked = true;
            trackAccommodationView();
        }
    }, { passive: true });
    
    <?php endif; ?>
})();
</script>

</body>
</html>
