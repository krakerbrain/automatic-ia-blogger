<!-- VISTA 2: EDICIÓN DEL BORRADOR Y DISEÑO DE PORTADA (PASO 2 y 3) -->
<!-- Barra de progreso -->
<div class="step-tracker">
    <div class="step-item completed">
        <div class="step-badge">1</div>
        <span>Texto Generado</span>
    </div>
    <div class="step-line completed"></div>
    <div class="step-item active">
        <div class="step-badge">2</div>
        <span>Revisar y Editar</span>
    </div>
    <div class="step-line"></div>
    <div class="step-item">
        <div class="step-badge">3</div>
        <span>Diseñar Portada</span>
    </div>
    <div class="step-line"></div>
    <div class="step-item">
        <div class="step-badge">4</div>
        <span>Publicar</span>
    </div>
</div>

<div style="max-width: 900px; margin: 0 auto;">
    
    <div class="alert alert-warning" style="margin-bottom: 25px; background: rgba(245, 158, 11, 0.05); border-color: rgba(245, 158, 11, 0.25);">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <div>
            <strong>Modo Borrador Activo:</strong> El texto ha sido redactado. Puedes editar el título y el cuerpo antes de generar el diseño de portada.
        </div>
    </div>

    <form action="generar.php" method="POST" id="draft-form" enctype="multipart/form-data">
        <input type="hidden" name="post_id" value="<?php echo $draftPost['id']; ?>">
        
        <div style="display: grid; grid-template-columns: 1fr; gap: 25px;">
            <!-- Panel de Edición -->
            <div class="card-glass">
                <h2 style="font-size: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--border-glass); padding-bottom: 10px; color: var(--color-primary);">Edición del Contenido</h2>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" for="titulo">Título del Post</label>
                    <input type="text" id="titulo" name="titulo" class="form-control" style="font-size: 16px; padding: 12px; font-weight: bold; color: white;" value="<?php echo htmlspecialchars($draftPost['titulo']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="texto">Texto de la Entrada (Markdown o Texto Plano)</label>
                    <textarea id="texto" name="texto" class="form-control" rows="12" style="font-size: 14.5px; line-height: 1.6; padding: 15px;" required><?php echo htmlspecialchars($draftPost['texto']); ?></textarea>
                </div>
            </div>
            
            <!-- Panel de Selección de Imagen de Portada -->
            <div class="image-options-grid" style="display: grid; grid-template-columns: 1fr; gap: 20px;">
                <!-- Columna 1: Diseñar con IA -->
                <div class="card-glass" style="border-color: rgba(99, 102, 241, 0.25); background: linear-gradient(135deg, rgba(99, 102, 241, 0.02), rgba(99, 102, 241, 0.05)); display: flex; flex-direction: column; justify-content: space-between; padding: 25px;">
                    <div>
                        <h3 style="font-size: 16px; margin: 0 0 10px 0; color: #818CF8; display: flex; align-items: center; gap: 8px;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Opción A: Diseñar Portada con IA
                        </h3>
                        <p style="color: var(--text-secondary); font-size: 13px; line-height: 1.5; margin: 0 0 15px 0;">
                            Generar el banner personalizado llamará a <strong>Gemini Imagen 3/4</strong> para diseñar una pieza gráfica horizontal y fotográfica basada en el tema del artículo.
                        </p>
                        
                        <div style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.2); padding: 12px; border-radius: 8px; font-size: 12.5px; color: #FBBF24; display: flex; gap: 8px; margin-bottom: 20px;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="flex-shrink: 0;"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span><strong>Atención:</strong> Al diseñar la portada con IA se consumirá el recurso de generación y no se podrá volver a generar automáticamente. Asegúrate de estar conforme con la redacción.</span>
                        </div>
                    </div>

                    <button type="submit" name="action" value="diseñar_imagen" id="btn-submit-image" class="btn-custom btn-primary <?php echo (isset($_SESSION['is_demo']) && $_SESSION['is_demo'] === true) ? 'guide-highlight' : ''; ?>" style="width: 100%; padding: 12px; justify-content: center; background: linear-gradient(135deg, var(--color-primary), #6366F1); font-weight: 600;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Diseñar con IA y Finalizar
                    </button>
                </div>

                <!-- Columna 2: Cargar manualmente -->
                <div class="card-glass" style="border-color: rgba(16, 185, 129, 0.25); background: linear-gradient(135deg, rgba(16, 185, 129, 0.02), rgba(16, 185, 129, 0.05)); display: flex; flex-direction: column; justify-content: space-between; padding: 25px;">
                    <div>
                        <h3 style="font-size: 16px; margin: 0 0 10px 0; color: #34D399; display: flex; align-items: center; gap: 8px;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Opción B: Subir Imagen Personalizada
                        </h3>
                        <p style="color: var(--text-secondary); font-size: 13px; line-height: 1.5; margin: 0 0 15px 0;">
                            Sube un archivo de imagen desde tu dispositivo o ingresa una dirección URL web externa para usar como portada del artículo.
                        </p>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label class="form-label" for="custom_image_file" style="font-size: 12px; margin-bottom: 4px;">Archivo de Imagen (PNG, JPG, WEBP)</label>
                            <input type="file" id="custom_image_file" name="custom_image_file" class="form-control" style="font-size: 13px; padding: 8px 12px; background: rgba(0,0,0,0.15);" accept="image/*">
                        </div>

                        <div style="text-align: center; color: var(--text-muted); font-size: 11px; margin: 5px 0;">— o ingresa una URL —</div>

                        <div class="form-group" style="margin-bottom: 20px;">
                            <label class="form-label" for="custom_image_url" style="font-size: 12px; margin-bottom: 4px;">Enlace URL de Imagen</label>
                            <input type="url" id="custom_image_url" name="custom_image_url" class="form-control" placeholder="https://ejemplo.com/portada.jpg" style="font-size: 13.2px; padding: 10px; background: rgba(0,0,0,0.15);">
                        </div>
                    </div>

                    <button type="submit" name="action" value="subir_personalizada" class="btn-custom" style="width: 100%; padding: 12px; justify-content: center; background: linear-gradient(135deg, #10B981, #059669); color: white; border: none; font-weight: 600;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                        Usar Imagen Manual y Finalizar
                    </button>
                </div>
            </div>

            <!-- Botón de solo guardar texto -->
            <div style="text-align: center; margin-top: 10px;">
                <button type="submit" name="action" value="guardar_borrador" class="btn-custom btn-secondary" style="padding: 10px 20px; font-size: 13px; display: inline-flex; gap: 8px; border-color: rgba(255,255,255,0.08);">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    Solo Guardar Cambios en el Texto
                </button>
            </div>
        </div>
    </form>
</div>
