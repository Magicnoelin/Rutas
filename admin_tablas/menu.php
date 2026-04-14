<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Panel PHP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            display: flex;
        }

        /* Estilo del Menú Lateral */
        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: #2c3e50;
            color: white;
            padding-top: 20px;
        }

        .sidebar h2 {
            text-align: center;
            font-size: 1.2rem;
            margin-bottom: 30px;
            color: #1abc9c;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            padding: 15px 20px;
            transition: 0.3s;
        }

        .sidebar ul li:hover {
            background-color: #34495e;
            border-left: 4px solid #1abc9c;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        /* Espacio para los iconos */
        .sidebar ul li a i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }

        .content {
            padding: 20px;
            flex-grow: 1;
        }
    </style>
</head>
<body>

    <nav class="sidebar">
        <h2>Rutas</h2>
        <ul>
            <li><a href="index.php"><i class="fas fa-home"></i> Inicio</a></li>
            <li><a href="usuarios_index.php"><i class="fas fa-users"></i> Gestión Usuarios</a></li>
             <li><a href="gestor_recursos.php"><i class="fas fa-users"></i> Gestor Recursos</a></li>
            <li><a href="reportes.php"><i class="fas fa-chart-bar"></i> Reportes</a></li>
            <li><a href="configuracion.php"><i class="fas fa-cog"></i> Ajustes</a></li>
            <li><a href="logout.php" style="color: #e74c3c;"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
        </ul>
    </nav>

    <div class="content">
        <h1>Bienvenido al Panel</h1>
        <p>Selecciona una opción del menú para comenzar.</p>
    </div>
    <nav class="sidebar">
    <h2>Rutas Rurales Admin</h2>
    <ul>
        <li><a href="https://rutasrurales.io/admin_tablas/index.php"><i class="fas fa-bed"></i> Alojamientos</a></li>
        <li><a href="https://rutasrurales.io/admin_tablas/lugares_index.php"><i class="fas fa-map-marked-alt"></i> Lugares de Interés</a></li>
        <li><a href="https://rutasrurales.io/admin_tablas/actividades_index.php"><i class="fas fa-walking"></i> Actividades</a></li>
        <li><a href="https://rutasrurales.io/admin_tablas/eventos_index.php"><i class="fas fa-calendar-alt"></i> Eventos Culturales</a></li>
        
        <hr style="border: 0.5px solid #34495e; margin: 15px 0;">

         <li><a href="https://rutasrurales.io/admin_tablas/sql_manager.php"><i class="fas fa-code"></i>sql_manager</a></li>
          <li><a href="https://rutasrurales.io/admin_tablas/moderacion_alojamientos.php"><i class="fas fa-code"></i>moderacion_alojamientos</a></li>
            <li><a href="https://rutasrurales.io/admin_tablas/moderacion_lugares.php"><i class="fas fa-code"></i>moderacion_lugares</a></li>
            <li><a href="https://rutasrurales.io/admin_tablas/moderacion_lugares.php?type=activity"><i class="fas fa-walking"></i>Moderación de Actividades</a></li>
            <li><a href="https://rutasrurales.io/admin_tablas/moderacion_fotos.php"><i class="fas fa-images"></i>Fotos y Lugares Sugeridos</a></li>
        
        <li><a href="cultural_events_trads_index.php"><i class="fas fa-table"></i> Traducciones eventos</a></li>
    </ul>
</nav>

</body>
</html>