<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderación de Alojamientos - Rutas Rurales</title>
    <?php include 'sidebar.php'; ?>
    <style>
        .moderation-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        .pending-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            gap: 20px;
        }
        .item-image {
            width: 200px;
            height: 150px;
            border-radius: 8px;
            object-fit: cover;
            background: #f0f0f0;
        }
        .item-content {
            flex: 1;
        }
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        .item-title {
            font-size: 1.3rem;
            color: #2c3e50;
            margin: 0;
        }
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-update { background: #d1ecf1; color: #0c5460; }
        .status-new { background: #d4edda; color: #155724; }
        
        /* Botón eliminar */
        .btn-delete {
            background: #6c757d;
            color: white;
        }
        .btn-delete:hover {
            background: #5a6268;
        }
        .btn-archive {
            background: #6f42c1;
            color: white;
        }
        .btn-archive:hover {
            background: #5a32a3;
        }
        
        /* Cambios destacados */
        .changes-highlight {
            background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%);
            border: 2px solid #ffc107;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
        }
        .change-field {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 5px solid #ffc107;
        }
        .change-field-name {
            background: #ffc107;
            color: #333;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
        }
        .old-value {
            background: #ffcdd2;
            border: 2px solid #f44336;
            color: #c62828;
            padding: 12px;
            border-radius: 8px;
            text-decoration: line-through;
        }
        .new-value {
            background: #c8e6c9;
            border: 2px solid #4caf50;
            color: #2e7d32;
            padding: 12px;
            border-radius: 8px;
            font-weight: bold;
        }
        .change-summary {
            background: #e3f2fd;
            border: 2px solid #2196f3;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .change-summary h4 {
            margin: 0 0 10px 0;
            color: #1565c0;
        }
        .change-summary .count {
            font-size: 2rem;
            font-weight: bold;
            color: #1976d2;
        }
        .item-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .item-description {
            color: #555;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        .item-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-approve {
            background: #28a745;
            color: white;
        }
        .btn-approve:hover {
            background: #218838;
        }
        .btn-reject {
            background: #dc3545;
            color: white;
        }
        .btn-reject:hover {
            background: #c82333;
        }
        .btn-details {
            background: #17a2b8;
            color: white;
        }
        .btn-details:hover {
            background: #138496;
        }
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            background: white;
            padding: 10px;
            border-radius: 10px;
        }
        .filter-tab {
            padding: 10px 20px;
            border: none;
            background: transparent;
            cursor: pointer;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .filter-tab.active {
            background: var(--primary-color);
            color: white;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
        }
        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
        }
        textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #eee;
            border-radius: 8px;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <h1><i class="fas fa-clipboard-check"></i> Moderación de Alojamientos</h1>
        
        <!-- Estadísticas -->
        <div class="moderation-stats" id="stats">
            <div class="stat-card">
                <div class="stat-number" id="stat-pending">-</div>
                <div class="stat-label">Pendientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="stat-approved">-</div>
                <div class="stat-label">Aprobados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="stat-rejected">-</div>
                <div class="stat-label">Rechazados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="stat-pending-changes">-</div>
                <div class="stat-label">Cambios Pendientes</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filter-tabs">
            <button class="filter-tab active" onclick="filterItems('all')">
                <i class="fas fa-list"></i> Todos
            </button>
            <button class="filter-tab" onclick="filterItems('pending')">
                <i class="fas fa-clock"></i> Nuevos Pendientes
            </button>
            <button class="filter-tab" onclick="filterItems('pending_changes')">
                <i class="fas fa-edit"></i> Cambios Pendientes
            </button>
        </div>

        <!-- Lista de items pendientes -->
        <div id="pending-list">
            <div class="empty-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Cargando alojamientos pendientes...</p>
            </div>
        </div>
    </div>

    <!-- Modal de Rechazo -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-times-circle"></i> Rechazar Alojamiento</h3>
                <button class="modal-close" onclick="closeRejectModal()">&times;</button>
            </div>
            <p>Por favor, indica el motivo del rechazo. Este mensaje será enviado al usuario.</p>
            <textarea id="rejectionReason" placeholder="Ej: Las fotos no son claras, falta información de contacto, descripción insuficiente..."></textarea>
            <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                <button class="btn" style="background: #6c757d; color: white;" onclick="closeRejectModal()">Cancelar</button>
                <button class="btn btn-reject" onclick="confirmReject()">
                    <i class="fas fa-ban"></i> Confirmar Rechazo
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Detalles -->
    <div id="detailsModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h3><i class="fas fa-info-circle"></i> Detalles del Alojamiento</h3>
                <button class="modal-close" onclick="closeDetailsModal()">&times;</button>
            </div>
            <div id="detailsContent">
                <p style="text-align: center; color: #999;">Cargando...</p>
            </div>
        </div>
    </div>

    <script>
        let currentFilter = 'all';
        let currentRejectId = null;

        // Cargar datos al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            loadPendingItems();
        });

        // Cargar items pendientes
        async function loadPendingItems() {
            try {
                const response = await fetch(`acciones_moderacion/list_pending.php?status=${currentFilter}`);
                const result = await response.json();

                if (result.success) {
                    // Actualizar estadísticas
                    document.getElementById('stat-pending').textContent = result.data.stats.pending_count || 0;
                    document.getElementById('stat-approved').textContent = result.data.stats.approved_count || 0;
                    document.getElementById('stat-rejected').textContent = result.data.stats.rejected_count || 0;
                    document.getElementById('stat-pending-changes').textContent = result.data.stats.pending_changes_count || 0;

                    // Renderizar items
                    renderItems(result.data.items);
                } else {
                    showError('Error al cargar datos: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Error de conexión al cargar datos');
            }
        }

// renderItems 
        function renderItems(items) {
            const container = document.getElementById('pending-list');

            if (items.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <h3>¡Todo al día!</h3>
                        <p>No hay alojamientos pendientes de revisión.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = items.map(item => {
                // LÓGICA DE COLORES: Si es update, ponemos fondo crema y borde naranja
                const isUpdate = item.change_type === 'update';
                const cardStyle = isUpdate 
                    ? 'border-left: 10px solid #ffc107; background-color: #fff9e6;' 
                    : 'border-left: 10px solid #28a745; background-color: #ffffff;';
                
                return `
                <div class="pending-item" style="${cardStyle} margin-bottom: 20px; display: flex; gap: 20px; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <img src="${item.photo1 || 'https://via.placeholder.com/200x150?text=Sin+Foto'}" 
                         alt="${item.name || 'Alojamiento'}"
                         class="item-image" style="width: 200px; height: 150px; object-fit: cover; border-radius: 8px;">
                    <div class="item-content" style="flex: 1;">
                        <div class="item-header" style="display: flex; justify-content: space-between; align-items: start;">
                            <div><span class="item-id">#${item.id}</span>
                                <h3 class="item-title" style="margin: 0; font-size: 1.4rem;">${item.name}</h3>
                                ${isUpdate ? '<strong style="color: #856404; font-size: 0.8rem;"><i class="fas fa-exclamation-circle"></i> CAMBIOS DETECTADOS</strong>' : ''}
                            </div>
                            <span class="status-badge ${isUpdate ? 'status-update' : 'status-new'}" style="padding: 5px 15px; border-radius: 20px; font-weight: bold;">
                                ${isUpdate ? '✏️ Actualización' : '🆕 Nuevo'}
                            </span>
                        </div>
                        <div class="item-meta" style="color: #666; margin: 10px 0;">
                            <i class="fas fa-map-marker-alt"></i> ${item.municipality}, ${item.province} | 
                            <i class="fas fa-clock"></i> Esperando hace ${item.days_pending || 0} días
                        </div>
                        <div class="item-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button class="btn btn-details" onclick="showDetails(${item.id})"><i class="fas fa-eye"></i> Revisar</button>
                            <button class="btn btn-approve" onclick="approveItem(${item.id}, '${item.name}')"><i class="fas fa-check"></i> Aprobar</button>
                            <button class="btn btn-archive" onclick="archiveItem(${item.id}, '${item.name.replace(/'/g, "\\'")}')"><i class="fas fa-archive"></i> Archivar</button>
                            <button class="btn btn-delete" style="background: #dc3545; color: white;" onclick="deleteItem(${item.id}, '${item.name.replace(/'/g, "\\'")}')"><i class="fas fa-trash"></i> BORRAR</button>
                        </div>
                    </div>
                </div>`;
            }).join('');
        }
        
        
        // Filtrar items
        function filterItems(filter) {
            currentFilter = filter;
            
            // Actualizar tabs activos
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.closest('.filter-tab').classList.add('active');

            loadPendingItems();
        }

        // Aprobar item
        async function approveItem(id, name) {
            if (!confirm(`¿Aprobar el alojamiento "${name}"?`)) {
                return;
            }

            try {
                const response = await fetch('acciones_moderacion/approve.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        accommodation_id: id,
                        admin_notes: 'Aprobado desde panel de moderación'
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ Alojamiento aprobado correctamente');
                    loadPendingItems();
                } else {
                    alert('❌ Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Error de conexión');
            }
        }

        // Mostrar modal de rechazo
        function showRejectModal(id) {
            currentRejectId = id;
            document.getElementById('rejectionReason').value = '';
            document.getElementById('rejectModal').classList.add('active');
        }

        // Cerrar modal de rechazo
        function closeRejectModal() {
            document.getElementById('rejectModal').classList.remove('active');
            currentRejectId = null;
        }

        // Confirmar rechazo
        async function confirmReject() {
            const reason = document.getElementById('rejectionReason').value.trim();

            if (!reason) {
                alert('Por favor, indica el motivo del rechazo');
                return;
            }

            try {
                const response = await fetch('acciones_moderacion/reject.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        accommodation_id: currentRejectId,
                        rejection_reason: reason,
                        admin_notes: 'Rechazado desde panel de moderación'
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ Alojamiento rechazado. El usuario recibirá una notificación.');
                    closeRejectModal();
                    loadPendingItems();
                } else {
                    alert('❌ Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Error de conexión');
            }
        }

        // Mostrar detalles
        async function showDetails(id) {
            document.getElementById('detailsModal').classList.add('active');
            document.getElementById('detailsContent').innerHTML = '<p style="text-align: center; color: #999;"><i class="fas fa-spinner fa-spin"></i> Cargando...</p>';

            try {
                const response = await fetch(`/api/moderation/get_details.php?id=${id}`);
                const result = await response.json();

                if (result.success) {
                    renderDetails(result.data);
                } else {
                    document.getElementById('detailsContent').innerHTML = '<p style="color: #dc3545;">Error: ' + result.error + '</p>';
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('detailsContent').innerHTML = '<p style="color: #dc3545;">Error de conexión</p>';
            }
        }

        // Renderizar detalles
        function renderDetails(data) {
            const acc = data.accommodation;
            const history = data.history || [];
            const pendingChanges = data.pending_changes;

            let html = '';

            // Mostrar cambios si existen
            if (pendingChanges && pendingChanges.changes && Object.keys(pendingChanges.changes).length > 0) {
                const changes = pendingChanges.changes;
                const fieldNames = {
                    'name': 'Nombre',
                    'description': 'Descripción',
                    'municipality': 'Municipio',
                    'province': 'Provincia',
                    'address': 'Dirección',
                    'capacity': 'Capacidad',
                    'price_per_night': 'Precio/noche',
                    'phone': 'Teléfono',
                    'website': 'Sitio web',
                    'meta_title': 'Meta título',
                    'meta_description': 'Meta descripción',
                    'photo1': 'Foto 1',
                    'photo2': 'Foto 2',
                    'photo3': 'Foto 3',
                    'photo4': 'Foto 4'
                };

                html += '<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 1.5rem; margin-bottom: 1.5rem; border-radius: 8px;">';
                html += '<h4 style="margin: 0 0 1rem 0; color: #856404;"><i class="fas fa-exclamation-triangle"></i> Cambios Pendientes de Aprobación</h4>';
                
                for (const [field, change] of Object.entries(changes)) {
                    const fieldName = fieldNames[field] || field;
                    const oldValue = change.old || 'Vacío';
                    const newValue = change.new || 'Vacío';
                    
                    // Truncar valores muy largos
                    const maxLength = 200;
                    const oldDisplay = oldValue.length > maxLength ? oldValue.substring(0, maxLength) + '...' : oldValue;
                    const newDisplay = newValue.length > maxLength ? newValue.substring(0, maxLength) + '...' : newValue;
                    
                    html += '<div style="margin-bottom: 1rem; padding: 1rem; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">';
                    html += `<strong style="color: #2c3e50; font-size: 1.1rem;">${fieldName}:</strong><br>`;
                    html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 0.8rem;">';
                    
                    // Columna ANTERIOR (rojo)
                    html += '<div style="background: #ffebee; padding: 1rem; border-radius: 8px; border-left: 4px solid #f44336;">';
                    html += '<div style="color: #666; font-size: 0.85rem; margin-bottom: 0.5rem; font-weight: 600;">❌ Anterior:</div>';
                    html += `<div style="color: #d32f2f; text-decoration: line-through; word-break: break-word;">${oldDisplay}</div>`;
                    html += '</div>';
                    
                    // Columna NUEVO (verde)
                    html += '<div style="background: #e8f5e9; padding: 1rem; border-radius: 8px; border-left: 4px solid #4caf50;">';
                    html += '<div style="color: #666; font-size: 0.85rem; margin-bottom: 0.5rem; font-weight: 600;">✅ Nuevo:</div>';
                    html += `<div style="color: #388e3c; font-weight: 600; word-break: break-word;">${newDisplay}</div>`;
                    html += '</div>';
                    
                    html += '</div></div>';
                }
                
                html += '</div>';
            }

            // Información completa actual
            html += `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <h4>Información Básica</h4>
                        <p><strong>Nombre:</strong> ${acc.name}</p>
                        <p><strong>Tipo:</strong> ${acc.accommodation_type || 'No especificado'}</p>
                        <p><strong>Dirección:</strong> ${acc.address}</p>
                        <p><strong>Municipio:</strong> ${acc.municipality}</p>
                        <p><strong>Provincia:</strong> ${acc.province}</p>
                        <p><strong>Capacidad:</strong> ${acc.capacity} personas</p>
                        <p><strong>Precio:</strong> ${acc.price_per_night ? acc.price_per_night + '€/noche' : 'No especificado'}</p>
                    </div>
                    <div>
                        <h4>Contacto</h4>
                        <p><strong>Teléfono:</strong> ${acc.phone || 'No especificado'}</p>
                        <p><strong>Email:</strong> ${acc.email || 'No especificado'}</p>
                        <p><strong>Web:</strong> ${acc.website ? '<a href="' + acc.website + '" target="_blank">' + acc.website + '</a>' : 'No especificado'}</p>
                        <p><strong>Instagram:</strong> ${acc.instagram_url || 'No especificado'}</p>
                        <h4 style="margin-top: 20px;">Propietario</h4>
                        <p><strong>Nombre:</strong> ${acc.first_name} ${acc.last_name}</p>
                        <p><strong>Email:</strong> ${acc.user_email}</p>
                        <p><strong>Teléfono:</strong> ${acc.user_phone || 'No especificado'}</p>
                    </div>
                </div>
                <div style="margin-bottom: 20px;">
                    <h4>Descripción</h4>
                    <p>${acc.description || 'Sin descripción'}</p>
                </div>
            `;

            if (history.length > 0) {
                html += `
                    <div style="margin-top: 20px;">
                        <h4>Historial de Moderación</h4>
                        <div style="max-height: 200px; overflow-y: auto;">
                            ${history.map(h => `
                                <div style="padding: 10px; background: #f8f9fa; margin-bottom: 10px; border-radius: 5px;">
                                    <strong>${h.action}</strong> por ${h.first_name} ${h.last_name} 
                                    <br><small>${new Date(h.created_at).toLocaleString()}</small>
                                    ${h.notes ? '<br>' + h.notes : ''}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }

            document.getElementById('detailsContent').innerHTML = html;
        }
async function deleteItem(id) {
    if (!confirm(`¿ESTÁS SEGURO? Esta acción eliminará el alojamiento ID: ${id} definitivamente de la base de datos y no se puede deshacer.`)) {
        return;
    }

    try {
        // CAMBIO DE RUTA: Apuntamos a la carpeta correcta
        const response = await fetch('acciones_moderacion/delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                accommodation_id: id
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('Alojamiento eliminado con éxito.');
            // Recargar la lista para que desaparezca el item borrado
            loadPendingItems();
        } else {
            alert('Error al eliminar: ' + result.error);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error de conexión: No se pudo encontrar "acciones_moderacion/delete.php". Asegúrate de que el archivo esté en esa carpeta.');
    }
}

        // Archivar item (ocultar de moderación sin borrar)
        async function archiveItem(id, name) {
            if (!confirm(`¿Archivar "${name}"?\n\nEl alojamiento quedará oculto en la cola de moderación pero seguirá existiendo en la base de datos (las URLs de Google no se romperán).`)) {
                return;
            }

            try {
                const response = await fetch('acciones_moderacion/archive.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ accommodation_id: id })
                });

                const result = await response.json();

                if (result.success) {
                    alert('📦 Alojamiento archivado. Ya no aparecerá en esta lista.');
                    loadPendingItems();
                } else {
                    alert('❌ Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Error de conexión');
            }
        }

        // Cerrar modal de detalles
        function closeDetailsModal() {
            document.getElementById('detailsModal').classList.remove('active');
        }

        // Mostrar error
        function showError(message) {
            document.getElementById('pending-list').innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i>
                    <h3>Error</h3>
                    <p>${message}</p>
                    <button class="btn btn-details" onclick="loadPendingItems()">Reintentar</button>
                </div>
            `;
        }

        // Cerrar modales al hacer clic fuera
        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) closeRejectModal();
        });

        document.getElementById('detailsModal').addEventListener('click', function(e) {
            if (e.target === this) closeDetailsModal();
        });
    </script>
</body>
</html>
