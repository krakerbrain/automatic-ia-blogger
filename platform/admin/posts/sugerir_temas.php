<?php
/**
 * Endpoint AJAX para sugerir temas usando Gemini
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/DB.php';
require_once __DIR__ . '/../../lib/GeminiClient.php';

// Verificar autenticación
if (!isset($_SESSION['role'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
    exit();
}

// Validar solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$cliente_id = intval($input['cliente_id'] ?? 0);

// Si el rol es cliente, forzar su propio cliente_id (no pueden sugerir para otros)
if ($_SESSION['role'] === 'cliente') {
    $cliente_id = $_SESSION['cliente_id'];
}

if (!$cliente_id) {
    echo json_encode(['status' => 'error', 'message' => 'ID de cliente no especificado.']);
    exit();
}

try {
    $db = DB::getInstance();
    $stmt = $db->prepare("SELECT * FROM clientes WHERE id = ? AND activo = 1");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch();

    if (!$cliente) {
        echo json_encode(['status' => 'error', 'message' => 'Cliente no encontrado o inactivo.']);
        exit();
    }

    require_once __DIR__ . '/../../lib/PromptTemplates.php';

    // Construir el prompt para Gemini usando PromptTemplates
    $prompt = PromptTemplates::getTopicSuggestionsPrompt($cliente);
    $systemInstruction = PromptTemplates::getTopicSuggestionsSystemInstruction();

    // Generar temas
    $result = GeminiClient::generateJson($prompt, $systemInstruction);

    if (!isset($result['temas']) || !is_array($result['temas'])) {
        throw new Exception("La respuesta de Gemini no contiene la lista de temas esperada.");
    }

    $temas = array_slice($result['temas'], 0, 5); // Garantizar máximo 5
    $sugerencias = [];
    
    // Insertar en sugerencias_temas
    $insertStmt = $db->prepare("INSERT INTO sugerencias_temas (cliente_id, tema, angulo_contextual, estado, fecha_sugerencia) VALUES (?, ?, ?, 'pendiente', NOW())");
    foreach ($temas as $t) {
        $anguloJson = json_encode([
            'consejo_practico' => $t['consejo_practico'] ?? '',
            'servicio_a_promocionar' => $t['servicio_a_promocionar'] ?? ''
        ], JSON_UNESCAPED_UNICODE);
        
        $insertStmt->execute([$cliente_id, $t['titulo_sugerido'], $anguloJson]);
        $sugerencias[] = [
            'id' => $db->lastInsertId(),
            'tema' => $t['titulo_sugerido']
        ];
    }

    // Registrar Consumo de Tokens de Sugerencia de Temas
    $textTokens = GeminiClient::getLastUsageMetadata();
    if ($textTokens) {
        GeminiClient::logUsage(
            $cliente_id,
            GEMINI_TEXT_MODEL,
            'sugerir_temas',
            $textTokens['promptTokenCount'] ?? 0,
            $textTokens['candidatesTokenCount'] ?? 0
        );
    }

    echo json_encode([
        'status' => 'success',
        'sugerencias' => $sugerencias
    ]);

} catch (Exception $e) {
    error_log("Error en sugerir_temas.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Ocurrió un error al consultar la Inteligencia Artificial: ' . $e->getMessage()
    ]);
}
