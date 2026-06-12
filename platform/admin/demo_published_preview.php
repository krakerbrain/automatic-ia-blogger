<?php
/**
 * Vista de simulación de publicación en el sitio web del cliente (Modo Demo)
 */
require_once __DIR__ . '/auth_check.php';

$db = DB::getInstance();

// Asegurar que la sesión de demo esté activa
if (!isset($_SESSION['is_demo']) || $_SESSION['is_demo'] !== true) {
    header("Location: " . BASE_URL . "/platform/admin/login.php");
    exit();
}

$post_id = intval($_GET['post_id'] ?? 0);

// Cargar información del post y del cliente
$stmt = $db->prepare("
    SELECT p.*, 
           c.nombre as cliente_nombre, c.logo_url as cliente_logo, 
           c.fuente_titulo as cliente_fuente_titulo, c.fuente_texto as cliente_fuente_texto, 
           c.color_primario as cliente_color_primario, c.color_texto as cliente_color_texto, 
           c.nombre_autor as cliente_nombre_autor, c.foto_autor_url as cliente_foto_autor, 
           c.dominio as cliente_dominio, c.slug as cliente_slug, c.rubro as cliente_rubro
    FROM posts p 
    JOIN clientes c ON p.cliente_id = c.id 
    WHERE p.id = ?
");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    echo "Post no encontrado.";
    exit();
}

// Generar un slug simple para la URL simulada
$urlSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $post['titulo'])));
$mockUrl = "https://www." . $post['cliente_slug'] . ".com/blog/" . $urlSlug;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Publicado (Simulado) — AI Blogger Central</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-base: #0B0F19;
            --bg-surface: rgba(22, 28, 45, 0.6);
            --border-glass: rgba(255, 255, 255, 0.08);
            --text-primary: #F3F4F6;
            --text-secondary: #9CA3AF;
            --color-primary: #8B5CF6;
            --color-primary-hover: #7C3AED;
            --font-ui: 'Inter', sans-serif;
            --font-display: 'Outfit', sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-base);
            color: var(--text-primary);
            font-family: var(--font-ui);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Notificación Superior */
        .success-bar {
            background: linear-gradient(135deg, #10B981, #059669);
            color: #ffffff;
            padding: 16px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);
            z-index: 100;
        }

        .success-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .success-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            width: 28px;
            height: 28px;
            border-radius: 50%;
        }

        .success-text h4 {
            font-family: var(--font-display);
            font-size: 15px;
            font-weight: 700;
        }

        .success-text p {
            font-size: 12px;
            opacity: 0.9;
        }

        .success-actions {
            display: flex;
            gap: 12px;
        }

        .btn-action {
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .btn-back-demo {
            background: #ffffff;
            color: #059669;
        }

        .btn-back-demo:hover {
            background: #f0fdf4;
            transform: translateY(-1px);
        }

        .btn-admin {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        .btn-admin:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }

        /* Simulador de Navegador */
        .browser-container {
            flex: 1;
            margin: 20px;
            background: #1e1e24;
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
        }

        /* Barra de Direcciones */
        .browser-navbar {
            background: #18181c;
            height: 48px;
            display: flex;
            align-items: center;
            padding: 0 16px;
            gap: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .browser-dots {
            display: flex;
            gap: 6px;
        }

        .browser-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .dot-red { background: #ff5f56; }
        .dot-yellow { background: #ffbd2e; }
        .dot-green { background: #27c93f; }

        .browser-address-bar {
            flex: 1;
            background: #2b2b36;
            border-radius: 8px;
            height: 28px;
            display: flex;
            align-items: center;
            padding: 0 12px;
            font-size: 12px;
            color: #a5a5b1;
            gap: 8px;
            border: 1px solid rgba(255, 255, 255, 0.03);
        }

        /* Ventana del Sitio */
        .browser-viewport {
            flex: 1;
            background: #0D0E12;
            overflow-y: auto;
            /* Degradado oscuro arriba abajo de la página de ejemplo */
            background-image: linear-gradient(to bottom, #111319 0%, #08090C 100%);
            display: flex;
            flex-direction: column;
        }

        /* Estilo de la Página Web del Cliente */
        .web-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 8%;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(8px);
            position: sticky;
            top: 0;
            background: rgba(17, 19, 25, 0.85);
            z-index: 10;
        }

        .web-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .web-logo-img {
            height: 36px;
            width: 36px;
            border-radius: 8px;
            object-fit: cover;
        }

        .web-logo-text {
            font-family: <?php echo htmlspecialchars($post['cliente_fuente_titulo']); ?>;
            font-weight: 700;
            font-size: 18px;
            color: #ffffff;
        }

        .web-nav {
            display: flex;
            gap: 24px;
            list-style: none;
        }

        .web-nav-link {
            color: #9CA3AF;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }

        .web-nav-link:hover, .web-nav-link.active {
            color: <?php echo htmlspecialchars($post['cliente_color_primario']); ?>;
        }

        /* Contenido del Blog */
        .blog-hero {
            padding: 60px 8% 40px 8%;
            text-align: center;
            max-width: 900px;
            margin: 0 auto;
        }

        .blog-category {
            display: inline-block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: <?php echo htmlspecialchars($post['cliente_color_primario']); ?>;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .blog-title {
            font-family: <?php echo htmlspecialchars($post['cliente_fuente_titulo']); ?>;
            font-size: 40px;
            font-weight: 800;
            line-height: 1.2;
            color: #ffffff;
            margin-bottom: 24px;
        }

        .blog-meta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            font-size: 13px;
            color: #6B7280;
        }

        .blog-author {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #D1D5DB;
        }

        .blog-author-img {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
            border: 1.5px solid <?php echo htmlspecialchars($post['cliente_color_primario']); ?>;
        }

        .blog-featured-image-wrapper {
            max-width: 900px;
            margin: 0 auto 50px auto;
            padding: 0 8%;
            width: 100%;
        }

        .blog-featured-image {
            width: 100%;
            max-height: 480px;
            object-fit: cover;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        /* Cuerpo del Post */
        .blog-body-container {
            max-width: 780px;
            margin: 0 auto;
            padding: 0 8% 80px 8%;
            font-family: <?php echo htmlspecialchars($post['cliente_fuente_texto']); ?>;
            color: #D1D5DB;
            font-size: 17px;
            line-height: 1.8;
            text-align: justify;
        }

        .blog-body-container p {
            margin-bottom: 24px;
        }

        .blog-body-container h1, .blog-body-container h2, .blog-body-container h3 {
            color: #ffffff;
            font-family: <?php echo htmlspecialchars($post['cliente_fuente_titulo']); ?>;
            margin: 40px 0 20px 0;
            font-weight: 700;
        }

        .blog-body-container h2 {
            font-size: 24px;
        }

        .blog-body-container blockquote {
            border-left: 4px solid <?php echo htmlspecialchars($post['cliente_color_primario']); ?>;
            padding-left: 20px;
            font-style: italic;
            color: #9CA3AF;
            margin: 30px 0;
        }

        /* Caja de CTA Comercial al final */
        .blog-cta-box {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.02) 0%, rgba(255, 255, 255, 0.05) 100%);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 16px;
            padding: 30px;
            margin-top: 50px;
            text-align: center;
        }

        .blog-cta-title {
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 10px;
        }

        .blog-cta-desc {
            font-size: 14px;
            color: #9CA3AF;
            margin-bottom: 20px;
        }

        .blog-cta-button {
            display: inline-block;
            background: <?php echo htmlspecialchars($post['cliente_color_primario']); ?>;
            color: <?php echo htmlspecialchars($post['cliente_color_texto']); ?>;
            font-weight: 700;
            font-size: 14px;
            padding: 10px 24px;
            border-radius: 8px;
            text-decoration: none;
            transition: transform 0.2s;
        }

        .blog-cta-button:hover {
            transform: scale(1.03);
        }

        /* Responsive Styles for Mobile Devices */
        @media (max-width: 768px) {
            .success-bar {
                flex-direction: column;
                gap: 15px;
                padding: 16px 15px;
                text-align: center;
            }
            .success-info {
                flex-direction: column;
                align-items: center;
                gap: 8px;
            }
            .success-text h4 {
                font-size: 14px;
            }
            .success-text p {
                font-size: 11px;
            }
            .success-actions {
                width: 100%;
                flex-direction: column;
                gap: 8px;
            }
            .btn-action {
                width: 100%;
                text-align: center;
                box-sizing: border-box;
            }
            .browser-container {
                margin: 8px;
                border-radius: 12px;
            }
            .browser-navbar {
                padding: 0 10px;
                gap: 10px;
            }
            .browser-address-bar {
                font-size: 11px;
                padding: 0 8px;
            }
            .web-header {
                flex-direction: column;
                padding: 16px 12px;
                gap: 12px;
            }
            .web-nav {
                gap: 12px;
                justify-content: center;
                flex-wrap: wrap;
            }
            .blog-hero {
                padding: 30px 12px 20px 12px;
            }
            .blog-title {
                font-size: 24px;
                margin-bottom: 16px;
            }
            .blog-meta {
                flex-wrap: wrap;
                gap: 8px;
                font-size: 12px;
            }
            .blog-featured-image-wrapper {
                padding: 0 12px;
                margin-bottom: 24px;
            }
            .blog-featured-image {
                max-height: 240px;
                border-radius: 8px;
            }
            .blog-body-container {
                padding: 0 12px 40px 12px;
                font-size: 15px;
                line-height: 1.7;
            }
            .blog-body-container h2 {
                font-size: 19px;
                margin: 30px 0 15px 0;
            }
            .blog-cta-box {
                padding: 20px 12px;
                margin-top: 30px;
            }
            .blog-cta-title {
                font-size: 16px;
            }
            .blog-cta-desc {
                font-size: 13px;
            }
            .blog-cta-button {
                width: 100%;
                text-align: center;
                box-sizing: border-box;
            }
        }

        /* Guía Interactiva */
        .demo-guide-card {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 320px;
            max-height: 400px;
            background: rgba(17, 24, 39, 0.95);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(139, 92, 246, 0.4);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 12px;
            animation: slideInRight 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        .demo-guide-card.collapsed {
            width: 48px;
            height: 48px;
            padding: 0;
            border-radius: 50%;
            cursor: pointer;
            border-color: var(--color-primary);
            box-shadow: 0 0 15px var(--color-primary);
            justify-content: center;
            align-items: center;
            animation: pulse-guide-btn 2s infinite ease-in-out;
        }

        @keyframes pulse-guide-btn {
            0%, 100% { transform: scale(1); box-shadow: 0 0 15px rgba(139, 92, 246, 0.6); }
            50% { transform: scale(1.08); box-shadow: 0 0 25px rgba(139, 92, 246, 0.9); }
        }

        /* Ocultar contenido al colapsar */
        .demo-guide-card.collapsed .guide-body,
        .demo-guide-card.collapsed .guide-footer,
        .demo-guide-card.collapsed .guide-close-btn {
            display: none !important;
        }

        .demo-guide-card.collapsed .guide-header {
            border: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .demo-guide-card.collapsed .guide-header h4 {
            display: none !important;
        }

        .demo-guide-card.collapsed .guide-icon {
            font-size: 20px;
            margin: 0;
            animation: none;
        }

        /* Botón de Cerrar/Colapsar */
        .guide-close-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            margin-left: auto;
        }

        .guide-close-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .guide-header {
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 8px;
            width: 100%;
        }

        .guide-icon {
            font-size: 18px;
        }

        .guide-header h4 {
            font-family: var(--font-display);
            font-size: 15px;
            font-weight: 700;
            color: #ffffff;
            margin: 0;
        }

        .guide-body p {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.5;
            margin: 0;
        }

        .guide-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: var(--text-secondary);
        }

        .guide-step-badge {
            background: rgba(139, 92, 246, 0.2);
            color: #C084FC;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 600;
        }

        /* Destacar botones de acción */
        .guide-highlight {
            box-shadow: 0 0 0 3px var(--color-primary), 0 0 15px var(--color-primary) !important;
            animation: pulse-border-glow 2s infinite ease-in-out;
        }

        @keyframes pulse-border-glow {
            0%, 100% { box-shadow: 0 0 0 3px var(--color-primary), 0 0 15px var(--color-primary); }
            50% { box-shadow: 0 0 0 5px #f43f5e, 0 0 25px #f43f5e; }
        }
    </style>
</head>
<body>

    <!-- Guía Interactiva Paso a Paso (Publicación Exitosa) -->
    <div class="demo-guide-card" id="demo-guide" onclick="expandGuide(event)">
        <div class="guide-header">
            <span class="guide-icon">✨</span>
            <h4>Guía de la Demo</h4>
            <button class="guide-close-btn" onclick="toggleGuide(event)" title="Minimizar ayuda">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="guide-body">
            <p id="guide-text">
                ¡Enhorabuena! El artículo ha sido <strong>publicado</strong> y formateado con los estilos únicos de tu blog. Puedes hacer scroll para ver el resultado final integrado. Haz clic en <strong>"Elegir Otro Cliente"</strong> o <strong>"Entrar al Panel Admin"</strong>.
            </p>
        </div>
        <div class="guide-footer">
            <span class="guide-step-badge" id="guide-step">Paso 5 de 5: Visualización</span>
        </div>
    </div>

    <!-- Barra de Éxito de Publicación -->
    <div class="success-bar">
        <div class="success-info">
            <div class="success-icon">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
            </div>
            <div class="success-text">
                <h4>¡Post Publicado con Éxito!</h4>
                <p>El artículo se ha sincronizado y publicado correctamente en tu sitio web.</p>
            </div>
        </div>
        
        <div class="success-actions">
            <a href="demo_onboarding.php" class="btn-action btn-back-demo guide-highlight">← Elegir Otro Cliente</a>
            <a href="demo_onboarding.php?action=admin" class="btn-action btn-admin">Entrar al Panel Admin</a>
        </div>
    </div>

    <!-- Simulador de Ventana de Navegador -->
    <div class="browser-container">
        <!-- Encabezado del Navegador -->
        <div class="browser-navbar">
            <div class="browser-dots">
                <div class="browser-dot dot-red"></div>
                <div class="browser-dot dot-yellow"></div>
                <div class="browser-dot dot-green"></div>
            </div>
            
            <div class="browser-address-bar">
                <!-- Icono de Candado Seguro -->
                <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                <span><?php echo htmlspecialchars($mockUrl); ?></span>
            </div>
        </div>

        <!-- Vista Interna del Sitio Web (Viewport) -->
        <div class="browser-viewport">
            
            <!-- Cabecera del Sitio Web del Cliente -->
            <header class="web-header">
                <div class="web-logo">
                    <?php if (!empty($post['cliente_logo'])): ?>
                        <img src="<?php echo htmlspecialchars($post['cliente_logo']); ?>" alt="Logo" class="web-logo-img">
                    <?php endif; ?>
                    <span class="web-logo-text"><?php echo htmlspecialchars($post['cliente_nombre']); ?></span>
                </div>
                
                <nav>
                    <ul class="web-nav">
                        <li><a href="#" class="web-nav-link">Inicio</a></li>
                        <li><a href="#" class="web-nav-link">Servicios</a></li>
                        <li><a href="#" class="web-nav-link active">Blog</a></li>
                        <li><a href="#" class="web-nav-link">Contacto</a></li>
                    </ul>
                </nav>
            </header>

            <!-- Detalle del Post en el Blog -->
            <article>
                <div class="blog-hero">
                    <span class="blog-category"><?php echo htmlspecialchars($post['cliente_rubro']); ?></span>
                    <h1 class="blog-title"><?php echo htmlspecialchars($post['titulo']); ?></h1>
                    
                    <div class="blog-meta">
                        <div class="blog-author">
                            <img src="<?php echo htmlspecialchars($post['cliente_foto_autor']); ?>" alt="Autor" class="blog-author-img">
                            <span>Por <strong><?php echo htmlspecialchars($post['cliente_nombre_autor']); ?></strong></span>
                        </div>
                        <span>•</span>
                        <span><?php echo date('d M, Y', strtotime($post['fecha_aprobacion'] ?? 'now')); ?></span>
                        <span>•</span>
                        <span>Lectura: 3 min</span>
                    </div>
                </div>

                <!-- Imagen Destacada del Post -->
                <?php if (!empty($post['imagen_url'])): ?>
                    <div class="blog-featured-image-wrapper">
                        <img src="<?php echo htmlspecialchars($post['imagen_url']); ?>" alt="Portada del Post" class="blog-featured-image">
                    </div>
                <?php endif; ?>

                <!-- Cuerpo del Post -->
                <div class="blog-body-container">
                    <?php 
                    // Convertir saltos de línea a párrafos y dar formato a negritas sencillas
                    $parrafos = explode("\n\n", $post['texto']);
                    foreach ($parrafos as $p) {
                        $p = trim($p);
                        if (empty($p)) continue;
                        
                        // Si empieza con un número o asteriscos (tipo lista o subtítulo)
                        if (preg_replace('/[^a-zA-Z]/', '', substr($p, 0, 5)) === '' && (strpos($p, '1.') === 0 || strpos($p, '2.') === 0 || strpos($p, '3.') === 0 || strpos($p, '4.') === 0 || strpos($p, '5.') === 0)) {
                            // Dar formato a listas numeradas
                            echo "<div style='margin-bottom: 20px; padding-left: 15px; border-left: 2px solid " . htmlspecialchars($post['cliente_color_primario']) . ";'>" . nl2br(preg_replace('/\\*\\*(.*?)\\*\\*/', '<strong>$1</strong>', $p)) . "</div>";
                        } elseif (strpos($p, '*') === 0 && strpos($p, '*Consejo') !== false) {
                            // Es una cita o consejo final
                            echo "<blockquote>" . nl2br(preg_replace('/\\*\\*(.*?)\\*\\*/', '<strong>$1</strong>', substr($p, 1))) . "</blockquote>";
                        } else {
                            echo "<p>" . nl2br(preg_replace('/\\*\\*(.*?)\\*\\*/', '<strong>$1</strong>', $p)) . "</p>";
                        }
                    }
                    ?>

                    <!-- Caja de Llamada a la Acción (CTA) Dinámica -->
                    <div class="blog-cta-box">
                        <h4 class="blog-cta-title">¿Quieres experimentar resultados profesionales?</h4>
                        <p class="blog-cta-desc">Lleva el cuidado de tu estilo de vida al siguiente nivel con el asesoramiento de nuestros expertos.</p>
                        <a href="#" class="blog-cta-button" onclick="alert('Esta es una acción simulada del sitio del cliente.'); return false;">Agendar Servicio / Contacto</a>
                    </div>
                </div>
            </article>

        </div>
    </div>

    <script>
        // Funciones de colapsar y expandir guía
        function toggleGuide(e) {
            if (e) e.stopPropagation();
            const card = document.getElementById('demo-guide');
            if (card) {
                card.classList.add('collapsed');
                const icon = card.querySelector('.guide-icon');
                if (icon) icon.innerText = '💡';
            }
        }

        function expandGuide(e) {
            const card = document.getElementById('demo-guide');
            if (card && card.classList.contains('collapsed')) {
                card.classList.remove('collapsed');
                const icon = card.querySelector('.guide-icon');
                if (icon) icon.innerText = '✨';
            }
        }
    </script>
</body>
</html>
