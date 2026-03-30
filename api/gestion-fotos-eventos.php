<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Manager | Rutas Rurales</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --accent: #3498db;
            --success: #27ae60;
            --danger: #e74c3c;
            --bg: #f8f9fa;
            --card-bg: #ffffff;
            --text: #333;
        }

        body { 
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; 
            background: var(--bg); 
            color: var(--text);
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Header Profesional */
        .top-bar {
            background: var(--primary);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .container { 
            max-width: 1100px; 
            margin: 20px auto; 
            padding: 0 20px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid #edf2f7;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary);
        }

        /* Selector de Entidad (Badge) */
        .entity-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: bold;
            background: #e1e8ed;
        }

        /* Grid de Categorías Mejorado */
        .categories-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); 
            gap: 12px; 
            margin: 20px 0; 
        }

        .cat-item { 
            padding: 15px 10px; 
            background: #fff; 
            border: 2px solid #edf2f7; 
            border-radius: 10px; 
            cursor: pointer; 
            text-align: center; 
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .cat-item i { font-size: 1.5rem; color: #94a3b8; }
        .cat-item span { font-size: 0.85rem; font-weight: 500; }

        .cat-item:hover { border-color: var(--accent); background: #f0f7ff; }
        .cat-item.selected { 
            border-color: var(--accent); 
            background: var(--accent); 
            color: white; 
        }
        .cat-item.selected i { color: white; }

        /* Área de Carga Estilo Moderno */
        .upload-zone {
            border: 2px dashed #cbd5e1;
            padding: 40px;
            text-align: center;
            border-radius: 12px;
            background: #fdfdfd;
            transition: border 0.3s;
            cursor: pointer;
        }
        .upload-zone:hover { border-color: var(--accent); background: #f8fbff; }

        .btn-upload {
            width: 100%;
            padding: 15px;
            background: var(--success);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: opacity 0.2s;
        }
        .btn-upload:disabled { background: #cbd5e1; cursor: not-allowed; }

        /* Galería */
        .gallery-section { margin-top: 30px; }
        .photo-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); 
            gap: 15px; 
        }
        .photo-card {
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            aspect-ratio: 1;
        }
        .photo-card img { width: 100%; height: 100%; object-fit: cover; }
        .photo-card .actions {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.4);
            display: flex;
            align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.3s;
        }
        .photo-card:hover .actions { opacity: 1; }
        .btn-del {
            background: var(--danger);
            color: white;
            border: none;
            padding: 8px;
            border-radius: 50%;
            cursor: pointer;
        }

        /* Debug & Info */
        .status-msg { padding: 15px; border-radius: 8px; margin-bottom: 20px; display: none; }
        .status-msg.info { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }

        .loading-overlay {
            position: fixed; top:0; left:0; width:100%; height:100%;
            background: rgba(255,255,255,0.8);
            display: none; align-items: center; justify-content: center; z-index: 1000;
        }
    </style>
</head>
<body>

    <div class="top-bar">
        <div>
            <i class="fas fa-images"></i> <strong>Rutas Rurales</strong> | Media Manager
        </div>
        <div id="entityDisplay" class="entity-badge">Cargando...</div>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-title"><i class="fas fa-info-circle"></i> Información del recurso</div>
            <div id="statusMsg" class="status-msg info"></div>
            <div id="resourceDetail">
                <h2 id="resName" style="margin:0; color: var(--primary);">Seleccione un recurso</h2>
                <p id="resMeta" style="color: #64748b; margin: 5px 0;"></p>
            </div>
        </div>

        <div class="card">
            <div class="card-title"><i class="fas fa-cloud-upload-alt"></i> Subir Nueva Fotografía</div>
            
            <label style="font-size: 0.9rem; font-weight: 600;">Paso 1: Selecciona la categoría</label>
            <div class="categories-grid" id="catGrid"></div>

            <label style="font-size: 0.9rem; font-weight: 600;">Paso 2: Selecciona el archivo</label>
            <div class="upload-zone" onclick="document.getElementById('fileInput').click()">
                <i class="fas fa-file-image" style="font-size: 2.5rem; color: var(--accent); margin-bottom: 10px;"></i>
                <p id="fileNameDisplay">Arrastra o haz clic para buscar imagen</p>
                <input type="file" id="fileInput" hidden onchange="handleFile(this)">
            </div>

            <button id="uploadBtn" class="btn-upload" disabled onclick="processUpload()">
                <i class="fas fa-rocket"></i> SUBIR A SERVIDOR
            </button>
        </div>

        <div class="card">
            <div class="card-title"><i class="fas fa-th"></i> Galería Organizada</div>
            <div id="galleryContainer">
                <p style="color: #94a3b8;">Cargue un recurso para gestionar sus fotos.</p>
            </div>
        </div>
    </div>

    <div id="loading" class="loading-overlay">
        <div class="card"><i class="fas fa-spinner fa-spin"></i> Procesando...</div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        // --- CONFIGURACIÓN DE ENTIDADES ---
        const config = {
            accommodation: {
                label: 'Alojamiento',
                apiPath: '../api/alojamiento-slug.php',
                categories: [
                    {id: 'fachada', name: 'Fachada', icon: 'fa-home'},
                    {id: 'interior', name: 'Interior', icon: 'fa-couch'},
                    {id: 'habitacion', name: 'Habitación', icon: 'fa-bed'},
                    {id: 'entorno', name: 'Entorno', icon: 'fa-mountain'}
                ]
            },
            cultural_event: {
                label: 'Evento Cultural',
                apiPath: '../api/evento-slug.php', // Deberás crear este o usar uno genérico
                categories: [
                    {id: 'recreacion', name: 'Recreación', icon: 'fa-mask'},
                    {id: 'publico', name: 'Ambiente', icon: 'fa-users'},
                    {id: 'detalle', name: 'Detalle', icon: 'fa-camera-retro'}
                ]
            }
        };

        let currentType = 'accommodation';
        let currentSlug = '';
        let currentId = null;
        let selectedCategory = '';
        let selectedFile = null;

        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            currentType = params.get('type') || 'accommodation';
            currentSlug = params.get('slug');

            updateUI();
            if (currentSlug) loadResourceData();
        });

        function updateUI() {
            // Actualizar Badge
            const badge = document.getElementById('entityDisplay');
            badge.textContent = config[currentType].label;
            
            // Cargar Categorías Dinámicas
            const grid = document.getElementById('catGrid');
            grid.innerHTML = '';
            config[currentType].categories.forEach(cat => {
                const div = document.createElement('div');
                div.className = 'cat-item';
                div.innerHTML = `<i class="fas ${cat.icon}"></i><span>${cat.name}</span>`;
                div.onclick = () => {
                    selectedCategory = cat.id;
                    document.querySelectorAll('.cat-item').forEach(el => el.classList.remove('selected'));
                    div.classList.add('selected');
                    checkReady();
                };
                grid.appendChild(div);
            });
        }

        async function loadResourceData() {
            try {
                const resp = await fetch(`${config[currentType].apiPath}?slug=${currentSlug}`);
                const res = await resp.json();
                if (res.success) {
                    currentId = res.data.id;
                    document.getElementById('resName').textContent = res.data.name || res.data.Nombre;
                    document.getElementById('resMeta').innerHTML = `<strong>ID:</strong> ${currentId} | <strong>Slug:</strong> ${currentSlug}`;
                    loadGallery();
                }
            } catch (e) { console.error("Error al cargar datos", e); }
        }

        function handleFile(input) {
            selectedFile = input.files[0];
            if (selectedFile) {
                document.getElementById('fileNameDisplay').innerHTML = `<strong>Archivo:</strong> ${selectedFile.name}`;
                checkReady();
            }
        }

        function checkReady() {
            document.getElementById('uploadBtn').disabled = !(selectedCategory && selectedFile && currentId);
        }

        async function processUpload() {
            document.getElementById('loading').style.display = 'flex';
            const fd = new FormData();
            fd.append('entity_type', currentType);
            fd.append('entity_id', currentId);
            fd.append('slug', currentSlug);
            fd.append('category', selectedCategory);
            fd.append('photo', selectedFile);

            try {
                // AQUÍ APUNTAREMOS AL NUEVO PHP UNIVERSAL QUE HAREMOS
                const r = await fetch('../api/upload_universal.php', { method: 'POST', body: fd });
                const data = await r.json();
                if(data.success) {
                    alert("¡Imagen subida con éxito!");
                    loadGallery();
                }
            } catch (e) { alert("Error en la subida"); }
            document.getElementById('loading').style.display = 'none';
        }

        async function loadGallery() {
            const container = document.getElementById('galleryContainer');
            container.innerHTML = 'Cargando galería...';
            // Aquí llamarías a un fetch de tus fotos basado en currentId y currentType
        }
        const entityConfig = {
    accommodations: {
        label: 'Alojamiento',
        categories: [
            {id: 'fachada', name: 'Fachada', icon: 'fa-home'},
            {id: 'salon', name: 'Salón', icon: 'fa-couch'},
            {id: 'cocina', name: 'Cocina', icon: 'fa-utensils'},
            {id: 'habitacion', name: 'Habitación', icon: 'fa-bed'},
            {id: 'bano', name: 'Baño', icon: 'fa-shower'},
            {id: 'entorno', name: 'Entorno', icon: 'fa-tree'}
        ]
    },
    cultural_events: {
        label: 'Evento Cultural',
        categories: [
            {id: 'patronales', name: 'Patronales', icon: 'fa-church'},
            {id: 'tradicionales', name: 'Tradicionales', icon: 'fa-mask'},
            {id: 'conciertos', name: 'Conciertos', icon: 'fa-music'},
            {id: 'procesiones', name: 'Procesiones', icon: 'fa-users-rays'},
            {id: 'gastronomia', name: 'Gastronomía', icon: 'fa-plate-wheat'}
        ]
    },
    places_of_interest: {
        label: 'Lugar de Interés',
        categories: [
            {id: 'monumento', name: 'Monumento', icon: 'fa-landmark'},
            {id: 'panoramica', name: 'Panorámica', icon: 'fa-mountain-sun'},
            {id: 'museo', name: 'Museo', icon: 'fa-building-columns'},
            {id: 'casco-historico', name: 'Casco Histórico', icon: 'fa-road'}
        ]
    },
    activities: {
        label: 'Actividad',
        categories: [
            {id: 'aventura', name: 'Aventura', icon: 'fa-person-hiking'},
            {id: 'taller', name: 'Taller', icon: 'fa-palette'},
            {id: 'visita-guiada', name: 'Visita Guiada', icon: 'fa-bullhorn'},
            {id: 'infantil', name: 'Infantil', icon: 'fa-child-reaching'}
        ]
    }
};
async function buscarRecurso() {
    const query = document.getElementById('searchInput').value;
    const resultsDiv = document.getElementById('searchResults');
    
    // Obtenemos el tipo actual (accommodations, cultural_events...)
    // que guardamos al cargar la página desde la URL
    const type = state.type; 

    if (query.length < 3) return;

    try {
        const response = await fetch(`../api/search_universal.php?query=${query}&type=${type}`);
        const data = await response.json();

        if (data.success && data.results.length > 0) {
            resultsDiv.innerHTML = '';
            resultsDiv.style.display = 'block';
            data.results.forEach(item => {
                const div = document.createElement('div');
                div.className = 'result-item'; // Usa tus estilos de la versión 1
                div.innerHTML = `<strong>${item.name}</strong> <small>(${item.slug})</small>`;
                div.onclick = () => seleccionarRecurso(item);
                resultsDiv.appendChild(div);
            });
        } else {
            resultsDiv.innerHTML = '<div style="padding:10px">No se encontraron resultados</div>';
        }
    } catch (e) {
        console.error("Error en la búsqueda:", e);
    }
}

function seleccionarRecurso(item) {
    state.id = item.id;
    state.slug = item.slug;
    
    // Actualizamos la UI
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('resInfo').classList.add('active');
    document.getElementById('resName').textContent = item.name;
    document.getElementById('resSlug').textContent = item.slug;
    document.getElementById('resId').textContent = item.id;
    
    checkReady(); // Habilita el botón si ya hay categoría y archivo
}
    </script>
</body>
</html>