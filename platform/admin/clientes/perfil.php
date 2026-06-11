<?php
/**
 * Perfil del Cliente - Edición Limitada para el Cliente Logueado
 */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/DB.php';
require_once __DIR__ . '/../../lib/GeminiClient.php';

// Validar que el rol sea cliente
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cliente') {
    $_SESSION['flash_error'] = "Acceso restringido a clientes.";
    header("Location: ../login.php");
    exit();
}

$db = DB::getInstance();
$id = intval($_SESSION['cliente_id']);

// Obtener datos del cliente
$stmt = $db->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    $_SESSION['flash_error'] = "Cliente no encontrado.";
    header("Location: ../login.php");
    exit();
}

// Calcular consumo del mes
$limite_mensual_usd = floatval($cliente['limite_mensual_usd'] ?? 5.0000);
$gasto_mensual = GeminiClient::getMonthlySpend($id);
$porcentaje_consumo = $limite_mensual_usd > 0 ? min(100, ($gasto_mensual / $limite_mensual_usd) * 100) : 0;

// Procesar Formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rubro = trim($_POST['rubro']);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $temas_relacionar = trim($_POST['temas_relacionar'] ?? '');
    $tono_marca = trim($_POST['tono_marca']);
    $email_revisor = trim($_POST['email_revisor']);
    $nombre_autor = trim($_POST['nombre_autor']);
    $foto_autor_url = trim($_POST['foto_autor_url']);
    $color_primario = trim($_POST['color_primario']);
    $color_texto = trim($_POST['color_texto']);
    $fuente_titulo = trim($_POST['fuente_titulo']);
    $fuente_texto = trim($_POST['fuente_texto']);
    $logo_url = trim($_POST['logo_url'] ?? '');
    $estilo_redaccion = trim($_POST['estilo_redaccion'] ?? 'Cercano y Cotidiano');
    $autor_identidad = trim($_POST['autor_identidad'] ?? '');
    $autor_trasfondo = trim($_POST['autor_trasfondo'] ?? '');
    $autor_personalidad = trim($_POST['autor_personalidad'] ?? '');
    $autor_tratamiento = trim($_POST['autor_tratamiento'] ?? 'tú');

    // Validaciones básicas
    if (empty($rubro) || empty($tono_marca) || empty($email_revisor) || empty($nombre_autor) || empty($foto_autor_url)) {
        $error = "Todos los campos obligatorios (*) deben ser completados.";
    } else {
        try {
            $update = $db->prepare("UPDATE clientes SET 
                 rubro = ?, descripcion = ?, temas_relacionar = ?, tono_marca = ?, 
                 email_revisor = ?, nombre_autor = ?, foto_autor_url = ?, 
                 color_primario = ?, color_texto = ?, fuente_titulo = ?, fuente_texto = ?, 
                 logo_url = ?, estilo_redaccion = ?, autor_identidad = ?, autor_trasfondo = ?, autor_personalidad = ?, autor_tratamiento = ?
                 WHERE id = ?");
            
            $update->execute([
                $rubro, $descripcion, $temas_relacionar, $tono_marca,
                $email_revisor, $nombre_autor, $foto_autor_url,
                $color_primario, $color_texto, $fuente_titulo, $fuente_texto,
                $logo_url, $estilo_redaccion, $autor_identidad, $autor_trasfondo, $autor_personalidad, $autor_tratamiento,
                $id
            ]);

            $_SESSION['flash_success'] = "Tu perfil de negocio ha sido actualizado correctamente.";
            header("Location: perfil.php");
            exit();
        } catch (Exception $e) {
            $error = "Error al actualizar el perfil: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/../layout_header.php';
?>

<div style="margin-bottom: 25px;">
    <h1 style="font-size: 28px; margin-bottom: 5px;">Mi Perfil de Negocio</h1>
    <p style="color: var(--text-secondary); font-size: 14px;">Administra los datos de tu marca que la IA utiliza para redactar el contenido y la apariencia visual de tu blog.</p>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-error">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr; gap: 30px; align-items: start;" class="form-row">
    <!-- Formulario de Configuración de Marca -->
    <form action="perfil.php" method="POST" style="display: grid; grid-template-columns: 1fr; gap: 30px; margin: 0;">
        
        <div class="card-glass">
            <h2 style="font-size: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--border-glass); padding-bottom: 10px; color: var(--color-primary);">1. Configuración de Marca y Textos (IA)</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="rubro">Rubro / Actividad del Negocio *</label>
                    <input type="text" id="rubro" name="rubro" class="form-control" placeholder="Ej: peluquería femenina, repostería artesanal" required value="<?php echo htmlspecialchars($cliente['rubro']); ?>">
                    <span style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block;">Determina el contexto de los artículos redactados.</span>
                </div>
                <div class="form-group">
                    <label class="form-label" for="tono_marca">Tono de la Marca *</label>
                    <input type="text" id="tono_marca" name="tono_marca" class="form-control" placeholder="Ej: cercano, moderno, educativo, empoderador" required value="<?php echo htmlspecialchars($cliente['tono_marca']); ?>">
                    <span style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block;">Tono de voz de la IA (máx 3-4 palabras clave).</span>
                </div>
            </div>

            <div class="form-row" style="margin-top: 15px;">
                <div class="form-group">
                    <label class="form-label" for="email_revisor">Email del Revisor de Contenidos *</label>
                    <input type="email" id="email_revisor" name="email_revisor" class="form-control" placeholder="Ej: adri@adrihairstyle.cl" required value="<?php echo htmlspecialchars($cliente['email_revisor']); ?>">
                    <span style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block;">Dirección donde recibirás las sugerencias de posts.</span>
                </div>
                <div class="form-group">
                    <label class="form-label" for="logo_url">URL del Logo de tu Negocio</label>
                    <input type="url" id="logo_url" name="logo_url" class="form-control" placeholder="Ej: https://tudominio.com/logo.png" value="<?php echo htmlspecialchars($cliente['logo_url'] ?? ''); ?>">
                    <span style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block;">Se muestra en la cabecera de la revisión.</span>
                </div>
            </div>

            <div class="form-group" style="margin-top: 15px;">
                <label class="form-label" for="descripcion">Descripción del Negocio</label>
                <textarea id="descripcion" name="descripcion" class="form-control" rows="3" placeholder="Describe detalladamente a qué se dedica el negocio, especialidades y fortalezas..."><?php echo htmlspecialchars($cliente['descripcion'] ?? ''); ?></textarea>
                <span style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block;">Crucial para que la IA entienda tus especialidades y redacte con precisión.</span>
            </div>

            <div class="form-group" style="margin-top: 15px;">
                <label class="form-label" for="temas_relacionar">Temas de Interés a Relacionar</label>
                <textarea id="temas_relacionar" name="temas_relacionar" class="form-control" rows="2" placeholder="Ej: autoestima de la mujer, estilo de vida saludable, cuidado ambiental..."><?php echo htmlspecialchars($cliente['temas_relacionar'] ?? ''); ?></textarea>
                <span style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block;">La IA entrelazará estos temas secundarios con el rubro principal para generar empatía.</span>
            </div>
        </div>

        <div class="card-glass">
            <h2 style="font-size: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--border-glass); padding-bottom: 10px; color: var(--color-primary);">2. Datos del Autor del Blog</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="nombre_autor">Nombre del Autor *</label>
                    <input type="text" id="nombre_autor" name="nombre_autor" class="form-control" placeholder="Ej: Adri Montenegro" required value="<?php echo htmlspecialchars($cliente['nombre_autor']); ?>">
                    <span style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block;">Nombre que aparecerá firmado en cada post del blog.</span>
                </div>
                
                <div class="form-group">
                    <div style="display: flex; gap: 20px; align-items: center;">
                        <div style="flex-grow: 1;">
                            <label class="form-label" for="foto_autor_url">URL Foto del Autor (Absoluta) *</label>
                            <input type="url" id="foto_autor_url" name="foto_autor_url" class="form-control" placeholder="https://ejemplo.com/foto.jpg" required value="<?php echo htmlspecialchars($cliente['foto_autor_url']); ?>">
                        </div>
                        
                        <div style="text-align: center; flex-shrink: 0;">
                            <span class="form-label" style="margin-bottom: 8px;">Avatar</span>
                            <div style="width: 65px; height: 65px; border-radius: 50%; overflow: hidden; border: 2px solid var(--color-primary); background: #1E293B; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.3);">
                                <img id="preview-foto-autor" src="" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-glass">
            <h2 style="font-size: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--border-glass); padding-bottom: 10px; color: var(--color-primary); display: flex; align-items: center; gap: 8px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                3. Estilo de Redacción y Voz del Autor (IA)
            </h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="estilo_redaccion">Estilo de Redacción *</label>
                    <select id="estilo_redaccion" name="estilo_redaccion" class="form-control" required>
                        <option value="Cercano y Cotidiano" <?php echo (($cliente['estilo_redaccion'] ?? '') === 'Cercano y Cotidiano') ? 'selected' : ''; ?>>Cercano y Cotidiano: Como hablar con un amigo (Evita palabras complejas)</option>
                        <option value="Profesional y Directo" <?php echo (($cliente['estilo_redaccion'] ?? '') === 'Profesional y Directo') ? 'selected' : ''; ?>>Profesional y Directo: Equilibrio entre seriedad y claridad</option>
                        <option value="Elevado e Inspiracional" <?php echo (($cliente['estilo_redaccion'] ?? '') === 'Elevado e Inspiracional') ? 'selected' : ''; ?>>Elevado e Inspiracional: Lenguaje más sofisticado, premium o transformacional</option>
                        <option value="Educativo y Didáctico" <?php echo (($cliente['estilo_redaccion'] ?? '') === 'Educativo y Didáctico') ? 'selected' : ''; ?>>Educativo y Didáctico: Explicaciones paso a paso, ideal para guías y tutoriales</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="autor_tratamiento">Tratamiento al Lector *</label>
                    <select id="autor_tratamiento" name="autor_tratamiento" class="form-control" required>
                        <option value="tú" <?php echo (($cliente['autor_tratamiento'] ?? '') === 'tú') ? 'selected' : ''; ?>>De Tú (Cercano)</option>
                        <option value="usted" <?php echo (($cliente['autor_tratamiento'] ?? '') === 'usted') ? 'selected' : ''; ?>>De Usted (Formal)</option>
                        <option value="comunidad" <?php echo (($cliente['autor_tratamiento'] ?? '') === 'comunidad') ? 'selected' : ''; ?>>De Chicas/Chicos (Comunidad)</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top: 15px;">
                <label class="form-label" for="autor_identidad">¿Quién escribe? (Identidad del Autor)</label>
                <input type="text" id="autor_identidad" name="autor_identidad" class="form-control" placeholder="Ej: Una estilista profesional, apasionada por la salud capilar" value="<?php echo htmlspecialchars($cliente['autor_identidad'] ?? ''); ?>">
            </div>

            <div class="form-row" style="margin-top: 15px;">
                <div class="form-group">
                    <label class="form-label" for="autor_trasfondo">Origen / Trasfondo (Opcional)</label>
                    <input type="text" id="autor_trasfondo" name="autor_trasfondo" class="form-control" placeholder="Ej: Venezolana viviendo en Chile. Aporta calidez caribeña..." value="<?php echo htmlspecialchars($cliente['autor_trasfondo'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="autor_personalidad">Rasgos de Personalidad</label>
                    <input type="text" id="autor_personalidad" name="autor_personalidad" class="form-control" placeholder="Ej: Educada, empática, un toque alocada y muy directa..." value="<?php echo htmlspecialchars($cliente['autor_personalidad'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <div class="card-glass">
            <h2 style="font-size: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--border-glass); padding-bottom: 10px; color: var(--color-primary);">4. Identidad Visual (Estilos para el Blog)</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="color_primario">Color Primario (Dominante) *</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="color" id="color_primario_picker" class="form-control" style="width: 50px; padding: 2px; height: 40px; cursor: pointer;" value="<?php echo htmlspecialchars($cliente['color_primario']); ?>">
                        <input type="text" id="color_primario" name="color_primario" class="form-control" placeholder="#E8B4B8" maxlength="7" required value="<?php echo htmlspecialchars($cliente['color_primario']); ?>">
                    </div>
                    <span style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block;">Se usará de fondo en las imágenes generadas por IA.</span>
                </div>
                <div class="form-group">
                    <label class="form-label" for="color_texto">Color de Texto *</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="color" id="color_texto_picker" class="form-control" style="width: 50px; padding: 2px; height: 40px; cursor: pointer;" value="<?php echo htmlspecialchars($cliente['color_texto']); ?>">
                        <input type="text" id="color_texto" name="color_texto" class="form-control" placeholder="#2C2C2A" maxlength="7" required value="<?php echo htmlspecialchars($cliente['color_texto']); ?>">
                    </div>
                </div>
            </div>

            <div class="form-row" style="margin-top: 15px;">
                <div class="form-group">
                    <label class="form-label" for="fuente_titulo">Fuente de Título *</label>
                    <select id="fuente_titulo" name="fuente_titulo" class="form-control" required>
                        <option value="Georgia, serif" <?php echo ($cliente['fuente_titulo'] === 'Georgia, serif') ? 'selected' : ''; ?>>Georgia (Serif Elegante)</option>
                        <option value="'Outfit', sans-serif" <?php echo ($cliente['fuente_titulo'] === "'Outfit', sans-serif") ? 'selected' : ''; ?>>Outfit (Moderna Geométrica)</option>
                        <option value="'Playfair Display', serif" <?php echo ($cliente['fuente_titulo'] === "'Playfair Display', serif") ? 'selected' : ''; ?>>Playfair Display (Premium Editorial)</option>
                        <option value="system-ui, sans-serif" <?php echo ($cliente['fuente_titulo'] === 'system-ui, sans-serif') ? 'selected' : ''; ?>>System UI (Limpia por Defecto)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="fuente_texto">Fuente de Texto *</label>
                    <select id="fuente_texto" name="fuente_texto" class="form-control" required>
                        <option value="system-ui, sans-serif" <?php echo ($cliente['fuente_texto'] === 'system-ui, sans-serif') ? 'selected' : ''; ?>>System UI (Limpia por Defecto)</option>
                        <option value="'Inter', sans-serif" <?php echo ($cliente['fuente_texto'] === "'Inter', sans-serif") ? 'selected' : ''; ?>>Inter (Altamente Legible)</option>
                        <option value="Georgia, serif" <?php echo ($cliente['fuente_texto'] === 'Georgia, serif') ? 'selected' : ''; ?>>Georgia (Estilo Literario)</option>
                    </select>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 15px; justify-content: flex-end; margin-bottom: 30px;">
            <button type="submit" class="btn-custom btn-primary" style="padding: 12px 30px;">Guardar Cambios de Perfil</button>
        </div>
    </form>

    <!-- Información de Límites y Configuración Administrativa -->
    <div style="display: grid; grid-template-columns: 1fr; gap: 30px; margin: 0;">
        <!-- Card de Presupuesto Mensual -->
        <div class="card-glass" style="border: 1px solid rgba(139, 92, 246, 0.2); background: linear-gradient(135deg, rgba(22, 28, 45, 0.8), rgba(30, 41, 59, 0.4));">
            <h2 style="font-size: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--border-glass); padding-bottom: 10px; color: var(--color-primary); display: flex; align-items: center; gap: 8px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Límite de Consumo Mensual (IA)
            </h2>
            
            <div style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px;">
                    <span style="color: var(--text-secondary);">Gasto Estimado Este Mes:</span>
                    <span style="font-weight: 700; color: var(--text-primary);">$<?php echo number_format($gasto_mensual, 4); ?> USD</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 13px;">
                    <span style="color: var(--text-secondary);">Límite Asignado por Admin:</span>
                    <span style="font-weight: 700; color: var(--color-primary);">$<?php echo number_format($limite_mensual_usd, 4); ?> USD</span>
                </div>

                <!-- Barra de Progreso -->
                <div style="width: 100%; height: 10px; background: rgba(255,255,255,0.05); border-radius: 5px; overflow: hidden; margin-bottom: 10px; border: 1px solid var(--border-glass);">
                    <div style="width: <?php echo $porcentaje_consumo; ?>%; height: 100%; background: linear-gradient(90deg, var(--color-primary), var(--color-accent)); border-radius: 5px; transition: width 0.5s ease;"></div>
                </div>
                
                <div style="text-align: right; font-size: 11px; color: var(--text-muted);">
                    Consumido: <?php echo number_format($porcentaje_consumo, 1); ?>%
                </div>
            </div>

            <p style="font-size: 12px; color: var(--text-secondary); line-height: 1.5; background: rgba(0,0,0,0.2); padding: 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.02); margin: 0;">
                <strong>Nota:</strong> Para prevenir generación accidental descontrolada, el administrador configura un presupuesto máximo al mes. Si alcanzas el 100%, la generación de posts con IA se detendrá hasta el siguiente mes.
            </p>
        </div>

        <!-- Card de Configuración Técnica de Integración (Solo Lectura) -->
        <div class="card-glass">
            <h2 style="font-size: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--border-glass); padding-bottom: 10px; color: var(--color-accent); display: flex; align-items: center; gap: 8px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Configuración del Negocio (Admin)
            </h2>
            
            <div style="display: grid; grid-template-columns: 1fr; gap: 15px;">
                <div>
                    <label class="form-label" style="font-size: 11px; color: var(--text-muted); margin-bottom: 2px;">Nombre Oficial del Negocio</label>
                    <div style="color: var(--text-primary); font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($cliente['nombre']); ?></div>
                </div>

                <div>
                    <label class="form-label" style="font-size: 11px; color: var(--text-muted); margin-bottom: 2px;">Slug de Marca (Identificador)</label>
                    <div style="color: var(--text-secondary); font-family: monospace; font-size: 13px;"><?php echo htmlspecialchars($cliente['slug']); ?></div>
                </div>

                <div>
                    <label class="form-label" style="font-size: 11px; color: var(--text-muted); margin-bottom: 2px;">Dominio Web Asociado</label>
                    <div style="color: var(--text-primary); font-size: 14px;">
                        <a href="<?php echo htmlspecialchars($cliente['dominio']); ?>" target="_blank" style="color: var(--color-primary); text-decoration: none;">
                            <?php echo htmlspecialchars($cliente['dominio']); ?> ↗
                        </a>
                    </div>
                </div>

                <div>
                    <label class="form-label" style="font-size: 11px; color: var(--text-muted); margin-bottom: 2px;">Frecuencia Planificada de Publicación</label>
                    <div style="color: var(--text-primary); font-size: 14px;">Sugerencia cada <strong><?php echo intval($cliente['frecuencia_dias']); ?> días</strong></div>
                </div>

                <div>
                    <label class="form-label" style="font-size: 11px; color: var(--text-muted); margin-bottom: 2px;">Endpoint Receptor Remoto</label>
                    <div style="color: var(--text-secondary); font-size: 12px; font-family: monospace; overflow-x: auto; white-space: nowrap; max-width: 100%; border-bottom: 1px dashed rgba(255,255,255,0.05); padding-bottom: 4px;"><?php echo htmlspecialchars($cliente['endpoint_publicar']); ?></div>
                </div>

                <div>
                    <label class="form-label" style="font-size: 11px; color: var(--text-muted); margin-bottom: 2px;">Llave Secreta del Sitio Receptor</label>
                    <div style="color: var(--text-muted); font-size: 12px; font-family: monospace;">•••••••••••••••••••••••••••••••• (Establecida)</div>
                </div>

                <div style="background: rgba(236, 72, 153, 0.05); border: 1px dashed rgba(236, 72, 153, 0.2); padding: 12px; border-radius: 8px; margin-top: 10px; font-size: 11.5px; color: var(--text-secondary); line-height: 1.4;">
                    Si necesitas cambiar tu dominio web, el endpoint de tu blog o la frecuencia de generación de posts, por favor contacta al Administrador de la plataforma.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fotoAutorInput = document.getElementById('foto_autor_url');
    const previewFoto = document.getElementById('preview-foto-autor');
    
    const colorPrimario = document.getElementById('color_primario');
    const colorPrimarioPicker = document.getElementById('color_primario_picker');
    const colorTexto = document.getElementById('color_texto');
    const colorTextoPicker = document.getElementById('color_texto_picker');

    // Previsualización de Foto del Autor en vivo
    const updateAvatarPreview = () => {
        const url = fotoAutorInput.value.trim();
        if (url) {
            previewFoto.src = url;
        } else {
            previewFoto.src = "https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=128&h=128&q=80";
        }
    };
    fotoAutorInput.addEventListener('input', updateAvatarPreview);
    updateAvatarPreview();

    // Sincronización de Pickers de Color
    colorPrimarioPicker.addEventListener('input', () => { colorPrimario.value = colorPrimarioPicker.value.toUpperCase(); });
    colorPrimario.addEventListener('input', () => { if(/^#[0-9A-F]{6}$/i.test(colorPrimario.value)) { colorPrimarioPicker.value = colorPrimario.value; } });

    colorTextoPicker.addEventListener('input', () => { colorTexto.value = colorTextoPicker.value.toUpperCase(); });
    colorTexto.addEventListener('input', () => { if(/^#[0-9A-F]{6}$/i.test(colorTexto.value)) { colorTextoPicker.value = colorTexto.value; } });
});
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
