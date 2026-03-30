<?php
// 1. Configuración de la base de datos
$host = "localhost";
$db_name = "u412199647_Rutas";
$user = "u412199647_olgamarin";
$pass = "Rutas5Rurales7$"; // <-- TU CLAVE REAL AQUÍ

$conn = new mysqli($host, $user, $pass, $db_name);
if ($conn->connect_error) { die("Fallo de conexión"); }
$conn->set_charset("utf8mb4");

// 2. CONSULTA SQL (Solo activos) - incluyendo photo1 para miniaturas
$sql = "SELECT name, municipality, province, latitude, longitude, slug, photo1 
        FROM accommodations WHERE is_active = 1 AND latitude > 38.5 LIMIT 1000"; 
$result = $conn->query($sql);
$alojamientos = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $suma = (float)$row['latitude'] + (float)$row['longitude'];
        $row['es_totalidad'] = ($suma > 34.8 && $suma < 39.5);
        $alojamientos[] = $row;
    }
}
$conn->close();

include 'header.php'; 
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    .hero-eclipse { margin-top: 100px; background: #0b1a2d; color: white; padding: 60px 20px; text-align: center; border-radius: 20px; }
    #map-eclipse { height: 600px; width: 100%; border-radius: 20px; margin-top: 30px; background: #eee; border: 1px solid #ccc; z-index: 5; }
    .badge-totalidad { background: #ffd700; color: #000; padding: 3px 10px; border-radius: 15px; font-weight: bold; font-size: 0.8em; }
    #timer { font-size: 1.8rem; font-weight: bold; color: #ffd700; }
    .container-eclipse { max-width: 1100px; margin: 0 auto; padding: 0 20px; }
    .seo-text { margin-top: 40px; line-height: 1.7; color: #444; }
</style>

<div class="container-eclipse">
    <div class="hero-eclipse">
        <h1>🌒 Dónde ver el Eclipse Solar Total 2026 en España</h1>
        <p>Encuentra hoteles y casas rurales en la <strong>franja de totalidad</strong>.</p>
        <div id="timer">Calculando tiempo...</div>
    </div>

    <div id="map-eclipse"></div>

    <section class="seo-text">
        <h2>Mejores sitios para ver el eclipse de 2026</h2>
        <p>Para vivir la <strong>oscuridad total</strong> el próximo 12 de agosto de 2026, es imprescindible estar dentro de la franja de sombra. Nuestro mapa interactivo utiliza coordenadas de <strong>latitud y longitud</strong> para mostrarte solo los alojamientos que garantizan una experiencia astronómica única.</p>
    </section>
</div>

<script>
// USAMOS WINDOW.ONLOAD PARA ASEGURARNOS QUE TODO CARGUE ANTES DE PINTAR EL MAPA
window.onload = function() {
    console.log("Iniciando mapa..."); // Mira esto en la consola (F12)
    
    var map = L.map('map-eclipse').setView([41.50, -3.70], 6);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // Dibujar la franja de sombra
    L.polygon([
        [44.8, -10.5], [41.2, 5.8], [38.5, 4.2], [41.8, -11.5]
    ], {
        color: '#000', fillColor: '#000', fillOpacity: 0.1, weight: 1, interactive: false
    }).addTo(map);

    // Datos desde PHP
    var locations = <?php echo json_encode($alojamientos); ?>;

    if(locations.length > 0) {
        locations.forEach(function(loc) {
            if(loc.latitude && loc.longitude) {
                var badge = loc.es_totalidad ? 
                    '<span class="badge-totalidad">ZONA TOTALIDAD 100%</span>' : 
                    '<span style="color:gray; font-size:0.8em;">Parcial avanzado</span>';

                // Imagen miniatura con fallback
                var fotoUrl = loc.photo1 && loc.photo1.trim() !== '' 
                    ? loc.photo1 
                    : 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=200&h=150&fit=crop';
                
                L.marker([loc.latitude, loc.longitude]).addTo(map)
                 .bindPopup(`
                    <div style="text-align:center; min-width:180px;">
                        <img src="${fotoUrl}" alt="${loc.name}" style="width:100%; max-width:200px; height:120px; object-fit:cover; border-radius:8px; margin-bottom:10px;">
                        <strong>${loc.name}</strong><br>${badge}<br>
                        <small>${loc.municipality}</small><br>
                        <a href="/alojamiento/${loc.slug}" style="display:block; margin-top:10px; background:#0b1a2d; color:white; padding:5px; border-radius:4px; text-decoration:none;">Ver Detalles</a>
                    </div>
                 `);
            }
        });
    }

    // Cuenta atrás
    var target = new Date("Aug 12, 2026 19:30:00").getTime();
    setInterval(function() {
        var now = new Date().getTime();
        var d = target - now;
        var days = Math.floor(d / (1000 * 60 * 60 * 24));
        var el = document.getElementById("timer");
        if(el) el.innerHTML = "Faltan " + days + " días";
    }, 1000);
};
</script>

<?php include 'footer.php'; ?>