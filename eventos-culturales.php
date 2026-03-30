<?php
// 1. Configuración de la base de datos
$host = "localhost";
$db   = "u412199647_Rutas";
$user = "u412199647_olgamarin";
$pass = "Rutas5Rurales7$";
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Error de conexión: " . $e->getMessage());
}

// 2. Consulta de eventos (Activos, aprobados y futuros)
// 2. Consulta mejorada: Trae los más cercanos a hoy (pasados o futuros)
$stmt = $pdo->query("SELECT *, 
                     ABS(DATEDIFF(start_date, CURDATE())) as cercania 
                     FROM cultural_events 
                     WHERE is_active = 1 
                     AND moderation_status = 'approved'
                     ORDER BY cercania ASC, RAND() 
                     LIMIT 12");
$eventos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Cultural Soria - Rutas Rurales</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="menu_images/Favicon.png" type="image/png">
</head>
<body>

    <?php include 'header.php'; ?>

    <section class="hero-eventos">
        <div class="hero-overlay"></div>
        <div class="hero-content-eventos">
            <h1><i class="fas fa-calendar-alt"></i> Eventos Culturales en Soria</h1>
            <p class="hero-subtitle">Agenda dinámica actualizada automáticamente</p>
        </div>
    </section>

    <section class="section-eventos">
        <div class="container">
            <h2 class="section-title"><i class="fas fa-star"></i> Próximas Citas</h2>
            
            <div class="grid-eventos">
                <?php if (empty($eventos)): ?>
                    <div style="text-align: center; grid-column: 1/-1; padding: 50px;">
                        <i class="fas fa-calendar-times" style="font-size: 3rem; color: #ccc;"></i>
                        <p style="margin-top: 20px;">No hay eventos programados para los próximos días.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($eventos as $evento): ?>
                        <div class="card-evento">
                            <div class="evento-fecha">
                                <?php 
                                    $fecha = strtotime($evento['start_date']);
                                    $dia = date('d', $fecha);
                                    $meses = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
                                    $mes = $meses[date('n', $fecha)-1];
                                ?>
                                <span class="dia"><?php echo $dia; ?></span>
                                <span class="mes"><?php echo $mes; ?></span>
                            </div>

                            <div class="evento-imagen">
                                <?php 
                                    $imagen = !empty($evento['poster_image']) ? $evento['poster_image'] : 'https://images.unsplash.com/photo-1514320291840-2e0a9bf2a9ae?w=400';
                                ?>
                                <img src="<?php echo $imagen; ?>" alt="<?php echo htmlspecialchars($evento['name']); ?>">
                            </div>

                            <div class="card-content">
                                <div class="evento-categoria">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($evento['target_audience'] ?? 'Cultura'); ?>
                                </div>
                                
                                <h3><?php echo htmlspecialchars($evento['name']); ?></h3>
                                
                                <p class="location">
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?php echo htmlspecialchars($evento['venue_name'] . " - " . $evento['municipality']); ?>
                                </p>
                                
                                <p class="evento-descripcion">
                                    <?php 
                                        $desc = htmlspecialchars($evento['short_description']);
                                        echo (strlen($desc) > 90) ? substr($desc, 0, 90) . "..." : $desc; 
                                    ?>
                                </p>
                                
                                <div class="evento-footer">
                                    <span class="precio">
                                        <?php echo ($evento['is_free'] == 1) ? 'Gratis' : $evento['ticket_price'] . '€'; ?>
                                    </span>
                                    <a href="evento.php?id=<?php echo $evento['slug']; ?>" class="btn-evento">Más info</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div style="text-align: center; margin-top: 3rem;">
                <a href="#" class="btn-primary">Cargar más eventos</a>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>

</body>
</html>