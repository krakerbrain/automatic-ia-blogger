<?php
/**
 * Módulo de Revisión y Publicación de Posts (Público por Token)
 */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/DB.php';
require_once __DIR__ . '/../../lib/Publisher.php';

$db = DB::getInstance();

// 1. Validar Token de Revisión
if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("<div style='padding: 50px; text-align: center; font-family: sans-serif; background: #0B0F19; color: #EF4444;'><h2>Error: Token de revisión no proporcionado.</h2></div>");
}

$token = trim($_GET['token']);
$stmt = $db->prepare("SELECT p.*, c.id as cliente_id, c.nombre as cliente_nombre, c.logo_url as cliente_logo, c.color_primario, c.color_texto, c.fuente_titulo, c.fuente_texto, c.foto_autor_url, c.nombre_autor, c.dominio FROM posts p JOIN clientes c ON p.cliente_id = c.id WHERE p.token_revision = ?");
$stmt->execute([$token]);
$post = $stmt->fetch();

if (!$post) {
    die("<div style='padding: 50px; text-align: center; font-family: sans-serif; background: #0B0F19; color: #EF4444;'><h2>Error: Token de revisión inválido o post inexistente.</h2></div>");
}

$postId = $post['id'];
$clienteId = $post['cliente_id'];
$mensaje_alerta = null;
$error_alerta = null;

// 2. Procesar Acciones (Aprobar / Rechazar / Reintentar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'aprobar') {
            // Actualizar estado en la plataforma
            $stmtApprove = $db->prepare("UPDATE posts SET estado = 'aprobado', fecha_aprobacion = NOW() WHERE id = ?");
            $stmtApprove->execute([$postId]);
            
            // Intentar publicación
            if (isset($_SESSION['is_demo']) && $_SESSION['is_demo'] === true) {
                $db->prepare("UPDATE posts SET publicacion_exitosa = 1 WHERE id = ?")->execute([$postId]);
                $pubRes = ['ok' => true];
            } else {
                $pubRes = Publisher::publish($postId);
            }
            
            if ($pubRes['ok']) {
                $mensaje_alerta = "El post ha sido aprobado y publicado en el sitio del cliente con éxito.";
            } else {
                $error_alerta = "El post fue aprobado localmente, pero falló la publicación en el sitio del cliente: " . $pubRes['error'];
            }
            
            // Recargar datos actualizados
            $stmt->execute([$token]);
            $post = $stmt->fetch();

        } elseif ($action === 'rechazar') {
            $comentario = trim($_POST['comentario_rechazo'] ?? '');
            
            if (empty($comentario)) {
                $error_alerta = "Debes ingresar un comentario de rechazo obligatorio.";
            } else {
                $stmtReject = $db->prepare("UPDATE posts SET estado = 'rechazado', comentario_rechazo = ?, publicacion_exitosa = NULL, fecha_aprobacion = NULL WHERE id = ?");
                $stmtReject->execute([$comentario, $postId]);
                $mensaje_alerta = "El post ha sido rechazado. Los redactores recibirán tus comentarios.";
                
                // Recargar datos actualizados
                $stmt->execute([$token]);
                $post = $stmt->fetch();
            }
        } elseif ($action === 'reintentar') {
            // Solo reintentar si está aprobado
            if ($post['estado'] === 'aprobado') {
                if (isset($_SESSION['is_demo']) && $_SESSION['is_demo'] === true) {
                    $db->prepare("UPDATE posts SET publicacion_exitosa = 1 WHERE id = ?")->execute([$postId]);
                    $pubRes = ['ok' => true];
                } else {
                    $pubRes = Publisher::publish($postId);
                }
                if ($pubRes['ok']) {
                    $mensaje_alerta = "¡Reintento exitoso! El post se ha publicado correctamente.";
                } else {
                    $error_alerta = "El reintento de publicación volvió a fallar: " . $pubRes['error'];
                }
                
                // Recargar datos actualizados
                $stmt->execute([$token]);
                $post = $stmt->fetch();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisar Entrada: <?php echo htmlspecialchars($post['titulo']); ?></title>
    <!-- Google Fonts dinámicas según preferencias del cliente -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Georgia&display=swap" rel="stylesheet">
    
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #F3F4F6;
            font-family: <?php echo $post['fuente_texto']; ?>, sans-serif;
            color: #1F2937;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Barra de navegación superior de la marca del cliente */
        .brand-header {
            background-color: #111827;
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: white;
            border-bottom: 4px solid <?php echo $post['color_primario']; ?>;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
        }

        .brand-logo img {
            max-height: 40px;
            border-radius: 6px;
        }

        .brand-name {
            font-family: <?php echo $post['fuente_titulo']; ?>;
            font-size: 20px;
            font-weight: bold;
        }

        /* Contenedor principal */
        .review-wrapper {
            flex-grow: 1;
            max-width: 900px;
            width: 100%;
            margin: 30px auto;
            padding: 0 20px;
            box-sizing: border-box;
        }

        /* Alertas visuales */
        .status-alert {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 15px;
            font-weight: 500;
        }

        .status-alert-success { background: #D1FAE5; border: 1px solid #10B981; color: #065F46; }
        .status-alert-danger { background: #FEE2E2; border: 1px solid #EF4444; color: #991B1B; }
        .status-alert-warning { background: #FEF3C7; border: 1px solid #F59E0B; color: #92400E; }

        /* Tarjeta del artículo */
        .blog-post-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 40px;
            border: 1px solid #E5E7EB;
        }

        .blog-banner {
            width: 100%;
            max-height: 450px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .blog-title {
            font-family: <?php echo $post['fuente_titulo']; ?>;
            color: <?php echo $post['color_texto']; ?>;
            font-size: 36px;
            line-height: 1.25;
            margin: 0 0 20px 0;
            font-weight: 700;
        }

        .blog-content {
            font-family: <?php echo $post['fuente_texto']; ?>;
            color: #374151;
            font-size: 17px;
            line-height: 1.8;
            text-align: justify;
            white-space: pre-line;
        }

        .blog-author {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 35px;
            padding-top: 25px;
            border-top: 1px solid #E5E7EB;
        }

        .blog-author img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid <?php echo $post['color_primario']; ?>;
        }

        .blog-author-name {
            font-weight: bold;
            font-size: 15px;
            color: #1F2937;
        }

        /* Consola de Acciones */
        .review-console {
            background: #1F2937;
            border-radius: 16px;
            padding: 30px;
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 50px;
        }

        .console-title {
            font-family: 'Outfit', sans-serif;
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 10px;
        }

        .btn-console {
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            padding: 14px 28px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
        }

        .btn-console-approve {
            background-color: <?php echo $post['color_primario']; ?>;
            color: white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .btn-console-approve:hover {
            transform: translateY(-1px);
            opacity: 0.9;
        }

        .btn-console-reject {
            background-color: #EF4444;
            color: white;
        }

        .btn-console-reject:hover {
            background-color: #DC2626;
        }

        .btn-console-retry {
            background-color: #F59E0B;
            color: white;
        }

        .btn-console-retry:hover {
            background-color: #D97706;
        }

        .reject-textarea {
            width: 100%;
            height: 100px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 8px;
            padding: 12px;
            color: white;
            font-family: inherit;
            font-size: 14px;
            margin-bottom: 15px;
            resize: none;
            box-sizing: border-box;
        }

        .reject-textarea:focus {
            outline: none;
            border-color: <?php echo $post['color_primario']; ?>;
        }
    </style>
</head>
<body>

    <!-- Cabecera de la Marca -->
    <header class="brand-header">
        <a href="<?php echo htmlspecialchars($post['dominio']); ?>" target="_blank" class="brand-logo">
            <?php if (!empty($post['cliente_logo'])): ?>
                <img src="<?php echo htmlspecialchars($post['cliente_logo']); ?>" alt="Logo">
            <?php endif; ?>
            <span class="brand-name"><?php echo htmlspecialchars($post['cliente_nombre']); ?></span>
        </a>
        <div style="font-size: 13px; color: #9CA3AF;">Modo de Revisión Editorial</div>
    </header>

    <!-- Contenedor de Revisión -->
    <div class="review-wrapper">

        <!-- Mensajes de Acción Reciente -->
        <?php if ($mensaje_alerta): ?>
            <div class="status-alert status-alert-success">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div><?php echo $mensaje_alerta; ?></div>
            </div>
        <?php endif; ?>

        <?php if ($error_alerta): ?>
            <div class="status-alert status-alert-danger">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div><?php echo $error_alerta; ?></div>
            </div>
        <?php endif; ?>

        <!-- Alertas de Estado Actual del Post -->
        <?php if ($post['estado'] === 'aprobado'): ?>
            <div class="status-alert status-alert-success">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <strong>Post Aprobado.</strong> Fecha de aprobación: <?php echo date('d/m/Y H:i', strtotime($post['fecha_aprobacion'])); ?>.<br>
                    <?php if ($post['publicacion_exitosa'] === 1): ?>
                        <span style="font-size: 13px; opacity: 0.9;">✓ Publicado de forma exitosa en el sitio remoto.</span>
                    <?php else: ?>
                        <span style="font-size: 13px; opacity: 0.9; color: #B91C1C;">✗ Falló la publicación en el sitio remoto. Puedes reintentarla en el panel inferior.</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($post['estado'] === 'rechazado'): ?>
            <div class="status-alert status-alert-danger">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <strong>Post Rechazado.</strong><br>
                    <span style="font-size: 13px; opacity: 0.9;">Motivo: "<?php echo htmlspecialchars($post['comentario_rechazo']); ?>"</span>
                </div>
            </div>
        <?php else: ?>
            <div class="status-alert status-alert-warning">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div><strong>Pendiente de revisión.</strong> Utiliza la consola de la parte inferior para aprobar o rechazar esta publicación.</div>
            </div>
        <?php endif; ?>

        <!-- Cuerpo del Artículo -->
        <article class="blog-post-card">
            <!-- Imagen -->
            <img class="blog-banner" src="<?php echo htmlspecialchars($post['imagen_url']); ?>" alt="Banner de Blog">

            <!-- Título -->
            <h1 class="blog-title"><?php echo htmlspecialchars($post['titulo']); ?></h1>

            <!-- Contenido -->
            <div class="blog-content">
                <?php echo htmlspecialchars($post['texto']); ?>
            </div>

            <!-- Autor -->
            <div class="blog-author">
                <img src="<?php echo htmlspecialchars($post['foto_autor_url']); ?>" alt="Foto Autor">
                <div>
                    <div class="blog-author-name"><?php echo htmlspecialchars($post['nombre_autor']); ?></div>
                    <div style="font-size: 12px; color: #6B7280;">Autor e Instructor</div>
                </div>
            </div>
        </article>

        <!-- Consola de Aprobación/Rechazo (Solo visible para interactuar) -->
        <div class="review-console">
            <h3 class="console-title">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                Acciones de Evaluación
            </h3>

            <?php if ($post['estado'] === 'pendiente'): ?>
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <!-- APROBAR -->
                    <div>
                        <p style="margin-bottom: 12px; font-size: 14px; color: #D1D5DB;">Si apruebas este post, se publicará de manera inmediata en la web del cliente y se actualizará su estado.</p>
                        <form action="revisar.php?token=<?php echo $token; ?>" method="POST">
                            <input type="hidden" name="action" value="aprobar">
                            <button type="submit" class="btn-console btn-console-approve">
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                                Aprobar y Publicar Post
                            </button>
                        </form>
                    </div>

                    <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 10px 0;">

                    <!-- RECHAZAR -->
                    <div>
                        <p style="margin-bottom: 12px; font-size: 14px; color: #D1D5DB;">Si deseas rechazar el post, ingresa la razón obligatoria para que el equipo pueda re-generar el contenido.</p>
                        <form action="revisar.php?token=<?php echo $token; ?>" method="POST">
                            <input type="hidden" name="action" value="rechazar">
                            <textarea name="comentario_rechazo" class="reject-textarea" placeholder="Escribe el motivo del rechazo o los cambios que te gustaría ver..." required></textarea>
                            <button type="submit" class="btn-console btn-console-reject">
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                                Rechazar y Devolver
                            </button>
                        </form>
                    </div>
                </div>
            <?php elseif ($post['estado'] === 'aprobado' && $post['publicacion_exitosa'] === 0): ?>
                <!-- REINTENTAR PUBLICACIÓN -->
                <div>
                    <p style="margin-bottom: 15px; font-size: 14px; color: #FCA5A5;"><strong>Atención:</strong> El post ya fue aprobado, pero no se pudo enviar correctamente a tu sitio web. Presiona el botón a continuación para reintentar la conexión.</p>
                    <form action="revisar.php?token=<?php echo $token; ?>" method="POST">
                        <input type="hidden" name="action" value="reintentar">
                        <button type="submit" class="btn-console btn-console-retry">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.006 11H19"/></svg>
                            Reintentar Publicación Remota
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 10px 0; color: #9CA3AF; font-size: 14px;">
                    Este post ya ha sido procesado (Estado: <strong><?php echo strtoupper($post['estado']); ?></strong>). No se requieren más acciones.
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>
