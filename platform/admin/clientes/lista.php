<?php
/**
 * CRUD Clientes - Listado de Clientes
 */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/DB.php';

$db = DB::getInstance();

// Manejar acción de alternar estado activo/inactivo si se solicita
if (isset($_GET['toggle_activo']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $db->prepare("SELECT activo FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    $cliente = $stmt->fetch();
    
    if ($cliente) {
        $nuevo_estado = $cliente['activo'] ? 0 : 1;
        $updateStmt = $db->prepare("UPDATE clientes SET activo = ? WHERE id = ?");
        $updateStmt->execute([$nuevo_estado, $id]);
        $_SESSION['flash_success'] = "Estado del cliente actualizado correctamente.";
    }
    header("Location: lista.php");
    exit();
}

// Obtener todos los clientes
$stmt = $db->query("SELECT * FROM clientes ORDER BY nombre ASC");
$clientes = $stmt->fetchAll();

include __DIR__ . '/../layout_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <div>
        <h1 style="font-size: 28px; margin-bottom: 5px;">Clientes Registrados</h1>
        <p style="color: var(--text-secondary); font-size: 14px;">Administra los negocios de tu red de blogs automáticos.</p>
    </div>
    <a href="nuevo.php" class="btn-custom btn-primary">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
        Registrar Cliente
    </a>
</div>

<div class="card-glass" style="padding: 0; overflow: hidden;">
    <?php if (empty($clientes)): ?>
        <div style="padding: 50px; text-align: center; color: var(--text-secondary);">
            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom: 15px; opacity: 0.5;"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <h3 style="margin-bottom: 5px;">No hay clientes registrados</h3>
            <p style="margin-bottom: 20px;">Registra tu primer cliente para comenzar a generar contenido con IA.</p>
            <a href="nuevo.php" class="btn-custom btn-primary">Agregar Cliente</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Rubro</th>
                        <th>Dominio</th>
                        <th>Autor</th>
                        <th>Estilo Visual</th>
                        <th>Estado</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $c): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if (!empty($c['logo_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($c['logo_url']); ?>" alt="Logo" style="width: 32px; height: 32px; border-radius: 6px; object-fit: cover; border: 1px solid var(--border-glass);">
                                    <?php else: ?>
                                        <div style="width: 32px; height: 32px; border-radius: 6px; background: linear-gradient(135deg, var(--color-primary), var(--color-accent)); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px; color: white;">
                                            <?php echo strtoupper(substr($c['nombre'], 0, 2)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($c['nombre']); ?></div>
                                        <div style="font-size: 11px; color: var(--text-muted);">slug: <?php echo htmlspecialchars($c['slug']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-primary"><?php echo htmlspecialchars($c['rubro']); ?></span>
                            </td>
                            <td>
                                <a href="<?php echo htmlspecialchars($c['dominio']); ?>" target="_blank" style="color: var(--text-secondary); text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                    <?php echo htmlspecialchars(preg_replace('#^https?://#', '', $c['dominio'])); ?>
                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <img src="<?php echo htmlspecialchars($c['foto_autor_url']); ?>" alt="Autor" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
                                    <span><?php echo htmlspecialchars($c['nombre_autor']); ?></span>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width: 14px; height: 14px; border-radius: 4px; background-color: <?php echo $c['color_primario']; ?>; border: 1px solid rgba(255,255,255,0.1);" title="Color Primario"></div>
                                    <div style="width: 14px; height: 14px; border-radius: 4px; background-color: <?php echo $c['color_texto']; ?>; border: 1px solid rgba(255,255,255,0.1);" title="Color de Texto"></div>
                                    <span style="font-size: 12px; color: var(--text-secondary); font-family: <?php echo htmlspecialchars($c['fuente_titulo']); ?>;">Aa</span>
                                </div>
                            </td>
                            <td>
                                <a href="lista.php?toggle_activo=1&id=<?php echo $c['id']; ?>" style="text-decoration: none;">
                                    <?php if ($c['activo']): ?>
                                        <span class="badge badge-success" style="cursor: pointer;">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger" style="cursor: pointer;">Inactivo</span>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 8px;">
                                    <a href="../posts/lista.php?cliente_id=<?php echo $c['id']; ?>" class="btn-custom btn-secondary btn-sm" title="Ver Historial de Posts">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        Posts
                                    </a>
                                    <a href="editar.php?id=<?php echo $c['id']; ?>" class="btn-custom btn-secondary btn-sm" style="border-color: rgba(139, 92, 246, 0.3); color: #C084FC;" title="Editar Cliente">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Editar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
