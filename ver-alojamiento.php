<?php
// Configuración de base de datos directa (sin incluir config_updated.php)
$host = 'localhost';
$dbname = 'u412199647_Rutas';
$username = 'u412199647_olgamarin';
$password = 'Rutas5Rurales7$';

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
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
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

// Función para validar URLs de imágenes
function validarUrlImagen($url) {
    if (empty($url)) return false;
    // Verificar que la URL parece válida
    if (!filter_var($url, FILTER_VALIDATE_URL)) return false;
    // Verificar que no esté truncada (tiene extensión de imagen)
    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
}

$fotos = [];
if ($alojamiento) {
    if (!empty($alojamiento['photos'])) {
        $arr = json_decode($alojamiento['photos'], true);
        if (is_array($arr)) {
            foreach ($arr as $foto) {
                if (validarUrlImagen($foto)) $fotos[] = $foto;
            }
        }
    }
    if (empty($fotos)) {
        if (validarUrlImagen($alojamiento['photo1'])) $fotos[] = $alojamiento['photo1'];
        if (validarUrlImagen($alojamiento['photo2'])) $fotos[] = $alojamiento['photo2'];
        if (validarUrlImagen($alojamiento['photo3'])) $fotos[] = $alojamiento['photo3'];
        if (validarUrlImagen($alojamiento['photo4'])) $fotos[] = $alojamiento['photo4'];
    }
}
// Solo usar imagen por defecto si no hay fotos válidas
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

// Información turística de municipios de Castilla y León
$infoTuristica = [
    // VALLADOLID
    'Valladolid' => [
        'descripcion' => 'Capital de Castilla y León, ciudad histórica con un rico patrimonio cultural. Conocida por su Semana Santa, museos de arte, gastronomía excepcional y vinos de denominación de origen.',
        'atractivos' => ['Plaza Mayor', 'Catedral de Valladolid', 'Museo Nacional de Escultura', 'Campo Grande', 'Casa-Museo de Cervantes'],
        'gastronomia' => 'Lechazo asado, queso de oveja, pinchos y vinos D.O. Cigales y Ribera del Duero'
    ],
    'Peñafiel' => [
        'descripcion' => 'Villa medieval famosa por su impresionante castillo en forma de barco y por ser corazón de la Ribera del Duero. Destino enoturístico de primer nivel.',
        'atractivos' => ['Castillo de Peñafiel', 'Museo Provincial del Vino', 'Plaza del Coso', 'Bodegas con catas', 'Murallas medievales'],
        'gastronomia' => 'Lechazo asado, morcilla, vinos D.O. Ribera del Duero y quesos artesanales'
    ],
    'Medina del Campo' => [
        'descripcion' => 'Ciudad histórica conocida por su castillo de La Mota y su feria renacentista. Lugar donde falleció Isabel la Católica.',
        'atractivos' => ['Castillo de La Mota', 'Plaza Mayor de la Hispanidad', 'Palacio Real Testamentario', 'Colegiata de San Antolín', 'Mercado tradicional'],
        'gastronomia' => 'Cochinillo, lechazo, pan de Valladolid y dulces conventuales'
    ],
    'Tordesillas' => [
        'descripcion' => 'Villa histórica a orillas del Duero, famosa por el Tratado de Tordesillas. Conserva un importante patrimonio monumental.',
        'atractivos' => ['Real Monasterio de Santa Clara', 'Casas del Tratado', 'Puente sobre el Duero', 'Plaza Mayor', 'Museo del Tratado'],
        'gastronomia' => 'Cochinillo asado, sopa castellana, truchas del Duero y repostería tradicional'
    ],
    'Olmedo' => [
        'descripcion' => 'Villa medieval con un rico patrimonio mudéjar. Conocida por sus eventos culturales y su parque temático del mudéjar.',
        'atractivos' => ['Parque Temático del Mudéjar', 'Castillo de Olmedo', 'Iglesia de San Miguel', 'Plaza Mayor', 'Murallas medievales'],
        'gastronomia' => 'Lechazo, pinchos de morcilla y vinos de Rueda'
    ],
    'Medina de Rioseco' => [
        'descripcion' => 'La "Ciudad de los Almirantes", con un impresionante patrimonio artístico. Destaca por sus iglesias y su arquitectura tradicional.',
        'atractivos' => ['Iglesia de Santa María', 'Museo de San Francisco', 'Calle de la Rúa', 'Canal de Castilla', 'Semana Santa'],
        'gastronomia' => 'Lechazo, pan de Valladolid, quesos y embutidos artesanales'
    ],
    'Simancas' => [
        'descripcion' => 'Villa histórica con el Archivo General más importante de España. Pueblo con encanto a orillas del Pisuerga.',
        'atractivos' => ['Archivo General de Simancas', 'Castillo de Simancas', 'Puente medieval', 'Iglesia del Salvador', 'Paseo fluvial'],
        'gastronomia' => 'Cocina tradicional castellana, asados y productos de la huerta'
    ],
    'Cigales' => [
        'descripcion' => 'Capital de la D.O. Cigales, famosa por sus vinos rosados y tintos. Tradición vitivinícola centenaria.',
        'atractivos' => ['Bodegas subterráneas', 'Museo del Vino', 'Iglesia de Santiago', 'Ruta del vino', 'Cuevas-bodega'],
        'gastronomia' => 'Vinos rosados D.O. Cigales, lechazo, morcilla y quesos'
    ],
    'Tudela de Duero' => [
        'descripcion' => 'Municipio vitivinícola a orillas del Duero con tradición en la elaboración de vinos. Naturaleza y tranquilidad.',
        'atractivos' => ['Bodegas locales', 'Iglesia de la Asunción', 'Paseos por el Duero', 'Entorno natural', 'Ruta del vino'],
        'gastronomia' => 'Vinos locales, asados tradicionales y productos del Duero'
    ],
    // SORIA
    'Soria' => [
        'descripcion' => 'Ciudad castellana a orillas del Duero, cuna de poetas. Destaca por su patrimonio románico, naturaleza y la celebración de San Juan.',
        'atractivos' => ['Monasterio de San Juan de Duero', 'Ermita de San Saturio', 'Concatedral de San Pedro', 'Plaza Mayor', 'Castillo de Soria'],
        'gastronomia' => 'Torreznos, migas, setas, trufa negra y carne de caza'
    ],
    'El Burgo de Osma' => [
        'descripcion' => 'Villa medieval declarada Conjunto Histórico-Artístico. Conocida por su catedral gótica y su gastronomía.',
        'atractivos' => ['Catedral de Santa María', 'Castillo de Osma', 'Plaza Mayor', 'Universidad de Santa Catalina', 'Río Ucero'],
        'gastronomia' => 'Lechazo asado, morcilla, torreznos y vinos de la tierra'
    ],
    'Castilfrío de la Sierra' => [
        'descripcion' => 'Pequeño pueblo de montaña en plena Sierra de Urbión, rodeado de naturaleza virgen. Ideal para el turismo rural y de desconexión.',
        'atractivos' => ['Naturaleza y bosques', 'Rutas de senderismo', 'Arquitectura tradicional serrana', 'Miradores naturales', 'Observación de fauna'],
        'gastronomia' => 'Cocina serrana, lechazo, setas, embutidos artesanales y productos de la tierra'
    ],
    'Vinuesa' => [
        'descripcion' => 'Encantador pueblo serrano a orillas de la Laguna Negra. Destino perfecto para amantes de la naturaleza y el senderismo.',
        'atractivos' => ['Laguna Negra', 'Picos de Urbión', 'Cañón del Río Lobos', 'Arquitectura popular pinariega', 'Rutas de montaña'],
        'gastronomia' => 'Trucha, setas, carne de caza, miel y repostería casera'
    ],
    'Ágreda' => [
        'descripcion' => 'Villa histórica fronteriza con Aragón, conocida por sus murallas y palacios. Cuna de Sor María Jesús de Ágreda.',
        'atractivos' => ['Murallas medievales', 'Plaza Mayor', 'Palacio de los Castejones', 'Convento de la Concepción', 'Iglesias románicas'],
        'gastronomia' => 'Cordero asado, migas, tortas de aceite y dulces conventuales'
    ],
    // BURGOS
    'Burgos' => [
        'descripcion' => 'Ciudad del Camino de Santiago con impresionante patrimonio. Su catedral es Patrimonio de la Humanidad.',
        'atractivos' => ['Catedral de Burgos', 'Museo de la Evolución Humana', 'Monasterio de las Huelgas', 'Castillo de Burgos', 'Cartuja de Miraflores'],
        'gastronomia' => 'Morcilla de Burgos, lechazo asado, queso fresco y vinos D.O. Ribera del Duero'
    ],
    'Aranda de Duero' => [
        'descripcion' => 'Capital de la Ribera del Duero, famosa por su lechazo asado y sus vinos. Rica tradición enoturística.',
        'atractivos' => ['Iglesia de Santa María', 'Bodegas subterráneas', 'Plaza Mayor', 'Museo del Vino', 'Santuario de la Virgen de las Viñas'],
        'gastronomia' => 'Lechazo asado en horno de leña, vinos D.O. Ribera del Duero y morcilla'
    ],
    'Covarrubias' => [
        'descripcion' => 'Uno de los pueblos más bonitos de España. Villa medieval con un patrimonio excepcional.',
        'atractivos' => ['Colegiata de San Cosme y San Damián', 'Murallas medievales', 'Archivo del Adelantamiento de Castilla', 'Torreón de Fernán González', 'Casas tradicionales'],
        'gastronomia' => 'Morcilla, lechazo, olla podrida y dulces artesanales'
    ]
];

// Obtener información del municipio actual
$infoMunicipio = null;
if ($alojamiento && isset($infoTuristica[$municipio])) {
    $infoMunicipio = $infoTuristica[$municipio];
} elseif ($alojamiento) {
    // Información genérica para municipios sin datos específicos
    $infoMunicipio = [
        'descripcion' => 'Encantador municipio de la provincia de ' . $provincia . ', ideal para disfrutar del turismo rural y la tranquilidad del entorno castellano. Descubre la autenticidad de sus tradiciones y la hospitalidad de su gente.',
        'atractivos' => ['Arquitectura tradicional', 'Gastronomía local', 'Entorno natural', 'Rutas de senderismo', 'Patrimonio cultural'],
        'gastronomia' => 'Cocina tradicional castellana con productos locales de la tierra'
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8'); ?></title>
<meta name="description" content="<?php echo htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8'); ?>">
<link rel="canonical" href="https://rutasrurales.io/alojamientos/<?php echo $slug; ?>">
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
.foto-principal{width:100%;height:400px;object-fit:cover;border-radius:10px;margin-bottom:1rem;display:block}
.foto-principal.hidden{display:none}
.fotos-secundarias{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem}
.foto-secundaria{width:100%;height:120px;object-fit:cover;border-radius:8px;cursor:pointer;display:block}
.foto-secundaria.hidden{display:none}
.mapa-container{background:white;border-radius:15px;padding:2rem;box-shadow:0 2px 10px rgba(0,0,0,0.1);margin-bottom:2rem}
.mapa-container iframe{width:100%;height:350px;border:0;border-radius:10px}
.info-turistica{background:linear-gradient(135deg,#667eea20,#764ba220);border-radius:15px;padding:2rem;margin-bottom:2rem;border-left:5px solid var(--primary-color)}
.info-turistica h3{color:var(--primary-color);margin-bottom:1rem;display:flex;align-items:center;gap:0.5rem}
.atractivos-lista{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin:1rem 0}
.atractivo-item{background:white;padding:1rem;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.1);display:flex;align-items:center;gap:0.5rem}
.gastronomia-box{background:white;padding:1.5rem;border-radius:10px;margin-top:1rem;border-left:3px solid var(--accent-color)}
.info-card{background:white;border-radius:15px;padding:2rem;box-shadow:0 2px 10px rgba(0,0,0,0.1);margin-bottom:2rem}
.info-card h3{color:var(--primary-color);margin-bottom:1rem}
.contacto-botones{display:flex;gap:1rem;flex-wrap:wrap;margin-top:1rem}
.btn-contacto{padding:1rem 2rem;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-flex;align-items:center;gap:0.5rem}
.btn-whatsapp{background:#25D366;color:white}
.btn-telefono{background:var(--primary-color);color:white}
.btn-email{background:var(--secondary-color);color:white}
.volver-lista{text-align:center;margin:3rem 0}
.error-container{text-align:center;padding:4rem 2rem}
@media(max-width:768px){.detalle-titulo{font-size:1.8rem}.foto-principal{height:250px}.atractivos-lista{grid-template-columns:1fr}.mapa-container iframe{height:250px}}
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

<?php if (count($fotos) > 0 && $fotos[0] !== $imagenDefecto): ?>
<div class="fotos-galeria">
<h2><i class="fas fa-camera"></i> Fotos</h2>
<img id="fotoPrincipal" src="<?php echo $fotos[0]; ?>" alt="<?php echo $nombre; ?>" class="foto-principal" onerror="this.classList.add('hidden')">
<?php if (count($fotos) > 1): ?>
<div class="fotos-secundarias">
<?php foreach ($fotos as $i => $foto): ?>
<img src="<?php echo $foto; ?>" alt="Foto <?php echo $i+1; ?>" class="foto-secundaria" onclick="cambiarFoto('<?php echo $foto; ?>', this)" onerror="this.classList.add('hidden')">
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
<?php endif; ?>

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

<?php if ($infoMunicipio): ?>
<div class="info-turistica">
<h3><i class="fas fa-map-marked-alt"></i> Descubre <?php echo $municipio; ?></h3>
<p style="line-height:1.8;margin-bottom:1.5rem"><?php echo $infoMunicipio['descripcion']; ?></p>
<h4 style="color:var(--secondary-color);margin-bottom:1rem"><i class="fas fa-star"></i> Principales Atractivos</h4>
<div class="atractivos-lista">
<?php foreach ($infoMunicipio['atractivos'] as $atractivo): ?>
<div class="atractivo-item">
<i class="fas fa-check-circle" style="color:var(--primary-color)"></i>
<span><?php echo $atractivo; ?></span>
</div>
<?php endforeach; ?>
</div>
<div class="gastronomia-box">
<h4 style="color:var(--accent-color);margin-bottom:0.5rem"><i class="fas fa-utensils"></i> Gastronomia Local</h4>
<p><?php echo $infoMunicipio['gastronomia']; ?></p>
</div>
</div>
<?php endif; ?>

<div class="mapa-container">
<h3><i class="fas fa-map-marker-alt"></i> Ubicacion</h3>
<iframe src="https://maps.google.com/maps?q=<?php echo urlencode($municipio . ', ' . $provincia . ($direccion ? ', ' . $direccion : '')); ?>&output=embed&z=12" allowfullscreen loading="lazy"></iframe>
<p style="margin-top:1rem;text-align:center;color:#666">
<i class="fas fa-info-circle"></i> <?php echo $municipio; ?>, <?php echo $provincia; ?>
<?php if ($direccion): ?>- <?php echo $direccion; ?><?php endif; ?>
</p>
</div>

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

<script>
// Función para cambiar foto principal con validación
function cambiarFoto(url, imgElement) {
    const fotoPrincipal = document.getElementById('fotoPrincipal');
    if (fotoPrincipal && !imgElement.classList.contains('hidden')) {
        fotoPrincipal.src = url;
    }
}

// Ocultar galería completa si no hay fotos válidas
document.addEventListener('DOMContentLoaded', function() {
    const fotoPrincipal = document.getElementById('fotoPrincipal');
    const galeria = document.querySelector('.fotos-galeria');
    
    if (fotoPrincipal) {
        fotoPrincipal.addEventListener('error', function() {
            const secundarias = document.querySelectorAll('.foto-secundaria:not(.hidden)');
            if (secundarias.length > 0) {
                // Si hay fotos secundarias válidas, usar la primera
                fotoPrincipal.src = secundarias[0].src;
            } else if (galeria) {
                // Si no hay fotos válidas, ocultar toda la galería
                galeria.style.display = 'none';
            }
        });
    }
    
    // Verificar si todas las fotos secundarias fallaron
    setTimeout(function() {
        const secundarias = document.querySelectorAll('.foto-secundaria');
        const secundariasOcultas = document.querySelectorAll('.foto-secundaria.hidden');
        if (secundarias.length > 0 && secundarias.length === secundariasOcultas.length && galeria) {
            galeria.style.display = 'none';
        }
    }, 2000);
});
</script>

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
</body>
</html>
