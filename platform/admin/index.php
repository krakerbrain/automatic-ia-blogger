<?php
/**
 * Dashboard Central - Panel de Control
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/DB.php';

$db = DB::getInstance();

try {
    // 1. Obtener métricas globales
    
    // A. Clientes activos
    $totalClientes = $db->query("SELECT COUNT(*) FROM clientes WHERE activo = 1")->fetchColumn();
    
    // B. Posts generados este mes
    $inicioMes = date('Y-m-01 00:00:00');
    $stmtMes = $db->prepare("SELECT COUNT(*) FROM posts WHERE fecha_creacion >= ?");
    $stmtMes->execute([$inicioMes]);
    $postsEsteMes = $stmtMes->fetchColumn();
    
    // C. Posts pendientes de revisión
    $postsPendientes = $db->query("SELECT COUNT(*) FROM posts WHERE estado = 'pendiente'")->fetchColumn();
    
    // D. Posts publicados exitosamente
    $postsExitosos = $db->query("SELECT COUNT(*) FROM posts WHERE estado = 'aprobado' AND publicacion_exitosa = 1")->fetchColumn();
    
    // 2. Obtener actividad reciente (últimos 10 posts)
    $stmtRecientes = $db->query("
        SELECT p.id, p.titulo, p.tema, p.estado, p.fecha_creacion, p.token_revision, p.publicacion_exitosa,
               c.nombre as cliente_nombre, c.logo_url as cliente_logo
        FROM posts p
        JOIN clientes c ON p.cliente_id = c.id
        ORDER BY p.fecha_creacion DESC
        LIMIT 10
    ");
    $postsRecientes = $stmtRecientes->fetchAll();

    // 3. Obtener métricas de consumo de la API de Gemini
    $costoTotal = $db->query("SELECT SUM(costo_estimado) FROM consumo_tokens")->fetchColumn() ?? 0.0;
    $tokensTotales = $db->query("SELECT SUM(total_tokens) FROM consumo_tokens")->fetchColumn() ?? 0;
    $tokensPrompt = $db->query("SELECT SUM(prompt_tokens) FROM consumo_tokens")->fetchColumn() ?? 0;
    $tokensCompletion = $db->query("SELECT SUM(completion_tokens) FROM consumo_tokens")->fetchColumn() ?? 0;
    $totalImagenes = $db->query("SELECT COUNT(*) FROM consumo_tokens WHERE accion = 'generar_imagen'")->fetchColumn() ?? 0;

    $stmtConsumoClientes = $db->query("
        SELECT c.nombre, c.logo_url, c.limite_mensual_usd,
               COALESCE(SUM(ct.total_tokens), 0) as total_tokens, 
               COALESCE(SUM(ct.costo_estimado), 0.0) as costo_total,
               SUM(CASE WHEN ct.accion = 'generar_imagen' THEN 1 ELSE 0 END) as total_imagenes,
               COALESCE(SUM(CASE WHEN ct.fecha_registro >= DATE_FORMAT(NOW(), '%Y-%m-01 00:00:00') THEN ct.costo_estimado ELSE 0.0 END), 0.0) as costo_mes
        FROM clientes c
        LEFT JOIN consumo_tokens ct ON c.id = ct.cliente_id
        GROUP BY c.id
        ORDER BY costo_total DESC
    ");
    $consumoClientes = $stmtConsumoClientes->fetchAll();

    $stmtConsumoAcciones = $db->query("
        SELECT accion, 
               COALESCE(SUM(total_tokens), 0) as total_tokens, 
               COALESCE(SUM(costo_estimado), 0.0) as costo_total,
               COUNT(*) as total_llamadas
        FROM consumo_tokens
        GROUP BY accion
        ORDER BY costo_total DESC
    ");
    $consumoAcciones = $stmtConsumoAcciones->fetchAll();

} catch (Exception $e) {
    error_log("Error en Dashboard: " . $e->getMessage());
    $errorMsg = "No se pudieron cargar las métricas. Asegúrate de importar el esquema SQL.";
    $totalClientes = 0;
    $postsEsteMes = 0;
    $postsPendientes = 0;
    $postsExitosos = 0;
    $postsRecientes = [];
    $costoTotal = 0.0;
    $tokensTotales = 0;
    $tokensPrompt = 0;
    $tokensCompletion = 0;
    $totalImagenes = 0;
    $consumoClientes = [];
    $consumoAcciones = [];
}

include __DIR__ . '/layout_header.php';
?>

<!-- Estilos para las tarjetas de métricas -->
<style>
    .metrics-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
        margin-bottom: 35px;
    }
    
    @media (min-width: 576px) {
        .metrics-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (min-width: 992px) {
        .metrics-grid { grid-template-columns: repeat(4, 1fr); }
    }

    .metric-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-glass);
        border-radius: 12px;
        padding: 16px 20px;
        box-shadow: var(--shadow-glass);
        display: flex;
        align-items: center;
        gap: 16px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4);
        border-color: rgba(255,255,255,0.1);
    }

    .metric-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .metric-value {
        font-family: var(--font-display);
        font-size: 24px;
        font-weight: 800;
        line-height: 1.1;
        margin-top: 2px;
        background: linear-gradient(to right, #ffffff, #E2E8F0);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .metric-label {
        font-size: 13px;
        color: var(--text-secondary);
        font-weight: 500;
    }
</style>

<div style="margin-bottom: 30px;">
    <h1 style="font-size: 28px; margin-bottom: 5px;">Dashboard Resumen</h1>
    <p style="color: var(--text-secondary); font-size: 14px;">Visión global del rendimiento y estado de tus publicaciones automatizadas.</p>
</div>

<?php if (isset($errorMsg)): ?>
    <div class="alert alert-error">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
            <strong>Error del Sistema:</strong> <?php echo htmlspecialchars($errorMsg); ?><br>
            <span style="font-size: 12px; opacity: 0.8;">Crea e importa el archivo <a href="/schema.sql" style="color: white; text-decoration: underline;">schema.sql</a> en tu PHPMyAdmin.</span>
        </div>
    </div>
<?php endif; ?>

<!-- Cuadrícula de Métricas -->
<div class="metrics-grid">
    <!-- Métricas 1: Clientes Activos -->
    <div class="metric-card">
        <div class="metric-icon" style="background: rgba(139, 92, 246, 0.15); color: var(--color-primary); border: 1px solid rgba(139, 92, 246, 0.25);">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 20h5-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        </div>
        <div>
            <div class="metric-label">Clientes Activos</div>
            <div class="metric-value"><?php echo $totalClientes; ?></div>
        </div>
    </div>

    <!-- Métricas 2: Posts Generados Este Mes -->
    <div class="metric-card">
        <div class="metric-icon" style="background: rgba(236, 72, 153, 0.15); color: var(--color-accent); border: 1px solid rgba(236, 72, 153, 0.25);">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        </div>
        <div>
            <div class="metric-label">Generados este Mes</div>
            <div class="metric-value"><?php echo $postsEsteMes; ?></div>
        </div>
    </div>

    <!-- Métricas 3: Posts Pendientes de Revisión -->
    <div class="metric-card">
        <div class="metric-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--color-warning); border: 1px solid rgba(245, 158, 11, 0.25);">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <div>
            <div class="metric-label">Pendientes de Revisión</div>
            <div class="metric-value"><?php echo $postsPendientes; ?></div>
        </div>
    </div>

    <!-- Métricas 4: Publicaciones Exitosas -->
    <div class="metric-card">
        <div class="metric-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--color-success); border: 1px solid rgba(16, 185, 129, 0.25);">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <div class="metric-label">Publicados con Éxito</div>
            <div class="metric-value"><?php echo $postsExitosos; ?></div>
        </div>
    </div>
</div>

<!-- Resumen Global de Consumo IA (solo admin) -->
<div class="metrics-grid" style="grid-template-columns: repeat(1, 1fr); margin-bottom: 35px;">
    <div style="grid-column: 1 / -1; margin-bottom: 5px;">
        <h2 style="font-size: 17px; font-family: var(--font-display); display: flex; align-items: center; gap: 8px; margin-bottom: 0;">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color: var(--color-primary);"><path d="M9.663 17h4.673M12 3v1m6.364.364l-.707.707M21 12h-1m-.364 6.364l-.707-.707M12 21v-1m-7.657-.364l.707-.707M3 12h1m.364-6.364l.707.707M12 7a5 5 0 100 10 5 5 0 000-10z"/></svg>
            Consumo Acumulado de IA
        </h2>
    </div>
</div>
<div class="metrics-grid" style="margin-bottom: 40px;">
    <!-- Gasto total USD -->
    <div class="metric-card" style="border-color: rgba(52, 211, 153, 0.25);">
        <div class="metric-icon" style="background: rgba(52, 211, 153, 0.15); color: #34D399; border: 1px solid rgba(52, 211, 153, 0.25);">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <div class="metric-label">Gasto Total Acumulado</div>
            <div class="metric-value" style="font-size: 20px; color: #34D399; -webkit-text-fill-color: #34D399;">$<?php echo number_format((float)$costoTotal, 4); ?> <span style="font-size: 12px; font-weight: 500; opacity: 0.7;">USD</span></div>
        </div>
    </div>

    <!-- Tokens totales -->
    <div class="metric-card">
        <div class="metric-icon" style="background: rgba(139, 92, 246, 0.15); color: var(--color-primary); border: 1px solid rgba(139, 92, 246, 0.25);">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <div>
            <div class="metric-label">Tokens Totales Consumidos</div>
            <div class="metric-value" style="font-size: 20px;"><?php echo number_format((int)$tokensTotales); ?></div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Entrada: <?php echo number_format((int)$tokensPrompt); ?> · Salida: <?php echo number_format((int)$tokensCompletion); ?></div>
        </div>
    </div>

    <!-- Imágenes generadas -->
    <div class="metric-card">
        <div class="metric-icon" style="background: rgba(236, 72, 153, 0.15); color: var(--color-accent); border: 1px solid rgba(236, 72, 153, 0.25);">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <div>
            <div class="metric-label">Imágenes IA Generadas</div>
            <div class="metric-value" style="font-size: 20px;"><?php echo number_format((int)$totalImagenes); ?></div>
        </div>
    </div>

    <!-- Gasto este mes -->
    <?php
    $gastoMes = 0.0;
    foreach ($consumoClientes as $cc) { $gastoMes += $cc['costo_mes']; }
    ?>
    <div class="metric-card" style="border-color: rgba(245, 158, 11, 0.25);">
        <div class="metric-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--color-warning); border: 1px solid rgba(245, 158, 11, 0.25);">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <div>
            <div class="metric-label">Gasto Este Mes</div>
            <div class="metric-value" style="font-size: 20px; color: #FCD34D; -webkit-text-fill-color: #FCD34D;">$<?php echo number_format($gastoMes, 4); ?> <span style="font-size: 12px; font-weight: 500; opacity: 0.7;">USD</span></div>
        </div>
    </div>
</div>


<div style="margin-bottom: 25px; margin-top: 40px;">
    <h2 style="font-size: 20px; font-family: var(--font-display); display: flex; align-items: center; gap: 8px;">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color: var(--color-primary);"><path d="M9.663 17h4.673M12 3v1m6.364.364l-.707.707M21 12h-1m-.364 6.364l-.707-.707M12 21v-1m-7.657-.364l.707-.707M3 12h1m.364-6.364l.707.707M12 7a5 5 0 100 10 5 5 0 000-10z"/></svg>
        Estado del Servicio y Consumo por Cliente
    </h2>
    <p style="color: var(--text-secondary); font-size: 13px;">Seguimiento del estado de generación y uso de tokens por cliente activo.</p>
</div>

<div class="card-glass" style="padding: 0; overflow: hidden; margin-bottom: 40px;">
    <div style="padding: 18px 22px; border-bottom: 1px solid var(--border-glass);">
        <h3 style="font-size: 16px; font-weight: bold; margin: 0;">Uso del Servicio por Cliente</h3>
    </div>
    <div class="table-responsive">
        <table class="table-custom" style="font-size: 13.5px;">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Tokens Usados</th>
                    <th>Costo Total</th>
                    <th>Presupuesto Mensual</th>
                    <th>Imágenes</th>
                    <th style="text-align: right;">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($consumoClientes)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">Sin consumos registrados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($consumoClientes as $cc): 
                        $isExceeded = $cc['costo_mes'] >= $cc['limite_mensual_usd'];
                        $porcentaje = $cc['limite_mensual_usd'] > 0 ? min(100, ($cc['costo_mes'] / $cc['limite_mensual_usd']) * 100) : 0;
                        $barColor = $porcentaje >= 90 ? '#EF4444' : ($porcentaje >= 70 ? '#F59E0B' : '#34D399');
                    ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <?php if (!empty($cc['logo_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($cc['logo_url']); ?>" alt="Logo" style="width: 20px; height: 20px; border-radius: 4px; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 20px; height: 20px; border-radius: 4px; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; font-size: 8px; font-weight: bold;">CL</div>
                                    <?php endif; ?>
                                    <span style="font-weight: 500;"><?php echo htmlspecialchars($cc['nombre']); ?></span>
                                </div>
                            </td>
                            <td><?php echo number_format($cc['total_tokens']); ?> tokens</td>
                            <td style="color: #34D399; font-weight: 600;">$<?php echo number_format((float)$cc['costo_total'], 4); ?> USD</td>
                            <td>
                                <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px;">
                                    $<?php echo number_format((float)$cc['costo_mes'], 4); ?> / $<?php echo number_format((float)$cc['limite_mensual_usd'], 2); ?> USD
                                </div>
                                <div style="background: rgba(255,255,255,0.06); border-radius: 4px; height: 6px; overflow: hidden;">
                                    <div style="height: 100%; width: <?php echo $porcentaje; ?>%; background: <?php echo $barColor; ?>; border-radius: 4px; transition: width 0.5s ease;"></div>
                                </div>
                            </td>
                            <td><?php echo $cc['total_imagenes']; ?> imágenes</td>
                            <td style="text-align: right; font-weight: 500;">
                                <?php if ($isExceeded): ?>
                                    <span class="badge badge-danger" style="font-size: 11px; font-weight: 600;">Límite Excedido</span>
                                <?php else: ?>
                                    <span class="badge badge-success" style="font-size: 11px; font-weight: 600;">Normal</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Actividad Reciente -->
<div class="card-glass" style="padding: 0; overflow: hidden;">
    <div style="padding: 20px 25px; border-bottom: 1px solid var(--border-glass); display: flex; justify-content: space-between; align-items: center;">
        <h3 style="font-size: 18px;">Actividad Reciente (Últimos 10 Posts)</h3>
        <a href="<?php echo BASE_URL; ?>/platform/admin/posts/lista.php" style="color: var(--color-primary); text-decoration: none; font-size: 13px; font-weight: 600;">Ver todos los posts &rarr;</a>
    </div>

    <?php if (empty($postsRecientes)): ?>
        <div style="padding: 50px; text-align: center; color: var(--text-secondary);">
            <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom: 15px; opacity: 0.5;"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <p>No se registran posts generados en la plataforma.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Título Propuesto</th>
                        <th>Tema</th>
                        <th>Estado</th>
                        <th>Publicación</th>
                        <th>Fecha de Creación</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($postsRecientes as $p): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <?php if (!empty($p['cliente_logo'])): ?>
                                        <img src="<?php echo htmlspecialchars($p['cliente_logo']); ?>" alt="Logo" style="width: 24px; height: 24px; border-radius: 4px; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 24px; height: 24px; border-radius: 4px; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: bold;">
                                            CL
                                        </div>
                                    <?php endif; ?>
                                    <span style="font-weight: 500;"><?php echo htmlspecialchars($p['cliente_nombre']); ?></span>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 600; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($p['titulo']); ?>">
                                    <?php echo htmlspecialchars($p['titulo']); ?>
                                </div>
                            </td>
                            <td>
                                <span style="color: var(--text-secondary);"><?php echo htmlspecialchars($p['tema']); ?></span>
                            </td>
                            <td>
                                <?php if ($p['estado'] === 'pendiente'): ?>
                                    <span class="badge badge-warning">Pendiente</span>
                                <?php elseif ($p['estado'] === 'aprobado'): ?>
                                    <span class="badge badge-success">Aprobado</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Rechazado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['estado'] !== 'aprobado'): ?>
                                    <span style="color: var(--text-muted);">—</span>
                                <?php elseif ($p['publicacion_exitosa'] === 1): ?>
                                    <span class="badge badge-success" style="padding: 2px 8px; font-size: 10px;">Éxito</span>
                                <?php else: ?>
                                    <span class="badge badge-danger" style="padding: 2px 8px; font-size: 10px;">Fallido</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-size: 12px; color: var(--text-muted);">
                                    <?php echo date('d/m/Y H:i', strtotime($p['fecha_creacion'])); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?php echo BASE_URL; ?>/platform/admin/posts/revisar.php?token=<?php echo $p['token_revision']; ?>" class="btn-custom btn-secondary btn-sm" target="_blank" title="Ver Pantalla de Revisión Pública">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    Revisar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/layout_footer.php'; ?>
