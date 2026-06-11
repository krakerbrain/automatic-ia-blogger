<?php
/**
 * Historial y Listado de Posts con Filtros y Regeneración
 */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/DB.php';
require_once __DIR__ . '/../../lib/GeminiClient.php';
require_once __DIR__ . '/../../lib/Mailer.php';

$db = DB::getInstance();

// A. PROCESAR ACCIÓN REGENERAR (Para posts rechazados)
if (isset($_GET['action']) && $_GET['action'] === 'regenerar' && isset($_GET['id'])) {
    $postId = intval($_GET['id']);
    
    try {
        // Obtener el post actual
        $stmtPost = $db->prepare("SELECT * FROM posts WHERE id = ?");
        $stmtPost->execute([$postId]);
        $post = $stmtPost->fetch();
        
        if (!$post) {
            throw new Exception("El post seleccionado no existe.");
        }
        
        if ($post['estado'] !== 'rechazado') {
            throw new Exception("Solo se pueden regenerar posts que hayan sido rechazados.");
        }
        
        // Obtener el cliente correspondiente
        $stmtCli = $db->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmtCli->execute([$post['cliente_id']]);
        $cliente = $stmtCli->fetch();
        
        if (!$cliente) {
            throw new Exception("El cliente de este post no está registrado o fue eliminado.");
        }

        // 1. Eliminar banner antiguo si existe localmente
        $oldFilename = basename($post['imagen_url']);
        $oldFilePath = UPLOAD_DIR . '/' . $oldFilename;
        if (!empty($oldFilename) && file_exists($oldFilePath)) {
            @unlink($oldFilePath);
        }

        // 2. Construir prompts basados en la configuración original
        $systemInstruction = "Eres redactor/a de contenido para {$cliente['nombre']}, un/a {$cliente['rubro']}. "
                           . "Tono de marca: {$cliente['tono_marca']}. Escribe en español. "
                           . "Genera un título atractivo (máx 70 caracteres) y un texto de blog de 250-350 palabras sobre el tema indicado. "
                           . "Responde SOLO en JSON válido: {\"titulo\": \"...\", \"texto\": \"...\"}";
        
        $textPrompt = "Tema del post: " . $post['tema'];
        
        $colorDescription = "tonos suaves y elegantes";
        if (!empty($cliente['color_primario'])) {
            if (strtolower($cliente['color_primario']) === '#e8b4b8') {
                $colorDescription = "tonos rosa pastel y cálidos";
            } else {
                $colorDescription = "paleta de colores armónica";
            }
        }
        
        $imagePrompt = "Fotografía publicitaria profesional y elegante para un blog de {$cliente['rubro']}. "
                     . "Concepto visual: {$post['tema']}. "
                     . "Estilo fotográfico moderno, iluminación limpia, encuadre cinematográfico. "
                     . "Paleta de colores: {$colorDescription} de fondo. "
                     . "IMPORTANTE: La imagen debe ser únicamente una fotografía limpia, sin ningún tipo de texto, letras, logotipos, marcas de agua ni layouts de diseño.";

        // 3. Consultar las APIs de Gemini
        $textResult = GeminiClient::generateText($textPrompt, $systemInstruction);
        $imageBytes = GeminiClient::generateImage($imagePrompt);

        // 4. Guardar nuevo banner
        $newFilename = $cliente['slug'] . '-' . time() . '.jpg';
        $destPath = UPLOAD_DIR . '/' . $newFilename;
        if (file_put_contents($destPath, $imageBytes) === false) {
            throw new Exception("No se pudo escribir el nuevo banner en el servidor.");
        }

        // Formar nueva URL absoluta
        $imageUrl = UPLOAD_URL . '/' . $newFilename;

        // 5. Actualizar en Base de Datos (Resetear a Pendiente)
        $updateStmt = $db->prepare("
            UPDATE posts SET 
                titulo = ?, 
                texto = ?, 
                imagen_url = ?, 
                estado = 'pendiente', 
                comentario_rechazo = NULL, 
                publicacion_exitosa = NULL, 
                fecha_creacion = NOW(), 
                fecha_aprobacion = NULL 
            WHERE id = ?
        ");
        $updateStmt->execute([
            $textResult['titulo'],
            $textResult['texto'],
            $imageUrl,
            $postId
        ]);

        // Registrar Consumo de Tokens e Imágenes de la Regeneración
        $textTokens = GeminiClient::getLastUsageMetadata();
        if ($textTokens) {
            GeminiClient::logUsage(
                $post['cliente_id'],
                GEMINI_TEXT_MODEL,
                'regenerar_post',
                $textTokens['promptTokenCount'] ?? 0,
                $textTokens['candidatesTokenCount'] ?? 0,
                0.0,
                $postId
            );
        }
        
        GeminiClient::logUsage(
            $post['cliente_id'],
            GEMINI_IMAGE_MODEL,
            'generar_imagen',
            0,
            0,
            GEMINI_PRICE_IMAGE,
            $postId
        );

        // Obtener el post actualizado para el correo
        $stmtPost->execute([$postId]);
        $updatedPost = $stmtPost->fetch();

        // 6. Enviar nuevo correo de revisión al cliente
        $enviadoMail = Mailer::sendReviewEmail($cliente, $updatedPost);

        if ($enviadoMail) {
            $_SESSION['flash_success'] = "El post fue regenerado correctamente con IA y se envió un nuevo correo de revisión.";
        } else {
            $_SESSION['flash_success'] = "El post fue regenerado con éxito, pero falló el envío del correo de notificación.";
        }
        
    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Error al regenerar el post: " . $e->getMessage();
    }
    
    header("Location: lista.php");
    exit();
}

// A2. PROCESAR ACCIÓN REENVIAR CORREO
if (isset($_GET['action']) && $_GET['action'] === 'reenviar' && isset($_GET['id'])) {
    $postId = intval($_GET['id']);
    try {
        $stmtPost = $db->prepare("SELECT * FROM posts WHERE id = ?");
        $stmtPost->execute([$postId]);
        $post = $stmtPost->fetch();
        
        if (!$post) {
            throw new Exception("El post seleccionado no existe.");
        }
        
        $stmtCli = $db->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmtCli->execute([$post['cliente_id']]);
        $cliente = $stmtCli->fetch();
        
        if (!$cliente) {
            throw new Exception("El cliente de este post no está registrado.");
        }

        $enviadoMail = Mailer::sendReviewEmail($cliente, $post);
        if ($enviadoMail) {
            $_SESSION['flash_success'] = "El correo de revisión fue reenviado con éxito a " . htmlspecialchars($cliente['email_revisor']) . ".";
        } else {
            throw new Exception("El servidor de correo SMTP no pudo procesar el envío. Detalles: " . Mailer::getLastMailError());
        }
    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Error al reenviar el correo: " . $e->getMessage();
    }
    header("Location: lista.php");
    exit();
}

// A3. PROCESAR ACCIÓN ELIMINAR POST
if (isset($_GET['action']) && $_GET['action'] === 'eliminar' && isset($_GET['id'])) {
    $postId = intval($_GET['id']);
    try {
        // Obtener datos del post para borrar la imagen
        $stmtPost = $db->prepare("SELECT imagen_url FROM posts WHERE id = ?");
        $stmtPost->execute([$postId]);
        $post = $stmtPost->fetch();
        
        if ($post) {
            // Eliminar banner antiguo si existe localmente
            $filename = basename($post['imagen_url']);
            $filePath = UPLOAD_DIR . '/' . $filename;
            if (!empty($filename) && file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        $stmtDelete = $db->prepare("DELETE FROM posts WHERE id = ?");
        $stmtDelete->execute([$postId]);
        $_SESSION['flash_success'] = "El post fue eliminado del historial correctamente.";
    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Error al eliminar el post: " . $e->getMessage();
    }
    header("Location: lista.php");
    exit();
}


// B. CONSTRUIR FILTROS Y OBTENER RESULTADOS
$filtro_cliente = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : 0;
$filtro_estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
$filtro_desde = isset($_GET['fecha_desde']) ? trim($_GET['fecha_desde']) : '';
$filtro_hasta = isset($_GET['fecha_hasta']) ? trim($_GET['fecha_hasta']) : '';

// Obtener todos los clientes para el selector de filtros
$clientes = $db->query("SELECT id, nombre FROM clientes ORDER BY nombre ASC")->fetchAll();

// Armar Query Dinámica
$query = "
    SELECT p.*, c.nombre as cliente_nombre, c.logo_url as cliente_logo
    FROM posts p
    JOIN clientes c ON p.cliente_id = c.id
    WHERE 1=1
";
$params = [];

if ($filtro_cliente > 0) {
    $query .= " AND p.cliente_id = ?";
    $params[] = $filtro_cliente;
}

if (!empty($filtro_estado)) {
    $query .= " AND p.estado = ?";
    $params[] = $filtro_estado;
}

if (!empty($filtro_desde)) {
    $query .= " AND p.fecha_creacion >= ?";
    $params[] = $filtro_desde . " 00:00:00";
}

if (!empty($filtro_hasta)) {
    $query .= " AND p.fecha_creacion <= ?";
    $params[] = $filtro_hasta . " 23:59:59";
}

$query .= " ORDER BY p.fecha_creacion DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$posts = $stmt->fetchAll();

include __DIR__ . '/../layout_header.php';
?>

<!-- Estilos extra para panel de carga durante regeneración -->
<style>
    .loading-overlay-regenerar {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(11, 15, 25, 0.9);
        backdrop-filter: blur(8px);
        z-index: 99999;
        justify-content: center;
        align-items: center;
        flex-direction: column;
    }
</style>

<!-- Overlay de Carga de Regenerar -->
<div id="loading-regenerar" class="loading-overlay-regenerar">
    <div style="width: 50px; height: 50px; border: 3px solid rgba(255,255,255,0.1); border-top-color: var(--color-primary); border-radius: 50%; animation: spin 1s linear infinite;"></div>
    <h3 style="color: white; margin-top: 15px; font-family: var(--font-display);">Regenerando contenido con IA...</h3>
    <p style="color: var(--text-secondary); margin-top: 5px; font-size: 13px;">Llamando a las APIs de Gemini y enviando correo de revisión...</p>
</div>
<style>
    @keyframes spin { to { transform: rotate(360deg); } }
</style>

<div style="margin-bottom: 25px;">
    <h1 style="font-size: 28px; margin-bottom: 5px;">Historial de Publicaciones</h1>
    <p style="color: var(--text-secondary); font-size: 14px;">Busca, filtra y administra todas las entradas generadas para tus clientes.</p>
</div>

<!-- Filtros de Búsqueda -->
<div class="card-glass" style="margin-bottom: 30px; padding: 20px;">
    <form action="lista.php" method="GET" style="display: grid; grid-template-columns: 1fr; gap: 15px; align-items: end;" class="filters-form">
        <div style="display: grid; grid-template-columns: 1fr; gap: 15px;" class="form-row">
            <div class="form-group" style="margin: 0;">
                <label class="form-label" for="cliente_id" style="font-size: 12px;">Filtrar por Cliente</label>
                <select id="cliente_id" name="cliente_id" class="form-control" style="padding: 10px;">
                    <option value="0">-- Todos los Clientes --</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($filtro_cliente === $c['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label" for="estado" style="font-size: 12px;">Estado de Revisión</label>
                <select id="estado" name="estado" class="form-control" style="padding: 10px;">
                    <option value="">-- Todos los Estados --</option>
                    <option value="pendiente" <?php echo ($filtro_estado === 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="aprobado" <?php echo ($filtro_estado === 'aprobado') ? 'selected' : ''; ?>>Aprobado</option>
                    <option value="rechazado" <?php echo ($filtro_estado === 'rechazado') ? 'selected' : ''; ?>>Rechazado</option>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr; gap: 15px;" class="form-row">
            <div class="form-group" style="margin: 0;">
                <label class="form-label" for="fecha_desde" style="font-size: 12px;">Creado Desde</label>
                <input type="date" id="fecha_desde" name="fecha_desde" class="form-control" style="padding: 9px;" value="<?php echo htmlspecialchars($filtro_desde); ?>">
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label" for="fecha_hasta" style="font-size: 12px;">Creado Hasta</label>
                <input type="date" id="fecha_hasta" name="fecha_hasta" class="form-control" style="padding: 9px;" value="<?php echo htmlspecialchars($filtro_hasta); ?>">
            </div>
        </div>

        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 10px;">
            <a href="lista.php" class="btn-custom btn-secondary" style="padding: 10px 18px;">Limpiar</a>
            <button type="submit" class="btn-custom btn-primary" style="padding: 10px 22px;">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Buscar
            </button>
        </div>
    </form>
</div>

<!-- Tabla de Resultados -->
<div class="card-glass" style="padding: 0; overflow: hidden;">
    <?php if (empty($posts)): ?>
        <div style="padding: 60px; text-align: center; color: var(--text-secondary);">
            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom: 15px; opacity: 0.5;"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <h3 style="margin-bottom: 5px;">No se encontraron posts</h3>
            <p>Intenta ajustar los filtros o genera un nuevo post.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Título</th>
                        <th>Tema</th>
                        <th>Estado</th>
                        <th>Publicación Remota</th>
                        <th>Fecha Creación</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $p): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <?php if (!empty($p['cliente_logo'])): ?>
                                        <img src="<?php echo htmlspecialchars($p['cliente_logo']); ?>" alt="Logo" style="width: 24px; height: 24px; border-radius: 4px; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 24px; height: 24px; border-radius: 4px; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: bold;">
                                            CL
                                        </div>
                                    <?php endif; ?>
                                    <span style="font-weight: 500;"><?php echo htmlspecialchars($p['cliente_nombre']); ?></span>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 600; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($p['titulo']); ?>">
                                    <?php echo htmlspecialchars($p['titulo']); ?>
                                </div>
                            </td>
                            <td>
                                <span style="color: var(--text-secondary);"><?php echo htmlspecialchars($p['tema']); ?></span>
                            </td>
                            <td>
                                <?php if ($p['estado'] === 'pendiente'): ?>
                                    <span class="badge badge-warning">Pendiente</span>
                                <?php elseif ($p['estado'] === 'aprobado'): ?>
                                    <span class="badge badge-success">Aprobado</span>
                                <?php else: ?>
                                    <span class="badge badge-danger" title="Motivo: <?php echo htmlspecialchars($p['comentario_rechazo']); ?>">Rechazado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['estado'] !== 'aprobado'): ?>
                                    <span style="color: var(--text-muted);">—</span>
                                <?php elseif ($p['publicacion_exitosa'] === 1): ?>
                                    <span class="badge badge-success" style="padding: 2px 8px; font-size: 10px;">Éxito</span>
                                <?php else: ?>
                                    <span class="badge badge-danger" style="padding: 2px 8px; font-size: 10px;" title="Error en la publicación. Puedes reintentar abriendo el enlace de revisión.">Fallido</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-size: 12px; color: var(--text-muted);">
                                    <?php echo date('d/m/Y H:i', strtotime($p['fecha_creacion'])); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 8px;">
                                    <?php if ($p['estado'] === 'rechazado'): ?>
                                        <a href="lista.php?action=regenerar&id=<?php echo $p['id']; ?>" class="btn-custom btn-secondary btn-sm btn-regenerar-trigger" style="border-color: rgba(245, 158, 11, 0.4); color: var(--color-warning);" title="Regenerar con IA y reenviar correo de revisión">
                                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.006 11H19"/></svg>
                                            Regenerar
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($p['estado'] !== 'aprobado'): ?>
                                        <a href="lista.php?action=reenviar&id=<?php echo $p['id']; ?>" class="btn-custom btn-secondary btn-sm" title="Reenviar correo de revisión al cliente" style="border-color: rgba(99, 102, 241, 0.4); color: #818CF8;">
                                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            Reenviar
                                        </a>
                                    <?php endif; ?>
                                    <a href="revisar.php?token=<?php echo $p['token_revision']; ?>" class="btn-custom btn-secondary btn-sm" target="_blank">
                                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        Revisar
                                    </a>
                                    <a href="lista.php?action=eliminar&id=<?php echo $p['id']; ?>" class="btn-custom btn-secondary btn-sm" style="border-color: rgba(239, 68, 68, 0.4); color: #F87171;" title="Eliminar post permanentemente" onclick="return confirm('¿Estás seguro de que deseas eliminar este post permanentemente del historial? Esta acción borrará también la imagen y no se puede deshacer.');">
                                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Eliminar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('loading-regenerar');
    const triggers = document.querySelectorAll('.btn-regenerar-trigger');
    
    triggers.forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (confirm('¿Estás seguro de que deseas regenerar este post? Se borrará el banner anterior, se generará contenido y banner nuevo con Gemini y se enviará un nuevo correo al revisor.')) {
                loader.style.display = 'flex';
            } else {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
