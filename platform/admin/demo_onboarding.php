<?php
/**
 * Onboarding interactivo para el Modo Demo: Explicación y Bandeja de Correo Simulada
 */
require_once __DIR__ . '/auth_check.php';

$db = DB::getInstance();

// Asegurar que la sesión de demo esté activa
if (!isset($_SESSION['is_demo']) || $_SESSION['is_demo'] !== true) {
    header("Location: " . BASE_URL . "/platform/admin/login.php");
    exit();
}

// Acción: Ingresar directamente al Panel de Control General (Admin)
if (isset($_GET['action']) && $_GET['action'] === 'admin') {
    $_SESSION['role'] = 'admin';
    header("Location: " . BASE_URL . "/platform/admin/index.php");
    exit();
}

// Obtener los clientes demo registrados
$stmt = $db->query("SELECT * FROM clientes WHERE slug IN ('adri-hair-style-demo', 'fitlife-gym-demo', 'cafe-aroma-demo') ORDER BY id ASC");
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cargar sugerencias para cada cliente demo para tenerlas listas en JS
$sugerencias_por_cliente = [];
$sugStmt = $db->prepare("SELECT * FROM sugerencias_temas WHERE cliente_id = ? AND estado = 'pendiente' ORDER BY id ASC");

foreach ($clientes as $c) {
    $sugStmt->execute([$c['id']]);
    $sugerencias_por_cliente[$c['id']] = $sugStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demostración Guiada — AI Blogger Central</title>
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
            --color-accent: #EC4899;
            --font-ui: 'Inter', sans-serif;
            --font-display: 'Outfit', sans-serif;
            --shadow-glass: 0 12px 40px 0 rgba(0, 0, 0, 0.5);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-base);
            background-image: 
                radial-gradient(at 15% 15%, rgba(139, 92, 246, 0.12) 0px, transparent 50%),
                radial-gradient(at 85% 85%, rgba(236, 72, 153, 0.12) 0px, transparent 50%);
            background-attachment: fixed;
            color: var(--text-primary);
            font-family: var(--font-ui);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Header de la demo */
        .demo-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-glass);
            z-index: 10;
        }

        .logo-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--color-primary), var(--color-accent));
            width: 40px;
            height: 40px;
            border-radius: 12px;
            font-weight: 800;
            font-family: var(--font-display);
            font-size: 18px;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }

        .logo-text {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 20px;
            letter-spacing: -0.5px;
        }

        /* Contenedor principal */
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            position: relative;
        }

        /* Sección de Onboarding */
        .onboarding-card {
            background: var(--bg-surface);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: 30px;
            width: 100%;
            max-width: 900px;
            padding: 50px;
            box-shadow: var(--shadow-glass);
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .intro-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .badge-step {
            display: inline-block;
            background: rgba(139, 92, 246, 0.15);
            color: #C084FC;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }

        .intro-title {
            font-family: var(--font-display);
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #ffffff 0%, #C7D2FE 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .intro-desc {
            color: var(--text-secondary);
            font-size: 16px;
            line-height: 1.6;
            max-width: 680px;
            margin: 0 auto;
        }

        .seo-insights-box {
            background: rgba(139, 92, 246, 0.08);
            border-left: 4px solid var(--color-primary);
            border-radius: 0 12px 12px 0;
            padding: 16px 20px;
            margin: 25px auto 0 auto;
            max-width: 680px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            text-align: left;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .seo-insights-icon {
            font-size: 20px;
            line-height: 1;
        }

        .seo-insights-text {
            font-size: 13.5px;
            line-height: 1.5;
            color: var(--text-primary);
            margin: 0;
        }

        .seo-insights-text strong {
            color: #C084FC;
        }

        /* Características explicativas */
        .explain-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 40px;
        }

        .explain-item {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }

        .explain-icon {
            background: rgba(139, 92, 246, 0.1);
            color: #A78BFA;
            padding: 10px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .explain-text h4 {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--text-primary);
        }

        .explain-text p {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        /* Selección de empresas */
        .companies-section {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 35px;
        }

        .section-title {
            font-family: var(--font-display);
            font-size: 20px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 25px;
        }

        .companies-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .company-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .company-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(180deg, rgba(255,255,255,0.05) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .company-card:hover {
            transform: translateY(-5px);
            border-color: rgba(139, 92, 246, 0.4);
            background: rgba(139, 92, 246, 0.04);
            box-shadow: 0 10px 25px -5px rgba(139, 92, 246, 0.15);
        }

        .company-card:hover::before {
            opacity: 1;
        }

        .company-logo {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            object-fit: cover;
            margin-bottom: 16px;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .company-name {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--text-primary);
        }

        .company-rubro {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #C084FC;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .company-desc {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.5;
            flex-grow: 1;
        }

        /* =================================================================== */
        /* INTERFAZ DE CORREO SIMULADA */
        /* =================================================================== */
        .inbox-container {
            display: none; /* Se activa por JS */
            background: #111827;
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            width: 100%;
            max-width: 1100px;
            height: 700px;
            box-shadow: var(--shadow-glass);
            overflow: hidden;
            animation: fadeIn 0.4s ease-out forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.98); }
            to { opacity: 1; transform: scale(1); }
        }

        .inbox-layout {
            display: grid;
            grid-template-columns: 240px 1fr;
            height: 100%;
        }

        /* Sidebar del cliente de correo */
        .mail-sidebar {
            background: #0B0F19;
            border-right: 1px solid var(--border-glass);
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .mail-btn-compose {
            background: linear-gradient(135deg, var(--color-primary), var(--color-accent));
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2);
            cursor: not-allowed;
            opacity: 0.8;
        }

        .mail-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .mail-menu-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s;
        }

        .mail-menu-item.active {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            font-weight: 600;
        }

        .mail-menu-item:hover:not(.active) {
            background: rgba(255, 255, 255, 0.02);
            color: var(--text-primary);
        }

        .mail-menu-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .badge-unread {
            background: var(--color-primary);
            color: #ffffff;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 700;
        }

        /* Contenido del correo */
        .mail-content-pane {
            display: flex;
            flex-direction: column;
            background: #111827;
            height: 100%;
        }

        .mail-header-bar {
            height: 60px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            border-bottom: 1px solid var(--border-glass);
            background: #131C2E;
        }

        .mail-search-box {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-glass);
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 13px;
            color: var(--text-primary);
            width: 300px;
        }

        .mail-body-wrapper {
            display: grid;
            grid-template-columns: 320px 1fr;
            flex-grow: 1;
            height: calc(100% - 60px);
            overflow: hidden;
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
            50% { box-shadow: 0 0 0 5px var(--color-accent), 0 0 25px var(--color-accent); }
        }

        /* Lista de Correos */
        .mail-list-pane {
            border-right: 1px solid var(--border-glass);
            background: #111827;
            overflow-y: auto;
        }

        .mail-list-item {
            padding: 20px;
            border-bottom: 1px solid var(--border-glass);
            cursor: pointer;
            transition: background 0.2s;
            position: relative;
        }

        .mail-list-item.active {
            background: rgba(139, 92, 246, 0.05);
        }

        .mail-list-item:hover:not(.active) {
            background: rgba(255, 255, 255, 0.02);
        }

        .mail-list-item-unread::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 8px;
            height: 8px;
            background: #8B5CF6;
            border-radius: 50%;
        }

        .mail-list-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }

        .mail-sender {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .mail-time {
            font-size: 11px;
            color: var(--text-secondary);
        }

        .mail-subject-short {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .mail-snippet {
            font-size: 12px;
            color: var(--text-secondary);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Detalle del Correo Abierto */
        .mail-view-pane {
            background: #182235;
            padding: 30px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .mail-subject-full {
            font-family: var(--font-display);
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 20px;
        }

        .mail-sender-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .mail-sender-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--color-primary), var(--color-accent));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #ffffff;
            font-size: 14px;
        }

        .mail-sender-info h5 {
            font-size: 13px;
            color: var(--text-primary);
        }

        .mail-sender-info p {
            font-size: 11px;
            color: var(--text-secondary);
        }

        /* Cuerpo del correo premium simulado */
        .mail-html-body {
            background: #ffffff;
            color: #333333;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            max-width: 600px;
            margin: 0 auto;
            width: 100%;
        }

        .mail-html-header {
            background-color: #111827;
            padding: 24px;
            text-align: center;
            border-radius: 8px 8px 0 0;
            border-bottom: 3px solid var(--accent-border-color, #8B5CF6);
            margin: -30px -30px 30px -30px;
        }

        .mail-html-logo {
            max-height: 50px;
            margin-bottom: 10px;
            border-radius: 6px;
        }

        .mail-html-logo-fallback {
            margin: 0 0 10px 0;
            font-size: 20px;
            font-weight: 700;
        }

        .mail-html-intro {
            font-size: 15px;
            line-height: 1.6;
            color: #4B5563;
            margin-bottom: 24px;
        }

        /* Bloques de sugerencias */
        .mail-sug-item {
            background-color: #F9FAFB;
            border-left: 4px solid var(--accent-border-color, #8B5CF6);
            padding: 16px;
            margin-bottom: 16px;
            border-radius: 0 8px 8px 0;
            text-align: left;
        }

        .mail-sug-num {
            font-size: 10px;
            text-transform: uppercase;
            color: #9CA3AF;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            font-weight: 700;
        }

        .mail-sug-title {
            margin: 0 0 10px 0;
            color: #1F2937;
            font-size: 15px;
            line-height: 1.4;
            font-weight: 700;
        }

        .mail-sug-action {
            display: inline-block;
            background-color: var(--accent-border-color, #8B5CF6);
            color: #ffffff !important;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 12px;
            transition: opacity 0.2s;
        }

        .mail-sug-action:hover {
            opacity: 0.9;
        }

        .mail-html-footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #E5E7EB;
            text-align: center;
            font-size: 11px;
            color: #9CA3AF;
            line-height: 1.4;
        }

        /* Responsive Styles for Mobile Devices */
        @media (max-width: 768px) {
            .demo-nav {
                padding: 15px 16px;
            }
            .logo-text {
                font-size: 16px;
            }
            .btn-skip {
                font-size: 12px;
                padding: 6px 12px;
            }
            .main-content {
                padding: 20px 10px;
            }
            .onboarding-card {
                padding: 30px 16px;
                border-radius: 20px;
            }
            .intro-title {
                font-size: 24px;
            }
            .intro-desc {
                font-size: 14px;
            }
            .explain-grid {
                grid-template-columns: 1fr;
                gap: 15px;
                margin-bottom: 30px;
            }
            .explain-item {
                padding: 16px;
            }
            .companies-section {
                padding-top: 25px;
            }
            .section-title {
                font-size: 16px;
                margin-bottom: 20px;
            }
            .companies-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .company-card {
                padding: 20px;
            }

            /* Inbox Layout Mobile Adjustments */
            .inbox-container {
                height: auto;
                min-height: 600px;
            }
            .inbox-layout {
                grid-template-columns: 1fr;
            }
            .mail-sidebar {
                display: none; /* Hide decorative email sidebar on small viewports */
            }
            .mail-header-bar {
                padding: 0 16px;
            }
            .mail-search-box {
                width: 160px;
                font-size: 12px;
            }
            .mail-body-wrapper {
                grid-template-columns: 1fr;
            }
            .mail-list-pane {
                display: none; /* Hide email list since there's only 1 email, showing detail view directly */
            }
            .mail-view-pane {
                padding: 16px;
            }
            .mail-subject-full {
                font-size: 17px;
                margin-bottom: 15px;
            }
            .mail-sender-row {
                margin-bottom: 20px;
                gap: 8px;
            }
            .mail-sender-avatar {
                width: 32px;
                height: 32px;
                font-size: 12px;
            }
            .mail-html-body {
                padding: 16px 12px;
            }
            .mail-html-header {
                margin: -16px -12px 20px -12px;
                padding: 16px;
            }
            .mail-html-logo {
                max-height: 40px;
            }
            .mail-html-intro {
                font-size: 13.5px;
            }
            .mail-sug-item {
                padding: 12px;
            }
            .mail-sug-title {
                font-size: 13.5px;
            }
            .mail-sug-action {
                width: 100%;
                text-align: center;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>

    <!-- Header de la Demo -->
    <header class="demo-nav">
        <div class="logo-group">
            <div class="logo-icon">AI</div>
            <div class="logo-text">Blogger Central</div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="main-content">

        <!-- PASO 1: EXPLICACIÓN Y SELECCIÓN DE EMPRESA -->
        <section class="onboarding-card" id="step-selector">
            <div class="intro-header">
                <span class="badge-step">Modo Demo Interactivo</span>
                <h2 class="intro-title">¿Cómo funciona la automatización?</h2>
                <p class="intro-desc">
                    El sistema elimina el esfuerzo de buscar y redactar contenido. Periódicamente (según la frecuencia que elijas), recibirás sugerencias de temas personalizadas para tu blog directamente en tu bandeja de entrada.
                </p>
                <div class="seo-insights-box">
                    <span class="seo-insights-icon">💡</span>
                    <p class="seo-insights-text">
                        <strong>¿Por qué es vital tener un blog?</strong> Probablemente creas que la gente ya no lee blogs (y en parte es verdad), pero <strong>los buscadores como Google sí los leen y los indexan</strong>. Cada artículo relevante posiciona tu web para responder a búsquedas de tus potenciales clientes, dirigiéndolos de manera orgánica a conocer y comprar tus productos o servicios.
                    </p>
                </div>
            </div>

            <!-- Grid de Características -->
            <div class="explain-grid">
                <div class="explain-item">
                    <div class="explain-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="explain-text">
                        <h4>1. Correo Automatizado</h4>
                        <p>Recibes sugerencias de temas listas para ser redactadas e integradas a tu blog.</p>
                    </div>
                </div>
                <div class="explain-item">
                    <div class="explain-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div class="explain-text">
                        <h4>2. Generación Instantánea</h4>
                        <p>Al hacer clic en un tema, la IA redacta el post completo y diseña la portada en segundos.</p>
                    </div>
                </div>
            </div>

            <div class="companies-section">
                <h3 class="section-title">Elige una empresa de prueba para simular tu rubro:</h3>
                
                <div class="companies-grid">
                    <?php foreach ($clientes as $c): ?>
                        <div class="company-card guide-highlight" onclick="selectCompany(<?php echo htmlspecialchars(json_encode($c)); ?>)">
                            <img src="<?php echo htmlspecialchars($c['logo_url']); ?>" alt="<?php echo htmlspecialchars($c['nombre']); ?>" class="company-logo">
                            <h4 class="company-name"><?php echo htmlspecialchars($c['nombre']); ?></h4>
                            <span class="company-rubro"><?php echo htmlspecialchars($c['rubro']); ?></span>
                            <p class="company-desc"><?php echo htmlspecialchars($c['descripcion']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- PASO 2: INTERFAZ DE BANDEJA DE CORREO SIMULADA -->
        <section class="inbox-container" id="step-inbox">
            <div class="inbox-layout">
                <!-- Sidebar de Webmail -->
                <div class="mail-sidebar">
                    <div class="mail-btn-compose">Redactar</div>
                    <ul class="mail-menu">
                        <li class="mail-menu-item active">
                            <div class="mail-menu-left">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0l-3.5 2.5a3 3 0 01-3 0L4 13"/></svg>
                                <span>Recibidos</span>
                            </div>
                            <span class="badge-unread">1</span>
                        </li>
                        <li class="mail-menu-item">
                            <div class="mail-menu-left">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                <span>Destacados</span>
                            </div>
                        </li>
                        <li class="mail-menu-item">
                            <div class="mail-menu-left">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>Borradores</span>
                            </div>
                        </li>
                        <li class="mail-menu-item">
                            <div class="mail-menu-left">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                <span>Enviados</span>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Contenido de la bandeja -->
                <div class="mail-content-pane">
                    <!-- Barra de Buscador y acciones del Correo -->
                    <div class="mail-header-bar">
                        <input type="text" class="mail-search-box" readonly value="Buscar correo...">
                        <div style="font-size: 12px; color: var(--text-secondary); display: flex; align-items: center; gap: 8px;">
                            <span style="background: rgba(16, 185, 129, 0.15); color: #34D399; padding: 4px 10px; border-radius: 6px; border: 1px solid rgba(16, 185, 129, 0.2); font-weight: 600;">Bandeja Simulación</span>
                        </div>
                    </div>

                    <!-- Panel Dividido (Lista y Vista) -->
                    <div class="mail-body-wrapper">
                        <!-- Lista de Emails -->
                        <div class="mail-list-pane">
                            <div class="mail-list-item mail-list-item-unread active">
                                <div class="mail-list-meta">
                                    <span class="mail-sender">AI Blogger Central</span>
                                    <span class="mail-time">10:48 AM</span>
                                </div>
                                <div class="mail-subject-short" id="inbox-subject-text">Ideas de temas sugeridos...</div>
                                <div class="mail-snippet">Hola, hemos generado algunas ideas y temas creativos para las próximas entradas...</div>
                            </div>
                        </div>

                        <!-- Vista del Correo Seleccionado -->
                        <div class="mail-view-pane">
                            <h3 class="mail-subject-full" id="mail-subject-full">Ideas de temas sugeridos para tu blog</h3>
                            
                            <div class="mail-sender-row">
                                <div class="mail-sender-avatar">AI</div>
                                <div class="mail-sender-info">
                                    <h5 id="mail-sender-name">AI Blogger Central &lt;no-reply@automaticblogger.ai&gt;</h5>
                                    <p>Para: <span id="mail-recipient">cliente@dominio.com</span></p>
                                </div>
                            </div>

                            <!-- Correo Renderizado -->
                            <div class="mail-html-body" id="mail-html-body">
                                <div class="mail-html-header" id="mail-html-header-element">
                                    <!-- Se inyecta dinámicamente -->
                                </div>
                                
                                <p class="mail-html-intro">
                                    Hola <strong id="mail-author-name">Autor</strong>,
                                </p>
                                <p class="mail-html-intro" style="margin-bottom: 24px;">
                                    Hemos generado algunas ideas y temas creativos para las próximas entradas del blog de <strong id="mail-company-name">Empresa</strong>. Haz clic en el botón de la idea que prefieras para redactarla y diseñar la imagen con IA.
                                </p>

                                <!-- Lista de sugerencias inyectadas por JS -->
                                <div id="mail-suggestions-list"></div>

                                <div class="mail-html-footer">
                                    Este es un correo automático de tu plataforma AI Blogger.<br>
                                    Dominio registrado: <a href="#" id="mail-domain-link" style="color: #6B7280; text-decoration: none;">dominio.com</a>
                                </div>
                            </div>
                        </div>
                            </div>
        </section>
    </main>

    <!-- Guía Interactiva Paso a Paso -->
    <div class="demo-guide-card" id="demo-guide" onclick="expandGuide(event)">
        <div class="guide-header">
            <span class="guide-icon">✨</span>
            <h4>Guía de la Demo</h4>
            <button class="guide-close-btn" onclick="toggleGuide(event)" title="Minimizar ayuda">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="guide-body">
            <p id="guide-text">Para comenzar, elige una de las <strong>empresas de prueba</strong>. Esto simulará tu negocio y el tipo de temas que recibirás en tu correo.</p>
        </div>
        <div class="guide-footer">
            <span class="guide-step-badge" id="guide-step">Paso 1 de 5: Selección</span>
        </div>
    </div>

    <script>
        const sugerenciasMap = <?php echo json_encode($sugerencias_por_cliente); ?>;

        function selectCompany(cliente) {
            // Animación y ocultar paso 1
            const stepSelector = document.getElementById('step-selector');
            stepSelector.style.transition = 'all 0.3s ease';
            stepSelector.style.opacity = '0';
            stepSelector.style.transform = 'translateY(-20px)';
            
            setTimeout(() => {
                stepSelector.style.display = 'none';
                
                // Mostrar y animar inbox
                const stepInbox = document.getElementById('step-inbox');
                stepInbox.style.display = 'block';
                
                // Configurar textos del email simulado
                document.getElementById('inbox-subject-text').innerText = 'Ideas de temas sugeridos: ' + cliente.nombre;
                document.getElementById('mail-subject-full').innerText = 'Ideas de temas sugeridos para tu blog: ' + cliente.nombre;
                document.getElementById('mail-recipient').innerText = cliente.email_revisor;
                document.getElementById('mail-author-name').innerText = cliente.nombre_autor;
                document.getElementById('mail-company-name').innerText = cliente.nombre;
                document.getElementById('mail-domain-link').innerText = cliente.dominio;
                document.getElementById('mail-domain-link').href = cliente.dominio;

                // Definir colores y estilo del header de correo
                const mailHeader = document.getElementById('mail-html-header-element');
                mailHeader.style.setProperty('--accent-border-color', cliente.color_primario);
                mailHeader.style.borderBottomColor = cliente.color_primario;
                
                if (cliente.logo_url) {
                    mailHeader.innerHTML = `<img src="${cliente.logo_url}" alt="${cliente.nombre}" class="mail-html-logo">
                                             <div style="color: #9CA3AF; font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px;">Sugerencias semanales de IA</div>`;
                } else {
                    mailHeader.innerHTML = `<h2 class="mail-html-logo-fallback" style="color: ${cliente.color_primario};">${cliente.nombre}</h2>
                                             <div style="color: #9CA3AF; font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px;">Sugerencias semanales de IA</div>`;
                }

                // Inyectar sugerencias
                const listContainer = document.getElementById('mail-suggestions-list');
                listContainer.innerHTML = '';
                
                const sugerencias = sugerenciasMap[cliente.id] || [];
                sugerencias.forEach((sug, index) => {
                    const num = index + 1;
                    const url = `posts/generar.php?sugerencia_id=${sug.id}&token=${encodeURIComponent(cliente.api_key_sitio)}`;
                    
                    const itemDiv = document.createElement('div');
                    itemDiv.className = 'mail-sug-item';
                    itemDiv.style.borderLeftColor = cliente.color_primario;
                    
                    itemDiv.innerHTML = `
                        <div class="mail-sug-num">Sugerencia #${num}</div>
                        <h4 class="mail-sug-title">"${sug.tema}"</h4>
                        <a href="${url}" class="mail-sug-action" style="background-color: ${cliente.color_primario}; color: ${cliente.color_texto}">Escribir sobre este tema</a>
                    `;
                    listContainer.appendChild(itemDiv);
                });

                // Actualizar Guía de la Demo
                const guideCard = document.getElementById('demo-guide');
                if (guideCard) {
                    guideCard.classList.remove('collapsed');
                    const icon = guideCard.querySelector('.guide-icon');
                    if (icon) icon.innerText = '✨';
                }
                document.getElementById('guide-text').innerHTML = '¡Excelente! Esta es la bandeja de entrada simulada de tu correo. Así de fácil recibirás las sugerencias. Elige el tema que prefieras y haz clic en <strong>"Escribir sobre este tema"</strong> para ver a la IA redactar en tiempo real.';
                document.getElementById('guide-step').innerText = 'Paso 2 de 5: Elegir Tema';

                // Añadir resaltado a las acciones del mail
                setTimeout(() => {
                    const actions = document.querySelectorAll('.mail-sug-action');
                    actions.forEach(btn => btn.classList.add('guide-highlight'));
                }, 400);

            }, 300);
        }

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
