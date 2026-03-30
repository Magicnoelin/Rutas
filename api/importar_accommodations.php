<?php
/**
 * Script para importar datos de accommodations.json a la tabla accommodations
 * Ejecutar UNA SOLA VEZ después de crear la tabla accommodations
 */

require_once 'config.php';

try {
    $pdo = getDBConnection();

    // Leer el archivo JSON
    $jsonFile = __DIR__ . '/../accommodations.json';
    if (!file_exists($jsonFile)) {
        throw new Exception('Archivo accommodations.json no encontrado');
    }

    $jsonData = file_get_contents($jsonFile);
    $accommodations = json_decode($jsonData, true);

    if (!$accommodations) {
        throw new Exception('Error al decodificar el JSON');
    }

    $importados = 0;
    $errores = [];

    foreach ($accommodations as $acc) {
        try {
            // Insertar en la tabla accommodations
            $sql = "INSERT INTO accommodations (
                id, name, type, address, capacity, price, description,
                phone, email, website, image1, image2, image3, image4, status
            ) VALUES (
                :id, :name, :type, :address, :capacity, :price, :description,
                :phone, :email, :website, :image1, :image2, :image3, :image4, :status
            ) ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                type = VALUES(type),
                address = VALUES(address),
                capacity = VALUES(capacity),
                price = VALUES(price),
                description = VALUES(description),
                phone = VALUES(phone),
                email = VALUES(email),
                website = VALUES(website),
                image1 = VALUES(image1),
                image2 = VALUES(image2),
                image3 = VALUES(image3),
                image4 = VALUES(image4),
                status = VALUES(status)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $acc['id'] ?? null,
                ':name' => $acc['name'] ?? $acc['Nombre'] ?? '',
                ':type' => $acc['type'] ?? $acc['Tipo'] ?? '',
                ':address' => $acc['address'] ?? $acc['Direccion'] ?? '',
                ':capacity' => $acc['capacity'] ?? $acc['Plazas'] ?? 0,
                ':price' => $acc['price'] ?? $acc['Precio'] ?? null,
                ':description' => $acc['description'] ?? $acc['Notaspublicas'] ?? '',
                ':phone' => $acc['phone'] ?? $acc['Telefono1'] ?? '',
                ':email' => $acc['email'] ?? $acc['Email'] ?? '',
                ':website' => $acc['website'] ?? $acc['Web'] ?? '',
                ':image1' => $acc['image1'] ?? $acc['Foto1'] ?? '',
                ':image2' => $acc['image2'] ?? '',
                ':image3' => $acc['image3'] ?? '',
                ':image4' => $acc['image4'] ?? '',
                ':status' => $acc['status'] ?? 'active'
            ]);

            $importados++;

        } catch (PDOException $e) {
            $errores[] = [
                'id' => $acc['id'] ?? 'desconocido',
                'name' => $acc['name'] ?? $acc['Nombre'] ?? 'desconocido',
                'error' => $e->getMessage()
            ];
        }
    }

    // Respuesta
    $resultado = [
        'success' => true,
        'mensaje' => 'Importación de accommodations completada',
        'estadisticas' => [
            'total_procesados' => count($accommodations),
            'importados' => $importados,
            'errores' => count($errores)
        ]
    ];

    if (!empty($errores)) {
        $resultado['detalles_errores'] = $errores;
    }

    header('Content-Type: application/json');
    echo json_encode($resultado, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Error durante la importación: ' . $e->getMessage()
    ]);
}
