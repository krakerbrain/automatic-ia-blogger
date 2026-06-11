CREATE DATABASE IF NOT EXISTS ia_automatic_blogger CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ia_automatic_blogger;

-- Tabla "clientes"
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    slug VARCHAR(80) UNIQUE NOT NULL,
    rubro VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    temas_relacionar TEXT NULL,
    tono_marca VARCHAR(200) NOT NULL,
    dominio VARCHAR(255) NOT NULL,
    endpoint_publicar VARCHAR(500) NOT NULL,
    api_key_sitio VARCHAR(128) NOT NULL,
    email_revisor VARCHAR(150) NOT NULL,
    nombre_autor VARCHAR(100) NOT NULL,
    foto_autor_url VARCHAR(500) NOT NULL,
    color_primario VARCHAR(7) NOT NULL DEFAULT '#E8B4B8',
    color_texto VARCHAR(7) NOT NULL DEFAULT '#2C2C2A',
    fuente_titulo VARCHAR(80) NOT NULL DEFAULT 'Georgia, serif',
    fuente_texto VARCHAR(80) NOT NULL DEFAULT 'system-ui, sans-serif',
    logo_url VARCHAR(500) NULL,
    estilo_redaccion VARCHAR(100) NOT NULL DEFAULT 'Cercano y Cotidiano',
    autor_identidad VARCHAR(500) NULL,
    autor_trasfondo VARCHAR(500) NULL,
    autor_personalidad VARCHAR(500) NULL,
    autor_tratamiento VARCHAR(50) NOT NULL DEFAULT 'tú',
    activo TINYINT(1) DEFAULT 1,
    frecuencia_dias INT NOT NULL DEFAULT 7,
    fecha_ultima_sugerencia DATETIME DEFAULT NULL,
    limite_mensual_usd DECIMAL(10,4) NOT NULL DEFAULT 5.0000,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla "sugerencias_temas"
CREATE TABLE IF NOT EXISTS sugerencias_temas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    tema VARCHAR(255) NOT NULL,
    angulo_contextual TEXT NULL,
    estado ENUM('pendiente', 'generado') DEFAULT 'pendiente',
    fecha_sugerencia DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla "posts"
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    tema VARCHAR(255) NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    texto LONGTEXT NOT NULL,
    imagen_url VARCHAR(500) NOT NULL,
    image_prompt TEXT NULL,
    estado ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente',
    comentario_rechazo TEXT NULL,
    token_revision VARCHAR(64) UNIQUE NOT NULL,
    publicacion_exitosa TINYINT(1) DEFAULT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_aprobacion DATETIME NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla "consumo_tokens"
CREATE TABLE IF NOT EXISTS consumo_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NULL,
    post_id INT NULL,
    modelo VARCHAR(100) NOT NULL,
    accion VARCHAR(50) NOT NULL, -- 'generar_post', 'sugerir_temas', 'regenerar_post', 'generar_imagen'
    prompt_tokens INT NOT NULL DEFAULT 0,
    completion_tokens INT NOT NULL DEFAULT 0,
    total_tokens INT NOT NULL DEFAULT 0,
    costo_estimado DECIMAL(10, 6) NOT NULL DEFAULT 0.000000,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar cliente inicial "Adri Hair Style"
INSERT INTO clientes (
    nombre, slug, rubro, descripcion, temas_relacionar, tono_marca, dominio, endpoint_publicar, api_key_sitio, 
    email_revisor, nombre_autor, foto_autor_url, color_primario, color_texto, 
    fuente_titulo, fuente_texto, logo_url, estilo_redaccion, autor_identidad, autor_trasfondo, 
    autor_personalidad, autor_tratamiento, activo, frecuencia_dias, limite_mensual_usd
) VALUES (
    'Adri Hair Style',
    'adri-hair-style',
    'peluquería femenina',
    'Salón boutique de estilismo femenino enfocado principalmente en alisados de alta calidad, con amplia trayectoria en coloración, hidratación profunda, balayage y un exclusivo servicio de spa coreano capilar.',
    'relación entre el cuidado personal y la autoestima de la mujer, series y películas populares relacionadas con el empoderamiento femenino y el cuidado personal, consejos de bienestar y estilo de vida.',
    'cercano, moderno, femenino, inspirador',
    'http://localhost/adri-hair-style-v2',
    'http://localhost/adri-hair-style-v2/publicar.php',
    'adri_secret_site_key_2026',
    'adri@adrihairstyle.cl',
    'Adri Montenegro',
    'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=256&h=256&q=80',
    '#E8B4B8',
    '#2C2C2A',
    'Georgia, serif',
    'system-ui, sans-serif',
    'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=128&h=128&q=80',
    'Cercano y Cotidiano',
    'Una estilista profesional, apasionada por la salud capilar.',
    'Venezolana viviendo en Chile. Aporta calidez caribeña combinada con la estructura local.',
    'Educada, empática, un toque alocada y muy directa. Detesta el rodeo y las palabras pretenciosas.',
    'comunidad',
    1,
    7,
    5.0000
) ON DUPLICATE KEY UPDATE id=id;
