<?php
/**
 * Endpoint AJAX para obtener sugerencias pendientes de un cliente
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/DB.php';

// Verificar autenticación
if (!isset($_SESSION['role'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
    exit();
}

$cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : 0;

// Si el rol es cliente, forzar su propio cliente_id (no pueden consultar datos de otros)
if ($_SESSION['role'] === 'cliente') {
    $cliente_id = $_SESSION['cliente_id'];
}

if (!$cliente_id) {
    echo json_encode(['status' => 'error', 'message' => 'ID de cliente no especificado.']);
    exit();
}

try {
    $db = DB::getInstance();
    
    // Obtener sugerencias pendientes (Últimas 10)
    $stmt = $db->prepare("SELECT id, tema FROM sugerencias_temas WHERE cliente_id = ? AND estado = 'pendiente' ORDER BY fecha_sugerencia DESC LIMIT 10");
    $stmt->execute([$cliente_id]);
    $sugerencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'sugerencias' => $sugerencias
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al obtener sugerencias: ' . $e->getMessage()
    ]);
}
