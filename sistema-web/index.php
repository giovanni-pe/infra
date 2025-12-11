<?php
// --- CONFIGURACIÓN DE BASE DE DATOS ---
// OJO: El host se llama 'db' porque así lo definimos en docker-compose
$host = 'db'; 
$db   = 'sistema_db';
$user = 'admin';
$pass = '123';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// --- 1. CONEXIÓN Y MIGRACIÓN AUTOMÁTICA ---
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Lógica de Migración: "Si no existe la tabla, créala"
    $sql_migrate = "CREATE TABLE IF NOT EXISTS tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(255) NOT NULL,
        descripcion TEXT,
        prioridad ENUM('Baja', 'Media', 'Alta') DEFAULT 'Media',
        estado ENUM('Pendiente', 'En Proceso', 'Resuelto') DEFAULT 'Pendiente',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql_migrate);
    $mensaje_sistema = "Conexión Exitosa. Base de datos sincronizada.";

} catch (\PDOException $e) {
    // Si falla, mostramos error (útil si el contenedor db aún está iniciando)
    die("<h1>Error de Conexión a Base de Datos</h1><p>Asegúrate de que el contenedor 'db' esté activo.</p><br>Error: " . $e->getMessage());
}

// --- 2. LOGICA DE NEGOCIO (GUARDAR TICKET) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_ticket'])) {
    $titulo = $_POST['titulo'];
    $descripcion = $_POST['descripcion'];
    $prioridad = $_POST['prioridad'];

    $stmt = $pdo->prepare("INSERT INTO tickets (titulo, descripcion, prioridad) VALUES (?, ?, ?)");
    $stmt->execute([$titulo, $descripcion, $prioridad]);
    
    // Recargar para evitar reenvío de formulario
    header("Location: index.php");
    exit;
}

// --- 3. OBTENER TICKETS ---
$stmt = $pdo->query("SELECT * FROM tickets ORDER BY created_at DESC");
$tickets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Tickets - Evaluación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">SGI PRO - Módulo Tickets</a>
        <span class="navbar-text text-white">
            Status DB: <span class="badge bg-success">Conectado</span>
        </span>
    </div>
</nav>

<div class="container">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Nuevo Ticket</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Título del Problema</label>
                            <input type="text" name="titulo" class="form-control" required placeholder="Ej: Error en login">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prioridad</label>
                            <select name="prioridad" class="form-select">
                                <option value="Baja">Baja</option>
                                <option value="Media" selected>Media</option>
                                <option value="Alta">Alta</option>
                            </select>
                        </div>
                        <button type="submit" name="crear_ticket" class="btn btn-primary w-100">Guardar Ticket</button>
                    </form>
                </div>
            </div>
            
            <div class="card mt-3 shadow-sm">
                <div class="card-body text-muted small">
                    <strong>Host DB:</strong> <?php echo $host; ?><br>
                    <strong>Versión PHP:</strong> <?php echo phpversion(); ?><br>
                    <strong>Server IP:</strong> <?php echo $_SERVER['SERVER_ADDR']; ?>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Listado de Tickets</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Título</th>
                                <th>Prioridad</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($tickets) > 0): ?>
                                <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td><?php echo $ticket['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($ticket['titulo']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($ticket['descripcion']); ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                            $badgeClass = match($ticket['prioridad']) {
                                                'Alta' => 'bg-danger',
                                                'Media' => 'bg-warning text-dark',
                                                'Baja' => 'bg-info',
                                            };
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo $ticket['prioridad']; ?></span>
                                    </td>
                                    <td><span class="badge bg-secondary"><?php echo $ticket['estado']; ?></span></td>
                                    <td><?php echo date('d/m H:i', strtotime($ticket['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">No hay tickets registrados aún.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>