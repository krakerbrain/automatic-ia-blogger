<?php
/**
 * Biblioteca para publicar posts de blog en los sitios receptores de los clientes
 */
require_once __DIR__ . '/DB.php';

class Publisher {
    /**
     * Envía el post por curl al endpoint del cliente y actualiza el estado en BD
     * @param int $postId
     * @return array ['ok' => bool, 'error' => string|null]
     */
    public static function publish(int $postId): array {
        $db = DB::getInstance();
        
        // Obtener post y cliente
        $stmt = $db->prepare("
            SELECT p.*, c.endpoint_publicar, c.api_key_sitio, c.nombre_autor, c.foto_autor_url,
                   c.fuente_titulo, c.fuente_texto, c.color_primario, c.color_texto
            FROM posts p
            JOIN clientes c ON p.cliente_id = c.id
            WHERE p.id = ?
        ");
        $stmt->execute([$postId]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return ['ok' => false, 'error' => 'Post o cliente no encontrado en la base de datos.'];
        }

        // Preparar el cuerpo JSON
        $payload = [
            'titulo' => $data['titulo'],
            'texto' => $data['texto'],
            'imagen_url' => $data['imagen_url'],
            'nombre_autor' => $data['nombre_autor'],
            'foto_autor_url' => $data['foto_autor_url'],
            'fuente_titulo' => $data['fuente_titulo'],
            'fuente_texto' => $data['fuente_texto'],
            'color_primario' => $data['color_primario'],
            'color_texto' => $data['color_texto']
        ];

        // Realizar POST vía cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $data['endpoint_publicar']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-API-Key: ' . $data['api_key_sitio']
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $errorMsg = "Error cURL: " . $curlError;
            $db->prepare("UPDATE posts SET publicacion_exitosa = 0 WHERE id = ?")->execute([$postId]);
            return ['ok' => false, 'error' => $errorMsg];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            $resData = json_decode($response, true);
            if (isset($resData['ok']) && $resData['ok'] === true) {
                // Publicación Exitosa
                $db->prepare("UPDATE posts SET publicacion_exitosa = 1 WHERE id = ?")->execute([$postId]);
                return ['ok' => true];
            } else {
                $errorMsg = "Respuesta del receptor inválida o fallida: " . ($resData['error'] ?? $response);
                $db->prepare("UPDATE posts SET publicacion_exitosa = 0 WHERE id = ?")->execute([$postId]);
                return ['ok' => false, 'error' => $errorMsg];
            }
        } else {
            $errorMsg = "El servidor receptor retornó código HTTP {$httpCode}. Respuesta: " . $response;
            $db->prepare("UPDATE posts SET publicacion_exitosa = 0 WHERE id = ?")->execute([$postId]);
            return ['ok' => false, 'error' => $errorMsg];
        }
    }
}
