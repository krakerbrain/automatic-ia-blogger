<?php
/**
 * API GET - Listado de posts aprobados para un cliente
 * Uso: GET /platform/api/posts.php?cliente=slug-cliente
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // Habilitar CORS para consultas desde los sitios de clientes

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/DB.php';

if (!isset($_GET['cliente']) || empty(trim($_GET['cliente']))) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Falta el parámetro obligatorio "cliente" (slug).'
    ]);
    exit();
}

$slug = trim($_GET['cliente']);
$db = DB::getInstance();

try {
    // 1. Obtener cliente activo por su slug
    $stmtCliente = $db->prepare("SELECT id, nombre, activo FROM clientes WHERE slug = ?");
    $stmtCliente->execute([$slug]);
    $cliente = $stmtCliente->fetch();

    if (!$cliente) {
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'error' => 'Cliente no registrado.'
        ]);
        exit();
    }

    if (!$cliente['activo']) {
        http_response_code(403);
        echo json_encode([
            'ok' => false,
            'error' => 'El cliente se encuentra inactivo.'
        ]);
        exit();
    }

    // 2. Obtener los posts aprobados del cliente
    $stmtPosts = $db->prepare("
        SELECT id, tema, titulo, texto, imagen_url, fecha_creacion, fecha_aprobacion 
        FROM posts 
        WHERE cliente_id = ? AND estado = 'aprobado' 
        ORDER BY fecha_aprobacion DESC
    ");
    $stmtPosts->execute([$cliente['id']]);
    $posts = $stmtPosts->fetchAll();

    echo json_encode([
        'ok' => true,
        'cliente' => $cliente['nombre'],
        'posts' => $posts
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
