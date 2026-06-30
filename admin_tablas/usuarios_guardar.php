<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = $_POST['id'];

        function clean($val) {
            $v = trim($val);
            return ($v === '') ? null : $v;
        }

        // Se añadieron 'whatsapp' y 'private_notes' a la consulta SQL
        $sql = "UPDATE users SET 
                nickname = ?, 
                email = ?, 
                first_name = ?, 
                last_name = ?, 
                phone = ?, 
                whatsapp = ?, 
                user_type = ?, 
                business_name = ?, 
                business_description = ?, 
                verification_status = ?, 
                subscription_level = ?, 
                avatar_url = ?, 
                status = ?, 
                email_verified = ?, 
                verification_token = ?, 
                terms_accepted = ?, 
                reset_token = ?,
                private_notes = ?
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        
        // El orden de las variables debe ser EXACTAMENTE el mismo que en el SQL de arriba
        $stmt->execute([
            clean($_POST['nickname']),
            clean($_POST['email']),
            clean($_POST['first_name']),
            clean($_POST['last_name']),
            clean($_POST['phone']),
            clean($_POST['whatsapp']), // <-- NUEVO
            $_POST['user_type'],
            clean($_POST['business_name']),
            clean($_POST['business_description']),
            $_POST['verification_status'],
            $_POST['subscription_level'],
            clean($_POST['avatar_url']),
            $_POST['status'],
            $_POST['email_verified'],
            clean($_POST['verification_token']),
            $_POST['terms_accepted'],
            clean($_POST['reset_token']),
            clean($_POST['private_notes']), // <-- NUEVO
            $id
        ]);

        header("Location: usuarios_index.php?status=updated");
        exit;

    } catch (PDOException $e) {
        die("Error crítico en la base de datos: " . $e->getMessage());
    }
}