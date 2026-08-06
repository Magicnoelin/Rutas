<?php
session_start();
include 'db.php';

// Consulta optimizada: Trae los usuarios y concatena los nombres de sus alojamientos activos (si los tiene)
$query = "
    SELECT u.*, 
           GROUP_CONCAT(a.name SEPARATOR '|||') AS alojamientos_asociados
    FROM users u
    LEFT JOIN user_resources ur 
        ON u.id = ur.user_id 
        AND ur.resource_type = 'accommodation' 
        AND ur.status = 'active'
    LEFT JOIN accommodations a 
        ON ur.resource_id = a.id
    GROUP BY u.id
    ORDER BY u.id DESC
";

$stmt = $pdo->query($query);
$usuarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Rutas Rurales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .dropdown-menu-alojamientos {
            max-height: 250px;
            overflow-y: auto;
            min-width: 240px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: none;
        }
        .dropdown-menu-alojamientos li {
            padding: 6px 12px;
            font-size: 0.85rem;
            border-bottom: 1px solid #f1f1f1;
        }
        .dropdown-menu-alojamientos li:last-child {
            border-bottom: none;
        }
        .btn-email-link {
            text-decoration: none;
            color: inherit;
        }
        .btn-email-link:hover {
            color: #0d6efd;
        }
    </style>
</head>
<body class="bg-light p-4">
    <div class="container-fluid">

        <!-- Banner de aviso si la emulación está activa (Solo visible al estar emulando) -->
        <?php if (isset($_SESSION['admin_impersonating']) && $_SESSION['admin_impersonating'] === true): ?>
            <div class="alert alert-warning d-flex justify-content-between align-items-center mb-4 shadow-sm border-warning">
                <div>
                    <i class="bi bi-incognito fs-4 me-2 text-dark"></i>
                    <strong>Modo Emulación Activo:</strong> Actualmente estás navegando como el <strong>Usuario ID: <?= (int)$_SESSION['user_id'] ?></strong>.
                </div>
                <a href="salir_user_emule.php" class="btn btn-danger btn-sm fw-bold">
                    <i class="bi bi-box-arrow-left me-1"></i> Salir de Emulación
                </a>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-people text-primary"></i> Gestión de Usuarios</h2>
            <a href="usuarios_nuevo.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> Nuevo Usuario</a>
        </div>

        <!-- Alertas dinámicas para el envío de Mail -->
        <div id="alertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 1060;"></div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>WhatsApp</th>
                            <th>Tipo</th>
                            <th>Suscripción</th>
                            <th>Estado</th>
                            <th>Notas Privadas</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td class="text-muted fw-bold"><?= $u['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($u['nickname'] ?? 'Sin alias') ?></strong>
                            </td>
                            <td><?= htmlspecialchars(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?></td>
                            <td>
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="me-2"><?= htmlspecialchars($u['email']) ?></span>
                                    <!-- Botón interactivo para abrir el modal de envío de email -->
                                    <button class="btn btn-link btn-sm p-0 btn-email-link" 
                                            onclick="abrirModalEmail('<?= htmlspecialchars($u['email']) ?>', '<?= htmlspecialchars(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?>')" 
                                            title="Enviar correo electrónico">
                                        <i class="bi bi-envelope-at text-primary fs-5"></i>
                                    </button>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($u['phone'] ?? 'No asignado') ?></td>
                            <td>
                                <?php if (!empty($u['whatsapp'])): ?>
                                    <span class="text-success"><i class="bi bi-whatsapp"></i> <?= htmlspecialchars($u['whatsapp']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">No asignado</span>
                                <?php endif; ?>
                            </td>
                            <td>
<td class="align-middle">

    <?php 
    // 1. Mapeo de colores según cada tipo/rol
    $badge_classes = [
        'admin'       => 'bg-warning text-dark',  // Naranja / Amarillo
        'alojamiento' => 'bg-primary text-white', // Azul
        'turista'     => 'bg-info text-dark',     // Azul claro / celeste
        'senderista'  => 'bg-success text-white', // Verde
        'gestor'      => 'bg-purple text-white',  // Púrpura
    ];

    // 2. Convertimos la cadena de la BBDD (ej: "turista,alojamiento") en un array limpio
    $roles_usuario = array_map('trim', explode(',', $u['user_type'] ?? ''));
    ?>

    <!-- 3. Mostramos una insignia por cada rol encontrado -->
    <div class="d-flex flex-wrap gap-1 mb-1">
        <?php foreach ($roles_usuario as $rol): ?>
            <?php 
            if (empty($rol)) continue;
            $clase_badge = $badge_classes[$rol] ?? 'bg-secondary text-white'; 
            ?>
            <span class="badge <?= $clase_badge ?> uppercase small">
                <?= htmlspecialchars($rol) ?>
            </span>
        <?php endforeach; ?>
    </div>

    <!-- 4. Desplegable de alojamientos asociados (si existen) -->
    <?php if (!empty($u['alojamientos_asociados'])): ?>
        <?php 
        $lista_alojamientos = explode('|||', $u['alojamientos_asociados']);
        $total_alojamientos = count($lista_alojamientos);
        ?>
        <div class="mt-1">
            <?php if ($total_alojamientos === 1): ?>
                <span class="badge bg-dark text-white d-inline-block text-truncate" style="max-width: 160px;" title="<?= htmlspecialchars($lista_alojamientos[0]) ?>">
                    <i class="bi bi-house-door-fill text-warning"></i> <?= htmlspecialchars($lista_alojamientos[0]) ?>
                </span>
            <?php else: ?>
                <div class="dropdown d-inline-block">
                    <button class="btn btn-xs btn-dark dropdown-toggle py-0 px-2 fw-semibold" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.75rem; border-radius: 4px;">
                        <i class="bi bi-house-door-fill text-warning"></i> <?= $total_alojamientos ?> alojamientos
                    </button>
                    <ul class="dropdown-menu dropdown-menu-alojamientos p-0">
                        <li class="bg-light fw-bold text-muted text-center" style="font-size: 0.75rem;">Lista de Alojamientos</li>
                        <?php foreach ($lista_alojamientos as $alojamiento): ?>
                            <li>
                                <i class="bi bi-arrow-right-short text-primary"></i> <?= htmlspecialchars($alojamiento) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</td>
<td>
    <?php $sub_level = $u['subscription_level'] ?? 'basic'; ?>
    <span class="badge <?= $sub_level === 'premium' ? 'bg-warning text-dark' : 'bg-secondary' ?>">
        <?= ucfirst($sub_level) ?>
    </span>
</td>
<td>
    <?php 
    $status = $u['status'] ?? 'inactive';
    $status_class = [
        'active'    => 'bg-success',
        'inactive'  => 'bg-secondary',
        'suspended' => 'bg-danger',
        'deleted'   => 'bg-dark'
    ][$status] ?? 'bg-secondary';
    ?>
    <span class="badge <?= $status_class ?>"><?= ucfirst($status) ?></span>
</td>
<td>
    <small class="text-muted d-inline-block text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($u['private_notes'] ?? '') ?>">
        <?= htmlspecialchars($u['private_notes'] ?? 'Sin notas') ?>
    </small>
</td>
<td class="text-center">
    <div class="btn-group">
        <a href="usuarios_editar.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
            <i class="bi bi-pencil"></i>
        </a>
        <!-- Botón para emular usuario (Inicia emulación) -->
        <a href="user_emule.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-warning" title="Emular usuario">
            <i class="bi bi-incognito"></i>
        </a>
        <button class="btn btn-sm btn-outline-danger" onclick="confirmarEliminar(<?= $u['id'] ?>)" title="Eliminar">
            <i class="bi bi-trash"></i>
        </button>
    </div>
</td>
</tr>
<?php endforeach; ?>
<?php if (empty($usuarios)): ?>
<tr>
    <td colspan="11" class="text-center py-4 text-muted">No se encontraron usuarios en la base de datos.</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>

<!-- MODAL PARA ENVIAR CORREO ELECTRÓNICO -->
<div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="emailForm">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="emailModalLabel"><i class="bi bi-envelope-paper"></i> Redactar Correo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="emailDestinatario" class="form-label fw-semibold">Destinatario</label>
                        <input type="text" class="form-control bg-light" id="emailDestinatarioDisplay" readonly>
                        <input type="hidden" name="email" id="emailDestinatario">
                    </div>
                    <div class="mb-3">
                        <label for="emailAsunto" class="form-label fw-semibold">Asunto</label>
                        <input type="text" class="form-control" name="asunto" id="emailAsunto" placeholder="Escribe el asunto del correo..." required>
                    </div>
                    <div class="mb-3">
                        <label for="emailMensaje" class="form-label fw-semibold">Mensaje</label>
                        <textarea class="form-control" name="mensaje" id="emailMensaje" rows="6" placeholder="Escribe tu mensaje aquí..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnEnviarMail">
                        <span id="btnText"><i class="bi bi-send"></i> Enviar Correo</span>
                        <span id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts de Bootstrap 5 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Inicializar instancia de Modal de Bootstrap para controlarla por JS
const emailModal = new bootstrap.Modal(document.getElementById('emailModal'));

// Función para preparar y abrir el modal
function abrirModalEmail(email, nombreCompleto) {
    // Resetear el formulario al abrir
    document.getElementById('emailForm').reset();
    
    // Poner los datos del destinatario
    document.getElementById('emailDestinatarioDisplay').value = nombreCompleto ? `${nombreCompleto} (${email})` : email;
    document.getElementById('emailDestinatario').value = email;
    
    // Abrir ventana flotante
    emailModal.show();
}

// Escuchar el envío del formulario mediante AJAX
document.getElementById('emailForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const btn = document.getElementById('btnEnviarMail');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');
    
    // Deshabilitar botón y activar spinner de carga
    btn.disabled = true;
    btnText.classList.add('d-none');
    btnSpinner.classList.remove('d-none');

    const formData = new FormData(this);

    fetch('enviar_mail_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Restaurar botón
        btn.disabled = false;
        btnText.classList.remove('d-none');
        btnSpinner.classList.add('d-none');

        if (data.status === 'success') {
            mostrarAlerta(data.message, 'success');
            emailModal.hide(); // Cerrar el modal
        } else {
            mostrarAlerta(data.message, 'danger');
        }
    })
    .catch(error => {
        btn.disabled = false;
        btnText.classList.remove('d-none');
        btnSpinner.classList.add('d-none');
        mostrarAlerta('Ocurrió un error inesperado al procesar la solicitud.', 'danger');
        console.error('Error:', error);
    });
});

// Función para pintar avisos flotantes (Toasts/Alerts) en la esquina de la pantalla
function mostrarAlerta(mensaje, tipo) {
    const container = document.getElementById('alertContainer');
    const wrapper = document.createElement('div');
    wrapper.innerHTML = `
        <div class="alert alert-${tipo} alert-dismissible fade show shadow" role="alert">
            <i class="bi ${tipo === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'} me-2"></i>
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    container.appendChild(wrapper);

    // Auto-eliminar la alerta después de 5 segundos
    setTimeout(() => {
        const alert = bootstrap.Alert.getOrCreateInstance(wrapper.firstElementChild);
        if(alert) alert.close();
    }, 5000);
}

function confirmarEliminar(id) {
    if(confirm('¿Seguro que deseas eliminar este usuario?')) {
        window.location.href = 'usuarios_eliminar.php?id=' + id;
    }
}
</script>
</body>
</html>