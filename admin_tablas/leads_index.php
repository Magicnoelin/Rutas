<?php
include 'db.php';

// Consulta para traer los leads y comprobar si ya se han registrado como usuarios
$query = "
    SELECT 
        l.*,
        u.id AS registrado_user_id,
        u.nickname AS registrado_nickname,
        (SELECT COUNT(*) FROM user_resources ur WHERE ur.user_id = u.id AND ur.resource_type = 'accommodation') AS total_alojamientos
    FROM leads l
    LEFT JOIN users u ON (
        l.user_id = u.id 
        OR l.contacto = u.email 
        -- Limpiamos espacios para comparar teléfonos de forma más segura
        OR REPLACE(REPLACE(l.contacto, ' ', ''), '+', '') = REPLACE(REPLACE(u.phone, ' ', ''), '+', '')
    )
    ORDER BY l.id DESC
";

$stmt = $pdo->query($query);
$leads = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Leads - Rutas Rurales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light p-4">
    <div class="container-fluid">
        
        <!-- Cabecera -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-funnel text-success"></i> Control de Leads y Captación</h2>
            <div>
                <a href="usuarios_index.php" class="btn btn-outline-secondary me-2"><i class="bi bi-people"></i> Volver a Usuarios</a>
                <a href="leads_nuevo.php" class="btn btn-success"><i class="bi bi-person-plus"></i> Añadir Lead</a>
            </div>
        </div>

        <!-- Tabla de Leads -->
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre/Alojamiento</th>
                            <th>Contacto</th>
                            <th>Canal de Origen</th>
                            <th>Estado de Gestión</th>
                            <th class="text-center">¿Registrado?</th>
                            <th>Alojamientos Creados</th>
                            <th>Notas de Acción</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leads as $l): ?>
                        <tr>
                            <td class="text-muted fw-bold"><?= $l['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($l['nombre']) ?></strong>
                            </td>
                            <td>
                                <!-- Badge según tipo de contacto -->
                                <?php
                                $badge_contacto = [
                                    'telefono'  => 'bg-success',
                                    'email'     => 'bg-primary',
                                    'instagram' => 'bg-danger', // Gradiente de IG o rojo
                                    'otro'      => 'bg-secondary'
                                ][$l['tipo_contacto']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?= $badge_contacto ?> small mb-1">
                                    <?php if($l['tipo_contacto'] === 'instagram'): ?>
                                        <i class="bi bi-instagram"></i>
                                    <?php elseif($l['tipo_contacto'] === 'telefono'): ?>
                                        <i class="bi bi-whatsapp"></i>
                                    <?php else: ?>
                                        <i class="bi bi-envelope"></i>
                                    <?php endif; ?>
                                    <?= ucfirst($l['tipo_contacto']) ?>
                                </span>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($l['contacto']) ?></div>
                            </td>
                            <td>
                                <!-- Origen de la campaña -->
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-compass"></i> <?= str_replace('_', ' ', ucfirst($l['origen'])) ?>
                                </span>
                            </td>
                            <td>
                                <!-- Estado de tu acción comercial -->
                                <?php
                                $estado_class = [
                                    'nuevo'      => 'bg-info text-dark',
                                    'contactado' => 'bg-warning text-dark',
                                    'interesado' => 'bg-success',
                                    'descartado' => 'bg-secondary text-white'
                                ][$l['estado']] ?? 'bg-light';
                                ?>
                                <span class="badge <?= $estado_class ?>"><?= ucfirst($l['estado']) ?></span>
                            </td>
                            <td class="text-center">
                                <!-- CASILLA DE VERIFICACIÓN: ¿Ya se registró? -->
                                <?php if (!empty($l['registrado_user_id'])): ?>
                                    <span class="badge bg-success py-2 px-3 fs-7" title="¡Lead Convertido!">
                                        <i class="bi bi-patch-check-fill"></i> SÍ 
                                    </span>
                                    <div class="mt-1">
                                        <a href="usuarios_editar.php?id=<?= $l['registrado_user_id'] ?>" class="btn btn-xs btn-outline-primary py-0 px-1" style="font-size: 0.75rem;">
                                            Ver Perfil <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted py-2 px-3 border">
                                        <i class="bi bi-hourglass-split"></i> No aún
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-bold">
                                <?php if (!empty($l['registrado_user_id'])): ?>
                                    <span class="badge <?= $l['total_alojamientos'] > 0 ? 'bg-dark text-warning' : 'bg-light text-muted border' ?>">
                                        🏠 <?= $l['total_alojamientos'] ?> alojamientos
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted d-inline-block text-truncate" style="max-width: 180px;" title="<?= htmlspecialchars($l['notas'] ?? '') ?>">
                                    <?= htmlspecialchars($l['notas'] ?? 'Sin anotaciones') ?>
                                </small>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="leads_editar.php?id=<?= $l['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar Lead / Cambiar Estado">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmarEliminar(<?= $l['id'] ?>)" title="Eliminar Lead">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($leads)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No tienes ningún lead registrado todavía. ¡Empieza a captar en tu grupo de WhatsApp!</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function confirmarEliminar(id) {
        if(confirm('¿Seguro que deseas eliminar este lead de tu control?')) {
            window.location.href = 'leads_eliminar.php?id=' + id;
        }
    }
    </script>
</body>
</html>