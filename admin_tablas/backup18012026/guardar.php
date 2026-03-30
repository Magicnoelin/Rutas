<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = $_POST['id'];
        
        // Sentencia SQL con TODOS los campos de tu formulario
        $sql = "UPDATE accommodations SET 
                name = :name, 
                description = :description, 
                meta_title = :meta_title, 
                meta_description = :meta_description,
                address = :address,
                postal_code = :postal_code,
                municipality = :municipality,
                province = :province,
                latitude = :latitude,
                longitude = :longitude,
                capacity = :capacity,
                price_per_night = :price_per_night,
                phone = :phone,
                website = :website,
                instagram_url = :instagram_url,
                is_active = :is_active,
                slug = :slug,
                photo1 = :photo1,
                photo2 = :photo2,
                photo3 = :photo3,
                photo4 = :photo4
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);

        // Vinculamos cada campo con su valor enviado desde el formulario
        $stmt->execute([
            ':id' => $id,
            ':name' => $_POST['name'],
            ':description' => $_POST['description'],
            ':meta_title' => $_POST['meta_title'],
            ':meta_description' => $_POST['meta_description'],
            ':address' => $_POST['address'],
            ':postal_code' => $_POST['postal_code'],
            ':municipality' => $_POST['municipality'],
            ':province' => $_POST['province'],
            ':latitude' => $_POST['latitude'],
            ':longitude' => $_POST['longitude'],
            ':capacity' => $_POST['capacity'],
            ':price_per_night' => $_POST['price_per_night'],
            ':phone' => $_POST['phone'],
            ':website' => $_POST['website'],
            ':instagram_url' => $_POST['instagram_url'],
            ':is_active' => $_POST['is_active'],
            ':slug' => $_POST['slug'],
            ':photo1' => $_POST['photo1'],
            ':photo2' => $_POST['photo2'],
            ':photo3' => $_POST['photo3'],
            ':photo4' => $_POST['photo4']
        ]);

        // Redirigir al listado con éxito
        header("Location: index.php?status=success");
        exit;

    } catch (PDOException $e) {
        // Si hay un error (ej: falta una columna en la DB), te lo dirá aquí
        die("Error crítico al guardar en la base de datos: " . $e->getMessage());
    }
}