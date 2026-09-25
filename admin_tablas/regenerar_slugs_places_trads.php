<?php
/**
 * MIGRACION: Regenerar slugs de places_of_interest_trads
 * =======================================================
 * v1 - 25/09/2026
 *
 * MODOS:
 *   GET  sin token -> AUDITORIA (solo SELECT, sin cambios)
 *   POST con token -> EJECUCION (UPDATE masivo)
 *
 * Siempre ejecuta primero en modo AUDITORIA para revisar los cambios.
 */

include  'db.php';
require_once 'slug_lugares_helper.php';

header('Content-Type: text/html; charset=utf-8');

const TOKEN_EJECUCION = 'REGENERAR-SLUGS-2026-SECRETO'; // <-- CAMBIA ESTE TOKEN

$modoEjecucion = (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['token'] ?? '') === TOKEN_EJECUCION
);

// Obtener todos los registros con su lugar padre
$stmt = $pdo->query("
    SELECT
        t.id              AS trad_id,
        t.place_id,
        t.language_code   AS lang,
        t.slug            AS slug_actual,
        p.name            AS nombre_es,
        p.municipality,
        p.province,
        COALESCE(c.name, '') AS categoria
    FROM places_of_interest_trads t
    JOIN places_of_interest p ON t.place_id = p.id
    LEFT JOIN categories_places c ON p.category_id = c.id
    WHERE p.is_active = 1
    ORDER BY t.place_id ASC, t.language_code ASC
");
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular slugs nuevos y detectar diferencias
$diferencias   = [];
$sinCambios    = 0;
$contieneError = false;

foreach ($registros as $r) {
    try {
        $slugNuevo = generarSlugLugar(
            $r['nombre_es'],
            $r['categoria'],
            $r['municipality'],
            $r['lang']
        );
        if ($slugNuevo !== $r['slug_actual']) {
            $diferencias[] = [
                'trad_id'    => $r['trad_id'],
                'place_id'   => $r['place_id'],
                'lang'       => $r['lang'],
                'nombre'     => $r['nombre_es'],
                'categoria'  => $r['categoria'],
                'municipio'  => $r['municipality'],
                'slug_viejo' => $r['slug_actual'],
                'slug_nuevo' => $slugNuevo,
            ];
        } else {
            $sinCambios++;
        }
    } catch (Throwable $e) {
        $contieneError = true;
        $diferencias[] = [
            'trad_id'    => $r['trad_id'],
            'place_id'   => $r['place_id'],
            'lang'       => $r['lang'],
            'nombre'     => $r['nombre_es'],
            'categoria'  => $r['categoria'],
            'municipio'  => $r['municipality'],
            'slug_viejo' => $r['slug_actual'],
            'slug_nuevo' => 'ERROR: ' . $e->getMessage(),
        ];
    }
}

// Modo EJECUCION: UPDATE masivo
$actualizados  = 0;
$erroresUpdate = [];
if ($modoEjecucion && !$contieneError) {
    $updateStmt = $pdo->prepare(
        "UPDATE places_of_interest_trads SET slug = :slug WHERE id = :id"
    );
    foreach ($diferencias as $d) {
        if (str_starts_with($d['slug_nuevo'], 'ERROR')) continue;
        try {
            $updateStmt->execute([':slug' => $d['slug_nuevo'], ':id' => $d['trad_id']]);
            $actualizados++;
        } catch (PDOException $e) {
            $erroresUpdate[] = 'ID ' . $d['trad_id'] . ': ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Regenerar Slugs — places_of_interest_trads</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
.slug-old{color:#dc3545;font-family:monospace;font-size:.84em}
.slug-new{color:#198754;font-family:monospace;font-size:.84em;font-weight:bold}
.lang-badge{display:inline-block;padding:2px 7px;border-radius:4px;color:#fff;font-weight:bold;font-size:.78em}
.lang-en{background:#3b5998}.lang-fr{background:#0038a8}.lang-de{background:#e44d26}.lang-zh{background:#cc0000}
thead th{position:sticky;top:0;background:#212529;color:#fff;z-index:5}
</style>
</head>
<body class="bg-light">
<div class="container-fluid py-4">

<?php if ($modoEjecucion): ?>
<div class="alert alert-<?= $erroresUpdate ? 'warning' : 'success' ?> d-flex align-items-center mb-4">
    <i class="bi bi-<?= $erroresUpdate ? 'exclamation-triangle-fill' : 'check-circle-fill' ?> fs-4 me-2"></i>
    <div><strong>EJECUCION COMPLETADA</strong><br>
        <?= $actualizados ?> slugs actualizados.
        <?php if ($erroresUpdate): ?><br><span class="text-danger"><?= count($erroresUpdate) ?> errores.</span><?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="card shadow border-0 mb-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="bi bi-search"></i> Auditoria de Slugs — places_of_interest_trads</h4>
        <span class="badge bg-light text-dark"><?= count($registros) ?> analizados</span>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-auto"><div class="card text-center border-danger"><div class="card-body py-2 px-4">
                <div class="fs-3 fw-bold text-danger"><?= count($diferencias) ?></div>
                <div class="text-muted small">Con cambios</div>
            </div></div></div>
            <div class="col-auto"><div class="card text-center border-success"><div class="card-body py-2 px-4">
                <div class="fs-3 fw-bold text-success"><?= $sinCambios ?></div>
                <div class="text-muted small">Sin cambios</div>
            </div></div></div>
            <div class="col-auto"><div class="card text-center border-secondary"><div class="card-body py-2 px-4">
                <div class="fs-3 fw-bold"><?= count($registros) ?></div>
                <div class="text-muted small">Total</div>
            </div></div></div>
        </div>
        <?php if (!empty($diferencias)): ?>
        <div class="alert alert-warning mb-3">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <strong><?= count($diferencias) ?></strong> slugs necesitan actualizacion.
            Revisa la tabla y usa el formulario al final para confirmar el UPDATE.
        </div>
        <?php else: ?>
        <div class="alert alert-success"><i class="bi bi-check2-all me-1"></i> Todos los slugs son correctos.</div>
        <?php endif; ?>
        <a href="lugares_trads_index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($diferencias)): ?>
<div class="card shadow border-0 mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-table"></i>
            <?= $modoEjecucion ? 'Slugs actualizados' : 'Vista previa de cambios' ?>
        </h5>
    </div>
    <div class="card-body p-0" style="max-height:65vh;overflow-y:auto">
        <table class="table table-sm table-striped table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th><th>Place</th><th>Lang</th>
                    <th>Nombre (ES)</th><th>Categoria</th><th>Municipio</th>
                    <th>Slug ANTERIOR</th><th>Slug NUEVO</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($diferencias as $d): ?>
            <tr>
                <td><?= $d['trad_id'] ?></td>
                <td><?= $d['place_id'] ?></td>
                <td><span class="lang-badge lang-<?= $d['lang'] ?>"><?= strtoupper($d['lang']) ?></span></td>
                <td><?= htmlspecialchars($d['nombre']) ?></td>
                <td><small><?= htmlspecialchars($d['categoria']) ?></small></td>
                <td><small><?= htmlspecialchars($d['municipio']) ?></small></td>
                <td class="slug-old"><s><?= htmlspecialchars($d['slug_viejo']) ?></s></td>
                <td class="<?= str_starts_with($d['slug_nuevo'],'ERROR') ? 'text-danger' : 'slug-new' ?>">
                    <?= htmlspecialchars($d['slug_nuevo']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!$modoEjecucion): ?>
<div class="card shadow border-danger mb-4">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="bi bi-lightning-fill"></i> Ejecutar UPDATE masivo</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-octagon-fill me-1"></i>
            <strong>ATENCION:</strong> Actualiza <strong><?= count($diferencias) ?></strong> registros
            en produccion. Accion irreversible. Revisa la tabla antes de continuar.
        </div>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold">Token de seguridad</label>
                <input type="text" name="token" class="form-control w-auto"
                       placeholder="TOKEN_EJECUCION" required>
                <div class="form-text">Definido en la constante TOKEN_EJECUCION del script.</div>
            </div>
            <button type="submit" class="btn btn-danger"
                    onclick="return confirm('Confirmas UPDATE de <?= count($diferencias) ?> slugs en BD de produccion?')">
                <i class="bi bi-check2-all"></i> Confirmar UPDATE (<?= count($diferencias) ?> slugs)
            </button>
        </form>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php if (!empty($erroresUpdate)): ?>
<div class="alert alert-danger">
    <strong>Errores al actualizar:</strong>
    <ul class="mb-0 mt-1">
        <?php foreach ($erroresUpdate as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

</div>
</body>
</html>
