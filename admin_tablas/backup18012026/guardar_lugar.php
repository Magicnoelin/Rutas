<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = $_POST['id'];

        // Función para limpiar y manejar NULLs
        function clean($val) {
            $v = trim($val);
            return ($v === '') ? null : $v;
        }

        $sql = "UPDATE places_of_interest SET 
                name = ?, slug = ?, category_id = ?, subcategory_id = ?, 
                description = ?, short_description = ?, address = ?, 
                municipality = ?, province = ?, postal_code = ?, 
                latitude = ?, longitude = ?, phone = ?, email = ?, 
                website = ?, meta_title = ?, meta_description = ?, 
                keywords = ?, is_active = ?, photo1 = ?, 
                photo2 = ?, photo3 = ?, photo4 = ?
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            clean($_POST['name']),
            clean($_POST['slug']),
            clean($_POST['category_id']),
            clean($_POST['subcategory_id']),
            clean($_POST['description']),
            clean($_POST['short_description']),
            clean($_POST['address']),
            clean($_POST['municipality']),
            clean($_POST['province']),
            clean($_POST['postal_code']),
            clean($_POST['latitude']),
            clean($_POST['longitude']),
            clean($_POST['phone']),
            clean($_POST['email']),
            clean($_POST['website']),
            clean($_POST['meta_title']),
            clean($_POST['meta_description']),
            clean($_POST['keywords']),
            $_POST['is_active'],
            clean($_POST['photo1']),
            clean($_POST['photo2']),
            clean($_POST['photo3']),
            clean($_POST['photo4']),
            $id
        ]);

        header("Location: lugares_index.php?status=success");
        exit;

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            die("Error: El ID de categoría o subcategoría no existe en la base de datos.");
        }
        die("Error al guardar: " . $e->getMessage());
    }
}