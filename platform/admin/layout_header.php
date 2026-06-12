<?php
/**
 * Layout Header - Plataforma de Administración
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth_check.php';

// Obtener el nombre del archivo actual para marcar la navegación activa
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

$is_demo = (isset($_SESSION['is_demo']) && $_SESSION['is_demo'] === true);
$demo_class = $is_demo ? 'demo-locked' : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Blogger Central — Panel de Control</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-base: #0B0F19;
            --bg-surface: rgba(22, 28, 45, 0.7);
            --bg-card: rgba(30, 41, 59, 0.5);
            --border-glass: rgba(255, 255, 255, 0.06);
            --text-primary: #F3F4F6;
            --text-secondary: #9CA3AF;
            --text-muted: #6B7280;
            --color-primary: #8B5CF6; /* Violet */
            --color-primary-hover: #7C3AED;
            --color-accent: #EC4899; /* Pink */
            --color-success: #10B981; /* Emerald */
            --color-warning: #F59E0B; /* Amber */
            --color-error: #EF4444; /* Red */
            --font-ui: 'Inter', sans-serif;
            --font-display: 'Outfit', sans-serif;
            --shadow-glass: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-base);
            background-image: 
                radial-gradient(at 10% 10%, rgba(139, 92, 246, 0.08) 0px, transparent 50%),
                radial-gradient(at 90% 90%, rgba(236, 72, 153, 0.08) 0px, transparent 50%);
            background-attachment: fixed;
            color: var(--text-primary);
            font-family: var(--font-ui);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Contenedor principal con grid */
        .admin-container {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Barra de navegación */
        .main-nav {
            background: var(--bg-surface);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            padding: 15px 25px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-glass);
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-primary);
        }

        .nav-logo-icon {
            background: linear-gradient(135deg, var(--color-primary), var(--color-accent));
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-family: var(--font-display);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(139, 92, 246, 0.4);
        }

        .nav-logo-text {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 20px;
            letter-spacing: -0.5px;
            background: linear-gradient(to right, #ffffff, #E0E7FF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 10px;
            align-items: center;
        }

        .nav-item a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-item a:hover {
            color: var(--text-primary);
            background: rgba(255, 255, 255, 0.05);
        }

        .nav-item.active a {
            color: #ffffff;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(236, 72, 153, 0.1));
            border: 1px solid rgba(139, 92, 246, 0.3);
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.15);
        }

        /* Contenido de la página */
        .page-content {
            flex-grow: 1;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Componentes Comunes */
        h1, h2, h3, h4 {
            font-family: var(--font-display);
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        /* Mensajes flash */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            font-size: 14px;
            backdrop-filter: blur(8px);
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: var(--color-success);
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: var(--color-error);
        }

        /* Tarjetas Glassmorphic */
        .demo-locked {
            pointer-events: none !important;
            opacity: 0.55 !important;
            cursor: not-allowed !important;
        }
        .demo-locked a {
            cursor: not-allowed !important;
        }
        .card-glass {
            background: var(--bg-surface);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            padding: 25px;
            box-shadow: var(--shadow-glass);
            margin-bottom: 25px;
        }

        /* Tablas */
        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }

        .table-custom {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        .table-custom th {
            color: var(--text-secondary);
            font-weight: 600;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-glass);
            font-family: var(--font-display);
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        .table-custom td {
            padding: 18px 20px;
            border-bottom: 1px solid var(--border-glass);
            color: var(--text-primary);
        }

        .table-custom tbody tr {
            transition: background 0.2s ease;
        }

        .table-custom tbody tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success { background: rgba(16, 185, 129, 0.15); color: var(--color-success); border: 1px solid rgba(16, 185, 129, 0.25); }
        .badge-warning { background: rgba(245, 158, 11, 0.15); color: var(--color-warning); border: 1px solid rgba(245, 158, 11, 0.25); }
        .badge-danger { background: rgba(239, 68, 68, 0.15); color: var(--color-error); border: 1px solid rgba(239, 68, 68, 0.25); }
        .badge-primary { background: rgba(139, 92, 246, 0.15); color: var(--color-primary); border: 1px solid rgba(139, 92, 246, 0.25); }

        /* Botones */
        .btn-custom {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: var(--font-display);
            font-weight: 600;
            font-size: 14px;
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-hover));
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(139, 92, 246, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(139, 92, 246, 0.5);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.06);
            color: var(--text-primary);
            border: 1px solid var(--border-glass);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 6px;
        }

        /* Formularios */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .form-control {
            width: 100%;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-glass);
            border-radius: 8px;
            padding: 12px 16px;
            color: #ffffff;
            font-family: var(--font-ui);
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2);
            background: rgba(15, 23, 42, 0.8);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        @media (min-width: 768px) {
            .form-row {
                grid-template-columns: repeat(2, 1fr);
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
            animation: slideInRightLayout 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
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

        @keyframes slideInRightLayout {
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
            animation: pulse-border-glow-layout 2s infinite ease-in-out;
        }

        @keyframes pulse-border-glow-layout {
            0%, 100% { box-shadow: 0 0 0 3px var(--color-primary), 0 0 15px var(--color-primary); }
            50% { box-shadow: 0 0 0 5px #f43f5e, 0 0 25px #f43f5e; }
        }

        /* Ajustes de Navegación Móvil */
        @media (max-width: 768px) {
            .admin-container {
                padding: 10px;
            }

            .main-nav {
                flex-direction: column;
                gap: 12px;
                padding: 12px 14px;
                align-items: center;
                margin-bottom: 15px;
                border-radius: 12px;
            }

            .nav-logo {
                width: 100%;
                justify-content: center;
            }

            .nav-logo-text {
                font-size: 18px;
            }

            .nav-menu {
                width: 100%;
                overflow-x: auto;
                white-space: nowrap;
                justify-content: flex-start;
                padding-bottom: 5px;
                gap: 6px;
                scrollbar-width: none;
                -webkit-overflow-scrolling: touch;
            }

            .nav-menu::-webkit-scrollbar {
                display: none;
            }

            .nav-item {
                display: inline-block;
                flex-shrink: 0;
            }

            .nav-item a {
                padding: 6px 12px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>

<div class="admin-container">
    <!-- Navegación Compartida -->
    <nav class="main-nav">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="<?php echo BASE_URL; ?>/platform/admin/index.php" class="nav-logo <?php echo $demo_class; ?>">
                <div class="nav-logo-icon">AI</div>
                <div class="nav-logo-text">Blogger Central</div>
                <?php if (isset($_SESSION['is_demo']) && $_SESSION['is_demo'] === true): ?>
                    <span class="badge badge-success animate-pulse" style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); font-size: 11px; margin-left: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Simulación Demo</span>
                <?php endif; ?>
            </a>
            
            <ul class="nav-menu">
                <li class="nav-item <?php echo ($current_page == 'index.php' && $current_dir == 'admin') ? 'active' : ''; ?> <?php echo $demo_class; ?>">
                    <a href="<?php echo BASE_URL; ?>/platform/admin/index.php">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item <?php echo ($current_dir == 'clientes') ? 'active' : ''; ?> <?php echo $demo_class; ?>">
                    <a href="<?php echo BASE_URL; ?>/platform/admin/clientes/lista.php">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        Clientes
                    </a>
                </li>
                <li class="nav-item <?php echo ($current_page == 'lista.php' && $current_dir == 'posts') ? 'active' : ''; ?> <?php echo $demo_class; ?>">
                    <a href="<?php echo BASE_URL; ?>/platform/admin/posts/lista.php">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Historial Posts
                    </a>
                </li>
                <li class="nav-item <?php echo ($current_page == 'generar.php') ? 'active' : ''; ?> <?php echo $demo_class; ?>">
                    <a href="<?php echo BASE_URL; ?>/platform/admin/posts/generar.php" class="btn-custom btn-primary" style="padding: 6px 12px; margin-left: 10px;">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
                        Generar Post
                    </a>
                </li>
                <li class="nav-item">
                    <?php if (isset($_SESSION['is_demo']) && $_SESSION['is_demo'] === true): ?>
                        <a href="<?php echo BASE_URL; ?>/platform/admin/login.php?logout=1" style="color: #F87171; background: rgba(248, 113, 113, 0.1); border: 1px solid rgba(248, 113, 113, 0.25); padding: 8px 12px; display: inline-flex; border-radius: 8px; font-weight: bold; gap: 4px;">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Salir Demo
                        </a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/platform/admin/login.php?logout=1" style="color: var(--color-error); padding: 8px 12px;">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Salir
                        </a>
                    <?php endif; ?>
                </li>
            </ul>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>/platform/admin/posts/lista.php" class="nav-logo <?php echo $demo_class; ?>">
                <div class="nav-logo-icon" style="background: linear-gradient(135deg, var(--color-primary), #818CF8);">AI</div>
                <div class="nav-logo-text">Redactor de Contenido</div>
            </a>
            
            <ul class="nav-menu">
                <li class="nav-item <?php echo ($current_page == 'lista.php' && $current_dir == 'posts') ? 'active' : ''; ?> <?php echo $demo_class; ?>">
                    <a href="<?php echo BASE_URL; ?>/platform/admin/posts/lista.php">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Historial Posts
                    </a>
                </li>
                <li class="nav-item <?php echo ($current_page == 'perfil.php') ? 'active' : ''; ?> <?php echo $demo_class; ?>">
                    <a href="<?php echo BASE_URL; ?>/platform/admin/clientes/perfil.php">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Mi Perfil
                    </a>
                </li>
                <li class="nav-item" style="border-left: 1px solid var(--border-glass); margin-left: 10px; padding-left: 10px;">
                    <div style="font-size: 13px; color: var(--text-secondary); font-weight: 500; display: inline-flex; align-items: center; gap: 6px;">
                        <span>Hola, <strong><?php echo htmlspecialchars($_SESSION['cliente_nombre'] ?? 'Cliente'); ?></strong></span>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/platform/admin/login.php?logout=1" style="color: var(--color-error); padding: 8px 12px;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24 animate-pulse"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Salir
                    </a>
                </li>
            </ul>
        <?php endif; ?>
    </nav>

    <!-- Área de Alertas Flash -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-error">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
        </div>
    <?php endif; ?>

    <?php if ($is_demo): ?>
        <!-- Guía Interactiva Paso a Paso (Dashboard/Generación) -->
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
                    <?php if (isset($successPost)): ?>
                        ¡Casi listo! Revisa cómo lucirá el artículo redactado. Haz clic en el botón <strong>"Aprobar y Publicar Post"</strong> resaltado para publicarlo inmediatamente en tu sitio web.
                    <?php else: ?>
                        Aquí puedes ajustar el título o el contenido si lo deseas. También puedes <strong>generar una imagen con IA o subir una propia</strong> para la portada. Cuando estés conforme, haz clic en <strong>"Diseñar con IA y Finalizar"</strong> resaltado para continuar.
                    <?php endif; ?>
                </p>
            </div>
            <div class="guide-footer">
                <span class="guide-step-badge" id="guide-step">
                    <?php if (isset($successPost)): ?>
                        Paso 4 de 5: Publicar
                    <?php else: ?>
                        Paso 3 de 5: Editar y Diseñar
                    <?php endif; ?>
                </span>
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
    <?php endif; ?>

    <main class="page-content">
