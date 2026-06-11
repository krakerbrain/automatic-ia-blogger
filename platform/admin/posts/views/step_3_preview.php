<!-- VISTA 3: PREVISUALIZACIÓN COMPLETA Y PUBLICACIÓN (PASO 4) -->
<!-- Barra de progreso -->
<div class="step-tracker">
    <div class="step-item completed">
        <div class="step-badge">1</div>
        <span>Texto Generado</span>
    </div>
    <div class="step-line completed"></div>
    <div class="step-item completed">
        <div class="step-badge">2</div>
        <span>Borrador Revisado</span>
    </div>
    <div class="step-line completed"></div>
    <div class="step-item completed">
        <div class="step-badge">3</div>
        <span>Imagen Creada</span>
    </div>
    <div class="step-line completed"></div>
    <div class="step-item active">
        <div class="step-badge">4</div>
        <span>Publicar</span>
    </div>
</div>

<div class="card-glass" style="max-width: 900px; margin: 0 auto;">
    <h2 style="font-size: 18px; color: var(--color-primary); margin-bottom: 20px; border-bottom: 1px solid var(--border-glass); padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
        Previsualización Final (Estilos de <?php echo htmlspecialchars($successPost['cliente_nombre']); ?>)
    </h2>

    <div class="preview-blog-container">
        <!-- Imagen de cabecera -->
        <?php if (!empty($successPost['imagen_url'])): ?>
            <img src="<?php echo htmlspecialchars($successPost['imagen_url']); ?>" alt="Banner de Portada" style="width: 100%; max-height: 400px; object-fit: cover; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <?php endif; ?>

        <!-- Título con estilos del cliente -->
        <h1 style="font-family: <?php echo htmlspecialchars($successPost['cliente_fuente_titulo']); ?>; color: <?php echo htmlspecialchars($successPost['cliente_color_texto']); ?>; font-size: 32px; line-height: 1.25; margin-bottom: 20px; font-weight: 700;">
            <?php echo htmlspecialchars($successPost['titulo']); ?>
        </h1>

        <!-- Cuerpo del texto -->
        <div style="font-family: <?php echo htmlspecialchars($successPost['cliente_fuente_texto']); ?>; color: #4B5563; font-size: 16px; line-height: 1.7; margin-bottom: 30px; text-align: justify; white-space: pre-line;">
            <?php echo htmlspecialchars($successPost['texto']); ?>
        </div>

        <!-- Autor -->
        <div style="display: flex; align-items: center; gap: 12px; border-top: 1px solid #F3F4F6; padding-top: 20px;">
            <img src="<?php echo htmlspecialchars($successPost['cliente_foto_autor']); ?>" alt="Autor" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid <?php echo htmlspecialchars($successPost['cliente_color_primario']); ?>;">
            <div>
                <div style="font-weight: 700; color: #1F2937; font-size: 14px; font-family: <?php echo htmlspecialchars($successPost['cliente_fuente_texto']); ?>;">
                    <?php echo htmlspecialchars($successPost['cliente_nombre_autor']); ?>
                </div>
                <div style="font-size: 12px; color: #9CA3AF;">Autor del post</div>
            </div>
        </div>
    </div>

    <!-- Botones de Acción -->
    <div style="display: flex; gap: 15px; justify-content: space-between; margin-top: 30px; flex-wrap: wrap;">
        <div style="display: flex; gap: 10px;">
            <a href="generar.php" class="btn-custom btn-secondary" style="padding: 12px 20px;">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.006 11H19"/></svg>
                Generar Otro Post
            </a>
            
            <a href="generar.php?draft_id=<?php echo $successPost['id']; ?>" class="btn-custom btn-secondary" style="padding: 12px 20px; border-color: rgba(139, 92, 246, 0.4); color: #A78BFA;">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Editar Texto / Imagen
            </a>
        </div>
        
        <form action="generar.php" method="POST">
            <input type="hidden" name="action" value="publicar">
            <input type="hidden" name="post_id" value="<?php echo $successPost['id']; ?>">
            <button type="submit" class="btn-custom btn-primary" style="padding: 12px 28px; background: linear-gradient(135deg, <?php echo htmlspecialchars($successPost['cliente_color_primario']); ?>, var(--color-primary-hover)); color: white; border: none; font-size: 15.5px; font-weight: bold;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                Aprobar y Publicar Post
            </button>
        </form>
    </div>
</div>
