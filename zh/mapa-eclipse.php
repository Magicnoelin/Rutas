<?php
// Idioma chino
$lang = 'zh';
$page_title = '2026年日食地图 | 西班牙最佳观测地点 | 乡村路线';
$page_description = '寻找2026年8月12日日全食全食带的乡村住宿。西班牙酒店和乡村房屋的交互式地图。';

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
    'hero_title' => '🌒 2026年在西班牙观看日全食的最佳地点',
    'hero_subtitle' => '在<strong>全食带</strong>寻找酒店和乡村房屋。',
    'timer_prefix' => '剩余天数',
    'seo_title' => '2026年日食最佳观测地点',
    'seo_text' => '为了在2026年8月12日体验<strong>完全黑暗</strong>，必须位于阴影带内。我们的交互式地图使用<strong>经纬度</strong>坐标，仅向您展示保证独特天文体验的住宿。',
    'badge_total' => '全食带 100%',
    'badge_partial' => '高级偏食',
    'view_details' => '查看详情'
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

                // Thumbnail with alt attribute
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
        if(el) el.innerHTML = "<?php echo $page_translations['timer_prefix']; ?>: " + days + " 天";
    }, 1000);
};
</script>

<?php include '../footer.php'; ?>