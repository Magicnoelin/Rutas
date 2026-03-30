<?php
$archivo_datos = 'mis_consultas.json';

if (!file_exists($archivo_datos)) {
    file_put_contents($archivo_datos, json_encode([]));
}

// --- LÓGICA PARA GUARDAR ---
if (isset($_POST['guardar_sql'])) {
    $datos_actuales = json_decode(file_get_contents($archivo_datos), true);
    $nueva_consulta = [
        "id" => "sql_" . uniqid(), // ID único
        "titulo" => $_POST['titulo'],
        "desc" => $_POST['descripcion'],
        "code" => $_POST['codigo_sql']
    ];
    $datos_actuales[] = $nueva_consulta;
    file_put_contents($archivo_datos, json_encode($datos_actuales));
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// --- LÓGICA PARA BORRAR ---
if (isset($_POST['borrar_id'])) {
    $id_a_borrar = $_POST['borrar_id'];
    $datos_actuales = json_decode(file_get_contents($archivo_datos), true);
    
    // Filtramos el array para quitar el que tiene ese ID
    $datos_nuevos = array_filter($datos_actuales, function($item) use ($id_a_borrar) {
        return $item['id'] !== $id_a_borrar;
    });
    
    file_put_contents($archivo_datos, json_encode(array_values($datos_nuevos)));
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$consultas = json_decode(file_get_contents($archivo_datos), true);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SQL Manager - Rutas Rurales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .sql-card { border: none; border-radius: 12px; position: relative; }
        .code-block { 
            background: #282c34; color: #abb2bf; padding: 15px; border-radius: 8px; 
            font-family: monospace; position: relative; white-space: pre-wrap;
        }
        .copy-badge { 
            position: absolute; top: 10px; right: 10px; cursor: pointer;
            background: rgba(26, 188, 156, 0.2); color: #1abc9c; border: 1px solid #1abc9c;
            padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; z-index: 10;
        }
        .btn-delete {
            position: absolute; bottom: 10px; right: 10px;
            padding: 0px 5px; font-size: 0.8rem; opacity: 0.3; transition: 0.3s;
        }
        .sql-card:hover .btn-delete { opacity: 1; }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm p-4 sticky-top" style="top: 20px;">
                <h4 class="fw-bold"><i class="bi bi-plus-circle-fill text-success"></i> Nueva Consulta</h4>
                <form method="POST">
                    <div class="mb-3 mt-3">
                        <label class="form-label small fw-bold">Título</label>
                        <input type="text" name="titulo" class="form-control" required placeholder="Ej: Listar Rutas">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Descripción</label>
                        <input type="text" name="descripcion" class="form-control" required placeholder="Ej: Muestra rutas activas">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">SQL</label>
                        <textarea name="codigo_sql" class="form-control" rows="5" required placeholder="SELECT * FROM..."></textarea>
                    </div>
                    <button type="submit" name="guardar_sql" class="btn btn-primary w-100">Guardar</button>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <h2 class="mb-4 text-dark fw-bold">Mis database SQL copy paste!</h2>
            
            <div class="row mb-3">
                <div class="col-md-8">
                    <input type="text" id="searchInput" class="form-control" placeholder="Buscar por título o descripción...">
                </div>
                <div class="col-md-4">
                    <select id="sortSelect" class="form-select">
                        <option value="newest">Más recientes</option>
                        <option value="az">A-Z</option>
                        <option value="za">Z-A</option>
                    </select>
                </div>
            </div>

                <?php if (empty($consultas)): ?>
                    <div class="alert alert-info">Aún no hay consultas. ¡Crea la primera a la izquierda!</div>
                <?php else: ?>
                    <div id="cardsContainer">
                        <?php foreach (array_reverse($consultas) as $sql): ?>
                            <div class="card sql-card shadow-sm mb-3" data-titulo="<?php echo htmlspecialchars($sql['titulo']); ?>" data-desc="<?php echo htmlspecialchars($sql['desc']); ?>">
                                <div class="card-body">
                                    <h6 class="fw-bold text-primary"><?php echo htmlspecialchars($sql['titulo']); ?></h6>
                                    <p class="small text-muted mb-2"><?php echo htmlspecialchars($sql['desc']); ?></p>
                                    
                                    <div class="code-block" id="<?php echo $sql['id']; ?>"><?php echo htmlspecialchars($sql['code']); ?></div>
                                    
                                    <button class="copy-badge" onclick="copiarTexto('<?php echo $sql['id']; ?>', event)">Copiar</button>

                                    <form method="POST" onsubmit="return confirm('¿Seguro que quieres borrar esta consulta?');">
                                        <input type="hidden" name="borrar_id" value="<?php echo $sql['id']; ?>">
                                        <button type="submit" class="btn btn-link text-danger btn-delete">
                                            <i class="bi bi-trash"></i> Borrar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
        </div>
    </div>
</div>

<script>
function copiarTexto(id, event) {
    const texto = document.getElementById(id).innerText;
    navigator.clipboard.writeText(texto).then(() => {
        const btn = event.target;
        const original = btn.innerText;
        btn.innerText = "¡Hecho!";
        btn.style.background = "#1abc9c";
        btn.style.color = "white";
        setTimeout(() => {
            btn.innerText = original;
            btn.style.background = "rgba(26, 188, 156, 0.2)";
            btn.style.color = "#1abc9c";
        }, 1500);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const sortSelect = document.getElementById('sortSelect');
    const container = document.getElementById('cardsContainer');
    if (!container) return;
    const cards = Array.from(container.querySelectorAll('.sql-card'));
    const initialOrder = [...cards];

    searchInput.addEventListener('input', () => {
        const query = searchInput.value.toLowerCase();
        cards.forEach(card => {
            const title = card.dataset.titulo.toLowerCase();
            const desc = card.dataset.desc.toLowerCase();
            if (title.includes(query) || desc.includes(query)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });

    sortSelect.addEventListener('change', () => {
        const sortBy = sortSelect.value;
        const sortedCards = [...cards];
        if (sortBy === 'az') {
            sortedCards.sort((a, b) => a.dataset.titulo.localeCompare(b.dataset.titulo));
        } else if (sortBy === 'za') {
            sortedCards.sort((a, b) => b.dataset.titulo.localeCompare(a.dataset.titulo));
        } else {
            initialOrder.forEach(card => container.appendChild(card));
            return;
        }
        sortedCards.forEach(card => container.appendChild(card));
    });
});
</script>
</body>
</html>