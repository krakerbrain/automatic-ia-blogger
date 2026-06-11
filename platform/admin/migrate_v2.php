<?php
/**
 * Migración V2 - Agregar campos de Estilo de Redacción y Perfil de Autor a la tabla clientes
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/DB.php';

try {
    $db = DB::getInstance();
    
    // Lista de nuevas columnas a agregar
    $columns = [
        'estilo_redaccion' => "VARCHAR(100) NOT NULL DEFAULT 'Cercano y Cotidiano'",
        'autor_identidad' => "VARCHAR(500) NULL",
        'autor_trasfondo' => "VARCHAR(500) NULL",
        'autor_personalidad' => "VARCHAR(500) NULL",
        'autor_tratamiento' => "VARCHAR(50) NOT NULL DEFAULT 'tú'"
    ];
    
    echo "Iniciando migración de base de datos...\n";
    
    foreach ($columns as $columnName => $columnDefinition) {
        // Verificar si la columna ya existe
        $check = $db->query("SHOW COLUMNS FROM clientes LIKE '{$columnName}'")->fetch();
        
        if (!$check) {
            echo "Agregando columna '{$columnName}'... ";
            $db->exec("ALTER TABLE clientes ADD COLUMN {$columnName} {$columnDefinition}");
            echo "OK\n";
        } else {
            echo "La columna '{$columnName}' ya existe. Omitido.\n";
        }
    }
    
    // Actualizar cliente Adri Hair Style con datos iniciales de ejemplo para autor
    $db->exec("UPDATE clientes SET 
        autor_identidad = 'Una estilista profesional, apasionada por la salud capilar.',
        autor_trasfondo = 'Venezolana viviendo en Chile. Aporta calidez caribeña combinada con la estructura local.',
        autor_personalidad = 'Educada, empática, un toque alocada y muy directa. Detesta el rodeo y las palabras pretenciosas.',
        autor_tratamiento = 'comunidad',
        estilo_redaccion = 'Cercano y Cotidiano'
        WHERE slug = 'adri-hair-style' AND (autor_identidad IS NULL OR autor_identidad = '')");
    
    echo "¡Migración completada con éxito!\n";
} catch (Exception $e) {
    echo "ERROR en la migración: " . $e->getMessage() . "\n";
}
