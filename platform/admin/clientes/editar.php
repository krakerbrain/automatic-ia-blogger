<?php
/**
 * CRUD Clientes - Editar Cliente
 */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/DB.php';

$db = DB::getInstance();

// Validar ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_error'] = "ID de cliente no proporcionado.";
    header("Location: lista.php");
    exit();
}

$id = intval($_GET['id']);
$stmt = $db->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    $_SESSION['flash_error'] = "Cliente no encontrado.";
    header("Location: lista.php");
    exit();
}

// Procesar Formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $slug = trim($_POST['slug']);
    $rubro = trim($_POST['rubro']);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $temas_relacionar = trim($_POST['temas_relacionar'] ?? '');
    $tono_marca = trim($_POST['tono_marca']);
    $dominio = trim($_POST['dominio']);
    $endpoint_publicar = trim($_POST['endpoint_publicar']);
    $api_key_sitio = trim($_POST['api_key_sitio']);
    $email_revisor = trim($_POST['email_revisor']);
    $nombre_autor = trim($_POST['nombre_autor']);
    $foto_autor_url = trim($_POST['foto_autor_url']);
    $color_primario = trim($_POST['color_primario']);
    $color_texto = trim($_POST['color_texto']);
    $fuente_titulo = trim($_POST['fuente_titulo']);
    $fuente_texto = trim($_POST['fuente_texto']);
    $logo_url = trim($_POST['logo_url']);
    $frecuencia_dias = isset($_POST['frecuencia_dias']) ? intval($_POST['frecuencia_dias']) : 7;
    if ($frecuencia_dias <= 0) $frecuencia_dias = 7;
    $limite_mensual_usd = isset($_POST['limite_mensual_usd']) ? floatval($_POST['limite_mensual_usd']) : 5.0000;
    if ($limite_mensual_usd < 0) $limite_mensual_usd = 5.0000;
    $activo = isset($_POST['activo']) ? 1 : 0;

    // Validaciones básicas
    if (empty($nombre) || empty($slug) || empty($rubro) || empty($dominio) || empty($endpoint_publicar) || empty($api_key_sitio) || empty($email_revisor) || empty($nombre_autor) || empty($foto_autor_url)) {
        $error = "Todos los campos obligatorios (*) deben ser completados.";
    } else {
        try {
            // Verificar si el slug ya existe en OTRO cliente
            $check = $db->prepare("SELECT id FROM clientes WHERE slug = ? AND id != ?");
            $check->execute([$slug, $id]);
            if ($check->fetch()) {
                $error = "El slug '{$slug}' ya está en uso por otro cliente. Debe ser único.";
            } else {
                $update = $db->prepare("UPDATE clientes SET 
                     nombre = ?, slug = ?, rubro = ?, descripcion = ?, temas_relacionar = ?, tono_marca = ?, dominio = ?, 
                     endpoint_publicar = ?, api_key_sitio = ?, email_revisor = ?, 
                     nombre_autor = ?, foto_autor_url = ?, color_primario = ?, 
                     color_texto = ?, fuente_titulo = ?, fuente_texto = ?, 
                     logo_url = ?, activo = ?, frecuencia_dias = ?, limite_mensual_usd = ? 
                     WHERE id = ?");
                
                $update->execute([
                    $nombre, $slug, $rubro, $descripcion, $temas_relacionar, $tono_marca, $dominio,
                    $endpoint_publicar, $api_key_sitio, $email_revisor,
                    $nombre_autor, $foto_autor_url, $color_primario,
                    $color_texto, $fuente_titulo, $fuente_texto,
                    $logo_url, $activo, $frecuencia_dias, $limite_mensual_usd, $id
                ]);

                $_SESSION['flash_success'] = "Cliente '{$nombre}' actualizado correctamente.";
                header("Location: lista.php");
                exit();
            }
        } catch (Exception $e) {
            $error = "Error al actualizar el cliente: " . $e->getMessage();
        }
    }
    
    // Si hay error, recargar los datos enviados en el POST para no perderlos
    $cliente = $_POST;
    $cliente['id'] = $id; // Mantener ID
}

include __DIR__ . '/../layout_header.php';
?>

<div style="margin-bottom: 25px;">
    <a href="lista.php" style="color: var(--text-secondary); text-decoration: none; font-size: 14px; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 10px;">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Volver a la lista
    </a>
    <h1 style="font-size: 28px;">Editar Cliente: <?php echo htmlspecialchars($cliente['nombre']); ?></h1>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-error">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<form action="editar.php?id=<?php echo $id; ?>" method="POST">
    <div style="display: grid; grid-template-columns: 1fr; gap: 30px; align-items: start;">
        
        <div class="card-glass">
            <h2 style="font-size: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--border-glass); padding-bottom: 10px; color: var(--color-primary);">1. Información General del Negocio</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="nombre">Nombre del Negocio *</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ej: Adri Hair Style" required value="<?php echo htmlspecialchars($cliente['nombre']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="slug">Slug Único *</label>
                    <input type="text" id="slug" name="slug" class="form-control" placeholder="Ej: adri-hair-style" required value="<?php echo htmlspecialchars($cliente['slug']); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="rubro">Rubro *</label>
                    <input type="text" id="rubro" name="rubro" class="form-control" placeholder="Ej: peluquería femenina" required value="<?php echo htmlspecialchars($cliente['rubro']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="tono_marca">Tono de la Marca *</label>
                    <input type="text" id="tono_marca" name="tono_marca" class="form-control" placeholder="Ej: cercano, moderno, femenino, inspirador" required value="<?php echo htmlspecialchars($cliente['tono_marca']); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="dominio">Dominio de la Web *</label>
                    <input type="url" id="dominio" name="dominio" class="form-control" placeholder="Ej: https://adrihairstyle.cl" required value="<?php echo htmlspecialchars($cliente['dominio']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="logo_url">URL del Logo (Opcional)</label>
                    <input type="url" id="logo_url" name="logo_url" class="form-control" placeholder="Ej: https://adrihairstyle.cl/assets/logo.png" value="<?php echo htmlspecialchars($cliente['logo_url'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group" style="margin-top: 15px;">
                <label class="form-label" for="descripcion">Descripción del Negocio (Opcional)</label>
                <textarea id="descripcion" name="descripcion" class="form-control" rows="3" placeholder="Describe detalladamente a qué se dedica el negocio, especialidades y fortalezas..."><?php echo htmlspecialchars($cliente['descripcion'] ?? ''); ?></textarea>
            </div>

            <div class="form-group" style="margin-top: 15px;">
                <label class="form-label" for="temas_relacionar">Temas de Interés a Relacionar (Opcional)</label>
                <textarea id="temas_relacionar" name="temas_relacionar" class="form-control" rows="2" placeholder="Ej: autoestima de la mujer, películas/series empoderadoras, estilo de vida..."><?php echo htmlspecialchars($cliente['temas_relacionar'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="card-glass">
            <h2 style="font-size: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--border-glass); padding-bottom: 10px; color: var(--color-primary);">2. Integración y Revisión</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="endpoint_publicar">Endpoint de Publicación *</label>
                    <input type="url" id="endpoint_publicar" name="endpoint_publicar" class="form-control" placeholder="Ej: https://adrihairstyle.cl/blog/publicar.php" required value="<?php echo htmlspecialchars($cliente['endpoint_publicar']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="api_key_sitio">API Key Secreta del Sitio Receptor *</label>
                    <input type="text" id="api_key_sitio" name="api_key_sitio" class="form-control" required value="<?php echo htmlspecialchars($cliente['api_key_sitio']); ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="email_revisor">Email del Revisor *</label>
                <input type="email" id="email_revisor" name="email_revisor" class="form-control" placeholder="Ej: adri@adrihairstyle.cl" required value="<?php echo htmlspecialchars($cliente['email_revisor']); ?>">
            </div>

            <div class="form-row" style="margin-top: 15px;">
                <div class="form-group">
                    <label class="form-label" for="frecuencia_dias">Frecuencia de Sugerencias (Días) *</label>
                    <input type="number" id="frecuencia_dias" name="frecuencia_dias" class="form-control" min="1" max="365" required value="<?php echo htmlspecialchars($cliente['frecuencia_dias'] ?? '7'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="limite_mensual_usd">Límite de Gasto Mensual (USD) *</label>
                    <input type="number" step="0.0001" id="limite_mensual_usd" name="limite_mensual_usd" class="form-control" min="0" required value="<?php echo htmlspecialchars($cliente['limite_mensual_usd'] ?? '5.0000'); ?>">
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr; gap: 30px;" class="responsive-subgrid">
            <div class="card-glass">
                <h2 style="font-size: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--border-glass); padding-bottom: 10px; color: var(--color-primary);">3. Datos del Autor del Blog</h2>
                
                <div class="form-group">
                    <label class="form-label" for="nombre_autor">Nombre del Autor *</label>
                    <input type="text" id="nombre_autor" name="nombre_autor" class="form-control" placeholder="Ej: Adri Montenegro" required value="<?php echo htmlspecialchars($cliente['nombre_autor']); ?>">
                </div>

                <div style="display: flex; gap: 20px; align-items: center;">
                    <div style="flex-grow: 1;">
                        <label class="form-label" for="foto_autor_url">URL Foto del Autor (Absoluta) *</label>
                        <input type="url" id="foto_autor_url" name="foto_autor_url" class="form-control" placeholder="https://ejemplo.com/foto.jpg" required value="<?php echo htmlspecialchars($cliente['foto_autor_url']); ?>">
                    </div>
                    
                    <div style="text-align: center; flex-shrink: 0;">
                        <span class="form-label" style="margin-bottom: 8px;">Vista Previa</span>
                        <div style="width: 70px; height: 70px; border-radius: 50%; overflow: hidden; border: 2px solid var(--color-primary); background: #1E293B; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.3);">
                            <img id="preview-foto-autor" src="" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
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
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="color_texto">Color de Texto *</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="color" id="color_texto_picker" class="form-control" style="width: 50px; padding: 2px; height: 40px; cursor: pointer;" value="<?php echo htmlspecialchars($cliente['color_texto']); ?>">
                            <input type="text" id="color_texto" name="color_texto" class="form-control" placeholder="#2C2C2A" maxlength="7" required value="<?php echo htmlspecialchars($cliente['color_texto']); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-row">
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

                <div class="form-group" style="margin-top: 10px;">
                    <label style="display: inline-flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="activo" value="1" <?php echo $cliente['activo'] ? 'checked' : ''; ?> style="width: 18px; height: 18px; accent-color: var(--color-primary);">
                        <span style="font-size: 14px; font-weight: 600; color: var(--text-primary);">Cliente Activo</span>
                    </label>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 10px;">
            <a href="lista.php" class="btn-custom btn-secondary">Cancelar</a>
            <button type="submit" class="btn-custom btn-primary">Guardar Cambios</button>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nombreInput = document.getElementById('nombre');
    const slugInput = document.getElementById('slug');
    const fotoAutorInput = document.getElementById('foto_autor_url');
    const previewFoto = document.getElementById('preview-foto-autor');
    
    const colorPrimario = document.getElementById('color_primario');
    const colorPrimarioPicker = document.getElementById('color_primario_picker');
    const colorTexto = document.getElementById('color_texto');
    const colorTextoPicker = document.getElementById('color_texto_picker');

    // 1. Slugify (solo si es editado)
    nombreInput.addEventListener('input', function() {
        if (slugInput.dataset.edited !== 'true') {
            slugInput.value = nombreInput.value
                .toLowerCase()
                .trim()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/[\s_]+/g, '-')
                .replace(/-+/g, '-');
        }
    });

    slugInput.addEventListener('change', function() {
        slugInput.dataset.edited = 'true';
    });

    // 2. Previsualización de Foto del Autor en vivo
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

    // 3. Sincronización de Pickers de Color
    colorPrimarioPicker.addEventListener('input', () => { colorPrimario.value = colorPrimarioPicker.value.toUpperCase(); });
    colorPrimario.addEventListener('input', () => { if(/^#[0-9A-F]{6}$/i.test(colorPrimario.value)) { colorPrimarioPicker.value = colorPrimario.value; } });

    colorTextoPicker.addEventListener('input', () => { colorTexto.value = colorTextoPicker.value.toUpperCase(); });
    colorTexto.addEventListener('input', () => { if(/^#[0-9A-F]{6}$/i.test(colorTexto.value)) { colorTextoPicker.value = colorTexto.value; } });
});
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
