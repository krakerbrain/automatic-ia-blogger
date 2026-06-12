<?php
/**
 * Guardián de Autenticación y Control de Roles (Admin vs Cliente)
 */
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1a. Si está en Modo Demo, asegurar que existen los clientes y sugerencias demo en BD
if (isset($_SESSION['is_demo']) && $_SESSION['is_demo'] === true) {
    if (!class_exists('DB')) {
        require_once __DIR__ . '/../lib/DB.php';
    }
    try {
        $db = DB::getInstance();

        // 1. Clientes demo
        $demo_clients = [
            [
                'nombre' => 'Adri Hair Style (Demo)',
                'slug' => 'adri-hair-style-demo',
                'rubro' => 'estética y cuidado capilar',
                'descripcion' => 'Salón boutique de estilismo femenino enfocado en alisados de alta calidad, coloración, hidratación profunda y spa coreano capilar.',
                'temas_relacionar' => 'cuidado del cabello, bienestar femenino, empoderamiento, autoestima, tendencias de peinados',
                'tono_marca' => 'cercano, moderno, femenino, inspirador',
                'dominio' => 'http://localhost/adri-hair-style-v2',
                'endpoint_publicar' => 'http://localhost/adri-hair-style-v2/publicar.php',
                'api_key_sitio' => 'demo_adri_key_2026',
                'email_revisor' => 'demo-adri@blog.cl',
                'nombre_autor' => 'Adri Montenegro (Demo)',
                'foto_autor_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=256&h=256&q=80',
                'color_primario' => '#E8B4B8',
                'color_texto' => '#2C2C2A',
                'fuente_titulo' => 'Georgia, serif',
                'fuente_texto' => 'system-ui, sans-serif',
                'logo_url' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=128&h=128&q=80',
                'estilo_redaccion' => 'Cercano y Cotidiano',
                'autor_identidad' => 'Una estilista profesional, apasionada por la salud capilar.',
                'autor_trasfondo' => 'Venezolana viviendo en Chile. Aporta calidez caribeña.',
                'autor_personalidad' => 'Educada, empática, directa y divertida.',
                'autor_tratamiento' => 'comunidad'
            ],
            [
                'nombre' => 'FitLife Gym (Demo)',
                'slug' => 'fitlife-gym-demo',
                'rubro' => 'fitness y entrenamiento',
                'descripcion' => 'Gimnasio moderno y centro de acondicionamiento físico integral con clases dirigidas, entrenamiento personalizado y asesoramiento nutricional.',
                'temas_relacionar' => 'entrenamiento en casa, rutinas HIIT, nutrición deportiva, salud mental, flexibilidad',
                'tono_marca' => 'enérgico, motivador, técnico, amigable',
                'dominio' => 'http://localhost/fitlife-gym',
                'endpoint_publicar' => 'http://localhost/fitlife-gym/publicar.php',
                'api_key_sitio' => 'demo_fitlife_key_2026',
                'email_revisor' => 'demo-fitlife@blog.cl',
                'nombre_autor' => 'Carlos Entrenador (Demo)',
                'foto_autor_url' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=256&h=256&q=80',
                'color_primario' => '#10B981',
                'color_texto' => '#1F2937',
                'fuente_titulo' => 'Outfit, sans-serif',
                'fuente_texto' => 'system-ui, sans-serif',
                'logo_url' => 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=128&h=128&q=80',
                'estilo_redaccion' => 'Directo e Inspirador',
                'autor_identidad' => 'Un entrenador certificado apasionado por transformar vidas a través del deporte.',
                'autor_trasfondo' => 'Preparador físico con más de 10 años de experiencia.',
                'autor_personalidad' => 'Disciplinado, motivador y directo.',
                'autor_tratamiento' => 'tú'
            ],
            [
                'nombre' => 'Café Aroma (Demo)',
                'slug' => 'cafe-aroma-demo',
                'rubro' => 'cafetería de especialidad',
                'descripcion' => 'Cafetería de especialidad dedicada a servir los mejores granos de origen con métodos de filtrado artesanal y pastelería de autor.',
                'temas_relacionar' => 'métodos de café filtrado, barismo, orígenes del café, repostería, rituales matutinos',
                'tono_marca' => 'cálido, intelectual, detallista, sofisticado',
                'dominio' => 'http://localhost/cafe-aroma',
                'endpoint_publicar' => 'http://localhost/cafe-aroma/publicar.php',
                'api_key_sitio' => 'demo_aroma_key_2026',
                'email_revisor' => 'demo-aroma@blog.cl',
                'nombre_autor' => 'Sofía Barista (Demo)',
                'foto_autor_url' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=256&h=256&q=80',
                'color_primario' => '#D97706',
                'color_texto' => '#3F2A1D',
                'fuente_titulo' => 'Georgia, serif',
                'fuente_texto' => 'system-ui, sans-serif',
                'logo_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=128&h=128&q=80',
                'estilo_redaccion' => 'Sofisticado y Detallista',
                'autor_identidad' => 'Una barista experta apasionada por descubrir y compartir los secretos del buen café.',
                'autor_trasfondo' => 'Certificada por la Asociación de Cafés de Especialidad (SCA).',
                'autor_personalidad' => 'Tranquila, observadora, apasionada por la alquimia del café.',
                'autor_tratamiento' => 'usted'
            ]
        ];

        foreach ($demo_clients as $dc) {
            $stmtChk = $db->prepare("SELECT id FROM clientes WHERE slug = ?");
            $stmtChk->execute([$dc['slug']]);
            $client_id = $stmtChk->fetchColumn();

            if (!$client_id) {
                $stmtIns = $db->prepare("
                    INSERT INTO clientes (
                        nombre, slug, rubro, descripcion, temas_relacionar, tono_marca, dominio, endpoint_publicar, api_key_sitio, 
                        email_revisor, nombre_autor, foto_autor_url, color_primario, color_texto, 
                        fuente_titulo, fuente_texto, logo_url, estilo_redaccion, autor_identidad, autor_trasfondo, 
                        autor_personalidad, autor_tratamiento, activo, frecuencia_dias, limite_mensual_usd
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 7, 50.0000)
                ");
                $stmtIns->execute([
                    $dc['nombre'], $dc['slug'], $dc['rubro'], $dc['descripcion'], $dc['temas_relacionar'], $dc['tono_marca'], $dc['dominio'], $dc['endpoint_publicar'], $dc['api_key_sitio'],
                    $dc['email_revisor'], $dc['nombre_autor'], $dc['foto_autor_url'], $dc['color_primario'], $dc['color_texto'],
                    $dc['fuente_titulo'], $dc['fuente_texto'], $dc['logo_url'], $dc['estilo_redaccion'], $dc['autor_identidad'], $dc['autor_trasfondo'],
                    $dc['autor_personalidad'], $dc['autor_tratamiento']
                ]);
                $client_id = $db->lastInsertId();
            }

            // Crear sugerencias iniciales
            $stmtSug = $db->prepare("SELECT COUNT(*) FROM sugerencias_temas WHERE cliente_id = ? AND estado = 'pendiente'");
            $stmtSug->execute([$client_id]);
            $pending_sugs = $stmtSug->fetchColumn();

            if ($pending_sugs == 0) {
                $suggestions = [];
                if ($dc['slug'] === 'adri-hair-style-demo') {
                    $suggestions = [
                        [
                            'tema' => '5 peinados fáciles y rápidos para ir al trabajo',
                            'angulo' => json_encode(['consejo_practico' => 'Prefiere coleteros de satén para no maltratar la fibra.', 'servicio_a_promocionar' => 'Tratamiento de Hidratación Profunda'])
                        ],
                        [
                            'tema' => 'Cómo cuidar el cabello después de una decoloración',
                            'angulo' => json_encode(['consejo_practico' => 'Aplica mascarillas reconstructoras y limita los lavados.', 'servicio_a_promocionar' => 'Cauterización y Rescate Capilar'])
                        ],
                        [
                            'tema' => 'Los beneficios del Spa Coreano Capilar',
                            'angulo' => json_encode(['consejo_practico' => 'Desintoxicar el cuero cabelludo estimula el crecimiento.', 'servicio_a_promocionar' => 'Spa Coreano Capilar'])
                        ]
                    ];
                } elseif ($dc['slug'] === 'fitlife-gym-demo') {
                    $suggestions = [
                        [
                            'tema' => 'Rutina de 20 minutos de alta intensidad (HIIT)',
                            'angulo' => json_encode(['consejo_practico' => 'Da tu 100% en los intervalos de trabajo.', 'servicio_a_promocionar' => 'Entrenamiento Personalizado'])
                        ],
                        [
                            'tema' => 'La importancia del estiramiento después de entrenar',
                            'angulo' => json_encode(['consejo_practico' => 'Estira suavemente cuando el músculo aún esté caliente.', 'servicio_a_promocionar' => 'Flexibilidad y Yoga'])
                        ]
                    ];
                } elseif ($dc['slug'] === 'cafe-aroma-demo') {
                    $suggestions = [
                        [
                            'tema' => 'El arte del café filtrado en casa: métodos y tips',
                            'angulo' => json_encode(['consejo_practico' => 'Realiza una pre-infusión de 30 segundos para liberar el CO2.', 'servicio_a_promocionar' => 'Barra de Métodos de Filtrado'])
                        ],
                        [
                            'tema' => 'Diferencias entre café arábica y robusta',
                            'angulo' => json_encode(['consejo_practico' => 'El grano arábica ofrece mayor dulzura y complejidad.', 'servicio_a_promocionar' => 'Espresso de Especialidad'])
                        ]
                    ];
                }

                $stmtInsSug = $db->prepare("INSERT INTO sugerencias_temas (cliente_id, tema, angulo_contextual, estado, fecha_sugerencia) VALUES (?, ?, ?, 'pendiente', NOW())");
                foreach ($suggestions as $s) {
                    $stmtInsSug->execute([$client_id, $s['tema'], $s['angulo']]);
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error inicializando datos de Demo: " . $e->getMessage());
    }
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
$public_pages = ['login.php', 'demo_onboarding.php', 'demo_published_preview.php'];

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
    $allowed_client_scripts = ['generar.php', 'lista.php', 'revisar.php', 'perfil.php', 'demo_published_preview.php'];
    if (!in_array($current_script, $allowed_client_scripts)) {
        // Redirigir al login para que el admin pueda iniciar sesión
        header("Location: " . BASE_URL . "/platform/admin/login.php");
        exit();
    }
}

// 4. Protección para Modo Demo (bloquear accesos malintencionados a otras secciones)
if (isset($_SESSION['is_demo']) && $_SESSION['is_demo'] === true) {
    // Si intenta acceder a páginas de configuración, edición o creación
    $forbidden_demo_scripts = ['agregar.php', 'editar.php', 'eliminar.php', 'ajustes.php'];
    if (in_array($current_script, $forbidden_demo_scripts)) {
        $_SESSION['flash_error'] = "Esta acción no está permitida en el Modo Demostración.";
        header("Location: " . BASE_URL . "/platform/admin/posts/generar.php");
        exit();
    }

    // Bloquear peticiones POST que alteren clientes o configuraciones generales
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $request_uri = $_SERVER['REQUEST_URI'];
        if (strpos($request_uri, '/clientes/') !== false && strpos($request_uri, 'generar.php') === false) {
            $_SESSION['flash_error'] = "Las modificaciones no están permitidas en el Modo Demostración.";
            header("Location: " . BASE_URL . "/platform/admin/posts/generar.php");
            exit();
        }
    }
}

