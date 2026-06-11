<?php
/**
 * Módulo de Generación de Posts con IA - Controlador Modularizado
 */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/DB.php';
require_once __DIR__ . '/../../lib/GeminiClient.php';
require_once __DIR__ . '/../../lib/Mailer.php';
require_once __DIR__ . '/../../lib/Publisher.php';
require_once __DIR__ . '/../../lib/PromptTemplates.php';

$db = DB::getInstance();

$errorMsg = null;
$selected_cliente_id = 0;
$selected_tema = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_cliente_id = isset($_POST['cliente_id']) ? intval($_POST['cliente_id']) : 0;
    $selected_tema = isset($_POST['tema']) ? trim($_POST['tema']) : '';
} else {
    $selected_cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : 0;
    $selected_tema = isset($_GET['tema']) ? trim($_GET['tema']) : '';
}

// Forzar cliente_id si el usuario es cliente
if (isset($_SESSION['role']) && $_SESSION['role'] === 'cliente' && isset($_SESSION['cliente_id'])) {
    $selected_cliente_id = $_SESSION['cliente_id'];
    
    // El cliente no puede iniciar una nueva generación (desde cero o sugerencias)
    if (!isset($_GET['draft_id']) && !isset($_GET['success_id']) && !isset($_POST['post_id'])) {
        $_SESSION['flash_error'] = "No tienes permisos para generar posts nuevos directamente. Solo puedes editar borradores existentes.";
        header("Location: lista.php");
        exit();
    }
}

// 1. Obtener lista de clientes activos (filtrada por rol)
if (isset($_SESSION['role']) && $_SESSION['role'] === 'cliente' && isset($_SESSION['cliente_id'])) {
    // El cliente solo ve SU propia cuenta
    $stmtCl = $db->prepare("SELECT * FROM clientes WHERE activo = 1 AND id = ? ORDER BY nombre ASC");
    $stmtCl->execute([$_SESSION['cliente_id']]);
    $clientes = $stmtCl->fetchAll();
} else {
    $stmt = $db->query("SELECT * FROM clientes WHERE activo = 1 ORDER BY nombre ASC");
    $clientes = $stmt->fetchAll();
}

// 2. Procesar acción de publicar directamente el post en el sitio cliente
if (isset($_POST['action']) && $_POST['action'] === 'publicar' && isset($_POST['post_id'])) {
    $post_id = intval($_POST['post_id']);

    // Obtener post
    $stmtPost = $db->prepare("SELECT * FROM posts WHERE id = ?");
    $stmtPost->execute([$post_id]);
    $post = $stmtPost->fetch();

    if ($post) {
        // 1. Marcar como aprobado en la BD
        $stmtApprove = $db->prepare("UPDATE posts SET estado = 'aprobado', fecha_aprobacion = NOW() WHERE id = ?");
        $stmtApprove->execute([$post_id]);

        // 2. Publicar en el sitio cliente
        $pubRes = Publisher::publish($post_id);

        if ($pubRes['ok']) {
            $_SESSION['flash_success'] = "¡El post ha sido aprobado y publicado en el sitio web con éxito!";
        } else {
            $_SESSION['flash_error'] = "El post fue aprobado localmente, pero falló la publicación remota: " . $pubRes['error'];
        }
    }
    header("Location: lista.php");
    exit();
}

// 3. Procesar acción de guardar borrador
if (isset($_POST['action']) && $_POST['action'] === 'guardar_borrador' && isset($_POST['post_id'])) {
    $post_id = intval($_POST['post_id']);
    $titulo = trim($_POST['titulo']);
    $texto = trim($_POST['texto']);

    if (!empty($titulo) && !empty($texto)) {
        $stmtUpdate = $db->prepare("UPDATE posts SET titulo = ?, texto = ? WHERE id = ?");
        $stmtUpdate->execute([$titulo, $texto, $post_id]);
        $_SESSION['flash_success'] = "Borrador de texto guardado correctamente.";
    } else {
        $_SESSION['flash_error'] = "El título y el texto no pueden estar vacíos.";
    }
    header("Location: generar.php?draft_id=" . $post_id);
    exit();
}

// 3b. Procesar acción de usar imagen personalizada (subida o url)
if (isset($_POST['action']) && $_POST['action'] === 'subir_personalizada' && isset($_POST['post_id'])) {
    $post_id = intval($_POST['post_id']);
    $titulo = trim($_POST['titulo']);
    $texto = trim($_POST['texto']);

    try {
        // Cargar post y cliente
        $stmtPost = $db->prepare("SELECT p.*, c.rubro, c.nombre, c.slug, c.color_primario, c.logo_url, c.fuente_titulo, c.fuente_texto, c.color_texto, c.nombre_autor, c.foto_autor_url, c.dominio, c.email_revisor FROM posts p JOIN clientes c ON p.cliente_id = c.id WHERE p.id = ?");
        $stmtPost->execute([$post_id]);
        $post = $stmtPost->fetch();

        if (!$post) {
            throw new Exception("Borrador no encontrado.");
        }

        $cliente_id = $post['cliente_id'];

        // Guardar textos editados primero
        $stmtUpdate = $db->prepare("UPDATE posts SET titulo = ?, texto = ? WHERE id = ?");
        $stmtUpdate->execute([$titulo, $texto, $post_id]);

        $imageUrl = '';

        // 1. Verificar si hay archivo subido
        if (isset($_FILES['custom_image_file']) && $_FILES['custom_image_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['custom_image_file']['tmp_name'];
            $fileName = $_FILES['custom_image_file']['name'];
            $fileSize = $_FILES['custom_image_file']['size'];
            $fileType = $_FILES['custom_image_file']['type'];

            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            $allowedfileExtensions = ['jpg', 'gif', 'png', 'jpeg', 'webp'];
            if (in_array($fileExtension, $allowedfileExtensions)) {
                $newFileName = $post['slug'] . '-manual-' . time() . '.' . $fileExtension;
                $destPath = UPLOAD_DIR . '/' . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $imageUrl = UPLOAD_URL . '/' . $newFileName;
                } else {
                    throw new Exception("Hubo un error al mover el archivo subido al directorio de uploads.");
                }
            } else {
                throw new Exception("Extensión de archivo no permitida para la portada del post.");
            }
        }
        // 2. Si no hay archivo pero hay URL
        elseif (!empty($_POST['custom_image_url'])) {
            $imageUrl = trim($_POST['custom_image_url']);
            if (filter_var($imageUrl, FILTER_VALIDATE_URL) === false) {
                throw new Exception("La URL de la imagen provista no es válida.");
            }
        } else {
            throw new Exception("Por favor selecciona un archivo o provee una URL de imagen válida.");
        }

        // Actualizar URL de imagen en el post
        $stmtUpdateImg = $db->prepare("UPDATE posts SET imagen_url = ? WHERE id = ?");
        $stmtUpdateImg->execute([$imageUrl, $post_id]);

        $_SESSION['flash_success'] = "¡Imagen manual establecida con éxito y post completado!";
        header("Location: generar.php?success_id=" . $post_id);
        exit();

    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Error al asociar imagen manual: " . $e->getMessage();
        header("Location: generar.php?draft_id=" . $post_id);
        exit();
    }
}

// 4. Procesar acción de diseñar imagen por IA (Paso 2/3)
if (isset($_POST['action']) && $_POST['action'] === 'diseñar_imagen' && isset($_POST['post_id'])) {
    $post_id = intval($_POST['post_id']);
    $titulo = trim($_POST['titulo']);
    $texto = trim($_POST['texto']);

    try {
        // Cargar post y cliente
        $stmtPost = $db->prepare("SELECT p.*, c.rubro, c.nombre, c.slug, c.color_primario, c.logo_url, c.fuente_titulo, c.fuente_texto, c.color_texto, c.nombre_autor, c.foto_autor_url, c.dominio, c.email_revisor FROM posts p JOIN clientes c ON p.cliente_id = c.id WHERE p.id = ?");
        $stmtPost->execute([$post_id]);
        $post = $stmtPost->fetch();

        if (!$post) {
            throw new Exception("Borrador no encontrado.");
        }

        $cliente_id = $post['cliente_id'];

        // Verificar presupuesto mensual
        if (GeminiClient::isMonthlyLimitExceeded($cliente_id)) {
            throw new Exception("Se ha superado el presupuesto mensual de IA asignado a este cliente.");
        }

        // Guardar textos editados primero
        $stmtUpdate = $db->prepare("UPDATE posts SET titulo = ?, texto = ? WHERE id = ?");
        $stmtUpdate->execute([$titulo, $texto, $post_id]);

        // Construir prompt para la imagen usando el tema de manera conceptual
        $colorDescription = "soft and elegant colors";
        if (!empty($post['color_primario'])) {
            if (strtolower($post['color_primario']) === '#e8b4b8') {
                $colorDescription = "warm pastel pink colors";
            } else {
                $colorDescription = "harmonious color palette matching " . $post['color_primario'];
            }
        }

        // 1. Refinar el prompt usando Gemini Text (bajo costo) para obtener un prompt visual detallado en inglés para Imagen 3
        $refineSystemInstruction = PromptTemplates::getImageRefineSystemInstruction();

        $refineInput = "Client Brand Name: {$post['nombre']}\n"
            . "Business Industry: {$post['rubro']}\n"
            . "Theme of the post: {$post['tema']}\n"
            . "Title of the post: {$post['titulo']}\n"
            . "Preferred background colors: {$colorDescription}";

        $imagePrompt = "";
        try {
            $refineResult = GeminiClient::generateJson($refineInput, $refineSystemInstruction);
            $refinedPrompt = isset($refineResult['prompt']) ? trim($refineResult['prompt']) : '';
            if (!empty($refinedPrompt)) {
                $imagePrompt = $refinedPrompt . ", high resolution, cinematic lighting, editorial portrait photography. "
                    . "Strictly NO text, NO logos, NO writing, NO layout, NO graphic design elements, clean photograph only.";
            }
        } catch (Exception $e) {
            error_log("Error al refinar prompt de imagen con Gemini: " . $e->getMessage());
        }

        // Fallback en caso de que falle la refinería
        if (empty($imagePrompt)) {
            $imagePrompt = "A professional, clean, close-up photograph representing: {$post['tema']}. "
                . "Beautiful lighting, clean background with {$colorDescription}. "
                . "Strictly NO text, NO logos, NO writing, NO layout, NO graphic design elements, clean photograph only.";
        }

        // Generar imagen con IA
        $imageBytes = GeminiClient::generateImage($imagePrompt);

        // Guardar banner localmente
        $filename = $post['slug'] . '-' . time() . '.jpg';
        $destPath = UPLOAD_DIR . '/' . $filename;

        if (file_put_contents($destPath, $imageBytes) === false) {
            throw new Exception("No se pudo escribir la imagen de portada en el servidor.");
        }

        $imageUrl = UPLOAD_URL . '/' . $filename;

        // Actualizar URL de imagen en el post
        $stmtUpdateImg = $db->prepare("UPDATE posts SET imagen_url = ? WHERE id = ?");
        $stmtUpdateImg->execute([$imageUrl, $post_id]);

        // Registrar consumos de texto del refiner e imagen
        $refineTokens = GeminiClient::getLastUsageMetadata();
        if ($refineTokens) {
            GeminiClient::logUsage(
                $cliente_id,
                GEMINI_TEXT_MODEL,
                'refinar_prompt_imagen',
                $refineTokens['promptTokenCount'] ?? 0,
                $refineTokens['candidatesTokenCount'] ?? 0,
                0.0,
                $post_id
            );
        }

        GeminiClient::logUsage(
            $cliente_id,
            GEMINI_IMAGE_MODEL,
            'generar_imagen',
            0,
            0,
            GEMINI_PRICE_IMAGE,
            $post_id
        );

        $_SESSION['flash_success'] = "¡Imagen de portada diseñada con éxito y post completado!";
        header("Location: generar.php?success_id=" . $post_id);
        exit();

    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Error al diseñar la imagen: " . $e->getMessage();
        header("Location: generar.php?draft_id=" . $post_id);
        exit();
    }
}

// 5. Procesar acción de generar texto del post (Paso 1)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cliente_id']) && isset($_POST['tema'])) {
    $cliente_id = intval($_POST['cliente_id']);
    $tema = trim($_POST['tema']);
    $sugerencia_id = isset($_POST['sugerencia_id']) ? intval($_POST['sugerencia_id']) : 0;

    // Obtener cliente
    $stmtCli = $db->prepare("SELECT * FROM clientes WHERE id = ? AND activo = 1");
    $stmtCli->execute([$cliente_id]);
    $cliente = $stmtCli->fetch();

    if (!$cliente) {
        $errorMsg = "El cliente seleccionado no existe o no está activo.";
    } elseif (empty($tema)) {
        $errorMsg = "Por favor ingresa o selecciona un tema.";
    } elseif (GeminiClient::isMonthlyLimitExceeded($cliente_id)) {
        $errorMsg = "No se puede generar contenido. Este cliente ha alcanzado su límite de presupuesto mensual de IA.";
    } else {
        try {
            // Obtener ángulo contextual de la sugerencia si aplica
            $sugerencia = null;
            if ($sugerencia_id > 0) {
                $stmtSug = $db->prepare("SELECT * FROM sugerencias_temas WHERE id = ?");
                $stmtSug->execute([$sugerencia_id]);
                $sugerencia = $stmtSug->fetch();
                
                if ($sugerencia && !empty($sugerencia['angulo_contextual'])) {
                    $details = json_decode($sugerencia['angulo_contextual'], true);
                    if (is_array($details)) {
                        $sugerencia['consejo_practico'] = $details['consejo_practico'] ?? '';
                        $sugerencia['servicio_a_promocionar'] = $details['servicio_a_promocionar'] ?? '';
                    } else {
                        $sugerencia['consejo_practico'] = $sugerencia['angulo_contextual'];
                        $sugerencia['servicio_a_promocionar'] = '';
                    }
                    $sugerencia['titulo_sugerido'] = $sugerencia['tema'];
                }
            }

            // Construir prompt para Gemini usando PromptTemplates
            $systemInstruction = PromptTemplates::getBlogTextSystemInstruction($cliente);

            if ($sugerencia && (!empty($sugerencia['consejo_practico']) || !empty($sugerencia['servicio_a_promocionar']))) {
                $textPrompt = "Información del Cliente:\n"
                    . "- Marca: {$cliente['nombre']}\n"
                    . "- Filosofía: Enfoque boutique, salud, rescate y restauración capilar real.\n\n"
                    . "Datos de la Propuesta a Desarrollar:\n"
                    . "- Título Base: {$sugerencia['titulo_sugerido']}\n"
                    . "- Consejo Clave a incluir: {$sugerencia['consejo_practico']}\n"
                    . "- Servicio a Promocionar e invitar: {$sugerencia['servicio_a_promocionar']}\n\n"
                    . "Por favor, redacta el post asegurándote de que el consejo técnico sea el protagonista del desarrollo y que la invitación a probar el servicio en el local se sienta orgánica y atractiva.";
            } else {
                $textPrompt = "Tema de Interés / Tema del post: " . $tema;
            }

            // Generar texto únicamente
            $textResult = GeminiClient::generateText($textPrompt, $systemInstruction);

            $titulo = $textResult['titulo'];
            $texto = $textResult['texto'];

            // Insertar post como borrador (estado 'pendiente', imagen vacía temporalmente)
            $tokenRevision = bin2hex(random_bytes(16));
            $stmtInsert = $db->prepare("
                INSERT INTO posts (cliente_id, tema, titulo, texto, imagen_url, estado, token_revision) 
                VALUES (?, ?, ?, ?, '', 'pendiente', ?)
            ");
            $stmtInsert->execute([$cliente_id, $tema, $titulo, $texto, $tokenRevision]);
            $postId = $db->lastInsertId();

            // Registrar consumo de tokens de texto
            $usage = GeminiClient::getLastUsageMetadata();
            if ($usage) {
                GeminiClient::logUsage(
                    $cliente_id,
                    GEMINI_TEXT_MODEL,
                    'generar_post',
                    $usage['promptTokenCount'] ?? 0,
                    $usage['candidatesTokenCount'] ?? 0,
                    0.0, // Costo calculado por la función logUsage
                    $postId
                );
            }

            // Si vino de una sugerencia, marcarla como generada
            if ($sugerencia_id > 0) {
                $stmtMarkSugerencia = $db->prepare("UPDATE sugerencias_temas SET estado = 'generado' WHERE id = ?");
                $stmtMarkSugerencia->execute([$sugerencia_id]);
            }

            $_SESSION['flash_success'] = "Paso 1 Completado: Texto redactado correctamente con Gemini. Por favor revísalo y edítalo si lo deseas.";
            header("Location: generar.php?draft_id=" . $postId);
            exit();

        } catch (Exception $e) {
            $errorMsg = "Error en la generación con IA: " . $e->getMessage();
        }
    }
}

// 6. Cargar datos para vistas GET
$draftPost = null;
$successPost = null;
$selected_sugerencia = null;

if (isset($_GET['draft_id'])) {
    $draft_id = intval($_GET['draft_id']);
    $stmtDraft = $db->prepare("SELECT p.*, c.nombre as cliente_nombre, c.limite_mensual_usd FROM posts p JOIN clientes c ON p.cliente_id = c.id WHERE p.id = ?");
    $stmtDraft->execute([$draft_id]);
    $draftPost = $stmtDraft->fetch();
} elseif (isset($_GET['success_id'])) {
    $success_id = intval($_GET['success_id']);
    $stmtSuccess = $db->prepare("
        SELECT p.*, 
               c.nombre as cliente_nombre, c.logo_url as cliente_logo, c.foto_autor_url as cliente_foto_autor,
               c.nombre_autor as cliente_nombre_autor, c.color_primario as cliente_color_primario,
               c.color_texto as cliente_color_texto, c.fuente_titulo as cliente_fuente_titulo,
               c.fuente_texto as cliente_fuente_texto
        FROM posts p 
        JOIN clientes c ON p.cliente_id = c.id 
        WHERE p.id = ?
    ");
    $stmtSuccess->execute([$success_id]);
    $successPost = $stmtSuccess->fetch();
} elseif (isset($_GET['sugerencia_id'])) {
    $sug_id = intval($_GET['sugerencia_id']);
    $stmtSug = $db->prepare("SELECT * FROM sugerencias_temas WHERE id = ? AND estado = 'pendiente'");
    $stmtSug->execute([$sug_id]);
    $selected_sugerencia = $stmtSug->fetch();

    if ($selected_sugerencia) {
        $selected_cliente_id = $selected_sugerencia['cliente_id'];
        $selected_tema = $selected_sugerencia['tema'];
    }
}

include __DIR__ . '/../layout_header.php';
?>

<!-- Inyectar hoja de estilos modularizada -->
<link rel="stylesheet" href="generar.css?v=<?php echo time(); ?>">

<div style="margin-bottom: 25px;">
    <h1 style="font-size: 28px; margin-bottom: 5px;">Generador de Contenido Optimizado</h1>
    <p style="color: var(--text-secondary); font-size: 14px;">Escribe borradores y diseña banners optimizando el consumo
        de créditos de IA.</p>
</div>

<!-- Cargar el layout de loader animado y alertas comunes -->
<?php include __DIR__ . '/views/loader_and_alerts.php'; ?>

<!-- Cargar la vista HTML correspondiente -->
<?php
if ($successPost) {
    include __DIR__ . '/views/step_3_preview.php';
} elseif ($draftPost) {
    include __DIR__ . '/views/step_2_draft.php';
} else {
    include __DIR__ . '/views/step_1_text.php';
}
?>

<!-- Inyectar archivo JavaScript modularizado -->
<script src="generar.js?v=<?php echo time(); ?>"></script>

<?php include __DIR__ . '/../layout_footer.php'; ?>