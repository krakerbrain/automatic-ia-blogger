<?php
/**
 * Módulo de Generación de Posts con IA - Controlador Modularizado
 */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/DB.php';
require_once __DIR__ . '/../../lib/GeminiClient.php';
require_once __DIR__ . '/../../lib/Mailer.php';
require_once __DIR__ . '/../../lib/Publisher.php';
require_once __DIR__ . '/../../lib/PromptTemplates.php';

$db = DB::getInstance();

$errorMsg = null;
$selected_cliente_id = 0;
$selected_tema = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_cliente_id = isset($_POST['cliente_id']) ? intval($_POST['cliente_id']) : 0;
    $selected_tema = isset($_POST['tema']) ? trim($_POST['tema']) : '';
} else {
    $selected_cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : 0;
    $selected_tema = isset($_GET['tema']) ? trim($_GET['tema']) : '';
}

// Forzar cliente_id si el usuario es cliente
if (isset($_SESSION['role']) && $_SESSION['role'] === 'cliente' && isset($_SESSION['cliente_id'])) {
    $selected_cliente_id = $_SESSION['cliente_id'];
    
    // El cliente no puede iniciar una nueva generación desde cero (solo desde sugerencias o borradores)
    $is_allowed = false;
    if (isset($_GET['draft_id']) || isset($_GET['success_id']) || isset($_POST['post_id'])) {
        $is_allowed = true;
    } elseif (isset($_GET['sugerencia_id']) || isset($_GET['tema'])) {
        $is_allowed = true;
    } elseif (isset($_POST['sugerencia_id']) && intval($_POST['sugerencia_id']) > 0) {
        $is_allowed = true;
    } elseif (isset($_POST['tema']) && !isset($_POST['post_id'])) {
        $is_allowed = true;
    }
    
    if (!$is_allowed) {
        $_SESSION['flash_error'] = "No tienes permisos para generar posts nuevos desde cero. Por favor, selecciona un tema sugerido desde tu correo.";
        header("Location: lista.php");
        exit();
    }
}

// 1. Obtener lista de clientes activos (filtrada por rol)
if (isset($_SESSION['role']) && $_SESSION['role'] === 'cliente' && isset($_SESSION['cliente_id'])) {
    // El cliente solo ve SU propia cuenta
    $stmtCl = $db->prepare("SELECT * FROM clientes WHERE activo = 1 AND id = ? ORDER BY nombre ASC");
    $stmtCl->execute([$_SESSION['cliente_id']]);
    $clientes = $stmtCl->fetchAll();
} else {
    $stmt = $db->query("SELECT * FROM clientes WHERE activo = 1 ORDER BY nombre ASC");
    $clientes = $stmt->fetchAll();
}

// 2. Procesar acción de publicar directamente el post en el sitio cliente
if (isset($_POST['action']) && $_POST['action'] === 'publicar' && isset($_POST['post_id'])) {
    $post_id = intval($_POST['post_id']);

    // Obtener post
    $stmtPost = $db->prepare("SELECT * FROM posts WHERE id = ?");
    $stmtPost->execute([$post_id]);
    $post = $stmtPost->fetch();

    if ($post) {
        // 1. Marcar como aprobado en la BD
        $stmtApprove = $db->prepare("UPDATE posts SET estado = 'aprobado', fecha_aprobacion = NOW() WHERE id = ?");
        $stmtApprove->execute([$post_id]);

        // 2. Publicar en el sitio cliente (Simulado en Modo Demo)
        if (isset($_SESSION['is_demo']) && $_SESSION['is_demo'] === true) {
            $db->prepare("UPDATE posts SET publicacion_exitosa = 1 WHERE id = ?")->execute([$post_id]);
            $pubRes = ['ok' => true];
        } else {
            $pubRes = Publisher::publish($post_id);
        }

        if ($pubRes['ok']) {
            $_SESSION['flash_success'] = "¡El post ha sido aprobado y publicado en el sitio web con éxito!";
        } else {
            $_SESSION['flash_error'] = "El post fue aprobado localmente, pero falló la publicación remota: " . $pubRes['error'];
        }
    }
    
    // Intercepción Modo Demo para mostrar vista publicada simulada
    if (isset($_SESSION['is_demo']) && $_SESSION['is_demo'] === true) {
        header("Location: " . BASE_URL . "/platform/admin/demo_published_preview.php?post_id=" . $post_id);
        exit();
    }
    
    header("Location: lista.php");
    exit();
}

// 3. Procesar acción de guardar borrador
if (isset($_POST['action']) && $_POST['action'] === 'guardar_borrador' && isset($_POST['post_id'])) {
    $post_id = intval($_POST['post_id']);
    $titulo = trim($_POST['titulo']);
    $texto = trim($_POST['texto']);

    if (!empty($titulo) && !empty($texto)) {
        $stmtUpdate = $db->prepare("UPDATE posts SET titulo = ?, texto = ? WHERE id = ?");
        $stmtUpdate->execute([$titulo, $texto, $post_id]);
        $_SESSION['flash_success'] = "Borrador de texto guardado correctamente.";
    } else {
        $_SESSION['flash_error'] = "El título y el texto no pueden estar vacíos.";
    }
    header("Location: generar.php?draft_id=" . $post_id);
    exit();
}

// 3b. Procesar acción de usar imagen personalizada (subida o url)
if (isset($_POST['action']) && $_POST['action'] === 'subir_personalizada' && isset($_POST['post_id'])) {
    $post_id = intval($_POST['post_id']);
    $titulo = trim($_POST['titulo']);
    $texto = trim($_POST['texto']);

    try {
        // Cargar post y cliente
        $stmtPost = $db->prepare("SELECT p.*, c.rubro, c.nombre, c.slug, c.color_primario, c.logo_url, c.fuente_titulo, c.fuente_texto, c.color_texto, c.nombre_autor, c.foto_autor_url, c.dominio, c.email_revisor FROM posts p JOIN clientes c ON p.cliente_id = c.id WHERE p.id = ?");
        $stmtPost->execute([$post_id]);
        $post = $stmtPost->fetch();

        if (!$post) {
            throw new Exception("Borrador no encontrado.");
        }

        $cliente_id = $post['cliente_id'];

        // Guardar textos editados primero
        $stmtUpdate = $db->prepare("UPDATE posts SET titulo = ?, texto = ? WHERE id = ?");
        $stmtUpdate->execute([$titulo, $texto, $post_id]);

        $imageUrl = '';

        // 1. Verificar si hay archivo subido
        if (isset($_FILES['custom_image_file']) && $_FILES['custom_image_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['custom_image_file']['tmp_name'];
            $fileName = $_FILES['custom_image_file']['name'];
            $fileSize = $_FILES['custom_image_file']['size'];
            $fileType = $_FILES['custom_image_file']['type'];

            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            $allowedfileExtensions = ['jpg', 'gif', 'png', 'jpeg', 'webp'];
            if (in_array($fileExtension, $allowedfileExtensions)) {
                $newFileName = $post['slug'] . '-manual-' . time() . '.' . $fileExtension;
                $destPath = UPLOAD_DIR . '/' . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $imageUrl = UPLOAD_URL . '/' . $newFileName;
                } else {
                    throw new Exception("Hubo un error al mover el archivo subido al directorio de uploads.");
                }
            } else {
                throw new Exception("Extensión de archivo no permitida para la portada del post.");
            }
        }
        // 2. Si no hay archivo pero hay URL
        elseif (!empty($_POST['custom_image_url'])) {
            $imageUrl = trim($_POST['custom_image_url']);
            if (filter_var($imageUrl, FILTER_VALIDATE_URL) === false) {
                throw new Exception("La URL de la imagen provista no es válida.");
            }
        } else {
            throw new Exception("Por favor selecciona un archivo o provee una URL de imagen válida.");
        }

        // Actualizar URL de imagen en el post
        $stmtUpdateImg = $db->prepare("UPDATE posts SET imagen_url = ? WHERE id = ?");
        $stmtUpdateImg->execute([$imageUrl, $post_id]);

        $_SESSION['flash_success'] = "¡Imagen manual establecida con éxito y post completado!";
        header("Location: generar.php?success_id=" . $post_id);
        exit();

    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Error al asociar imagen manual: " . $e->getMessage();
        header("Location: generar.php?draft_id=" . $post_id);
        exit();
    }
}

// 4. Procesar acción de diseñar imagen por IA (Paso 2/3)
if (isset($_POST['action']) && $_POST['action'] === 'diseñar_imagen' && isset($_POST['post_id'])) {
    $post_id = intval($_POST['post_id']);
    $titulo = trim($_POST['titulo']);
    $texto = trim($_POST['texto']);

    try {
        // Cargar post y cliente
        $stmtPost = $db->prepare("SELECT p.*, c.rubro, c.nombre, c.slug, c.color_primario, c.logo_url, c.fuente_titulo, c.fuente_texto, c.color_texto, c.nombre_autor, c.foto_autor_url, c.dominio, c.email_revisor FROM posts p JOIN clientes c ON p.cliente_id = c.id WHERE p.id = ?");
        $stmtPost->execute([$post_id]);
        $post = $stmtPost->fetch();

        if (!$post) {
            throw new Exception("Borrador no encontrado.");
        }

        $cliente_id = $post['cliente_id'];

        // Intercepción para Modo Demo en Imagen
        if (isset($_SESSION['is_demo']) && $_SESSION['is_demo'] === true) {
            $imageUrlStatic = 'https://images.unsplash.com/photo-1560066984-138dadb4c035?q=80&w=800&auto=format&fit=crop';
            $temaLower = mb_strtolower($post['tema']);
            
            if ($post['slug'] === 'adri-hair-style-demo') {
                if (strpos($temaLower, 'peinados') !== false) {
                    $imageUrlStatic = 'https://images.unsplash.com/photo-1595476108010-b4d1f102b1b1?q=80&w=800&auto=format&fit=crop';
                } elseif (strpos($temaLower, 'decoloración') !== false) {
                    $imageUrlStatic = 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?q=80&w=800&auto=format&fit=crop';
                } elseif (strpos($temaLower, 'spa coreano') !== false) {
                    $imageUrlStatic = 'https://images.unsplash.com/photo-1519699047748-de8e457a634e?q=80&w=800&auto=format&fit=crop';
                }
            } elseif ($post['slug'] === 'fitlife-gym-demo') {
                if (strpos($temaLower, 'hiit') !== false || strpos($temaLower, '20 minutos') !== false) {
                    $imageUrlStatic = 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?q=80&w=800&auto=format&fit=crop';
                } elseif (strpos($temaLower, 'estiramiento') !== false) {
                    $imageUrlStatic = 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?q=80&w=800&auto=format&fit=crop';
                } else {
                    $imageUrlStatic = 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=800&auto=format&fit=crop';
                }
            } elseif ($post['slug'] === 'cafe-aroma-demo') {
                if (strpos($temaLower, 'filtrado') !== false || strpos($temaLower, 'v60') !== false) {
                    $imageUrlStatic = 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?q=80&w=800&auto=format&fit=crop';
                } elseif (strpos($temaLower, 'arábica') !== false) {
                    $imageUrlStatic = 'https://images.unsplash.com/photo-1447933601403-0c6688de566e?q=80&w=800&auto=format&fit=crop';
                } else {
                    $imageUrlStatic = 'https://images.unsplash.com/photo-1507133750040-4a8f57021571?q=80&w=800&auto=format&fit=crop';
                }
            }

            $imageBytes = @file_get_contents($imageUrlStatic);
            if ($imageBytes === false) {
                $imageBytes = @file_get_contents('https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=800&q=80');
            }

            if ($imageBytes === false) {
                throw new Exception("Modo Demo: No se pudo descargar la imagen de demostración.");
            }

            $filename = $post['slug'] . '-' . time() . '.jpg';
            $destPath = UPLOAD_DIR . '/' . $filename;

            if (file_put_contents($destPath, $imageBytes) === false) {
                throw new Exception("No se pudo escribir la imagen de portada de la demo en el servidor.");
            }

            $imageUrl = UPLOAD_URL . '/' . $filename;

            // Guardar textos editados
            $stmtUpdate = $db->prepare("UPDATE posts SET titulo = ?, texto = ? WHERE id = ?");
            $stmtUpdate->execute([$titulo, $texto, $post_id]);

            // Actualizar URL de imagen en el post
            $stmtUpdateImg = $db->prepare("UPDATE posts SET imagen_url = ? WHERE id = ?");
            $stmtUpdateImg->execute([$imageUrl, $post_id]);

            // Registrar consumos de texto del refiner e imagen simulados
            GeminiClient::logUsage($cliente_id, GEMINI_TEXT_MODEL, 'refinar_prompt_imagen', 65, 120, 0.0, $post_id);
            GeminiClient::logUsage($cliente_id, GEMINI_IMAGE_MODEL, 'generar_imagen', 0, 0, 0.03, $post_id);

            $_SESSION['flash_success'] = "¡Imagen de portada diseñada con éxito (Simulación) y post completado!";
            header("Location: generar.php?success_id=" . $post_id);
            exit();
        }

        // Verificar presupuesto mensual
        if (GeminiClient::isMonthlyLimitExceeded($cliente_id)) {
            throw new Exception("Se ha superado el presupuesto mensual de IA asignado a este cliente.");
        }

        // Guardar textos editados primero
        $stmtUpdate = $db->prepare("UPDATE posts SET titulo = ?, texto = ? WHERE id = ?");
        $stmtUpdate->execute([$titulo, $texto, $post_id]);

        // Construir prompt para la imagen usando el tema de manera conceptual
        $colorDescription = "soft and elegant colors";
        if (!empty($post['color_primario'])) {
            if (strtolower($post['color_primario']) === '#e8b4b8') {
                $colorDescription = "warm pastel pink colors";
            } else {
                $colorDescription = "harmonious color palette matching " . $post['color_primario'];
            }
        }

        // 1. Refinar el prompt usando Gemini Text (bajo costo) para obtener un prompt visual detallado en inglés para Imagen 3
        $refineSystemInstruction = PromptTemplates::getImageRefineSystemInstruction();

        $refineInput = "Client Brand Name: {$post['nombre']}\n"
            . "Business Industry: {$post['rubro']}\n"
            . "Theme of the post: {$post['tema']}\n"
            . "Title of the post: {$post['titulo']}\n"
            . "Preferred background colors: {$colorDescription}";

        $imagePrompt = "";
        try {
            $refineResult = GeminiClient::generateJson($refineInput, $refineSystemInstruction);
            $refinedPrompt = isset($refineResult['prompt']) ? trim($refineResult['prompt']) : '';
            if (!empty($refinedPrompt)) {
                $imagePrompt = $refinedPrompt . ", high resolution, cinematic lighting, editorial portrait photography. "
                    . "Strictly NO text, NO logos, NO writing, NO layout, NO graphic design elements, clean photograph only.";
            }
        } catch (Exception $e) {
            error_log("Error al refinar prompt de imagen con Gemini: " . $e->getMessage());
        }

        // Fallback en caso de que falle la refinería
        if (empty($imagePrompt)) {
            $imagePrompt = "A professional, clean, close-up photograph representing: {$post['tema']}. "
                . "Beautiful lighting, clean background with {$colorDescription}. "
                . "Strictly NO text, NO logos, NO writing, NO layout, NO graphic design elements, clean photograph only.";
        }

        // Generar imagen con IA
        $imageBytes = GeminiClient::generateImage($imagePrompt);

        // Guardar banner localmente
        $filename = $post['slug'] . '-' . time() . '.jpg';
        $destPath = UPLOAD_DIR . '/' . $filename;

        if (file_put_contents($destPath, $imageBytes) === false) {
            throw new Exception("No se pudo escribir la imagen de portada en el servidor.");
        }

        $imageUrl = UPLOAD_URL . '/' . $filename;

        // Actualizar URL de imagen en el post
        $stmtUpdateImg = $db->prepare("UPDATE posts SET imagen_url = ? WHERE id = ?");
        $stmtUpdateImg->execute([$imageUrl, $post_id]);

        // Registrar consumos de texto del refiner e imagen
        $refineTokens = GeminiClient::getLastUsageMetadata();
        if ($refineTokens) {
            GeminiClient::logUsage(
                $cliente_id,
                GEMINI_TEXT_MODEL,
                'refinar_prompt_imagen',
                $refineTokens['promptTokenCount'] ?? 0,
                $refineTokens['candidatesTokenCount'] ?? 0,
                0.0,
                $post_id
            );
        }

        GeminiClient::logUsage(
            $cliente_id,
            GEMINI_IMAGE_MODEL,
            'generar_imagen',
            0,
            0,
            GEMINI_PRICE_IMAGE,
            $post_id
        );

        $_SESSION['flash_success'] = "¡Imagen de portada diseñada con éxito y post completado!";
        header("Location: generar.php?success_id=" . $post_id);
        exit();

    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Error al diseñar la imagen: " . $e->getMessage();
        header("Location: generar.php?draft_id=" . $post_id);
        exit();
    }
}

// 5. Procesar acción de generar texto del post (Paso 1)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cliente_id']) && isset($_POST['tema'])) {
    $cliente_id = intval($_POST['cliente_id']);
    $tema = trim($_POST['tema']);
    $sugerencia_id = isset($_POST['sugerencia_id']) ? intval($_POST['sugerencia_id']) : 0;

    // Obtener cliente
    $stmtCli = $db->prepare("SELECT * FROM clientes WHERE id = ? AND activo = 1");
    $stmtCli->execute([$cliente_id]);
    $cliente = $stmtCli->fetch();

    if (!$cliente) {
        $errorMsg = "El cliente seleccionado no existe o no está activo.";
    } elseif (empty($tema)) {
        $errorMsg = "Por favor ingresa o selecciona un tema.";
    } elseif (isset($_SESSION['is_demo']) && $_SESSION['is_demo'] === true) {
        // Modo Demo: Interceptar Generación de Texto
        try {
            $titulo = '';
            $texto = '';
            $temaLower = mb_strtolower($tema);

            if ($cliente['slug'] === 'adri-hair-style-demo') {
                if (strpos($temaLower, 'peinados') !== false) {
                    $titulo = "5 Peinados Express: Luce increíble en la oficina en menos de 10 minutos";
                    $texto = "El ritmo de las mañanas suele ser acelerado, pero eso no significa que debas descuidar tu estilo. Con un par de minutos y los accesorios correctos, puedes transformar un cabello rebelde en un look sofisticado y profesional para tu jornada laboral.\n\nAquí te compartimos cinco opciones prácticas y elegantes:\n\n1. **La Ponytail Pulida:** El clásico infalible. Aplica un poco de sérum hidratante en la raíz para controlar el frizz, cepilla firmemente hacia atrás y sujeta a media altura. Cubre la banda elástica con un mechón de cabello para un acabado de salón.\n\n2. **El Moño Bajo Desenfadado (Low Bun):** Ideal para días lluviosos o con humedad. Recoge el cabello a la altura de la nuca y enróllalo de forma holgada. Asegúralo con horquillas invisibles dejando caer unos sutiles mechones al frente.\n\n3. **Trenza de Espiga Lateral:** Aporta un aire romántico y bohemio pero ordenado. Llévala hacia un lado y abre un poco los eslabones con los dedos para dar volumen natural.\n\n4. **Semirrecogido con Clip Metálico:** Para las amantes del cabello suelto. Separa las secciones frontales superiores, llévalas hacia atrás y únelas con un pasador geométrico minimalista.\n\n5. **El Twist Francés Moderno:** Enrolla el cabello sobre sí mismo hacia arriba y sujétalo con una pinza de carey, un accesorio muy en tendencia que aporta textura al instante.\n\n*Consejo de Adri:* Para que cualquiera de estos peinados dure todo el día sin maltratar tu fibra capilar, evita las ligas de goma expuestas y prefiere coleteros de satén. Si quieres preparar tu cabello para que luzca radiante en cada peinado, te invitamos a probar nuestro **Tratamiento de Hidratación Profunda** en el salón, diseñado para devolver la elasticidad y el brillo natural. ¡Agenda tu cita hoy mismo!";
                } elseif (strpos($temaLower, 'decoloración') !== false) {
                    $titulo = "Guía de Rescate: Cómo mantener tu rubio radiante y sano tras la decoloración";
                    $texto = "La decoloración es un proceso químico intenso que abre la cutícula capilar para extraer los pigmentos naturales. Aunque el resultado de color puede ser espectacular, la estructura del cabello queda vulnerable y requiere cuidados específicos para no convertirse en una textura pajiza y quebradiza.\n\nPara restaurar y proteger tu cabello decolorado, sigue esta rutina de rescate:\n\n1. **Espacia los lavados:** Intenta lavar tu cabello de 2 a 3 veces por semana como máximo. El exceso de agua y champú barre los aceites naturales esenciales.\n\n2. **Champú sin sulfatos:** Utiliza fórmulas extremadamente suaves y especializadas para cabello teñido.\n\n3. **Mascarilla de reconstrucción:** Sustituye el acondicionador común por una mascarilla reconstructora con aminoácidos y proteínas al menos una vez por semana. Déjala actuar durante 10 minutos antes de enjuagar con agua tibia o fría.\n\n4. **Protección térmica obligatoria:** Nunca uses planchas ni secadores sin antes aplicar un buen escudo térmico. El calor extremo es el peor enemigo del cabello decolorado.\n\n5. **Aceite de puntas diario:** Aplica dos gotas de aceite de argán o coco en las puntas secas por las mañanas y noches para sellar la humedad.\n\n*Consejo de Adri:* Los productos comerciales solo reparan la capa superficial del cabello. Si notas que tu cabello se estira como chicle o se rompe al peinarlo, necesita un aporte intensivo de lípidos y proteínas. En nuestro salón contamos con el servicio exclusivo de **Cauterización y Rescate Capilar**, que sella la cutícula y devuelve la fuerza perdida desde la primera sesión. ¡Ven a consentirte y devuelve la vida a tu melena!";
                } elseif (strpos($temaLower, 'spa coreano') !== false) {
                    $titulo = "Spa Coreano Capilar: El secreto asiático para un cuero cabelludo sano y un cabello radiante";
                    $texto = "El cuidado de la piel coreano (K-Beauty) ha revolucionado el mundo de la estética, pero ¿sabías que esta misma filosofía se aplica al cuero cabelludo? El Spa Coreano Capilar es un ritual terapéutico centrado en la desintoxicación, exfoliación y estimulación del cuero cabelludo, reconociendo que un cabello hermoso solo puede nacer de una raíz sana.\n\nAquí te explicamos los principales beneficios de este tratamiento integral:\n\n1. **Limpieza y exfoliación profunda:** Elimina la acumulación de sebo, células muertas y residuos de productos de peinado que obstruyen los folículos pilosos.\n\n2. **Estimulación de la circulación:** A través de masajes capilares y chorros de agua a presión controlada, se activa el flujo sanguíneo, acelerando el crecimiento y previniendo la caída.\n\n3. **Relajación profunda y reducción del estrés:** El ritual incluye aromaterapia, masajes en cuello y hombros, convirtiéndolo en una experiencia de bienestar única.\n\n4. **Regulación del pH:** Ayuda a equilibrar el cuero cabelludo graso o extremadamente seco, combatiendo la caspa y la picazón.\n\n5. **Nutrición intensiva:** Tras la limpieza, se aplican ampollas y sueros vitamínicos que penetran con mayor facilidad en los poros limpios.\n\n*Consejo de Adri:* Si sufres de caída de cabello, crecimiento lento o simplemente buscas un momento de desconexión absoluta, nuestro servicio de **Spa Coreano Capilar** en el salón boutique es justo lo que necesitas. Experimenta la relajación y el brillo instantáneo que tu melena merece. ¡Reserva tu sesión especial y siente la diferencia!";
                } else {
                    $titulo = "Cuidado Capilar Profesional para el Día a Día";
                    $texto = "Cuidar el cabello durante las rutinas diarias requiere una atención especial. La falta de humedad y la exposición al calor tienden a resecar las fibras capilares, dejándolas propensas al quiebre y a la pérdida de brillo natural.\n\nPara contrarrestar estos efectos, se recomienda utilizar tratamientos nutritivos ricos en aceites naturales de manera semanal. Además, es de vital importancia espaciar el uso de herramientas térmicas como planchas y secadores, e incorporar siempre un protector térmico de alta calidad.\n\nFinalmente, recuerda realizar masajes en el cuero cabelludo para mejorar la circulación y promover un crecimiento fuerte. ¡Un cabello sano comienza desde adentro!";
                }
            } elseif ($cliente['slug'] === 'fitlife-gym-demo') {
                if (strpos($temaLower, 'hiit') !== false || strpos($temaLower, '20 minutos') !== false) {
                    $titulo = "Quema grasa en tiempo récord: Rutina HIIT de 20 minutos para hacer en casa";
                    $texto = "La falta de tiempo ya no es una excusa para no entrenar. Las rutinas HIIT (Entrenamiento Interválico de Alta Intensidad) son ideales para acelerar el metabolismo, mejorar la capacidad cardiovascular y quemar calorías incluso horas después de haber terminado de hacer ejercicio.\n\nAquí tienes una rutina de 20 minutos sin equipamiento:\n\n- Calentamiento (3 minutos): Rotaciones articulares y trote suave en el sitio.\n- Circuito principal (14 minutos): Realiza cada ejercicio durante 40 segundos a máxima intensidad, seguido de 20 segundos de descanso. Repite el circuito completo 3 veces:\n  1. *Sentadillas con salto:* Fuerza en piernas y potencia.\n  2. *Flexiones de brazos:* Trabajo de pecho, tríceps y core.\n  3. *Burpees:* El rey de los ejercicios cardiovasculares de cuerpo completo.\n  4. *Escaladores (Mountain Climbers):* Agilidad y resistencia abdominal.\n  5. *Zancadas alternas:* Estabilidad y fortalecimiento del tren inferior.\n- Vuelta a la calma (3 minutos): Estiramientos lentos enfocados en la respiración.\n\n*Consejo de FitLife:* La clave del HIIT está en dar tu 100% en los intervalos de trabajo. Si estás buscando estructurar tus entrenamientos y alcanzar tus metas de forma segura y personalizada, te invitamos a probar nuestras clases guiadas y nuestro servicio de **Entrenamiento Personalizado** en FitLife Gym. ¡Te ayudamos a dar el máximo!";
                } elseif (strpos($temaLower, 'estiramiento') !== false) {
                    $titulo = "Flexibilidad y Recuperación: ¿Por qué NUNCA debes saltarte el estiramiento?";
                    $texto = "Terminar la última repetición del entrenamiento y salir corriendo de la sala es un error muy común. El estiramiento post-entrenamiento es una parte fundamental de la sesión física que ayuda a transicionar el cuerpo de un estado de alta tensión a uno de reposo y recuperación.\n\nLos beneficios principales de dedicar 10 minutos a estirar son:\n\n1. **Reduce la rigidez y el dolor muscular (agujetas):** Facilita la circulación y ayuda a barrer el ácido láctico acumulado.\n2. **Mejora la flexibilidad y el rango de movimiento:** Mantener los músculos elásticos previene lesiones articulares y mejora la postura corporal.\n3. **Favorece la relajación mental:** La respiración profunda durante el estiramiento reduce los niveles de cortisol y promueve el bienestar.\n\n*Consejo de FitLife:* Estira siempre cuando el músculo esté caliente, aplicando una tensión suave sin llegar a sentir dolor. En FitLife Gym disponemos de un área dedicada de **Flexibilidad y Yoga** donde te enseñamos a cuidar tu cuerpo integralmente. ¡Ven a conocer nuestras instalaciones!";
                } else {
                    $titulo = "Cómo mantener un estilo de vida activo y saludable";
                    $texto = "Llevar un estilo de vida saludable no se trata de dietas restrictivas ni entrenamientos extenuantes de cinco horas diarias. Se trata de consistencia, hábitos sustentables en el tiempo y el disfrute de mover el cuerpo.\n\nEncuentra una actividad física que realmente te apasione, hidrátate bien a lo largo de todo el día y prioriza el descanso nocturno. ¡Tu cuerpo te lo agradecerá!";
                }
            } else { // cafe-aroma-demo y fallback
                if (strpos($temaLower, 'filtrado') !== false || strpos($temaLower, 'v60') !== false) {
                    $titulo = "Cafetera V60 y Chemex: Cómo preparar un café de especialidad perfecto en casa";
                    $texto = "Preparar café filtrado en casa es un ritual casi alquímico que permite resaltar las notas más sutiles, florales y frutales de los granos de café de especialidad. A diferencia del espresso, el método de filtrado por goteo ofrece una taza limpia, ligera y aromática.\n\nSigue estos pasos esenciales para un filtrado perfecto:\n\n1. **Elige granos frescos:** Compra café que indique la fecha de tueste (idealmente menor a un mes). Muele al instante.\n2. **Usa agua de calidad:** El café es 98% agua. Utiliza agua filtrada a una temperatura de entre 90°C y 94°C (justo antes de hervir).\n3. **Pre-infusión:** Vierte un poco de agua caliente sobre el café molido en el filtro y espera 30 segundos. Verás burbujear los granos; esto libera el dióxido de carbono atrapado.\n4. **Vertido continuo y en círculos:** Añade el agua restante de forma lenta y constante, en espiral desde el centro hacia los bordes.\n\n*Consejo de Café Aroma:* Si quieres experimentar las notas auténticas de orígenes como Etiopía, Colombia o Kenia, visítanos en Café Aroma. Contamos con una exclusiva **Barra de Métodos de Filtrado** donde nuestros baristas te prepararán el café en V60 o Chemex y te guiarán en una cata de sabores inolvidable. ¡Te esperamos!";
                } elseif (strpos($temaLower, 'arábica') !== false) {
                    $titulo = "Arábica vs. Robusta: Guía práctica para entender tu taza de café diaria";
                    $texto = "Aunque existen decenas de especies de plantas de café en el mundo, el mercado mundial comercializa principalmente dos: Arábica y Robusta. Si alguna vez te has preguntado por qué una taza de café se siente dulce y aromática, mientras otra es densa y amarga, la respuesta está en la especie del grano.\n\nAquí te detallamos las diferencias clave:\n\n1. **Sabor y Aroma:** El grano Arábica es conocido por su acidez agradable, dulzura natural y notas complejas (frutales, florales, achocolatadas). El Robusta tiene un perfil más terroso, amargo, con notas que recuerdan a la madera o frutos secos quemados.\n2. **Contenido de Cafeína:** La planta Robusta tiene casi el doble de cafeína que la Arábica, lo que también la hace más resistente a las plagas.\n3. **Altitud de Cultivo:** El café Arábica crece a gran altura (entre 900 y 2000 metros), lo que ralentiza su maduración y enriquece el sabor. El Robusta se cultiva en llanuras bajas.\n\n*Consejo de Café Aroma:* En Café Aroma trabajamos exclusivamente con granos **100% Arábica de Especialidad**, seleccionados directamente de fincas sostenibles. Ven a degustar la complejidad de un buen espresso preparado con pasión por expertos baristas. ¡Notarás la diferencia desde el primer sorbo!";
                } else {
                    $titulo = "El Arte Detrás de una Taza de Café de Especialidad";
                    $texto = "El café de especialidad es mucho más que una bebida caliente por la mañana; es el resultado del esfuerzo y cuidado meticuloso de caficultores, tostadores y baristas. Cada taza cuenta una historia de origen, altitud y dedicación.\n\nLa próxima vez que disfrutes tu taza de café, tómate un momento para apreciar sus aromas y notas únicas.";
                }
            }

            // Insertar post como borrador (estado 'pendiente', imagen vacía temporalmente)
            $tokenRevision = bin2hex(random_bytes(16));
            $stmtInsert = $db->prepare("
                INSERT INTO posts (cliente_id, tema, titulo, texto, imagen_url, estado, token_revision) 
                VALUES (?, ?, ?, ?, '', 'pendiente', ?)
            ");
            $stmtInsert->execute([$cliente_id, $tema, $titulo, $texto, $tokenRevision]);
            $postId = $db->lastInsertId();

            // Registrar consumo de tokens de texto simulados
            GeminiClient::logUsage($cliente_id, GEMINI_TEXT_MODEL, 'generar_post', 280, 550, 0.0, $postId);

            // Si vino de una sugerencia, marcarla como generada
            if ($sugerencia_id > 0) {
                $stmtMarkSugerencia = $db->prepare("UPDATE sugerencias_temas SET estado = 'generado' WHERE id = ?");
                $stmtMarkSugerencia->execute([$sugerencia_id]);
            }

            $_SESSION['flash_success'] = "Paso 1 Completado: Texto redactado correctamente (Simulación). Por favor revísalo y edítalo si lo deseas.";
            header("Location: generar.php?draft_id=" . $postId);
            exit();

        } catch (Exception $e) {
            $errorMsg = "Error en la generación con IA simulada: " . $e->getMessage();
        }
    } elseif (GeminiClient::isMonthlyLimitExceeded($cliente_id)) {
        $errorMsg = "No se puede generar contenido. Este cliente ha alcanzado su límite de presupuesto mensual de IA.";
    } else {
        try {
            // Obtener ángulo contextual de la sugerencia si aplica
            $sugerencia = null;
            if ($sugerencia_id > 0) {
                $stmtSug = $db->prepare("SELECT * FROM sugerencias_temas WHERE id = ?");
                $stmtSug->execute([$sugerencia_id]);
                $sugerencia = $stmtSug->fetch();
                
                if ($sugerencia && !empty($sugerencia['angulo_contextual'])) {
                    $details = json_decode($sugerencia['angulo_contextual'], true);
                    if (is_array($details)) {
                        $sugerencia['consejo_practico'] = $details['consejo_practico'] ?? '';
                        $sugerencia['servicio_a_promocionar'] = $details['servicio_a_promocionar'] ?? '';
                    } else {
                        $sugerencia['consejo_practico'] = $sugerencia['angulo_contextual'];
                        $sugerencia['servicio_a_promocionar'] = '';
                    }
                    $sugerencia['titulo_sugerido'] = $sugerencia['tema'];
                }
            }

            // Construir prompt para Gemini usando PromptTemplates
            $systemInstruction = PromptTemplates::getBlogTextSystemInstruction($cliente);

            if ($sugerencia && (!empty($sugerencia['consejo_practico']) || !empty($sugerencia['servicio_a_promocionar']))) {
                $textPrompt = "Información del Cliente:\n"
                    . "- Marca: {$cliente['nombre']}\n"
                    . "- Filosofía: Enfoque boutique, salud, rescate y restauración capilar real.\n\n"
                    . "Datos de la Propuesta a Desarrollar:\n"
                    . "- Título Base: {$sugerencia['titulo_sugerido']}\n"
                    . "- Consejo Clave a incluir: {$sugerencia['consejo_practico']}\n"
                    . "- Servicio a Promocionar e invitar: {$sugerencia['servicio_a_promocionar']}\n\n"
                    . "Por favor, redacta el post asegurándote de que el consejo técnico sea el protagonista del desarrollo y que la invitación a probar el servicio en el local se sienta orgánica y atractiva.";
            } else {
                $textPrompt = "Tema de Interés / Tema del post: " . $tema;
            }

            // Generar texto únicamente
            $textResult = GeminiClient::generateText($textPrompt, $systemInstruction);

            $titulo = $textResult['titulo'];
            $texto = $textResult['texto'];

            // Insertar post como borrador (estado 'pendiente', imagen vacía temporalmente)
            $tokenRevision = bin2hex(random_bytes(16));
            $stmtInsert = $db->prepare("
                INSERT INTO posts (cliente_id, tema, titulo, texto, imagen_url, estado, token_revision) 
                VALUES (?, ?, ?, ?, '', 'pendiente', ?)
            ");
            $stmtInsert->execute([$cliente_id, $tema, $titulo, $texto, $tokenRevision]);
            $postId = $db->lastInsertId();

            // Registrar consumo de tokens de texto
            $usage = GeminiClient::getLastUsageMetadata();
            if ($usage) {
                GeminiClient::logUsage(
                    $cliente_id,
                    GEMINI_TEXT_MODEL,
                    'generar_post',
                    $usage['promptTokenCount'] ?? 0,
                    $usage['candidatesTokenCount'] ?? 0,
                    0.0, // Costo calculado por la función logUsage
                    $postId
                );
            }

            // Si vino de una sugerencia, marcarla como generada
            if ($sugerencia_id > 0) {
                $stmtMarkSugerencia = $db->prepare("UPDATE sugerencias_temas SET estado = 'generado' WHERE id = ?");
                $stmtMarkSugerencia->execute([$sugerencia_id]);
            }

            $_SESSION['flash_success'] = "Paso 1 Completado: Texto redactado correctamente con Gemini. Por favor revísalo y edítalo si lo deseas.";
            header("Location: generar.php?draft_id=" . $postId);
            exit();

        } catch (Exception $e) {
            $errorMsg = "Error en la generación con IA: " . $e->getMessage();
        }
    }
}

// 6. Cargar datos para vistas GET
$draftPost = null;
$successPost = null;
$selected_sugerencia = null;

if (isset($_GET['draft_id'])) {
    $draft_id = intval($_GET['draft_id']);
    $stmtDraft = $db->prepare("SELECT p.*, c.nombre as cliente_nombre, c.limite_mensual_usd FROM posts p JOIN clientes c ON p.cliente_id = c.id WHERE p.id = ?");
    $stmtDraft->execute([$draft_id]);
    $draftPost = $stmtDraft->fetch();
} elseif (isset($_GET['success_id'])) {
    $success_id = intval($_GET['success_id']);
    $stmtSuccess = $db->prepare("
        SELECT p.*, 
               c.nombre as cliente_nombre, c.logo_url as cliente_logo, c.foto_autor_url as cliente_foto_autor,
               c.nombre_autor as cliente_nombre_autor, c.color_primario as cliente_color_primario,
               c.color_texto as cliente_color_texto, c.fuente_titulo as cliente_fuente_titulo,
               c.fuente_texto as cliente_fuente_texto
        FROM posts p 
        JOIN clientes c ON p.cliente_id = c.id 
        WHERE p.id = ?
    ");
    $stmtSuccess->execute([$success_id]);
    $successPost = $stmtSuccess->fetch();
} elseif (isset($_GET['sugerencia_id'])) {
    $sug_id = intval($_GET['sugerencia_id']);
    $stmtSug = $db->prepare("SELECT * FROM sugerencias_temas WHERE id = ? AND estado = 'pendiente'");
    $stmtSug->execute([$sug_id]);
    $selected_sugerencia = $stmtSug->fetch();

    if ($selected_sugerencia) {
        $selected_cliente_id = $selected_sugerencia['cliente_id'];
        $selected_tema = $selected_sugerencia['tema'];
    }
}

include __DIR__ . '/../layout_header.php';
?>

<!-- Inyectar hoja de estilos modularizada -->
<link rel="stylesheet" href="generar.css?v=<?php echo time(); ?>">

<div style="margin-bottom: 25px;">
    <h1 style="font-size: 28px; margin-bottom: 5px;">Generador de Contenido Optimizado</h1>
    <p style="color: var(--text-secondary); font-size: 14px;">Escribe borradores y diseña banners optimizando el consumo
        de créditos de IA.</p>
</div>

<!-- Cargar el layout de loader animado y alertas comunes -->
<?php include __DIR__ . '/views/loader_and_alerts.php'; ?>

<!-- Cargar la vista HTML correspondiente -->
<?php
if ($successPost) {
    include __DIR__ . '/views/step_3_preview.php';
} elseif ($draftPost) {
    include __DIR__ . '/views/step_2_draft.php';
} else {
    include __DIR__ . '/views/step_1_text.php';
}
?>

<!-- Inyectar archivo JavaScript modularizado -->
<script src="generar.js?v=<?php echo time(); ?>"></script>

<?php include __DIR__ . '/../layout_footer.php'; ?>