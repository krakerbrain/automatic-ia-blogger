<?php
/**
 * Página de Inicio de Sesión - Administrador
 */
require_once __DIR__ . '/../config.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si hay una sesión de CLIENTE activa (vino desde un token de correo),
// limpiarla para que el admin pueda autenticarse con credenciales propias.
if (isset($_SESSION['role']) && $_SESSION['role'] === 'cliente') {
    session_unset();
    session_destroy();
    session_start();
}

// 1. Procesar Logout explícito
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    session_start();
    $logoutMsg = "Sesión cerrada correctamente.";
}

// Redirigir si ya está logueado como Admin
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: " . BASE_URL . "/platform/admin/index.php");
    exit();
}

// 1b. Procesar inicio de Modo Demo
if (isset($_GET['demo']) && $_GET['demo'] === '1') {
    session_unset();
    $_SESSION['is_demo'] = true;
    header("Location: " . BASE_URL . "/platform/admin/demo_onboarding.php");
    exit();
}

$errorMsg = '';

// 2. Procesar Formulario de Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $errorMsg = "Por favor, ingresa el usuario y la contraseña.";
    } elseif ($username === ADMIN_USER && $password === ADMIN_PASS) {
        $_SESSION['role'] = 'admin';
        header("Location: " . BASE_URL . "/platform/admin/index.php");
        exit();
    } else {
        $errorMsg = "Usuario o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — AI Blogger Central</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    
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
            --color-error: #EF4444;
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
                radial-gradient(at 10% 10%, rgba(139, 92, 246, 0.1) 0px, transparent 50%),
                radial-gradient(at 90% 90%, rgba(236, 72, 153, 0.1) 0px, transparent 50%);
            background-attachment: fixed;
            color: var(--text-primary);
            font-family: var(--font-ui);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: var(--bg-surface);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            width: 100%;
            max-width: 420px;
            padding: 40px;
            box-shadow: var(--shadow-glass);
            text-align: center;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--color-primary), var(--color-accent));
            width: 60px;
            height: 60px;
            border-radius: 18px;
            font-weight: 800;
            font-family: var(--font-display);
            font-size: 24px;
            color: #ffffff;
            margin-bottom: 20px;
            box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
        }

        .login-title {
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 24px;
            margin-bottom: 8px;
            background: linear-gradient(to right, #ffffff, #E0E7FF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .login-subtitle {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 30px;
        }

        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .form-control {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 14.5px;
            color: white;
            font-family: inherit;
            outline: none;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15);
            background: rgba(255, 255, 255, 0.06);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13.5px;
            margin-bottom: 20px;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #FCA5A5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #A7F3D0;
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-hover));
            color: white;
            border: none;
            padding: 14px;
            font-size: 15px;
            font-weight: 600;
            font-family: var(--font-display);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 14px rgba(139, 92, 246, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(139, 92, 246, 0.35);
        }

        .btn-submit:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="logo-container">AB</div>
        <h2 class="login-title">AI Blogger Central</h2>
        <p class="login-subtitle">Panel de Control & Generador</p>

        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-error">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><?php echo htmlspecialchars($errorMsg); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($logoutMsg)): ?>
            <div class="alert alert-success">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><?php echo htmlspecialchars($logoutMsg); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label class="form-label" for="username">Usuario Administrador</label>
                <input class="form-control" type="text" id="username" name="username" placeholder="Ingresa tu usuario" required autocomplete="username">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Contraseña</label>
                <input class="form-control" type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn-submit">
                <span>Ingresar al Panel</span>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </form>

        <div style="margin-top: 25px; border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 20px;">
            <p style="color: var(--text-secondary); font-size: 13px; margin-bottom: 12px; font-weight: 500;">¿Quieres probar la plataforma sin configurar APIs?</p>
            <a href="login.php?demo=1" class="btn-submit" style="background: linear-gradient(135deg, #10B981, #059669); margin-top: 0; text-decoration: none; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.25);">
                <span>Iniciar Demo (Simulado)</span>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </a>
        </div>
    </div>

</body>
</html>
