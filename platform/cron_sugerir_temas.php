<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/DB.php';
require_once __DIR__ . '/lib/GeminiClient.php';
require_once __DIR__ . '/lib/Mailer.php';
require_once __DIR__ . '/lib/PromptTemplates.php';

// Si se ejecuta por el navegador web, forzar salida de texto plano
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

// Modo forzado: omite los filtros de fecha para pruebas
// Activar con: ?force=1 (navegador) o php cron_sugerir_temas.php force (CLI)
$forceMode = isset($_GET['force']) || in_array('force', $argv ?? []);

echo "=== INICIANDO CRON DE SUGERENCIAS Y RECORDATORIOS IA ===\n";
echo "Fecha y hora actual: " . date('Y-m-d H:i:s') . "\n";
if ($forceMode) {
    echo "⚠️  MODO FORZADO ACTIVO: Se omiten los filtros de fecha (solo para pruebas).\n";
}
echo "\n";

try {
    $db = DB::getInstance();

    // ═══════════════════════════════════════════════════════════════════
    // FASE 1: SUGERENCIAS DE TEMAS (nuevas o recordatorio de pendientes)
    // ═══════════════════════════════════════════════════════════════════
    echo "--- FASE 1: Procesando sugerencias de temas ---\n\n";

    // Buscar clientes activos cuyo intervalo de sugerencias haya expirado
    // En modo forzado, se traen TODOS los clientes activos sin verificar la fecha
    if ($forceMode) {
        $stmt = $db->query("SELECT * FROM clientes WHERE activo = 1");
    } else {
        $stmt = $db->query("
            SELECT * FROM clientes 
            WHERE activo = 1 
              AND (fecha_ultima_sugerencia IS NULL 
                   OR fecha_ultima_sugerencia <= DATE_SUB(NOW(), INTERVAL frecuencia_dias DAY))
        ");
    }
    $clientesFase1 = $stmt->fetchAll();

    if (empty($clientesFase1)) {
        echo "No hay clientes que requieran nuevas sugerencias ahora.\n\n";
    } else {
        echo "Se encontraron " . count($clientesFase1) . " cliente(s) para procesar sugerencias.\n\n";

        foreach ($clientesFase1 as $cliente) {
            echo "--------------------------------------------------\n";
            echo "Cliente: " . $cliente['nombre'] . " (ID: " . $cliente['id'] . ")\n";
            echo "Último envío: " . ($cliente['fecha_ultima_sugerencia'] ?? 'Nunca') . "\n";

            try {
                // A. Verificar si el cliente publicó un post desde el último envío de sugerencias
                $ultimoEnvio = $cliente['fecha_ultima_sugerencia'];
                $publicoDesdeUltimoEnvio = false;

                if ($ultimoEnvio) {
                    $stmtPub = $db->prepare("
                        SELECT COUNT(*) FROM posts 
                        WHERE cliente_id = ? 
                          AND estado = 'aprobado' 
                          AND publicacion_exitosa = 1
                          AND fecha_aprobacion >= ?
                    ");
                    $stmtPub->execute([$cliente['id'], $ultimoEnvio]);
                    $publicoDesdeUltimoEnvio = $stmtPub->fetchColumn() > 0;
                }

                if ($publicoDesdeUltimoEnvio) {
                    // CAMINO PREMIO: El cliente publicó → marcar sugerencias viejas como expiradas y generar temas frescos
                    echo "  → Publicó un post desde el último envío. Generando temas frescos como recompensa...\n";
                    // Marcar las sugerencias anteriores como 'generado' para limpiar el estado
                    $stmtExpire = $db->prepare("UPDATE sugerencias_temas SET estado = 'generado' WHERE cliente_id = ? AND estado = 'pendiente'");
                    $stmtExpire->execute([$cliente['id']]);
                    // Forzar generación de nuevos temas (saltamos al CAMINO B de abajo)
                    $sugerenciasPendientes = [];
                } else {
                    // B. Verificar si ya tiene sugerencias pendientes sin usar en la BD
                    $stmtPend = $db->prepare("
                        SELECT id, tema FROM sugerencias_temas 
                        WHERE cliente_id = ? AND estado = 'pendiente' 
                        ORDER BY fecha_sugerencia DESC LIMIT 5
                    ");
                    $stmtPend->execute([$cliente['id']]);
                    $sugerenciasPendientes = $stmtPend->fetchAll(PDO::FETCH_ASSOC);
                }

                if (!empty($sugerenciasPendientes)) {
                    // CAMINO A: Ya tiene temas pendientes sin usar → Reenviar sin consumir IA
                    echo "  → Tiene " . count($sugerenciasPendientes) . " sugerencias sin usar. Enviando recordatorio (sin llamar a la IA)...\n";

                    $enviado = Mailer::sendTopicReminderEmail($cliente, $sugerenciasPendientes);

                    if ($enviado) {
                        // Actualizar marca de tiempo para no re-enviar hasta el próximo ciclo
                        $update = $db->prepare("UPDATE clientes SET fecha_ultima_sugerencia = NOW() WHERE id = ?");
                        $update->execute([$cliente['id']]);
                        echo "  ✓ Recordatorio de temas enviado y marca de tiempo actualizada.\n";
                    } else {
                        echo "  ✗ Error al enviar recordatorio: " . Mailer::getLastMailError() . "\n";
                    }

                } else {
                    // CAMINO B: No hay sugerencias pendientes → Generar nuevas con IA
                    echo "  → No hay sugerencias pendientes. Generando 5 nuevos temas con IA...\n";

                    $prompt = PromptTemplates::getTopicSuggestionsPrompt($cliente);
                    $systemInstruction = PromptTemplates::getTopicSuggestionsSystemInstruction();
                    $result = GeminiClient::generateJson($prompt, $systemInstruction);

                    if (!isset($result['temas']) || !is_array($result['temas']) || count($result['temas']) < 5) {
                        throw new Exception("La IA no devolvió una estructura válida de 5 temas.");
                    }

                    $temas = array_slice($result['temas'], 0, 5);
                    echo "  Temas generados:\n";
                    foreach ($temas as $i => $t) {
                        echo "    " . ($i + 1) . ". " . $t['titulo_sugerido'] . "\n";
                    }

                    // Guardar en BD
                    $sugerencias = [];
                    $insertStmt = $db->prepare("INSERT INTO sugerencias_temas (cliente_id, tema, angulo_contextual, estado, fecha_sugerencia) VALUES (?, ?, ?, 'pendiente', NOW())");
                    foreach ($temas as $t) {
                        $anguloJson = json_encode([
                            'consejo_practico'       => $t['consejo_practico'] ?? '',
                            'servicio_a_promocionar' => $t['servicio_a_promocionar'] ?? ''
                        ], JSON_UNESCAPED_UNICODE);
                        $insertStmt->execute([$cliente['id'], $t['titulo_sugerido'], $anguloJson]);
                        $sugerencias[] = ['id' => $db->lastInsertId(), 'tema' => $t['titulo_sugerido']];
                    }

                    // Enviar correo con los nuevos temas
                    $enviado = Mailer::sendTopicSuggestionsEmail($cliente, $sugerencias);

                    if ($enviado) {
                        $update = $db->prepare("UPDATE clientes SET fecha_ultima_sugerencia = NOW() WHERE id = ?");
                        $update->execute([$cliente['id']]);

                        // Registrar consumo de tokens
                        $textTokens = GeminiClient::getLastUsageMetadata();
                        if ($textTokens) {
                            GeminiClient::logUsage(
                                $cliente['id'],
                                GEMINI_TEXT_MODEL,
                                'sugerir_temas',
                                $textTokens['promptTokenCount'] ?? 0,
                                $textTokens['candidatesTokenCount'] ?? 0
                            );
                        }
                        echo "  ✓ Correo con nuevos temas enviado y marca de tiempo actualizada.\n";
                    } else {
                        throw new Exception("Error al enviar correo: " . Mailer::getLastMailError());
                    }
                }

            } catch (Exception $e) {
                echo "  ✗ ERROR: " . $e->getMessage() . "\n";
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // FASE 2: RECORDATORIO DE POSTS PENDIENTES (abandonados > 2 días)
    // ═══════════════════════════════════════════════════════════════════
    echo "\n--- FASE 2: Buscando posts pendientes sin publicar (>2 días) ---\n\n";

    // En modo forzado, se revisan TODOS los posts pendientes sin importar la fecha
    if ($forceMode) {
        $stmtPosts = $db->query("
            SELECT p.*, 
                   c.nombre as cliente_nombre, c.logo_url, c.color_primario, c.color_texto,
                   c.fuente_titulo, c.fuente_texto, c.nombre_autor, c.foto_autor_url,
                   c.email_revisor, c.dominio, c.api_key_sitio
            FROM posts p
            JOIN clientes c ON p.cliente_id = c.id
            WHERE p.estado = 'pendiente'
            ORDER BY p.fecha_creacion ASC
        ");
    } else {
        $stmtPosts = $db->query("
            SELECT p.*, 
                   c.nombre as cliente_nombre, c.logo_url, c.color_primario, c.color_texto,
                   c.fuente_titulo, c.fuente_texto, c.nombre_autor, c.foto_autor_url,
                   c.email_revisor, c.dominio, c.api_key_sitio
            FROM posts p
            JOIN clientes c ON p.cliente_id = c.id
            WHERE p.estado = 'pendiente'
              AND p.fecha_creacion <= DATE_SUB(NOW(), INTERVAL 2 DAY)
            ORDER BY p.fecha_creacion ASC
        ");
    }
    $postsPendientes = $stmtPosts->fetchAll(PDO::FETCH_ASSOC);

    if (empty($postsPendientes)) {
        echo "No hay posts pendientes sin publicar de más de 2 días.\n";
    } else {
        echo "Se encontraron " . count($postsPendientes) . " post(s) pendiente(s).\n\n";

        foreach ($postsPendientes as $post) {
            echo "--------------------------------------------------\n";
            echo "Post ID: " . $post['id'] . " — \"" . $post['titulo'] . "\"\n";
            echo "Cliente: " . $post['cliente_nombre'] . "\n";
            echo "Creado: " . $post['fecha_creacion'] . "\n";
            echo "Tiene imagen: " . (!empty($post['imagen_url']) ? "Sí" : "No") . "\n";

            // Reconstruir arreglo cliente con los campos necesarios para Mailer
            $cliente = [
                'nombre'           => $post['cliente_nombre'],
                'logo_url'         => $post['logo_url'],
                'color_primario'   => $post['color_primario'],
                'color_texto'      => $post['color_texto'],
                'fuente_titulo'    => $post['fuente_titulo'],
                'fuente_texto'     => $post['fuente_texto'],
                'nombre_autor'     => $post['nombre_autor'],
                'foto_autor_url'   => $post['foto_autor_url'],
                'email_revisor'    => $post['email_revisor'],
                'dominio'          => $post['dominio'],
                'api_key_sitio'    => $post['api_key_sitio'],
            ];

            try {
                $enviado = Mailer::sendPostReminderEmail($cliente, $post);
                if ($enviado) {
                    echo "  ✓ Recordatorio de post enviado a " . $post['email_revisor'] . ".\n";
                } else {
                    echo "  ✗ Error al enviar recordatorio: " . Mailer::getLastMailError() . "\n";
                }
            } catch (Exception $e) {
                echo "  ✗ ERROR: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\n=== CRON FINALIZADO ===\n";

} catch (Exception $e) {
    echo "FATAL ERROR en el cron: " . $e->getMessage() . "\n";
}
