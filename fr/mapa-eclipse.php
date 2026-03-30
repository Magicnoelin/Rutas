<?php
// Idioma francés
$lang = 'fr';
$page_title = 'Carte de l\'Éclipse Solaire 2026 | Meilleurs Endroits pour Observer en Espagne | Routes Rurales';
$page_description = 'Trouvez des hébergements ruraux dans la zone de totalité pour l\'éclipse solaire totale du 12 août 2026. Carte interactive avec hôtels et maisons rurales en Espagne.';

// Conexión a la base de datos
$host = "localhost";
$db_name = "u412199647_Rutas";
$user = "u412199647_olgamarin";
$pass = "Rutas5Rurales7$";

$conn = new mysqli($host, $user, $pass, $db_name);
if ($conn->connect_error) { die("Fallo de conexión"); }
$conn->set_charset("utf8mb4");

// Consulta SQL - incluyendo photo1 para miniaturas
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

// Traducciones específicas de la página
$page_translations = [
    'hero_title' => '🌒 Où Observer l\'Éclipse Solaire Totale 2026 en Espagne',
    'hero_subtitle' => 'Trouvez des hôtels et maisons rurales dans la <strong>zone de totalité</strong>.',
    'timer_prefix' => 'Jours restants',
    'seo_title' => 'Meilleurs endroits pour observer l\'éclipse de 2026',
    'seo_text' => 'Pour vivre l\'<strong>obscurité totale</strong> le 12 août 2026, il est essentiel d\'être dans la zone d\'ombre. Notre carte interactive utilise les coordonnées de <strong>latitude et longitude</strong> pour vous montrer uniquement les hébergements qui garantissent une expérience astronomique unique.',
    'badge_total' => 'ZONE DE TOTALITÉ 100%',
    'badge_partial' => 'Partiel avancé',
    'view_details' => 'Voir les détails'
];

include '../header.php'; 
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
    <h1>🌒 <?php echo $page_translations['hero_title']; ?></h1>
        <p><?php echo $page_translations['hero_subtitle']; ?></p>
        <div id="timer"><?php echo $page_translations['timer_prefix']; ?>...</div>
    </div>

    <div id="map-eclipse"></div>

    <section class="seo-text">
        <h2><?php echo $page_translations['seo_title']; ?></h2>
        <p><?php echo $page_translations['seo_text']; ?></p>
    </section>
</div>

<script>
window.onload = function() {
    console.log("Iniciando mapa..."); 
    
    var map = L.map('map-eclipse').setView([41.50, -3.70], 6);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // Draw shadow zone
    L.polygon([
        [44.8, -10.5], [41.2, 5.8], [38.5, 4.2], [41.8, -11.5]
    ], {
        color: '#000', fillColor: '#000', fillOpacity: 0.1, weight: 1, interactive: false
    }).addTo(map);

    // Data from PHP
    var locations = <?php echo json_encode($alojamientos); ?>;

    if(locations.length > 0) {
        locations.forEach(function(loc) {
            if(loc.latitude && loc.longitude) {
                var badge = loc.es_totalidad ? 
                    '<span class="badge-totalidad"><?php echo $page_translations['badge_total']; ?></span>' : 
                    '<span style="color:gray; font-size:0.8em;"><?php echo $page_translations['badge_partial']; ?></span>';

                // Miniature avec attribut alt
                var fotoUrl = loc.photo1 && loc.photo1.trim() !== '' 
                    ? loc.photo1 
                    : 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=200&h=150&fit=crop';
                
                L.marker([loc.latitude, loc.longitude]).addTo(map)
                 .bindPopup(`
                    <div style="text-align:center; min-width:180px;">
                        <img src="${fotoUrl}" alt="${loc.name}" style="width:100%; max-width:200px; height:120px; object-fit:cover; border-radius:8px; margin-bottom:10px;">
                        <strong>${loc.name}</strong><br>${badge}<br>
                        <small>${loc.municipality}</small><br>
                        <a href="/alojamiento/${loc.slug}" style="display:block; margin-top:10px; background:#0b1a2d; color:white; padding:5px; border-radius:4px; text-decoration:none;"><?php echo $page_translations['view_details']; ?></a>
                    </div>
                 `);
            }
        });
    }

    // Countdown
    var target = new Date("Aug 12, 2026 19:30:00").getTime();
    setInterval(function() {
        var now = new Date().getTime();
        var d = target - now;
        var days = Math.floor(d / (1000 * 60 * 60 * 24));
        var el = document.getElementById("timer");
        if(el) el.innerHTML = "<?php echo $page_translations['timer_prefix']; ?>: " + days + " jours";
    }, 1000);
};
</script>

<?php include '../footer.php'; ?>
