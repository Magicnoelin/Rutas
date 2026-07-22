<?php
include 'db.php';

$mensaje = '';
$tipo_mensaje = '';

// Procesar el formulario cuando se envía por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre        = trim($_POST['nombre'] ?? '');
    $contacto      = trim($_POST['contacto'] ?? '');
    $tipo_contacto = $_POST['tipo_contacto'] ?? 'telefono';
    $origen        = $_POST['origen'] ?? 'grupo_whatsapp';
    $estado        = $_POST['estado'] ?? 'nuevo';
    $notas         = trim($_POST['notas'] ?? '');

    // Validación básica de campos obligatorios
    if (empty($nombre) || empty($contacto)) {
        $mensaje = "Por favor, rellena los campos obligatorios (Nombre y Contacto).";
        $tipo_mensaje = "danger";
    } else {
        try {
            // Consulta de inserción segura con marcadores de posición
            $sql = "INSERT INTO leads (nombre, contacto, tipo_contacto, origen, estado, notas) 
                    VALUES (:nombre, :contacto, :tipo_contacto, :origen, :estado, :notas)";
            
            $stmt = $pdo->prepare($sql);
            $resultado = $stmt->execute([
                ':nombre'        => $nombre,
                ':contacto'      => $contacto,
                ':tipo_contacto' => $tipo_contacto,
                ':origen'        => $origen,
                ':estado'        => $estado,
                ':notas'         => $notas ?: null // Si está vacío se guarda como NULL
            ]);

            if ($resultado) {
                // Redireccionar al listado de leads con aviso de éxito
                header("Location: leads_index.php?status=success");
                exit;
            } else {
                $mensaje = "No se pudo registrar el lead en la base de datos.";
                $tipo_mensaje = "danger";
            }
        } catch (PDOException $e) {
            $mensaje = "Error en la base de datos: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Lead - Rutas Rurales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light p-4">
    <div class="container" style="max-width: 700px;">
        
        <!-- Migas de pan / Enlace para volver -->
        <div class="mb-3">
            <a href="leads_index.php" class="text-decoration-none text-muted">
                <i class="bi bi-arrow-left"></i> Volver al listado de Leads
            </a>
        </div>

        <div class="card shadow border-0">
            <div class="card-header bg-success text-white py-3">
                <h4 class="mb-0"><i class="bi bi-funnel"></i> Registrar Nuevo Lead</h4>
                <p class="mb-0 small text-white-50">Introduce los datos de captación del propietario o alojamiento para llevar el seguimiento.</p>
            </div>
            
            <div class="card-body p-4">
                
                <!-- Mostrar alertas si ocurren errores -->
                <?php if (!empty($mensaje)): ?>
                    <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= $mensaje ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="leads_nuevo.php" method="POST">
                    
                    <!-- Fila 1: Nombre o Alojamiento -->
                    <div class="mb-3">
                        <label for="nombre" class="form-label fw-bold">Nombre del Propietario o Alojamiento <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" id="nombre" placeholder="Ej. Juan de Cabañas Vallecino" required>
                    </div>

                    <!-- Fila 2: Tipo de contacto y Dato de contacto -->
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label for="tipo_contacto" class="form-label fw-bold">Vía de Contacto</label>
                            <select class="form-select" name="tipo_contacto" id="tipo_contacto" onchange="actualizarPlaceholder()">
                                <option value="telefono">WhatsApp / Teléfono</option>
                                <option value="email">Correo Electrónico</option>
                                <option value="instagram">Instagram</option>
                                <option value="otro">Otro medio</option>
                            </select>
                        </div>
                        <div class="col-md-7 mb-3">
                            <label for="contacto" class="form-label fw-bold">Dato de Contacto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="contacto" id="contacto" placeholder="Ej. +34 600 000 000" required>
                            <div class="form-text text-muted" id="contactoHelp">Usa el número de teléfono con prefijo si es posible para facilitar el auto-emparejamiento.</div>
                        </div>
                    </div>

                    <!-- Fila 3: Canal de Origen y Estado Inicial -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="origen" class="form-label fw-bold">Canal o Campaña de Origen</label>
                            <select class="form-select" name="origen" id="origen">
                                <option value="grupo_whatsapp">Grupo de WhatsApp</option>
                                <option value="instagram_direct">Instagram (MD)</option>
                                <option value="email_frio">Email Frío</option>
                                <option value="llamada">Llamada de Teléfono</option>
                                <option value="recomendado">Recomendado / Boca a boca</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="estado" class="form-label fw-bold">Estado de la Gestión</label>
                            <select class="form-select" name="estado" id="estado">
                                <option value="nuevo">Nuevo (Sin contactar)</option>
                                <option value="contactado">Ya contactado</option>
                                <option value="interesado">Interesado</option>
                                <option value="descartado">Descartado</option>
                            </select>
                        </div>
                    </div>

                    <!-- Fila 4: Notas de seguimiento -->
                    <div class="mb-4">
                        <label for="notas" class="form-label fw-bold">Notas Privadas / Acciones realizadas</label>
                        <textarea class="form-control" name="notas" id="notas" rows="4" placeholder="Escribe aquí detalles útiles, por ejemplo: 'Le escribí el lunes por WhatsApp, me dijo que se registraría el fin de semana cuando tenga las fotos listas...'"></textarea>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="d-flex justify-content-end gap-2">
                        <a href="leads_index.php" class="btn btn-light border">Cancelar</a>
                        <button type="submit" class="btn btn-success px-4">
                            <i class="bi bi-save"></i> Guardar Lead
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Pequeño script dinámico para cambiar el ejemplo (placeholder) según la vía de contacto elegida
    function actualizarPlaceholder() {
        const tipo = document.getElementById('tipo_contacto').value;
        const contactoInput = document.getElementById('contacto');
        const contactoHelp = document.getElementById('contactoHelp');

        if (tipo === 'telefono') {
            contactoInput.placeholder = 'Ej. +34 600 000 000';
            contactoHelp.textContent = 'Usa el número de teléfono con prefijo si es posible para facilitar el auto-emparejamiento.';
        } else if (tipo === 'email') {
            contactoInput.placeholder = 'Ej. ejemplo@alojamiento.com';
            contactoHelp.textContent = 'Introduce una dirección de correo válida para que el sistema la cruce con la cuenta del usuario registrado.';
        } else if (tipo === 'instagram') {
            contactoInput.placeholder = 'Ej. @cabanasyvallecino';
            contactoHelp.textContent = 'Introduce el nombre de usuario de Instagram (con o sin @).';
        } else {
            contactoInput.placeholder = 'Cualquier otro dato de contacto';
            contactoHelp.textContent = 'Especifica cómo localizar al lead.';
        }
    }
    </script>
</body>
</html>