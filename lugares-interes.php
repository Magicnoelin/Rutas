<?php
header('Content-Type: application/json');
require_once 'config.php';
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $stmt = $pdo->query("SELECT id, slug, name, description, short_description, address, municipality, province, phone, email, website, opening_hours, entry_fee, photo1, photo2, photo3, photo4, gallery, category_id FROM places_of_interest WHERE is_active = 1 ORDER BY name");
    $data = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fotos = array_filter([$row['photo1'], $row['photo2'], $row['photo3'], $row['photo4']]);
        $precio = !empty($row['entry_fee']) && $row['entry_fee'] > 0 ? $row['entry_fee'].'€' : 'Entrada gratuita';
        $data[] = ['id'=>$row['id'], 'slug'=>$row['slug'], 'nombre'=>$row['name'], 'descripcion'=>$row['description'], 'descripcion_corta'=>$row['short_description'], 'direccion'=>$row['address'], 'localidad'=>$row['municipality'], 'provincia'=>$row['province'], 'telefono'=>$row['phone'], 'email'=>$row['email'], 'web'=>$row['website'], 'horario'=>$row['opening_hours'], 'precio'=>$precio, 'fotos'=>$fotos, 'categoria'=>$row['category_id']];
    }
    echo json_encode(['success'=>true, 'data'=>$data]);
} catch(Exception $e) {
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
?>
