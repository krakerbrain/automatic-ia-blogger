/* Lógica Interactiva del Generador de Contenido IA */

document.addEventListener('DOMContentLoaded', function() {
    const selectCliente = document.getElementById('cliente_id');
    const miniCard = document.getElementById('cliente-mini-card');
    const cardLogo = document.getElementById('card-logo');
    const cardLogoFallback = document.getElementById('card-logo-fallback');
    const cardNombre = document.getElementById('card-nombre');
    const cardRubro = document.getElementById('card-rubro');
    const cardSpend = document.getElementById('card-spend');
    const cardLimit = document.getElementById('card-limit');
    
    const cardDescBox = document.getElementById('card-desc-box');
    const cardDescripcion = document.getElementById('card-descripcion');
    
    const btnSugerir = document.getElementById('btn-sugerir-temas');
    const containerSugerencias = document.getElementById('sugerencias-container');
    const listaSugerencias = document.getElementById('sugerencias-lista');
    const inputTema = document.getElementById('tema');
    const hiddenSugerenciaId = document.querySelector('input[name="sugerencia_id"]');
    
    const alertExceeded = document.getElementById('alert-presupuesto-excedido');
    const btnSubmitText = document.getElementById('btn-submit-text');
    
    const formText = document.getElementById('generate-form-text');
    const formDraft = document.getElementById('draft-form');
    
    const loader = document.getElementById('loader-overlay');
    const loaderTitle = document.getElementById('loader-title');
    const loaderStatus = document.getElementById('loader-status');

    const toggleSugerenciasHeader = document.getElementById('toggle-sugerencias-header');
    const sugerenciasListaWrapper = document.getElementById('sugerencias-lista-wrapper');
    const sugerenciasToggleIcon = document.getElementById('sugerencias-toggle-icon');
    
    if (toggleSugerenciasHeader && sugerenciasListaWrapper && sugerenciasToggleIcon) {
        toggleSugerenciasHeader.addEventListener('click', function() {
            if (sugerenciasListaWrapper.style.display === 'none') {
                sugerenciasListaWrapper.style.display = 'block';
                sugerenciasToggleIcon.textContent = 'OCULTAR ▲';
            } else {
                sugerenciasListaWrapper.style.display = 'none';
                sugerenciasToggleIcon.textContent = 'MOSTRAR ▼';
            }
        });
    }

    function escapeHTML(str) {
        if (!str) return '';
        return str.replace(/[&<>'"]/g, 
            tag => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#39;',
                '"': '&quot;'
            }[tag] || tag)
        );
    }

    // Cargar sugerencias desde la base de datos para el cliente seleccionado
    function cargarSugerenciasPendientes(clienteId) {
        if (!clienteId) return;
        
        listaSugerencias.innerHTML = `
            <div style="display: flex; align-items: center; gap: 8px; color: var(--text-secondary); font-size: 13px; padding: 10px;">
                <svg class="animate-spin" width="14" height="14" fill="none" viewBox="0 0 24 24" style="animation: spin 1s linear infinite;">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" style="opacity: 0.25;"></circle>
                    <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" style="opacity: 0.75;"></path>
                </svg>
                Cargando sugerencias de temas...
            </div>
        `;
        containerSugerencias.style.display = 'block';

        fetch('get_sugerencias.php?cliente_id=' + clienteId)
        .then(res => res.json())
        .then(data => {
            listaSugerencias.innerHTML = '';
            if (data.status === 'success' && data.sugerencias && data.sugerencias.length > 0) {
                data.sugerencias.forEach(sug => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'btn-custom btn-secondary';
                    item.style.textAlign = 'left';
                    item.style.padding = '10px 14px';
                    item.style.fontSize = '13.5px';
                    item.style.width = '100%';
                    item.style.borderColor = 'rgba(255,255,255,0.08)';
                    item.style.background = 'rgba(255,255,255,0.01)';
                    item.style.justifyContent = 'flex-start';
                    item.style.fontWeight = 'normal';
                    
                    item.innerHTML = `
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 8px; flex-shrink: 0; color: var(--color-primary);"><path d="M9.663 17h4.673M12 3v1m6.364.364l-.707.707M21 12h-1m-.364 6.364l-.707-.707M12 21v-1m-7.657-.364l.707-.707M3 12h1m.364-6.364l.707.707M12 7a5 5 0 100 10 5 5 0 000-10z"/></svg>
                        <span>${escapeHTML(sug.tema)}</span>
                    `;
                    
                    item.addEventListener('click', () => {
                        inputTema.value = sug.tema;
                        
                        // Crear o actualizar hidden input con sugerencia_id
                        let hiddenSug = document.getElementById('sugerencia_id_post');
                        if (!hiddenSug) {
                            hiddenSug = document.createElement('input');
                            hiddenSug.type = 'hidden';
                            hiddenSug.name = 'sugerencia_id';
                            hiddenSug.id = 'sugerencia_id_post';
                            formText.appendChild(hiddenSug);
                        }
                        hiddenSug.value = sug.id;
                        
                        // Resaltar
                        Array.from(listaSugerencias.children).forEach(c => {
                            c.style.borderColor = 'rgba(255,255,255,0.08)';
                            c.style.background = 'rgba(255,255,255,0.01)';
                        });
                        item.style.borderColor = 'var(--color-primary)';
                        item.style.background = 'rgba(139, 92, 246, 0.05)';
                    });
                    listaSugerencias.appendChild(item);
                });
            } else {
                listaSugerencias.innerHTML = `
                    <div style="color: var(--text-muted); font-size: 13px; font-style: italic; padding: 10px;">
                        No hay sugerencias guardadas en la base de datos. Haz clic en "Sugerir 5 Temas Más" para generar nuevas ideas.
                    </div>
                `;
            }
        })
        .catch(err => {
            listaSugerencias.innerHTML = `<div class="alert alert-error" style="padding: 8px 12px; margin: 0;">Error al consultar sugerencias.</div>`;
        });
    }

    // Cambiar Mini Card y cargar sugerencias al seleccionar cliente
    if (selectCliente) {
        selectCliente.addEventListener('change', function() {
            const opt = selectCliente.options[selectCliente.selectedIndex];
            const logo = opt.getAttribute('data-logo');
            const nombre = opt.getAttribute('data-nombre');
            const rubro = opt.getAttribute('data-rubro');
            const desc = opt.getAttribute('data-descripcion');
            const exceeded = opt.getAttribute('data-exceeded') === '1';
            const spend = opt.getAttribute('data-spend');
            const limit = opt.getAttribute('data-limit');

            if (nombre) {
                cardNombre.textContent = nombre;
                cardRubro.textContent = rubro;
                cardSpend.textContent = '$' + spend;
                cardLimit.textContent = '$' + limit;
                
                if (logo && logo.trim() !== '') {
                    cardLogo.src = logo;
                    cardLogo.style.display = 'block';
                    cardLogoFallback.style.display = 'none';
                } else {
                    cardLogoFallback.textContent = nombre.substring(0, 2).toUpperCase();
                    cardLogoFallback.style.display = 'flex';
                    cardLogo.style.display = 'none';
                }
                
                if (desc && desc.trim() !== '') {
                    cardDescripcion.textContent = desc;
                    cardDescBox.style.display = 'block';
                } else {
                    cardDescBox.style.display = 'none';
                }
                
                // Mostrar alerta si el límite de presupuesto está excedido
                if (exceeded) {
                    alertExceeded.style.display = 'flex';
                    btnSubmitText.disabled = true;
                    btnSubmitText.style.opacity = '0.5';
                } else {
                    alertExceeded.style.display = 'none';
                    btnSubmitText.disabled = false;
                    btnSubmitText.style.opacity = '1';
                }
                
                miniCard.style.display = 'block';
                btnSugerir.style.display = 'inline-flex';
                
                cargarSugerenciasPendientes(selectCliente.value);
            } else {
                miniCard.style.display = 'none';
                btnSugerir.style.display = 'none';
                containerSugerencias.style.display = 'none';
            }
        });

        // Cargar datos del cliente seleccionado al iniciar la página (ej. si viene pre-seleccionado por GET)
        if (selectCliente.value) {
            selectCliente.dispatchEvent(new Event('change'));
        }
    }

    // Botón para generar 5 sugerencias más con IA en base de datos
    if (btnSugerir) {
        btnSugerir.addEventListener('click', function() {
            const clienteId = selectCliente.value;
            if (!clienteId) return;

            listaSugerencias.innerHTML = `
                <div style="display: flex; align-items: center; gap: 8px; color: var(--text-secondary); font-size: 13px; padding: 10px; font-style: italic;">
                    <svg class="animate-spin" width="14" height="14" fill="none" viewBox="0 0 24 24" style="animation: spin 1s linear infinite;">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" style="opacity: 0.25;"></circle>
                        <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" style="opacity: 0.75;"></path>
                    </svg>
                    Llamando a Gemini para proponer nuevas ideas de temas y guardarlas...
                </div>
            `;

            fetch('sugerir_temas.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ cliente_id: parseInt(clienteId) })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Recargar la lista de sugerencias guardadas desde la base de datos
                    cargarSugerenciasPendientes(clienteId);
                } else {
                    listaSugerencias.innerHTML = `
                        <div class="alert alert-error" style="padding: 8px 12px; margin: 0;">
                            ${escapeHTML(data.message || 'No se pudieron generar nuevos temas.')}
                        </div>
                    `;
                }
            })
            .catch(err => {
                listaSugerencias.innerHTML = `<div class="alert alert-error" style="padding: 8px 12px; margin: 0;">Error de red.</div>`;
            });
        });
    }

    // Spinner para generar texto
    if (formText) {
        formText.addEventListener('submit', function() {
            loaderTitle.textContent = "Redactando Post con IA...";
            loaderStatus.textContent = "Google Gemini Flash está redactando la estructura del post y el título basándose en el tema seleccionado...";
            loader.style.display = 'flex';
        });
    }

    // Spinner para generar imagen
    if (formDraft) {
        formDraft.addEventListener('submit', function(e) {
            const activeAction = document.activeElement ? document.activeElement.value : '';
            if (activeAction === 'diseñar_imagen') {
                loaderTitle.textContent = "Diseñando Portada con IA...";
                loaderStatus.textContent = "Llamando a Gemini Imagen 3/4 para generar una fotografía apaisada representativa del post. Esto puede tomar entre 10 y 15 segundos...";
                loader.style.display = 'flex';
            }
        });
    }
});
