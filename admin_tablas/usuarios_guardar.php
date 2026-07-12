<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = $_POST['id'];

        function clean($val) {
            $v = trim($val);
            return ($v === '') ? null : $v;
        }

        // Recogemos los roles del formulario. Si no hay ninguno, por defecto es turista.
        $user_types_array = $_POST['user_types'] ?? ['turista'];
        $user_type_string = implode(',', $user_types_array); 

        // Mapeo manual de nombres a IDs reales de la tabla de roles
        $role_mapping = [
            'turista'          => 1,
            'alojamiento'      => 2,
            'promotor_eventos' => 3
        ];

        // Iniciamos una transacción PDO para asegurar ambas tablas
        $pdo->beginTransaction();

        // 1. ACTUALIZAR TABLA USERS (Campos principales + String Legacy)
        $sql = "UPDATE users SET 
                nickname = ?, email = ?, first_name = ?, last_name = ?, phone = ?, whatsapp = ?, 
                user_type = ?, business_name = ?, business_description = ?, verification_status = ?, 
                subscription_level = ?, avatar_url = ?, status = ?, email_verified = ?, 
                verification_token = ?, terms_accepted = ?, reset_token = ?, private_notes = ?
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            clean($_POST['nickname']), clean($_POST['email']), clean($_POST['first_name']), 
            clean($_POST['last_name']), clean($_POST['phone']), clean($_POST['whatsapp']),
            $user_type_string, // String de comas guardado en users
            clean($_POST['business_name']), clean($_POST['business_description']),
            $_POST['verification_status'], $_POST['subscription_level'], clean($_POST['avatar_url']),
            $_POST['status'], $_POST['email_verified'], clean($_POST['verification_token']),
            $_POST['terms_accepted'], clean($_POST['reset_token']), clean($_POST['private_notes']),
            $id
        ]);

        // 2. SINCRONIZAR TABLA PIVOTE ROLE_USER
        // Borramos los roles que tuviera este usuario para evitar duplicados
        $stmtDelete = $pdo->prepare("DELETE FROM role_user WHERE user_id = ?");
        $stmtDelete->execute([$id]);

        // Insertamos los nuevos mapeando el nombre al ID numérico
        $stmtInsert = $pdo->prepare("INSERT INTO role_user (user_id, role_id, assigned_at) VALUES (?, ?, NOW())");
        foreach ($user_types_array as $type_name) {
            if (isset($role_mapping[$type_name])) {
                $stmtInsert->execute([$id, $role_mapping[$type_name]]);
            }
        }

        // Si todo ha ido bien, consolidamos los cambios en la BBDD
        $pdo->commit();

        header("Location: usuarios_index.php?status=updated");
        exit;

    } catch (Exception $e) {
        // Si algo falla, deshacemos todo para evitar incoherencias
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Error crítico de sincronización: " . $e->getMessage());
    }
}