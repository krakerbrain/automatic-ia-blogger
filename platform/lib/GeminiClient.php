<?php
/**
 * Cliente de API de Google Gemini (Flash & Imagen 3)
 */

require_once __DIR__ . '/../config.php';

class GeminiClient {

    // Almacenamiento temporal del consumo de la última llamada
    private static ?array $lastUsageMetadata = null;

    /**
     * Obtiene los metadatos de uso de tokens de la última llamada
     * @return array|null
     */
    public static function getLastUsageMetadata(): ?array {
        return self::$lastUsageMetadata;
    }

    /**
     * Hace una llamada cURL genérica con reintentos
     * @param string $url
     * @param array $payload
     * @param int $maxRetries
     * @return array|string
     * @throws Exception
     */
    private static function makePostRequest(string $url, array $payload, int $maxRetries = 3) {
        $attempt = 0;
        $apiKey = GEMINI_API_KEY;
        
        // Agregar API Key a la URL
        $separator = (strpos($url, '?') === false) ? '?' : '&';
        $requestUrl = $url . $separator . 'key=' . urlencode($apiKey);

        while ($attempt <= $maxRetries) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $requestUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Timeout largo para generación de imágenes

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                error_log("Intento " . ($attempt + 1) . " - cURL Error: " . $curlError);
            } elseif ($httpCode >= 200 && $httpCode < 300) {
                return $response;
            } else {
                error_log("Intento " . ($attempt + 1) . " - Gemini API error (HTTP $httpCode): " . $response);
            }

            $attempt++;
            if ($attempt <= $maxRetries) {
                sleep(1 * $attempt); // Espera exponencial en segundos (1s, 2s, 3s) para mitigar picos de demanda 503/429
            }
        }

        throw new Exception("Fallo en la comunicación con la API de Gemini tras " . ($maxRetries + 1) . " intentos.");
    }

    /**
     * Genera un post de blog estructurado en JSON
     * @param string $prompt
     * @param string $systemInstruction
     * @return array ['titulo' => '...', 'texto' => '...']
     * @throws Exception
     */
    public static function generateText(string $prompt, string $systemInstruction = ''): array {
        if (env('DEV_MODE') === 'true') {
            self::$lastUsageMetadata = [
                'promptTokenCount' => 250,
                'candidatesTokenCount' => 450
            ];
            
            // Generar título según el prompt o tema
            $titulo = 'MOCK: Guía de Cuidado Capilar Profesional';
            if (preg_match('/Tema del post:\s*(.*)/i', $prompt, $matches)) {
                $titulo = 'MOCK: Todo sobre ' . trim($matches[1]);
            } elseif (preg_match('/Tema de Interés\s*:\s*(.*)/i', $prompt, $matches)) {
                $titulo = 'MOCK: ' . trim($matches[1]);
            }
            
            return [
                'titulo' => $titulo,
                'texto' => "Este es un texto simulado generado en Modo Desarrollo (DEV_MODE) para evitar consumos de la API de Gemini.\n\n"
                         . "Cuidar el cabello durante las rutinas diarias requiere una atención especial. La falta de humedad y la exposición al calor tienden a resecar las fibras capilares, dejándolas propensas al quiebre y a la pérdida de brillo natural.\n\n"
                         . "Para contrarrestar estos efectos, se recomienda utilizar tratamientos nutritivos ricos en aceites naturales de manera semanal. Además, es de vital importancia espaciar el uso de herramientas térmicas como planchas y secadores, e incorporar siempre un protector térmico de alta calidad.\n\n"
                         . "Finalmente, recuerda realizar masajes en el cuero cabelludo para mejorar la circulación y promover un crecimiento fuerte. ¡Un cabello sano comienza desde adentro!"
            ];
        }

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json'
            ]
        ];

        if (!empty($systemInstruction)) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ];
        }

        $rawResponse = self::makePostRequest(GEMINI_TEXT_URL, $payload);
        $data = json_decode($rawResponse, true);

        // Guardar metadatos de consumo de tokens
        if (isset($data['usageMetadata'])) {
            self::$lastUsageMetadata = $data['usageMetadata'];
        } else {
            self::$lastUsageMetadata = null;
        }

        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            error_log("Respuesta Gemini Inesperada: " . $rawResponse);
            throw new Exception("La respuesta de Gemini no contiene el formato de texto esperado.");
        }

        $textResponse = trim($data['candidates'][0]['content']['parts'][0]['text']);
        $parsedJson = json_decode($textResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON Inválido recibido de Gemini: " . $textResponse);
            throw new Exception("El contenido generado por Gemini no pudo ser parseado como un JSON válido.");
        }

        return [
            'titulo' => $parsedJson['titulo'] ?? 'Sin Título',
            'texto' => $parsedJson['texto'] ?? 'Sin Contenido'
        ];
    }

    /**
     * Genera cualquier estructura JSON especificada en el prompt y la decodifica
     * @param string $prompt
     * @param string $systemInstruction
     * @return array
     * @throws Exception
     */
    public static function generateJson(string $prompt, string $systemInstruction = ''): array {
        if (env('DEV_MODE') === 'true') {
            self::$lastUsageMetadata = [
                'promptTokenCount' => 15,
                'candidatesTokenCount' => 50
            ];
            return ['prompt' => 'A mock visual prompt representing: ' . str_replace("\n", " ", substr($prompt, 0, 80))];
        }

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json'
            ]
        ];

        if (!empty($systemInstruction)) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ];
        }

        $rawResponse = self::makePostRequest(GEMINI_TEXT_URL, $payload);
        $data = json_decode($rawResponse, true);

        // Guardar metadatos de consumo de tokens
        if (isset($data['usageMetadata'])) {
            self::$lastUsageMetadata = $data['usageMetadata'];
        } else {
            self::$lastUsageMetadata = null;
        }

        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            error_log("Respuesta Gemini Inesperada: " . $rawResponse);
            throw new Exception("La respuesta de Gemini no contiene el formato de texto esperado.");
        }

        $textResponse = trim($data['candidates'][0]['content']['parts'][0]['text']);
        $parsedJson = json_decode($textResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON Inválido recibido de Gemini: " . $textResponse);
            throw new Exception("El contenido generado por Gemini no pudo ser parseado como un JSON válido.");
        }

        return $parsedJson;
    }

    /**
     * Genera una imagen y retorna los bytes binarios (JPG)
     * @param string $prompt
     * @return string (bytes binarios de la imagen)
     * @throws Exception
     */
    public static function generateImage(string $prompt): string {
        if (env('DEV_MODE') === 'true') {
            // Descargar una imagen mock ligera de Picsum o usar una de Unsplash
            $mockImageUrl = 'https://picsum.photos/800/450';
            $imageBytes = @file_get_contents($mockImageUrl);
            if ($imageBytes === false) {
                // Fallback a Unsplash fija en caso de falla de Picsum o timeout
                $imageBytes = @file_get_contents('https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=800&q=80');
            }
            if ($imageBytes === false) {
                throw new Exception("Modo Desarrollo: No se pudo descargar la imagen de prueba y no hay conexión a internet.");
            }
            return $imageBytes;
        }

        $payload = [
            'instances' => [
                [
                    'prompt' => $prompt
                ]
            ],
            'parameters' => [
                'sampleCount' => 1,
                'aspectRatio' => '16:9',
                'outputMimeType' => 'image/jpeg'
            ]
        ];

        $rawResponse = self::makePostRequest(GEMINI_IMAGE_URL, $payload);
        $data = json_decode($rawResponse, true);

        // Intentar parsear predictions[0]['bytesBase64Encoded']
        if (isset($data['predictions'][0]['bytesBase64Encoded'])) {
            $base64Image = $data['predictions'][0]['bytesBase64Encoded'];
            $binaryData = base64_decode($base64Image);
            if ($binaryData === false) {
                throw new Exception("Error al decodificar la imagen base64 de Gemini.");
            }
            return $binaryData;
        }

        // Manejar errores de la API si vienen estructurados en la respuesta
        if (isset($data['error']['message'])) {
            throw new Exception("Error de Gemini Imagen: " . $data['error']['message']);
        }

        throw new Exception("La respuesta de Gemini Imagen no contiene los bytes codificados esperados.");
    }

    /**
     * Registra el consumo de tokens en la base de datos y calcula el costo en USD
     * @param int|null $clienteId
     * @param string $modelo
     * @param string $accion
     * @param int $promptTokens
     * @param int $completionTokens
     * @param float $costoManual
     * @param int|null $postId
     * @return bool
     */
    public static function logUsage(?int $clienteId, string $modelo, string $accion, int $promptTokens = 0, int $completionTokens = 0, float $costoManual = 0.0, ?int $postId = null): bool {
        try {
            require_once __DIR__ . '/DB.php';
            $db = DB::getInstance();
            
            // Calcular costo dinámicamente si no se provee un costo manual
            $costo = $costoManual;
            if ($costo === 0.0 && ($promptTokens > 0 || $completionTokens > 0)) {
                $costoInput = ($promptTokens * GEMINI_PRICE_INPUT_1M) / 1000000;
                $costoOutput = ($completionTokens * GEMINI_PRICE_OUTPUT_1M) / 1000000;
                $costo = $costoInput + $costoOutput;
            }

            $totalTokens = $promptTokens + $completionTokens;

            $stmt = $db->prepare("
                INSERT INTO consumo_tokens 
                (cliente_id, post_id, modelo, accion, prompt_tokens, completion_tokens, total_tokens, costo_estimado, fecha_registro)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            return $stmt->execute([
                $clienteId,
                $postId,
                $modelo,
                $accion,
                $promptTokens,
                $completionTokens,
                $totalTokens,
                $costo
            ]);
        } catch (Exception $e) {
            error_log("Error al registrar consumo en DB: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si un cliente ha superado su presupuesto mensual asignado.
     * @param int $clienteId
     * @return bool
     */
    public static function isMonthlyLimitExceeded(int $clienteId): bool {
        try {
            require_once __DIR__ . '/DB.php';
            $db = DB::getInstance();
            
            // Obtener el límite mensual del cliente
            $stmtCli = $db->prepare("SELECT limite_mensual_usd FROM clientes WHERE id = ?");
            $stmtCli->execute([$clienteId]);
            $limite = $stmtCli->fetchColumn();
            
            if ($limite === false) {
                return false; // Si no existe el cliente, no aplica
            }
            
            // Calcular el consumo total de este mes
            $inicioMes = date('Y-m-01 00:00:00');
            $stmtConsumo = $db->prepare("
                SELECT SUM(costo_estimado) 
                FROM consumo_tokens 
                WHERE cliente_id = ? AND fecha_registro >= ?
            ");
            $stmtConsumo->execute([$clienteId, $inicioMes]);
            $consumoMes = $stmtConsumo->fetchColumn() ?? 0.0;
            
            return $consumoMes >= $limite;
        } catch (Exception $e) {
            error_log("Error al verificar límite mensual en DB: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el consumo mensual actual del cliente.
     * @param int $clienteId
     * @return float
     */
    public static function getMonthlySpend(int $clienteId): float {
        try {
            require_once __DIR__ . '/DB.php';
            $db = DB::getInstance();
            $inicioMes = date('Y-m-01 00:00:00');
            $stmtConsumo = $db->prepare("
                SELECT SUM(costo_estimado) 
                FROM consumo_tokens 
                WHERE cliente_id = ? AND fecha_registro >= ?
            ");
            $stmtConsumo->execute([$clienteId, $inicioMes]);
            return (float)($stmtConsumo->fetchColumn() ?? 0.0);
        } catch (Exception $e) {
            return 0.0;
        }
    }
}
