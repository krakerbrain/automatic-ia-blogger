<?php
/**
 * Guardián de Autenticación y Control de Roles (Admin vs Cliente)
 */
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Detectar si viene un token de cliente por URL (?token=XXX)
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = trim($_GET['token']);
    
    // Buscar si corresponde a algún cliente activo
    if (!class_exists('DB')) {
        require_once __DIR__ . '/../lib/DB.php';
    }

    $db = DB::getInstance();
    $stmt = $db->prepare("SELECT * FROM clientes WHERE api_key_sitio = ? AND activo = 1");
    $stmt->execute([$token]);
    $cliente = $stmt->fetch();
    
    if ($cliente) {
        // Autenticar automáticamente como Cliente
        $_SESSION['role'] = 'cliente';
        $_SESSION['cliente_id'] = $cliente['id'];
        $_SESSION['cliente_nombre'] = $cliente['nombre'];
        $_SESSION['token'] = $token;
    }
}

// 2. Control de Rutas: Obtener el archivo actual y su directorio contenedor
$current_script = basename($_SERVER['PHP_SELF']);

// Páginas públicas que no requieren autenticación (accesibles por cualquiera)
$public_pages = ['login.php'];

// Si no está logueado y no está en una página pública, redirigir a login.php
if (!isset($_SESSION['role']) && !in_array($current_script, $public_pages)) {
    header("Location: " . BASE_URL . "/platform/admin/login.php");
    exit();
}

// 3. Restricción para Clientes:
// Si la sesión es de cliente pero intenta acceder a páginas de administración,
// redirigir al login para que el admin pueda autenticarse.
// Esto permite convivir con la sesión de cliente sin bloquear al admin.
if (isset($_SESSION['role']) && $_SESSION['role'] === 'cliente') {
    $allowed_client_scripts = ['generar.php', 'lista.php', 'revisar.php', 'perfil.php'];
    if (!in_array($current_script, $allowed_client_scripts)) {
        // Redirigir al login para que el admin pueda iniciar sesión
        header("Location: " . BASE_URL . "/platform/admin/login.php");
        exit();
    }
}
