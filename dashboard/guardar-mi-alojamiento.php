<?php
// 1. Iniciamos sesión
session_start();

// 2. Comprobar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    die("Error: No has iniciado sesión.");
}

// 3. RUTA CORREGIDA: Salimos de dashboard y entramos en admin_tablas
include '../admin_tablas/db.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recogemos los datos del formulario
    $id = $_POST['id'];
    $user_id = $_SESSION['user_id']; // ID del dueño logueado
    
    // Recogemos el resto de campos
    $name = $_POST['name'];
    $description = $_POST['description'];
    $meta_title = $_POST['meta_title'];
    $meta_description = $_POST['meta_description'];
    $address = $_POST['address'];
    $postal_code = $_POST['postal_code'];
    $municipality = $_POST['municipality'];
    $capacity = $_POST['capacity'];
    $price_per_night = $_POST['price_per_night'];
    $phone = $_POST['phone'];
    $instagram_url = $_POST['instagram_url'];
    
    // Fotos
    $photo1 = $_POST['photo1'];
    $photo2 = $_POST['photo2'];
    $photo3 = $_POST['photo3'];
    $photo4 = $_POST['photo4'];

    try {
        // 4. VALIDACIÓN DE SEGURIDAD: 
        // Verificamos en la tabla intermedia si este usuario tiene permiso sobre este recurso
        $check = $pdo->prepare("SELECT id FROM user_resources WHERE user_id = ? AND resource_id = ? AND resource_type = 'accommodation'");
        $check->execute([$user_id, $id]);

        if ($check->fetch()) {
            // Si existe el vínculo, procedemos al UPDATE
            $sql = "UPDATE accommodations SET 
                        name = ?, 
                        description = ?, 
                        meta_title = ?, 
                        meta_description = ?, 
                        address = ?, 
                        postal_code = ?, 
                        municipality = ?, 
                        capacity = ?, 
                        price_per_night = ?, 
                        phone = ?, 
                        instagram_url = ?, 
                        photo1 = ?, 
                        photo2 = ?, 
                        photo3 = ?, 
                        photo4 = ?
                    WHERE id = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $name, $description, $meta_title, $meta_description, 
                $address, $postal_code, $municipality, $capacity, 
                $price_per_night, $phone, $instagram_url, 
                $photo1, $photo2, $photo3, $photo4,
                $id
            ]);

            // 5. Redirigir con éxito al dashboard
            header("Location: https://rutasrurales.io/user-dashboard.html#mis-alojamientos");
            exit();
        } else {
            die("Error: No tienes permiso para editar este recurso.");
        }

    } catch (PDOException $e) {
        die("Error al guardar los cambios: " . $e->getMessage());
    }
} else {
    header("Location: index.php");
    exit();
}