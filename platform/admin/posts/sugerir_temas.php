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

    // Intercepción para Modo Demo
    if (isset($_SESSION['is_demo']) && $_SESSION['is_demo'] === true) {
        $temas_demo = [];
        if ($cliente['slug'] === 'adri-hair-style-demo') {
            $temas_demo = [
                ['titulo_sugerido' => 'Tendencias de color de cabello para esta temporada', 'consejo_practico' => 'Aplica protectores de color y evita el cloro.', 'servicio_a_promocionar' => 'Coloración Orgánica y Brillo'],
                ['titulo_sugerido' => 'Cómo mantener el alisado por más tiempo', 'consejo_practico' => 'Seca el cabello con aire templado de arriba hacia abajo.', 'servicio_a_promocionar' => 'Alisado de Keratina Premium'],
                ['titulo_sugerido' => 'Cuidado del cuero cabelludo en climas fríos', 'consejo_practico' => 'Evita el agua excesivamente caliente al lavar tu cabeza.', 'servicio_a_promocionar' => 'Tratamiento Nutritivo de Cuero Capilar'],
                ['titulo_sugerido' => 'La verdad sobre el corte de puntas mensual', 'consejo_practico' => 'Cortar las puntas previene que el quiebre suba por la hebra.', 'servicio_a_promocionar' => 'Corte de Puntas Terapéutico'],
                ['titulo_sugerido' => 'Cómo elegir el champú ideal según tu tipo de hebra', 'consejo_practico' => 'Identifica si tu cuero cabelludo es seco, mixto o graso.', 'servicio_a_promocionar' => 'Diagnóstico Capilar Computarizado']
            ];
        } elseif ($cliente['slug'] === 'fitlife-gym-demo') {
            $temas_demo = [
                ['titulo_sugerido' => 'Cómo mantener la motivación para hacer ejercicio', 'consejo_practico' => 'Define metas a corto plazo y registra tus avances.', 'servicio_a_promocionar' => 'Clases de Spinning y Comunidad'],
                ['titulo_sugerido' => 'Guía de alimentación pre y post entrenamiento', 'consejo_practico' => 'Consume carbohidratos complejos antes y proteínas después.', 'servicio_a_promocionar' => 'Asesoría Nutricional Personalizada'],
                ['titulo_sugerido' => 'Mitos y verdades sobre el ejercicio cardiovascular', 'consejo_practico' => 'Combina fuerza y cardio para una quema de grasa eficiente.', 'servicio_a_promocionar' => 'Área de Musculación y Cardio'],
                ['titulo_sugerido' => 'Los beneficios de entrenar con peso libre', 'consejo_practico' => 'El peso libre activa más músculos estabilizadores.', 'servicio_a_promocionar' => 'Entrenamiento de Fuerza Guiado'],
                ['titulo_sugerido' => 'Rutina de movilidad articular para la oficina', 'consejo_practico' => 'Haz pausas activas de 5 minutos cada dos horas.', 'servicio_a_promocionar' => 'Clases de Pilates Reformer']
            ];
        } else { // cafe-aroma-demo y fallback
            $temas_demo = [
                ['titulo_sugerido' => 'Cómo maridar el café con repostería fina', 'consejo_practico' => 'Combina cafés de alta acidez con pastelería frutal.', 'servicio_a_promocionar' => 'Pastelería de Autor'],
                ['titulo_sugerido' => 'La historia detrás del cappuccino perfecto', 'consejo_practico' => 'La leche debe estar texturizada a 65 grados centígrados.', 'servicio_a_promocionar' => 'Taller de Arte Latte'],
                ['titulo_sugerido' => 'Tendencias en bebidas de café frío para el verano', 'consejo_practico' => 'Prueba el Cold Brew reposado por 18 horas.', 'servicio_a_promocionar' => 'Bebidas de Estación Especiales'],
                ['titulo_sugerido' => 'El papel de la molienda en el sabor final de tu taza', 'consejo_practico' => 'Usa molienda gruesa para prensa francesa y fina para espresso.', 'servicio_a_promocionar' => 'Venta de Café en Grano Seleccionado'],
                ['titulo_sugerido' => 'Cafés de especialidad vs. cafés comerciales', 'consejo_practico' => 'El café de especialidad no contiene defectos ni azúcar añadida.', 'servicio_a_promocionar' => 'Degustación Exclusiva de Orígenes']
            ];
        }

        $sugerencias = [];
        $insertStmt = $db->prepare("INSERT INTO sugerencias_temas (cliente_id, tema, angulo_contextual, estado, fecha_sugerencia) VALUES (?, ?, ?, 'pendiente', NOW())");
        foreach ($temas_demo as $t) {
            $anguloJson = json_encode([
                'consejo_practico' => $t['consejo_practico'],
                'servicio_a_promocionar' => $t['servicio_a_promocionar']
            ], JSON_UNESCAPED_UNICODE);
            
            $insertStmt->execute([$cliente_id, $t['titulo_sugerido'], $anguloJson]);
            $sugerencias[] = [
                'id' => $db->lastInsertId(),
                'tema' => $t['titulo_sugerido']
            ];
        }

        // Registrar Consumo de Tokens Simulado (Modo Demo)
        GeminiClient::logUsage(
            $cliente_id,
            GEMINI_TEXT_MODEL,
            'sugerir_temas',
            180, // Fake prompt tokens
            350, // Fake completion tokens
            0.0,
            null
        );

        echo json_encode([
            'status' => 'success',
            'sugerencias' => $sugerencias
        ]);
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
