<?php
/**
 * Ejemplo Receptor - blog.php
 * Este archivo demuestra la opción sin base de datos (Opción B).
 * Hace una llamada a la API central para obtener los posts aprobados del cliente
 * y los renderiza con su propio diseño y estilos visuales.
 */

// 1. Configurar el slug del cliente
$clienteSlug = 'adri-hair-style';

// 2. Construir la URL de la API del servidor central (dinámica para localhost/producción)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$apiUrl = $protocol . $host . "/platform/api/posts.php?cliente=" . $clienteSlug;

// 3. Obtener los posts del API
$apiJson = @file_get_contents($apiUrl);
$postsData = [];
$errorMsg = null;

if ($apiJson === false) {
    $errorMsg = "No se pudo conectar con la plataforma central de blogs en: " . htmlspecialchars($apiUrl);
} else {
    $response = json_decode($apiJson, true);
    if (isset($response['ok']) && $response['ok'] === true) {
        $postsData = $response['posts'];
    } else {
        $errorMsg = "Error retornado por la API: " . ($response['error'] ?? 'Desconocido');
    }
}

// Estilos de marca fijos del cliente (Adri Hair Style)
$colorPrimario = '#E8B4B8';
$colorTexto = '#2C2C2A';
$fuenteTitulo = 'Georgia, serif';
$fuenteTexto = 'system-ui, sans-serif';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog — Adri Hair Style</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Georgia&display=swap" rel="stylesheet">
    
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #FAF6F0; /* Fondo crema muy suave, premium */
            color: <?php echo $colorTexto; ?>;
            font-family: <?php echo $fuenteTexto; ?>;
            line-height: 1.6;
        }

        /* Cabecera del Blog de Adri */
        .header-main {
            background-color: #111827;
            padding: 60px 20px;
            text-align: center;
            border-bottom: 5px solid <?php echo $colorPrimario; ?>;
            color: white;
        }

        .header-main h1 {
            font-family: <?php echo $fuenteTitulo; ?>;
            font-size: 42px;
            margin: 0 0 10px 0;
            letter-spacing: -1px;
        }

        .header-main p {
            color: #9CA3AF;
            font-size: 16px;
            margin: 0;
        }

        /* Grid de Artículos */
        .blog-container {
            max-width: 1000px;
            margin: 50px auto;
            padding: 0 20px;
        }

        .error-card {
            background-color: #FEE2E2;
            border: 1px solid #EF4444;
            color: #991B1B;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }

        .no-posts {
            text-align: center;
            padding: 50px;
            color: #6B7280;
            font-size: 18px;
        }

        /* Tarjeta de Post de Blog */
        .post-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            margin-bottom: 50px;
            border: 1px solid rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .post-card:hover {
            transform: translateY(-2px);
        }

        .post-banner {
            width: 100%;
            height: 380px;
            object-fit: cover;
        }

        .post-body {
            padding: 40px;
        }

        .post-date {
            font-size: 13px;
            color: #9CA3AF;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .post-title {
            font-family: <?php echo $fuenteTitulo; ?>;
            font-size: 32px;
            margin: 0 0 20px 0;
            line-height: 1.3;
            color: <?php echo $colorTexto; ?>;
        }

        .post-text {
            color: #4B5563;
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 30px;
            text-align: justify;
            white-space: pre-line;
        }

        /* Firma de Adri */
        .post-author {
            display: flex;
            align-items: center;
            gap: 12px;
            border-top: 1px solid #F3F4F6;
            padding-top: 20px;
        }

        .post-author img {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid <?php echo $colorPrimario; ?>;
        }

        .author-info {
            font-size: 14px;
        }

        .author-name {
            font-weight: bold;
            color: #1F2937;
        }

        .author-title {
            color: #9CA3AF;
            font-size: 12px;
        }
    </style>
</head>
<body>

    <!-- Cabecera del Blog -->
    <header class="header-main">
        <h1>Adri Hair Style</h1>
        <p>Consejos, tendencias y secretos de belleza para lucir tu mejor cabello</p>
    </header>

    <!-- Contenedor -->
    <div class="blog-container">
        
        <?php if ($errorMsg): ?>
            <div class="error-card">
                <strong>Error al cargar publicaciones:</strong><br>
                <?php echo $errorMsg; ?><br><br>
                <small style="opacity: 0.8;">Asegúrate de que el servidor local y MySQL de XAMPP estén activos y la API devuelva resultados.</small>
            </div>
        <?php endif; ?>

        <?php if (!$errorMsg && empty($postsData)): ?>
            <div class="no-posts">
                <p>Aún no hay entradas publicadas en este blog.</p>
                <p style="font-size: 14px; color: #9CA3AF; margin-top: 10px;">Las publicaciones aprobadas por el revisor aparecerán automáticamente aquí.</p>
            </div>
        <?php elseif (!$errorMsg): ?>
            <?php foreach ($postsData as $post): ?>
                <article class="post-card">
                    <!-- Banner -->
                    <img class="post-banner" src="<?php echo htmlspecialchars($post['imagen_url']); ?>" alt="<?php echo htmlspecialchars($post['titulo']); ?>">

                    <div class="post-body">
                        <!-- Fecha -->
                        <div class="post-date">
                            Publicado el <?php echo date('d \d\e F, Y', strtotime($post['fecha_aprobacion'] ?? $post['fecha_creacion'])); ?>
                        </div>

                        <!-- Título -->
                        <h2 class="post-title"><?php echo htmlspecialchars($post['titulo']); ?></h2>

                        <!-- Texto -->
                        <div class="post-text">
                            <?php echo htmlspecialchars($post['texto']); ?>
                        </div>

                        <!-- Firma -->
                        <div class="post-author">
                            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=256&h=256&q=80" alt="Adri Montenegro">
                            <div class="author-info">
                                <div class="author-name">Adri Montenegro</div>
                                <div class="author-title">Estilista y Directora</div>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

</body>
</html>
