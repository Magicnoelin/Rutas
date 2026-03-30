<?php
// Script para actualizar api/lugar-interes.php en el servidor
// Sube este archivo y ejecútalo

$contenido = '<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

require_once "config.php";

$response = ["success" => false, "data" => null, "message" => ""];

try {
    $slug = isset($_GET["slug"]) ? $_GET["slug"] : "";
    
    if (empty($slug)) {
        $response["message"] = "Slug no proporcionado";
        echo json_encode($response);
        exit;
    }

    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");

    $slug = strtolower(trim($slug));
    $slug = preg_replace("/[^a-z0-9-]/", "", $slug);

    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name
        FROM places_of_interest p
        LEFT JOIN categories_places c ON p.category_id = c.id
        WHERE p.slug = :slug AND p.is_active = 1
        LIMIT 1
    ");
    
    $stmt->execute(["slug" => $slug]);
    $lugar = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lugar) {
        $response["message"] = "Lugar de interés no encontrado";
        echo json_encode($response);
        exit;
    }

    $fotos = [];
    if (!empty($lugar["photo1"])) $fotos[] = $lugar["photo1"];
    if (!empty($lugar["photo2"])) $fotos[] = $lugar["photo2"];
    if (!empty($lugar["photo3"])) $fotos[] = $lugar["photo3"];
    if (!empty($lugar["photo4"])) $fotos[] = $lugar["photo4"];
    
    if (!empty($lugar["gallery"])) {
        $gallery = json_decode($lugar["gallery"], true);
        if (is_array($gallery)) $fotos = array_merge($fotos, $gallery);
    }

    $precio = !empty($lugar["entry_fee"]) && $lugar["entry_fee"] > 0 
        ? $lugar["entry_fee"] . "€" 
        : "Entrada gratuita";

    $response["success"] = true;
    $response["data"] = [
        "id" => $lugar["id"],
        "slug" => $lugar["slug"],
        "nombre" => $lugar["name"],
        "descripcion" => $lugar["description"],
        "descripcion_corta" => $lugar["short_description"],
        "direccion" => $lugar["address"],
        "localidad" => $lugar["municipality"],
        "provincia" => $lugar["province"],
        "telefono" => $lugar["phone"],
        "email" => $lugar["email"],
        "web" => $lugar["website"],
        "horario" => $lugar["opening_hours"],
        "precio" => $precio,
        "fotos" => $fotos,
        "category_name" => $lugar["category_name"]
    ];

} catch (PDOException $e) {
    $response["message"] = "Error de base de datos: " . $e->getMessage();
} catch (Exception $e) {
    $response["message"] = "Error: " . $e->getMessage();
}

echo json_encode($response);';

$archivo = __DIR__ . "/api/lugar-interes.php";
if (file_put_contents($archivo, $contenido)) {
    echo "✅ API lugar-interes.php actualizada correctamente";
} else {
    echo "❌ Error al guardar. Verifica que la carpeta api/ exista y tenga permisos.";
}
