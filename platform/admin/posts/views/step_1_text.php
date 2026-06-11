<!-- VISTA 1: GENERACIÓN DE TEXTO (PASO 1) -->
<!-- Barra de progreso -->
<div class="step-tracker">
    <div class="step-item active">
        <div class="step-badge">1</div>
        <span>Generar Texto</span>
    </div>
    <div class="step-line"></div>
    <div class="step-item">
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

<div class="card-glass" style="max-width: 800px; margin: 0 auto;">
    <form action="generar.php" method="POST" id="generate-form-text">
        
        <?php if ($selected_sugerencia): ?>
            <input type="hidden" name="sugerencia_id" value="<?php echo $selected_sugerencia['id']; ?>">
        <?php endif; ?>

        <!-- Paso 1: Seleccionar Cliente -->
        <div class="form-group" style="margin-bottom: 25px;">
            <?php
            $isCliente = isset($_SESSION['role']) && $_SESSION['role'] === 'cliente';
            $selectorDisabled = $selected_sugerencia || $isCliente;
            ?>
            <label class="form-label" for="cliente_id"><?php echo $isCliente ? 'Tu Cuenta' : 'Paso 1: Selecciona el Cliente *'; ?></label>
            <select id="cliente_id" name="cliente_id" class="form-control" required style="font-size: 15px; padding: 12px;" <?php echo $selectorDisabled ? 'disabled' : ''; ?>>
                <option value="" disabled <?php echo !$selected_cliente_id ? 'selected' : ''; ?>>-- Elige un cliente activo --</option>
                <?php foreach ($clientes as $c): 
                    $isExceeded = GeminiClient::isMonthlyLimitExceeded($c['id']);
                    $spend = GeminiClient::getMonthlySpend($c['id']);
                ?>
                    <option value="<?php echo $c['id']; ?>" 
                            <?php echo ($selected_cliente_id === intval($c['id'])) ? 'selected' : ''; ?>
                            data-logo="<?php echo htmlspecialchars($c['logo_url'] ?? ''); ?>" 
                            data-nombre="<?php echo htmlspecialchars($c['nombre']); ?>"
                            data-rubro="<?php echo htmlspecialchars($c['rubro']); ?>"
                            data-exceeded="<?php echo $isExceeded ? '1' : '0'; ?>"
                            data-spend="<?php echo number_format($spend, 4); ?>"
                            data-limit="<?php echo number_format($c['limite_mensual_usd'], 2); ?>"
                            data-descripcion="<?php echo htmlspecialchars($c['descripcion'] ?? ''); ?>"
                            data-temas="<?php echo htmlspecialchars($c['temas_relacionar'] ?? ''); ?>">
                        <?php echo htmlspecialchars($c['nombre']); ?> (<?php echo htmlspecialchars($c['rubro']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($selectorDisabled): ?>
                <!-- Campo oculto para mandar el cliente_id ya que el select está deshabilitado -->
                <input type="hidden" name="cliente_id" value="<?php echo $selected_cliente_id; ?>">
            <?php endif; ?>
        </div>

        <!-- Mini Card con detalles (presupuesto solo visible para admin) -->
        <div id="cliente-mini-card" style="display: none; margin-bottom: 30px;">
            <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-glass); border-radius: 12px; padding: 20px; display: flex; flex-direction: column; gap: 15px;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div id="card-logo-fallback" style="width: 40px; height: 40px; border-radius: 8px; background: linear-gradient(135deg, var(--color-primary), var(--color-accent)); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; color: white;">CL</div>
                        <img id="card-logo" src="" alt="Logo" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover; display: none;">
                        <div>
                            <h4 id="card-nombre" style="font-size: 16px; color: #ffffff; margin: 0;"></h4>
                            <span id="card-rubro" style="font-size: 11px; color: var(--text-secondary);"></span>
                        </div>
                    </div>
                    <?php if (!$isCliente): ?>
                    <div id="card-presupuesto-info" style="text-align: right;">
                        <div style="font-size: 11px; color: var(--text-secondary);">Presupuesto IA Mensual</div>
                        <div style="font-size: 14px; font-weight: bold; color: white;">
                            <span id="card-spend" style="color: #34D399;">$0.00</span> / <span id="card-limit">$5.00</span> <span style="font-size: 10px; color: var(--text-muted);">USD</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Alerta de Límite Mensual Excedido (visible para ambos cuando bloquea) -->
                <div id="alert-presupuesto-excedido" class="alert alert-error" style="margin: 0; padding: 10px 15px; display: none;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span style="font-size: 12.5px;">Límite mensual excedido. La generación está temporalmente deshabilitada.</span>
                </div>

                <div id="card-desc-box" style="border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 12px; display: none;">
                    <div style="font-size: 11px; color: var(--text-secondary); font-weight: 600;">Descripción del negocio:</div>
                    <p id="card-descripcion" style="font-size: 12.5px; color: var(--text-muted); line-height: 1.4; margin: 2px 0; font-style: italic;"></p>
                </div>
            </div>
        </div>

        <!-- Paso 2: Tema y Sugerencias -->
        <div class="form-group" style="margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <label class="form-label" for="tema" style="margin-bottom: 0;">Paso 2: Tema del Post *</label>
                <button type="button" id="btn-sugerir-temas" class="btn-custom btn-secondary btn-sm" style="display: none; padding: 6px 12px; border-color: rgba(139, 92, 246, 0.4); color: #C084FC; font-size: 12.5px; align-items: center; gap: 5px;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9.663 17h4.673M12 3v1m6.364.364l-.707.707M21 12h-1m-.364 6.364l-.707-.707M12 21v-1m-7.657-.364l.707-.707M3 12h1m.364-6.364l.707.707M12 7a5 5 0 100 10 5 5 0 000-10z"/></svg>
                    💡 Sugerir 5 Temas Más
                </button>
            </div>
            <input type="text" id="tema" name="tema" class="form-control" placeholder="Selecciona un tema de la lista de abajo" required readonly style="font-size: 15px; padding: 12px; background: rgba(0,0,0,0.15);" value="<?php echo htmlspecialchars($selected_tema); ?>">
            
            <!-- Lista de Sugerencias Cargadas desde BD -->
            <div id="sugerencias-container" style="display: none; margin-top: 15px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-glass); border-radius: 8px; padding: 15px;">
                <div id="toggle-sugerencias-header" style="font-size: 13px; color: var(--text-secondary); font-weight: 600; display: flex; align-items: center; justify-content: space-between; cursor: pointer; user-select: none;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color: var(--color-primary);"><path d="M9.663 17h4.673M12 3v1m6.364.364l-.707.707M21 12h-1m-.364 6.364l-.707-.707M12 21v-1m-7.657-.364l.707-.707M3 12h1m.364-6.364l.707.707M12 7a5 5 0 100 10 5 5 0 000-10z"/></svg>
                        <span>Sugerencias Disponibles</span>
                    </div>
                    <div id="sugerencias-toggle-icon" style="font-size: 11px; font-weight: 600; background: rgba(255,255,255,0.05); padding: 2px 8px; border-radius: 4px; border: 1px solid var(--border-glass); transition: all 0.2s ease;">MOSTRAR ▼</div>
                </div>
                <div id="sugerencias-lista-wrapper" style="margin-top: 12px; transition: all 0.3s ease; max-height: 500px; overflow-y: auto; display: none;">
                    <div id="sugerencias-lista" style="display: flex; flex-direction: column; gap: 8px;">
                        <!-- JS cargará las sugerencias de la BD -->
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" id="btn-submit-text" class="btn-custom btn-primary" style="padding: 14px; font-size: 16px; width: 100%; justify-content: center;">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Generar Redacción del Post
        </button>
    </form>
</div>
