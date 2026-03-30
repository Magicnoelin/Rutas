<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = $_POST['id'];
        
        // NUEVO: Si viene de una sugerencia, transferir fotos automáticamente
        $suggestedId = isset($_POST['from_suggested_id']) ? (int)$_POST['from_suggested_id'] : 0;

        // Función para limpiar y manejar NULLs
        function clean($val) {
            $v = trim($val);
            return ($v === '') ? null : $v;
        }
        
        // NUEVO: Forzar is_active = 0 siempre (pendiente de revisión)
        // El admin debe revisar slug y activar manualmente
        $isActive = 0;

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
            $isActive, // Siempre 0 - pendiente de revisión
            clean($_POST['photo1']),
            clean($_POST['photo2']),
            clean($_POST['photo3']),
            clean($_POST['photo4']),
            $id
        ]);
        
        // NUEVO: Si viene de sugerencia, transferir fotos de entity_photos
        if ($suggestedId > 0) {
            // Buscar fotos de la sugerencia
            $stmtPhotos = $pdo->prepare("SELECT * FROM entity_photos WHERE suggested_entity_id = ? AND entity_id = 0");
            $stmtPhotos->execute([$suggestedId]);
            $suggestedPhotos = $stmtPhotos->fetchAll(PDO::FETCH_ASSOC);
            
            $webRoot = dirname(__DIR__);
            $placeSlug = clean($_POST['slug']);
            $destDir = $webRoot . '/img/lugares/' . $placeSlug . '/';
            
            if (!empty($suggestedPhotos) && $placeSlug) {
                // Crear directorio destino
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                
                // Obtener fotos actuales del lugar para encontrar huecos libres
                $stmtPlace = $pdo->prepare("SELECT photo1, photo2, photo3, photo4 FROM places_of_interest WHERE id = ?");
                $stmtPlace->execute([$id]);
                $placePhotos = $stmtPlace->fetch();
                
                $slots = ['photo1' => 1, 'photo2' => 2, 'photo3' => 3, 'photo4' => 4];
                $usedSlots = [];
                foreach ($slots as $col => $num) {
                    if (!empty($placePhotos[$col])) {
                        $usedSlots[] = $num;
                    }
                }
                
                $nextSlot = 1;
                foreach ($usedSlots as $used) {
                    if ($nextSlot == $used) $nextSlot++;
                }
                
                // Transferir cada foto
                foreach ($suggestedPhotos as $photo) {
                    if ($nextSlot > 4) break; // Solo 4 fotos máx
                    
                    $srcPath = $photo['file_path'] ?? '';
                    if (empty($srcPath)) continue;
                    
                    // Convertir a ruta física si es web
                    if (str_starts_with($srcPath, '/')) {
                        $srcPath = $webRoot . $srcPath;
                    }
                    
                    if (!file_exists($srcPath)) continue;
                    
                    // Copiar archivo
                    $newFilename = $nextSlot . '.webp';
                    $destPath = $destDir . $newFilename;
                    $newWebUrl = '/img/lugares/' . $placeSlug . '/' . $newFilename;
                    
                    if (copy($srcPath, $destPath)) {
                        // Actualizar la columna photoN en el lugar
                        $colName = 'photo' . $nextSlot;
                        $stmtUpdatePhoto = $pdo->prepare("UPDATE places_of_interest SET $colName = ? WHERE id = ?");
                        $stmtUpdatePhoto->execute([$newWebUrl, $id]);
                        
                        // Actualizar entity_photos con el nuevo entity_id
                        $stmtUpdateEntity = $pdo->prepare("UPDATE entity_photos SET entity_id = ?, suggested_entity_id = NULL WHERE id = ?");
                        $stmtUpdateEntity->execute([$id, $photo['id']]);
                        
                        $nextSlot++;
                    }
                }
                
                // Limpiar carpeta suggested si está vacía
                $suggestedDir = $webRoot . '/img/entity_photos/suggested/' . $suggestedId . '/';
                if (is_dir($suggestedDir)) {
                    $files = glob($suggestedDir . '*');
                    if (empty($files)) {
                        rmdir($suggestedDir);
                    }
                }
            }
        }


        header("Location: lugares_index.php?status=success");
        exit;

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            die("Error: El ID de categoría o subcategoría no existe en la base de datos.");
        }
        die("Error al guardar: " . $e->getMessage());
    }
}