<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'oswifts');
define('DB_USER', 'app_cliente');
define('DB_PASS', 'CliPwdSegura456!');
define('UPLOAD_DIR', 'pfp/');

$mensaje = '';
$tipo_mensaje = '';
$usuarios = [];

// Obtener todos los usuarios
try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $stmt = $conn->query("
        SELECT 
            u.id_user, 
            u.username, 
            u.email, 
            u.reg_date,
            c.PROFILE_PIC_URL as profile_picture
        FROM USERS u
        LEFT JOIN CLIENTE c ON u.id_user = c.id_user
        ORDER BY u.reg_date DESC
    ");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $mensaje = 'Error al obtener usuarios: ' . $e->getMessage();
    $tipo_mensaje = 'error';
}

$conn = null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Usuarios - OSWifts</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            font-size: 14px;
        }
        
        .user-count {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-left: 10px;
        }
        
        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .user-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            text-align: center;
        }
        
        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .user-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #667eea;
            margin-bottom: 15px;
        }
        
        .user-no-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 15px;
            border: 3px solid #667eea;
        }
        
        .user-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .user-email {
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
            word-break: break-word;
        }
        
        .user-role {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .user-role.ADMIN {
            background: #ff6b6b;
            color: white;
        }
        
        .user-role.CUSTOMER {
            background: #4ecdc4;
            color: white;
        }
        
        .user-role.DRIVER {
            background: #ffa500;
            color: white;
        }
        
        .user-info {
            font-size: 12px;
            color: #999;
            margin-bottom: 15px;
        }
        
        .user-actions {
            margin-top: 15px;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .mensaje {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            background: white;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .mensaje.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .empty-state {
            background: white;
            border-radius: 12px;
            padding: 60px 30px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state-text {
            color: #666;
            font-size: 18px;
        }
        
        @media (max-width: 768px) {
            .users-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                Usuarios Registrados
                <span class="user-count"><?php echo count($usuarios); ?> usuarios</span>
            </h1>
            <p class="subtitle">Listado completo de usuarios en OSWifts</p>
        </div>
        
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <?php if (count($usuarios) > 0): ?>
            <div class="users-grid">
                <?php foreach ($usuarios as $usuario): ?>
                    <div class="user-card">
                        <?php if ($usuario['profile_picture'] && file_exists(UPLOAD_DIR . $usuario['profile_picture'])): ?>
                            <img src="<?php echo UPLOAD_DIR . htmlspecialchars($usuario['profile_picture']); ?>" 
                                 alt="<?php echo htmlspecialchars($usuario['username']); ?>" 
                                 class="user-avatar">
                        <?php else: ?>
                            <div class="user-no-avatar">
                                <?php echo strtoupper(substr($usuario['username'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="user-name">
                            <?php echo htmlspecialchars($usuario['username']); ?>
                        </div>
                        
                        <div class="user-email">
                            <?php echo htmlspecialchars($usuario['email']); ?>
                        </div>
                        
                        <div class="user-info">
                            ID: <?php echo $usuario['id_user']; ?><br>
                            Registrado: <?php echo date('d/m/Y', strtotime($usuario['reg_date'])); ?>
                        </div>
                        
                        <div class="user-actions">
                            <a href="actualizar_imagen.php?id=<?php echo $usuario['id_user']; ?>" class="btn">
                                Actualizar Imagen
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">👥</div>
                <div class="empty-state-text">
                    No hay usuarios registrados aún
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>