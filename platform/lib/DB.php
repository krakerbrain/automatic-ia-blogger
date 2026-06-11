<?php
/**
 * Conexión Singleton a la Base de Datos con PDO
 */

require_once __DIR__ . '/../config.php';

class DB {
    private static ?PDO $instance = null;

    // Deshabilitar constructor e inicialización externa
    private function __construct() {}
    private function __clone() {}
    public function __wakeup() {}

    /**
     * Obtiene la instancia única de PDO
     * @return PDO
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ];
                
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Registrar el error y abortar
                error_log("Error de conexión DB: " . $e->getMessage());
                die("Error de conexión a la base de datos. Por favor, asegúrate de que MySQL esté activo en XAMPP y configurado correctamente.");
            }
        }
        return self::$instance;
    }
}
