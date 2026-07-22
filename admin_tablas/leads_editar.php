<?php
include 'db.php';

$mensaje = '';
$tipo_mensaje = '';
$lead = null;

// 1. Verificar que nos llega un ID válido por la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: leads_index.php");
    exit;
}

$id = intval($_GET['id']);

// 2. Procesar la actualización si se envía el formulario (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre        = trim($_POST['nombre'] ?? '');
    $contacto      = trim($_POST['contacto'] ?? '');
    $tipo_contacto = $_POST['tipo_contacto'] ?? 'telefono';
    $origen        = $_POST['origen'] ?? 'grupo_whatsapp';
    $estado        = $_POST['estado'] ?? 'nuevo';
    $notas         = trim($_POST['notas'] ?? '');

    if (empty($nombre) || empty($contacto)) {
        $mensaje = "Por favor, rellena los campos obligatorios (Nombre y Contacto).";
        $tipo_mensaje = "danger";
    } else {
        try {
            $sql = "UPDATE leads 
                    SET nombre = :nombre, 
                        contacto = :contacto, 
                        tipo_contacto = :tipo_contacto, 
                        origen = :origen, 
                        estado = :estado, 
                        notas = :notas 
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $resultado = $stmt->execute([
                ':nombre'        => $nombre,
                ':contacto'      => $contacto,
                ':tipo_contacto' => $tipo_contacto,
                ':origen'        => $origen,
                ':estado'        => $estado,
                ':notas'         => $notas ?: null,
                ':id'            => $id
            ]);

            if ($resultado) {
                header("Location: leads_index.php?status=updated");
                exit;
            } else {
                $mensaje = "No se pudieron guardar los cambios.";
                $tipo_mensaje = "danger";
            }
        } catch (PDOException $e) {
            $mensaje = "Error en la base de datos: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
}

// 3. Recuperar los datos actuales del Lead para pintarlos en el formulario
try {
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
    $stmt->execute([$id]);
    $lead = $stmt->fetch();

    if (!$lead) {
        header("Location: leads_index.php");
        exit;
    }
} catch (PDOException $e) {
    die("Error al cargar el lead: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Lead - Rutas Rurales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light p-4">
    <div class="container" style="max-width: 700px;">
        
        <div class="mb-3">
            <a href="leads_index.php" class="text-decoration-none text-muted">
                <i class="bi bi-arrow-left"></i> Volver al listado de Leads
            </a>
        </div>

        <div class="card shadow border-0">
            <div class="card-header bg-primary text-white py-3">
                <h4 class="mb-0"><i class="bi bi-pencil-square"></i> Editar Lead #<?= $lead['id'] ?></h4>
                <p class="mb-0 small text-white-50">Modifica los datos del prospecto o actualiza su estado de gestión comercial.</p>
            </div>
            
            <div class="card-body p-4">
                
                <?php if (!empty($mensaje)): ?>
                    <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= $mensaje ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="leads_editar.php?id=<?= $lead['id'] ?>" method="POST">
                    
                    <!-- Campo: Nombre -->
                    <div class="mb-3">
                        <label for="nombre" class="form-label fw-bold">Nombre del Propietario o Alojamiento <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" id="nombre" value="<?= htmlspecialchars($lead['nombre']) ?>" required>
                    </div>

                    <!-- Campos: Tipo de contacto y Dato de contacto -->
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label for="tipo_contacto" class="form-label fw-bold">Vía de Contacto</label>
                            <select class="form-select" name="tipo_contacto" id="tipo_contacto" onchange="actualizarPlaceholder()">
                                <option value="telefono" <?= $lead['tipo_contacto'] === 'telefono' ? 'selected' : '' ?>>WhatsApp / Teléfono</option>
                                <option value="email" <?= $lead['tipo_contacto'] === 'email' ? 'selected' : '' ?>>Correo Electrónico</option>
                                <option value="instagram" <?= $lead['tipo_contacto'] === 'instagram' ? 'selected' : '' ?>>Instagram</option>
                                <option value="otro" <?= $lead['tipo_contacto'] === 'otro' ? 'selected' : '' ?>>Otro medio</option>
                            </select>
                        </div>
                        <div class="col-md-7 mb-3">
                            <label for="contacto" class="form-label fw-bold">Dato de Contacto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="contacto" id="contacto" value="<?= htmlspecialchars($lead['contacto']) ?>" required>
                            <div class="form-text text-muted" id="contactoHelp">Dato de localización del lead.</div>
                        </div>
                    </div>

                    <!-- Campos: Origen y Estado -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="origen" class="form-label fw-bold">Canal o Campaña de Origen</label>
                            <select class="form-select" name="origen" id="origen">
                                <option value="grupo_whatsapp" <?= $lead['origen'] === 'grupo_whatsapp' ? 'selected' : '' ?>>Grupo de WhatsApp</option>
                                <option value="instagram_direct" <?= $lead['origen'] === 'instagram_direct' ? 'selected' : '' ?>>Instagram (MD)</option>
                                <option value="email_frio" <?= $lead['origen'] === 'email_frio' ? 'selected' : '' ?>>Email Frío</option>
                                <option value="llamada" <?= $lead['origen'] === 'llamada' ? 'selected' : '' ?>>Llamada de Teléfono</option>
                                <option value="recomendado" <?= $lead['origen'] === 'recomendado' ? 'selected' : '' ?>>Recomendado / Boca a boca</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="estado" class="form-label fw-bold">Estado de la Gestión</label>
                            <select class="form-select" name="estado" id="estado">
                                <option value="nuevo" <?= $lead['estado'] === 'nuevo' ? 'selected' : '' ?>>Nuevo (Sin contactar)</option>
                                <option value="contactado" <?= $lead['estado'] === 'contactado' ? 'selected' : '' ?>>Ya contactado</option>
                                <option value="interesado" <?= $lead['estado'] === 'interesado' ? 'selected' : '' ?>>Interesado</option>
                                <option value="descartado" <?= $lead['estado'] === 'descartado' ? 'selected' : '' ?>>Descartado</option>
                            </select>
                        </div>
                    </div>

                    <!-- Campo: Notas -->
                    <div class="mb-4">
                        <label for="notas" class="form-label fw-bold">Notas Privadas / Acciones realizadas</label>
                        <textarea class="form-control" name="notas" id="notas" rows="4"><?= htmlspecialchars($lead['notas'] ?? '') ?></textarea>
                    </div>

                    <!-- Botones -->
                    <div class="d-flex justify-content-end gap-2">
                        <a href="leads_index.php" class="btn btn-light border">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save"></i> Guardar Cambios
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function actualizarPlaceholder() {
        const tipo = document.getElementById('tipo_contacto').value;
        const contactoHelp = document.getElementById('contactoHelp');

        if (tipo === 'telefono') {
            contactoHelp.textContent = 'Usa el número de teléfono con prefijo si es posible para facilitar el auto-emparejamiento.';
        } else if (tipo === 'email') {
            contactoHelp.textContent = 'Introduce una dirección de correo válida para que el sistema la cruce con la cuenta del usuario registrado.';
        } else if (tipo === 'instagram') {
            contactoHelp.textContent = 'Introduce el nombre de usuario de Instagram (con o sin @).';
        } else {
            contactoHelp.textContent = 'Especifica cómo localizar al lead.';
        }
    }
    // Ejecutar al cargar para que ponga el texto de ayuda correcto inicialmente
    actualizarPlaceholder();
    </script>
</body>
</html>