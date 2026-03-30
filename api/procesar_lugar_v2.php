<?php
header('Content-Type: application/json');

// 1. CONFIGURACIÓN DE LA BASE DE DATOS
$host = "localhost";
$db   = "u412199647_Rutas";
$user = "u412199647_olgamarin";
$pass = "Rutas5Rurales7$";
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

// 2. CAPTURA Y VALIDACIÓN DE DATOS (Mínimos obligatorios)
$name = isset($_POST['name']) ? trim($_POST['name']) : null;
$category_id = isset($_POST['category_id']) ? $_POST['category_id'] : 1;

if (empty($name)) {
    echo json_encode(['success' => false, 'error' => 'El campo Nombre es obligatorio.']);
    exit;
}

// 3. GENERACIÓN DE SLUG Y CARPETA
function crearSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    if (empty($text)) return 'lugar-' . time();
    return strtolower($text);
}

$slug = crearSlug($name);
$folderPath = "../uploads/lugares/" . $slug . "/";

if (!file_exists($folderPath)) {
    if (!mkdir($folderPath, 0755, true)) {
        echo json_encode(['success' => false, 'error' => 'Error: No se pudo crear la carpeta de fotos.']);
        exit;
    }
}

// 4. GUARDAR LAS FOTOS FÍSICAMENTE
$rutas_fotos = ['photo1' => null, 'photo2' => null, 'photo3' => null, 'photo4' => null];
for ($i = 1; $i <= 4; $i++) {
    if (isset($_FILES["photo$i"])) {
        $nombreArchivo = "foto_" . $i . "_" . time() . ".jpg";
        $rutaDestino = $folderPath . $nombreArchivo;
        if (move_uploaded_file($_FILES["photo$i"]["tmp_name"], $rutaDestino)) {
            // Guardamos la ruta que usará la web para mostrar la imagen
            $rutas_fotos["photo$i"] = "uploads/lugares/$slug/$nombreArchivo";
        }
    }
}

// 5. INSERTAR EN LA TABLA places_of_interest
try {
    $sql = "INSERT INTO places_of_interest (
        name, slug, category_id, description, address, municipality, 
        province, phone, email, website, opening_hours, entry_fee, 
        accessibility, photo1, photo2, photo3, photo4, moderation_status, created_at
    ) VALUES (
        :name, :slug, :cat, :desc, :addr, :muni, :prov, :phone, :email, :web, :hours, :fee, :access, 
        :p1, :p2, :p3, :p4, 'pending', NOW()
    )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name'  => $name,
        ':slug'  => $slug,
        ':cat'   => $category_id,
        ':desc'  => $_POST['description'] ?? '',
        ':addr'  => $_POST['address'] ?? '',
        ':muni'  => $_POST['municipality'] ?? '',
        ':prov'  => $_POST['province'] ?? 'Soria',
        ':phone' => $_POST['phone'] ?? '',
        ':email' => $_POST['email'] ?? '',
        ':web'   => $_POST['website'] ?? '',
        ':hours' => $_POST['opening_hours'] ?? '',
        ':fee'   => !empty($_POST['entry_fee']) ? $_POST['entry_fee'] : 0,
        ':access' => $_POST['accessibility'] ?? '',
        ':p1'    => $rutas_fotos['photo1'],
        ':p2'    => $rutas_fotos['photo2'],
        ':p3'    => $rutas_fotos['photo3'],
        ':p4'    => $rutas_fotos['photo4']
    ]);

    echo json_encode(['success' => true, 'message' => 'Lugar guardado correctamente y pendiente de revisión.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>