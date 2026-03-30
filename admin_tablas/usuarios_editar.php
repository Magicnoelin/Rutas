<?php 
include 'db.php';
$id = $_GET['id'] ?? die("ID no proporcionado");
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$u = $stmt->fetch();
if (!$u) die("Usuario no encontrado");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario: <?= htmlspecialchars($u['username']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .tab-content { border: 1px solid #dee2e6; border-top: none; background: #fff; padding: 25px; min-height: 450px; border-radius: 0 0 8px 8px; }
        .nav-tabs .nav-link { cursor: pointer; color: #555; font-weight: 500; }
        .nav-tabs .nav-link.active { border-top: 3px solid #0d6efd !important; color: #0d6efd !important; background: #fff !important; }
        .sticky-header { position: sticky; top: 0; z-index: 1000; background: #f8f9fa; padding: 10px 0; border-bottom: 1px solid #ddd; }
        .section-title { background: #f8f9fa; padding: 8px 12px; border-left: 4px solid #0d6efd; font-weight: bold; margin: 20px 0; color: #0d6efd; }
    </style>
</head>
<body class="bg-light px-4">

<div class="container mt-3 pb-5">
    <form action="usuarios_guardar.php" method="POST">
        <input type="hidden" name="id" value="<?= $u['id'] ?>">
        
        <div class="sticky-header mb-4">
            <div class="d-flex justify-content-between align-items-center px-3">
                <div>
                    <a href="usuarios_index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
                    <span class="ms-2 fw-bold">EDITAR USUARIO: <?= htmlspecialchars($u['username']) ?></span>
                </div>
                <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm">GUARDAR USUARIO</button>
            </div>
        </div>

        <ul class="nav nav-tabs" id="userTabs">
            <li class="nav-item"><button class="nav-link active" type="button" data-bs-target="#panel-perfil">👤 Perfil Personal</button></li>
            <li class="nav-item"><button class="nav-link" type="button" data-bs-target="#panel-negocio">🏢 Negocio / Verificación</button></li>
            <li class="nav-item"><button class="nav-link" type="button" data-bs-target="#panel-sistema">⚙️ Sistema y Seguridad</button></li>
        </ul>

        <div class="tab-content shadow-sm">
            <div class="tab-pane fade show active" id="panel-perfil">
                <div class="row g-3">
                    <div class="col-md-4"><label class="fw-bold">Username</label><input type="text" name="username" class="form-control" value="<?= htmlspecialchars($u['username']) ?>"></div>
                    <div class="col-md-4"><label class="fw-bold">Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($u['email']) ?>"></div>
                    <div class="col-md-4"><label class="fw-bold text-danger">Password Hash (Solo lectura)</label><input type="text" class="form-control bg-light" value="<?= htmlspecialchars($u['password_hash']) ?>" readonly></div>
                    
                    <div class="col-md-4"><label class="fw-bold">Nombre</label><input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($u['first_name']) ?>"></div>
                    <div class="col-md-4"><label class="fw-bold">Apellidos</label><input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($u['last_name']) ?>"></div>
                    <div class="col-md-4"><label class="fw-bold">Teléfono</label><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($u['phone']) ?>"></div>
                    
                    <div class="col-12"><label class="fw-bold">URL Avatar</label><input type="text" name="avatar_url" class="form-control" value="<?= htmlspecialchars($u['avatar_url']) ?>"></div>
                </div>
            </div>

            <div class="tab-pane fade" id="panel-negocio">
                <div class="section-title">INFORMACIÓN PROFESIONAL</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="fw-bold">Nombre Comercial</label>
                        <input type="text" name="business_name" class="form-control" value="<?= htmlspecialchars($u['business_name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold">Tipo de Usuario</label>
                        <select name="user_type" class="form-select">
                            <?php $types = ['turista', 'alojamiento', 'promotor_eventos']; foreach($types as $t): ?>
                                <option value="<?= $t ?>" <?= $u['user_type']==$t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="fw-bold">Descripción del Negocio</label>
                        <textarea name="business_description" class="form-control" rows="4"><?= htmlspecialchars($u['business_description']) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold">Estado de Verificación</label>
                        <select name="verification_status" class="form-select">
                            <option value="pending" <?= $u['verification_status']=='pending' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="verified" <?= $u['verification_status']=='verified' ? 'selected' : '' ?>>Verificado</option>
                            <option value="rejected" <?= $u['verification_status']=='rejected' ? 'selected' : '' ?>>Rechazado</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold">Nivel de Suscripción</label>
                        <select name="subscription_level" class="form-select text-primary fw-bold">
                            <option value="basic" <?= $u['subscription_level']=='basic' ? 'selected' : '' ?>>Basic</option>
                            <option value="premium" <?= $u['subscription_level']=='premium' ? 'selected' : '' ?>>Premium ⭐</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="panel-sistema">
                <div class="section-title">CONTROL INTERNO</div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="fw-bold">Estado de Cuenta</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= $u['status']=='active' ? 'selected' : '' ?>>Activo</option>
                            <option value="inactive" <?= $u['status']=='inactive' ? 'selected' : '' ?>>Inactivo</option>
                            <option value="suspended" <?= $u['status']=='suspended' ? 'selected' : '' ?>>Suspendido</option>
                            <option value="deleted" <?= $u['status']=='deleted' ? 'selected' : '' ?>>Eliminado</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold">Email Verificado</label>
                        <select name="email_verified" class="form-select">
                            <option value="1" <?= $u['email_verified']==1 ? 'selected' : '' ?>>SÍ</option>
                            <option value="0" <?= $u['email_verified']==0 ? 'selected' : '' ?>>NO</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold">Términos Aceptados</label>
                        <select name="terms_accepted" class="form-select">
                            <option value="1" <?= $u['terms_accepted']==1 ? 'selected' : '' ?>>SÍ</option>
                            <option value="0" <?= $u['terms_accepted']==0 ? 'selected' : '' ?>>NO</option>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="small text-muted">Token de Verificación</label><input type="text" name="verification_token" class="form-control form-control-sm" value="<?= $u['verification_token'] ?>"></div>
                    <div class="col-md-6"><label class="small text-muted">Token de Reset</label><input type="text" name="reset_token" class="form-control form-control-sm" value="<?= $u['reset_token'] ?>"></div>
                    
                    <div class="col-md-6"><label class="small text-muted">Último Login</label><input type="text" class="form-control form-control-sm" value="<?= $u['last_login'] ?>" readonly></div>
                    <div class="col-md-3"><label class="small text-muted">Creado el</label><input type="text" class="form-control form-control-sm" value="<?= $u['created_at'] ?>" readonly></div>
                    <div class="col-md-3"><label class="small text-muted">Actualizado el</label><input type="text" class="form-control form-control-sm" value="<?= $u['updated_at'] ?>" readonly></div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const tabs = document.querySelectorAll('#userTabs button');
    const contents = document.querySelectorAll('.tab-pane');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const target = this.getAttribute('data-bs-target');
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            contents.forEach(c => {
                c.classList.remove('show', 'active');
                c.style.display = 'none';
            });
            const activeContent = document.querySelector(target);
            activeContent.classList.add('show', 'active');
            activeContent.style.display = 'block';
        });
    });
    // Inicializar visualmente
    contents.forEach((c, i) => { if(i !== 0) c.style.display = 'none'; });
});
</script>
</body>
</html>