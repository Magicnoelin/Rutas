<?php
// 1. Configuración de la base de datos (RELLENA CON TUS DATOS)
$host = "localhost";
$db   = "u412199647_Rutas";
$user = "u412199647_olgamarin";
$pass = "Rutas5Rurales7$";
$charset = 'utf8mb4';


$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Error de conexión: " . $e->getMessage());
}

// 2. Consulta de los datos específicos
$stmt = $pdo->query("SELECT id, first_name, last_name, phone, whatsapp, private_notes FROM users");
$usuarios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Contactos WhatsApp</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #25D366; color: white; }
        .btn-ws { background: #25D366; color: white; padding: 5px 10px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .notes { font-size: 0.9em; color: #666; font-style: italic; }
    </style>
</head>
<body>

    <h2>Lista de Contactos y Notas Privadas</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Teléfono Registro</th>
                <th>WhatsApp</th>
                <th>Notas Privadas</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['first_name'] . " " . $u['last_name']) ?></td>
                <td><?= htmlspecialchars($u['phone']) ?></td>
                <td><?= htmlspecialchars($u['whatsapp']) ?></td>
                <td class="notes"><?= nl2br(htmlspecialchars($u['private_notes'] ?? 'Sin notas')) ?></td>
                <td>
                    <?php if ($u['whatsapp']): ?>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $u['whatsapp']) ?>" class="btn-ws" target="_blank">
                            Chatear
                        </a>
                    <?php else: ?>
                        <span style="color:red">No definido</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>