<?php
header('Content-Type: application/json');
require_once 'config.php';
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $stmt = $pdo->query("SELECT id, name, slug, description, short_description, municipality, province, duration, difficulty_level, price_adult, price_child, photo1, photo2, photo3, photo4, category_id FROM tourist_activities WHERE is_active = 1 ORDER BY name");
    $data = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fotos = array_filter([$row['photo1'], $row['photo2'], $row['photo3'], $row['photo4']]);
        $precio = !empty($row['price_adult']) && $row['price_adult'] > 0 ? $row['price_adult'].'€' : 'Gratis';
        $categoria = !empty($row['category_id']) ? $row['category_id'] : 0;
        $data[] = [
            'id'=>$row['id'],
            'slug'=>$row['slug'],
            'nombre'=>$row['name'],
            'descripcion'=>$row['description'],
            'descripcion_corta'=>$row['short_description'],
            'localidad'=>$row['municipality'],
            'provincia'=>$row['province'],
            'duracion'=>$row['duration'],
            'dificultad'=>$row['difficulty_level'],
            'precio'=>$precio,
            'fotos'=>$fotos,
            'categoria'=>$categoria
        ];
    }
    echo json_encode(['success'=>true, 'data'=>$data]);
} catch(Exception $e) {
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
?>
