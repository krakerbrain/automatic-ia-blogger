<?php
/**
 * Configuración Global de la Plataforma
 */

// Evitar acceso directo
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Acceso denegado');
}

// Configuración de visualización de errores (desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuración de Zona Horaria
date_default_timezone_set('America/Santiago');

// Definir Directorios base
define('BASE_PATH', __DIR__);
define('UPLOAD_DIR', BASE_PATH . '/uploads/banners');

// Calcular BASE_URL dinámicamente para soportar subdirectorios como /ia-automatic-blogger
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseDir = '';

if (php_sapi_name() === 'cli' || empty($_SERVER['HTTP_HOST'])) {
    // En CLI, determinamos el subdirectorio analizando la ruta absoluta de este archivo
    $currentDir = str_replace('\\', '/', __DIR__);
    $posPlatform = strpos($currentDir, '/platform');
    if ($posPlatform !== false) {
        $absBase = substr($currentDir, 0, $posPlatform);
        // Buscar /htdocs/ o similar en XAMPP para extraer la ruta relativa web
        $posHtdocs = stripos($absBase, '/htdocs');
        if ($posHtdocs !== false) {
            $baseDir = substr($absBase, $posHtdocs + 7);
        }
    }
} else {
    if (!empty($scriptName)) {
        $pos = strpos($scriptName, '/platform');
        if ($pos !== false) {
            $baseDir = substr($scriptName, 0, $pos);
        }
    }
}

define('BASE_URL', $protocol . $host . $baseDir);
define('UPLOAD_URL', BASE_URL . '/platform/uploads/banners');

// Crear directorio de uploads si no existe
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

/**
 * Cargador de Variables de Entorno (.env) simple y ligero
 */
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#') === 0 || empty($line)) {
            continue;
        }
        
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            
            // Quitar comillas si están presentes
            $value = trim($value, "\"'");
            
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Cargar archivo .env desde la raíz del proyecto
loadEnv(dirname(BASE_PATH) . '/.env');

// Helper para obtener variable con fallback
function env($key, $default = '') {
    $value = getenv($key);
    return $value !== false ? $value : $default;
}

// Configuración de la Base de Datos
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'ia_automatic_blogger'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

// Configuración de la API de Google Gemini (AI Studio)
define('GEMINI_API_KEY', env('GEMINI_API_KEY', ''));

// Endpoints de Gemini
define('GEMINI_TEXT_MODEL', 'gemini-2.5-flash-lite');
define('GEMINI_IMAGE_MODEL', 'imagen-4.0-generate-001');
define('GEMINI_TEXT_URL', 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_TEXT_MODEL . ':generateContent');
define('GEMINI_IMAGE_URL', 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_IMAGE_MODEL . ':predict');

// Precios de la API de Gemini (en USD)
define('GEMINI_PRICE_INPUT_1M', 0.075);  // por millón de tokens de entrada (Flash/Flash Lite)
define('GEMINI_PRICE_OUTPUT_1M', 0.30);  // por millón de tokens de salida (Flash/Flash Lite)
define('GEMINI_PRICE_IMAGE', 0.03);      // costo por imagen generada (Imagen 3/4)

// Configuración de Correo Electrónico (PHPMailer)
define('SMTP_HOST', env('SMTP_HOST', ''));
define('SMTP_PORT', intval(env('SMTP_PORT', 587)));
define('SMTP_USER', env('SMTP_USER', ''));
define('SMTP_PASS', env('SMTP_PASS', ''));
define('SMTP_SECURE', env('SMTP_SECURE', 'tls')); // 'tls' o 'ssl'
define('MAIL_FROM_EMAIL', env('MAIL_FROM_EMAIL', ''));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'Plataforma AI Blogger'));

// Credenciales de administración
define('ADMIN_USER', env('ADMIN_USER', 'admin'));
define('ADMIN_PASS', env('ADMIN_PASS', 'admin123'));

// Iniciar sesión globalmente
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
