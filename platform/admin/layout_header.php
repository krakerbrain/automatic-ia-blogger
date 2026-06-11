<?php
/**
 * Layout Header - Plataforma de Administración
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth_check.php';

// Obtener el nombre del archivo actual para marcar la navegación activa
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
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
    </style>
</head>
<body>

<div class="admin-container">
    <!-- Navegación Compartida -->
    <nav class="main-nav">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="<?php echo BASE_URL; ?>/platform/admin/index.php" class="nav-logo">
                <div class="nav-logo-icon">AI</div>
                <div class="nav-logo-text">Blogger Central</div>
            </a>
            
            <ul class="nav-menu">
                <li class="nav-item <?php echo ($current_page == 'index.php' && $current_dir == 'admin') ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/platform/admin/index.php">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item <?php echo ($current_dir == 'clientes') ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/platform/admin/clientes/lista.php">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        Clientes
                    </a>
                </li>
                <li class="nav-item <?php echo ($current_page == 'lista.php' && $current_dir == 'posts') ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/platform/admin/posts/lista.php">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Historial Posts
                    </a>
                </li>
                <li class="nav-item <?php echo ($current_page == 'generar.php') ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/platform/admin/posts/generar.php" class="btn-custom btn-primary" style="padding: 6px 12px; margin-left: 10px;">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
                        Generar Post
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/platform/admin/login.php?logout=1" style="color: var(--color-error); padding: 8px 12px;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Salir
                    </a>
                </li>
            </ul>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>/platform/admin/posts/lista.php" class="nav-logo">
                <div class="nav-logo-icon" style="background: linear-gradient(135deg, var(--color-primary), #818CF8);">AI</div>
                <div class="nav-logo-text">Redactor de Contenido</div>
            </a>
            
            <ul class="nav-menu">
                <li class="nav-item <?php echo ($current_page == 'lista.php' && $current_dir == 'posts') ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/platform/admin/posts/lista.php">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Historial Posts
                    </a>
                </li>
                <li class="nav-item <?php echo ($current_page == 'perfil.php') ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/platform/admin/clientes/perfil.php">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Mi Perfil
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

    <main class="page-content">
