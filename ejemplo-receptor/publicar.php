<?php
/**
 * Ejemplo Receptor - publicar.php
 * Este archivo simula el receptor en el sitio web del cliente (ej: Adri Hair Style)
 * Recibe el post vía POST, valida la API Key y lo guarda en su base de datos local.
 */
header('Content-Type: application/json; charset=utf-8');

// Constante local con la clave secreta esperada
define('LOCAL_API_KEY', 'adri_secret_site_key_2026');

// 1. Obtener Headers
$headers = getallheaders();
$apiKey = $headers['X-API-Key'] ?? $headers['x-api-key'] ?? '';

// 2. Validar API Key
if (empty($apiKey) || $apiKey !== LOCAL_API_KEY) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'error' => 'No autorizado. X-API-Key inválida o ausente.'
    ]);
    exit();
}

// 3. Obtener Payload JSON
$inputRaw = file_get_contents('php://input');
$data = json_decode($inputRaw, true);

if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'JSON inválido o cuerpo vacío.'
    ]);
    exit();
}

// Validar campos del post recibidos
$requiredFields = ['titulo', 'texto', 'imagen_url', 'nombre_autor', 'foto_autor_url'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty(trim($data[$field]))) {
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'error' => "El campo obligatorio '{$field}' está ausente o vacío."
        ]);
        exit();
    }
}

try {
    // 4. Conectar a Base de Datos local (Usamos SQLite para pruebas sin configuración)
    $dbPath = __DIR__ . '/cliente_blog.db';
    $db = new PDO("sqlite:" . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Crear tabla local si no existe
    $db->exec("
        CREATE TABLE IF NOT EXISTS local_posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo TEXT NOT NULL,
            texto TEXT NOT NULL,
            imagen_url TEXT NOT NULL,
            nombre_autor TEXT NOT NULL,
            foto_autor_url TEXT NOT NULL,
            color_primario TEXT,
            color_texto TEXT,
            fuente_titulo TEXT,
            fuente_texto TEXT,
            estado TEXT DEFAULT 'publicado',
            fecha_publicacion DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Insertar post
    $stmt = $db->prepare("
        INSERT INTO local_posts (
            titulo, texto, imagen_url, nombre_autor, foto_autor_url, 
            color_primario, color_texto, fuente_titulo, fuente_texto
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['titulo'],
        $data['texto'],
        $data['imagen_url'],
        $data['nombre_autor'],
        $data['foto_autor_url'],
        $data['color_primario'] ?? '#E8B4B8',
        $data['color_texto'] ?? '#2C2C2A',
        $data['fuente_titulo'] ?? 'Georgia, serif',
        $data['fuente_texto'] ?? 'system-ui, sans-serif'
    ]);

    $insertedId = $db->lastInsertId();

    echo json_encode([
        'ok' => true,
        'id' => $insertedId,
        'mensaje' => 'Post recibido y insertado exitosamente en el sitio local.'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Error en la base de datos local del cliente: ' . $e->getMessage()
    ]);
}
