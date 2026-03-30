<?php
/**
 * Script de corrección de datos corruptos en entity_photos
 * Los registros con bug tienen file_url = nombre_autor y author_name = user_id
 *
 * EJECUTAR UNA VEZ y luego borrar este archivo.
 * Acceder a: https://rutasrurales.io/api/fix_entity_photos_data.php
 */
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDBConnection();

    // Detectar registros corruptos: file_url no empieza por / ni http
    // y author_name parece un número (user_id)
    $stmt = $pdo->query("
        SELECT id, entity_type, entity_id, file_path, file_url, author_name, author_id
        FROM entity_photos
        WHERE (file_url NOT LIKE '/%' AND file_url NOT LIKE 'http%' AND file_url IS NOT NULL AND file_url != '')
           OR (author_name REGEXP '^[0-9]+$')
    ");
    $corrupted = $stmt->fetchAll();

    $fixed = [];
    $errors = [];

    foreach ($corrupted as $row) {
        try {
            // El bug era: execute([$entityType, $suggestedId, $destPath, $photoUrl, $authorName, $userId])
            // Pero las columnas eran: (entity_type, entity_id, suggested_entity_id, category, author_id, file_path, file_url, author_name)
            // Así que: author_id recibió $destPath, file_path recibió $photoUrl, file_url recibió $authorName, author_name recibió $userId

            // En los datos corruptos:
            // file_url = nombre del autor (ej: "Olga Marin Fernández")
            // author_name = user_id (ej: "97")
            // file_path = URL web correcta (ej: "/img/entity_photos/suggested/2/u97_...")
            // author_id = 0 (porque recibió $destPath que no es int)

            $realAuthorName = $row['file_url'];   // El nombre real está en file_url
            $realUserId     = (int)$row['author_name']; // El user_id real está en author_name
            $realFilePath   = $row['file_path'];  // file_path tiene la URL web (correcto)
            $realFileUrl    = $row['file_path'];  // Usar file_path como file_url también

            // Obtener nombre real del usuario si tenemos el ID
            if ($realUserId > 0) {
                $stmtUser = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE id = ? LIMIT 1");
                $stmtUser->execute([$realUserId]);
                $userData = $stmtUser->fetch();
                if ($userData) $realAuthorName = $userData['full_name'];
            }

            // Corregir el registro
            $stmtFix = $pdo->prepare("
                UPDATE entity_photos 
                SET 
                    author_id   = ?,
                    file_url    = ?,
                    author_name = ?
                WHERE id = ?
            ");
            $stmtFix->execute([$realUserId, $realFileUrl, $realAuthorName, $row['id']]);

            $fixed[] = [
                'id'             => $row['id'],
                'author_id_new'  => $realUserId,
                'file_url_new'   => $realFileUrl,
                'author_name_new'=> $realAuthorName,
            ];

        } catch (Exception $e) {
            $errors[] = ['id' => $row['id'], 'error' => $e->getMessage()];
        }
    }

    echo json_encode([
        'success'          => true,
        'corrupted_found'  => count($corrupted),
        'fixed'            => $fixed,
        'errors'           => $errors,
        'message'          => count($fixed) . ' registros corregidos. Borra este archivo del servidor.',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
