<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success" style="margin-bottom: 25px;">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div><?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-error" style="margin-bottom: 25px;">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div><?php echo $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></div>
    </div>
<?php endif; ?>

<?php if (isset($errorMsg) && $errorMsg): ?>
    <div class="alert alert-error" style="margin-bottom: 25px;">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div><?php echo htmlspecialchars($errorMsg); ?></div>
    </div>
<?php endif; ?>

<!-- Loader Premium a pantalla completa -->
<div id="loader-overlay" class="loading-overlay-premium">
    <div class="orbit-spinner">
        <div class="orbit"></div>
        <div class="orbit"></div>
        <div class="orbit"></div>
    </div>
    <h2 id="loader-title" style="margin-top: 25px; font-size: 20px; font-weight: 700; color: #ffffff;">Generando...</h2>
    <p id="loader-status" style="margin-top: 10px; font-size: 14px; color: #9CA3AF; max-width: 450px; text-align: center; line-height: 1.5;">Espere por favor, Gemini está procesando la solicitud.</p>
</div>
