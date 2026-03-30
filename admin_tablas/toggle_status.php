<?php
include 'db.php';
// Limpiamos cualquier salida previa
ob_clean(); 

if (isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $nuevo_estado = (int)$_POST['nuevo_estado'];

    $stmt = $pdo->prepare("UPDATE places_of_interest SET is_active = ? WHERE id = ?");
    
    if ($stmt->execute([$nuevo_estado, $id])) {
        echo "1"; // Devolvemos solo un número para que sea fácil de leer por JS
    } else {
        echo "0";
    }
}
exit; // Cortamos cualquier ejecución extra