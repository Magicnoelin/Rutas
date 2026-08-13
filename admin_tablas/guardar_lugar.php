<?php
include 'db.php';
require_once __DIR__ . '/../api/inbound_links_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = $_POST['id'];
        
        // NUEVO: Si viene de una sugerencia, transferir fotos automáticamente
        $suggestedId = isset($_POST['from_suggested_id']) ? (int)$_POST['from_suggested_id'] : 0;

        // Asegurar que la columna description_linked existe en places_of_interest
        try {
            $pdo->exec("ALTER TABLE places_of_interest ADD COLUMN IF NOT EXISTS description_linked LONGTEXT NULL AFTER description");
        } catch (Exception $e) {
            // La columna ya existe o hay otro error, continuar
        }

        // Función para limpiar y manejar NULLs
        function clean($val) {
            $v = trim($val);
            return ($v === '') ? null : $v;
        }
        
        // Validar category_id antes de proceder
        $categoryId = clean($_POST['category_id']);
        if ($categoryId !== null) {
            // Verificar que la categoría existe en la base de datos
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) as count FROM categories_places WHERE id = ?");
            $stmtCheck->execute([$categoryId]);
            $result = $stmtCheck->fetch();
            
            if ($result['count'] == 0) {
                die("Error: El ID de categoría '$categoryId' no existe en la base de datos. Por favor, selecciona una categoría válida.");
            }
        }
        
        // Validar subcategory_id si se proporciona
        $subcategoryId = clean($_POST['subcategory_id']);
        if ($subcategoryId !== null) {
            // Verificar que la subcategoría existe (asumiendo que está en la misma tabla)
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) as count FROM categories_places WHERE id = ?");
            $stmtCheck->execute([$subcategoryId]);
            $result = $stmtCheck->fetch();
            
            if ($result['count'] == 0) {
                die("Error: El ID de subcategoría '$subcategoryId' no existe en la base de datos. Por favor, introduce un ID válido o deja el campo vacío.");
            }
        }
        
        // Obtener el estado actual del lugar para mantenerlo
        $stmtCurrent = $pdo->prepare("SELECT is_active FROM places_of_interest WHERE id = ?");
        $stmtCurrent->execute([$id]);
        $currentPlace = $stmtCurrent->fetch();
        $isActive = $currentPlace['is_active']; // Mantener el estado actual (público o borrador)

        // ─── INBOUND LINKS: generar description_linked ───────────────────────
        $description_raw = $_POST['description'] ?? '';
        $description_linked = procesarInboundLinks($description_raw, $pdo);
        // ─────────────────────────────────────────────────────────────────────

        $sql = "UPDATE places_of_interest SET 
                name = ?, slug = ?, category_id = ?, subcategory_id = ?, 
                description = ?, description_linked = ?, short_description = ?, address = ?, 
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
            $description_linked, // ← NUEVO: inbound links pre-procesados
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
            // Get more detailed error information
            $errorInfo = $e->errorInfo;
            $errorMessage = "Error de restricción de clave foránea (23000).\n";
            $errorMessage .= "Mensaje del driver: " . ($errorInfo[2] ?? $e->getMessage()) . "\n";
            $errorMessage .= "Posibles causas:\n";
            $errorMessage .= "1. El ID de categoría no existe en la tabla categories_places\n";
            $errorMessage .= "2. El ID de subcategoría no existe en la tabla correspondiente\n";
            $errorMessage .= "3. Otro problema de restricción de clave foránea\n\n";
            $errorMessage .= "Valores enviados:\n";
            $errorMessage .= "- category_id: " . (isset($_POST['category_id']) ? ($_POST['category_id'] === '' ? '(vacío -> NULL)' : $_POST['category_id']) : '(no enviado)') . "\n";
            $errorMessage .= "- subcategory_id: " . (isset($_POST['subcategory_id']) ? ($_POST['subcategory_id'] === '' ? '(vacío -> NULL)' : $_POST['subcategory_id']) : '(no enviado)') . "\n";
            
            die($errorMessage);
        }
        die("Error al guardar: " . $e->getMessage());
    }
}