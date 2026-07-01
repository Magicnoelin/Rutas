<?php
header('Content-Type: application/json');
require_once 'config.php';
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $stmt = $pdo->query("SELECT p.id, p.slug, p.name, p.description, p.short_description, p.address, p.municipality, p.province, p.phone, p.email, p.website, p.opening_hours, p.entry_fee, p.photo1, p.photo2, p.photo3, p.photo4, p.gallery, p.category_id, c.name AS category_name FROM places_of_interest p LEFT JOIN categories_places c ON p.category_id = c.id WHERE p.is_active = 1 ORDER BY p.name");
    $data = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fotos = array_filter([$row['photo1'], $row['photo2'], $row['photo3'], $row['photo4']]);
        // Detectar categorías gastronómicas (no mostrar "Entrada gratuita")
        $catLower = mb_strtolower($row['category_name'] ?? '', 'UTF-8');
        $esGastronomico = false;
        foreach (['restauran','gastronom','enotur','bodega','cafeter','restauraci','taberna'] as $kw) {
            if (strpos($catLower, $kw) !== false) { $esGastronomico = true; break; }
        }
        $precio = !empty($row['entry_fee']) && $row['entry_fee'] > 0 ? $row['entry_fee'].'€' : ($esGastronomico ? null : 'Entrada gratuita');
        $data[] = ['id'=>$row['id'], 'slug'=>$row['slug'], 'nombre'=>$row['name'], 'descripcion'=>$row['description'], 'descripcion_corta'=>$row['short_description'], 'direccion'=>$row['address'], 'localidad'=>$row['municipality'], 'provincia'=>$row['province'], 'telefono'=>$row['phone'], 'email'=>$row['email'], 'web'=>$row['website'], 'horario'=>$row['opening_hours'], 'precio'=>$precio, 'fotos'=>$fotos, 'categoria'=>$row['category_id']];
    }
    echo json_encode(['success'=>true, 'data'=>$data]);
} catch(Exception $e) {
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
?>
