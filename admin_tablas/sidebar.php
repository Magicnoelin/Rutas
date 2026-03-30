<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    :root { --sidebar-width: 260px; --primary-color: #1abc9c; --dark-bg: #2c3e50; }
    body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; background: #f4f7f6; }
    .sidebar { width: var(--sidebar-width); height: 100vh; background: var(--dark-bg); color: white; position: fixed; }
    .sidebar h2 { text-align: center; font-size: 1.1rem; padding: 20px 0; color: var(--primary-color); border-bottom: 1px solid #34495e; }
    .sidebar ul { list-style: none; padding: 0; margin: 0; }
    .sidebar ul li a { color: #bdc3c7; text-decoration: none; padding: 15px 20px; display: flex; align-items: center; transition: 0.2s; }
    .sidebar ul li a:hover { background: #34495e; color: white; border-left: 4px solid var(--primary-color); }
    .sidebar ul li a i { margin-right: 12px; width: 20px; text-align: center; }
    .main-content { margin-left: var(--sidebar-width); padding: 30px; width: 100%; }
    /* Estilos para las cajas de SQL */
    .sql-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
    .sql-header { background: #34495e; color: white; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center; }
    pre { background: #282c34; color: #61afef; padding: 15px; margin: 0; overflow-x: auto; font-family: 'Consolas', monospace; }
    .btn-copy { background: var(--primary-color); border: none; color: white; padding: 5px 12px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; }
    .btn-copy:active { transform: scale(0.95); }
</style>

<nav class="sidebar">
    <h2><i class="fas fa-map-marked-alt"></i> Rutas Rurales</h2>
    <ul>
        <li><a href="https://rutasrurales.io/admin_tablas/index.php"><i class="fas fa-bed"></i> Alojamientos</a></li>
        <li><a href="https://rutasrurales.io/admin_tablas/lugares_index.php"><i class="fas fa-map-pin"></i> Lugares de Interés</a></li>
        <li><a href="https://rutasrurales.io/admin_tablas/actividades_index.php"><i class="fas fa-walking"></i> Actividades</a></li>
        <li><a href="https://rutasrurales.io/admin_tablas/eventos_index.php"><i class="fas fa-calendar-alt"></i> Eventos Culturales</a></li>
        <li style="padding: 10px 20px; font-size: 0.7rem; color: #7f8c8d; text-transform: uppercase;">Usuarios</li>
        <li><a href="https://rutasrurales.io/admin_tablas/usuarios_index.php"><i class="fas fa-users"></i> Gestión de Usuarios</a></li>
        <li><a href="https://rutasrurales.io/admin_tablas/usuarios_roles.php"><i class="fas fa-shield-alt"></i> Roles de Usuarios</a></li>
        <li style="padding: 10px 20px; font-size: 0.7rem; color: #7f8c8d; text-transform: uppercase;">Moderación</li>
        <li><a href="moderacion_alojamientos.php"><i class="fas fa-clipboard-check"></i> Revisar Alojamientos</a></li>
        <li><a href="moderacion_fotos.php?tab=suggestions"><i class="fas fa-lightbulb"></i> Lugares Sugeridos</a></li>
        <li><a href="moderacion_fotos.php?tab=photos"><i class="fas fa-images"></i> Fotos de Usuarios</a></li>
        <li style="padding: 10px 20px; font-size: 0.7rem; color: #7f8c8d; text-transform: uppercase;">Herramientas</li>
        <li><a href="sql_recurrentes.php"><i class="fas fa-code"></i> SQL Recurrentes</a></li>
        <li><a href="estructuras.php"><i class="fas fa-database"></i> Estructuras Tablas</a></li>
    </ul>
</nav>

<script>
function copiarAlPortapapeles(id) {
    const texto = document.getElementById(id).innerText;
    navigator.clipboard.writeText(texto).then(() => {
        alert("¡Copiado al portapapeles!");
    });
}
</script>