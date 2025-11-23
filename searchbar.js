// searchbar.js
// JavaScript para la barra de búsqueda con AJAX (solo 1 tag válido, proveniente de la tabla tags)

document.addEventListener('DOMContentLoaded', function() {

    // Endpoint del API (SearchbarController ya preparado)
    const API_URL = '/API-PHP/SearchbarController.php?action=search';

    // Solo una categoría: tags
    const searchConfig = {
        selectedTag: null // { id, tag, name }
    };

    // DOM
    const searchInput = document.getElementById('searchbar-input');
    const searchForm = document.getElementById('searchbar-form');
    const suggestionsDiv = document.getElementById('searchbar-suggestions');
    const selectedTagsDiv = document.getElementById('searchbar-selected-tags');
    const hiddenTagsContainer = document.getElementById('hidden-tags-container');

    let debounceTimer;

    if (!searchInput || !searchForm || !suggestionsDiv || !selectedTagsDiv || !hiddenTagsContainer) {
        console.error('Searchbar: elementos faltantes en el DOM');
        return;
    }

    // Si vienen tags iniciales desde GET (window.searchbarTagsIniciales)
    if (typeof window.searchbarTagsIniciales !== 'undefined' && window.searchbarTagsIniciales.length > 0) {
        procesarTagsIniciales(window.searchbarTagsIniciales);
    }

    // Submit del formulario: enviar solo si hay tag seleccionado proveniente de la lista
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Limpiar ocultos
        hiddenTagsContainer.innerHTML = '';

        if (!searchConfig.selectedTag) {
            // Forzamos a seleccionar un tag de la lista
            suggestionsDiv.innerHTML = '<div class="no-results">Selecciona un tag de la lista antes de buscar.</div>';
            suggestionsDiv.classList.add('active');
            searchInput.focus();
            return;
        }

        // Crear un solo campo oculto 'tag' con el tag seleccionado (reemplazar espacios por _ si quieres)
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'tag';
        input.value = searchConfig.selectedTag.tag;
        hiddenTagsContainer.appendChild(input);

        // Enviar el formulario normalmente (irá a ver_nfts.php si action en form está puesto)
        this.submit();
    });

    // Input: solicitudes de sugerencias con debounce
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();

        // Si se borra el input, también removemos selección previa
        if (query.length === 0) {
            // no limpiar selección automática para permitir ver tag seleccionado visualmente
            suggestionsDiv.classList.remove('active');
            return;
        }

        if (query.length < 2) {
            suggestionsDiv.classList.remove('active');
            return;
        }

        debounceTimer = setTimeout(() => {
            buscarTags(query);
        }, 250);
    });

    // Cerrar sugerencias al click fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.searchbar-container')) {
            suggestionsDiv.classList.remove('active');
        }
    });

    // Buscar tags via fetch
    function buscarTags(query) {
        suggestionsDiv.innerHTML = '<div class="searchbar-loading">Buscando...</div>';
        suggestionsDiv.classList.add('active');

        fetch(`${API_URL}&query=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (data && data.success) {
                    mostrarSugerencias(data.data);
                } else {
                    suggestionsDiv.innerHTML = '<div class="no-results">No se encontraron resultados</div>';
                }
            })
            .catch(err => {
                suggestionsDiv.innerHTML = '<div class="no-results">Error de conexión</div>';
            });
    }

    // Mostrar sugerencias — espera data.tags (array con {id, tag, name, maybe count})
    function mostrarSugerencias(data) {
        suggestionsDiv.innerHTML = '';
        let hayResultados = false;

        if (data.tags && data.tags.length > 0) {
            hayResultados = true;

            const header = document.createElement('div');
            header.className = 'suggestion-category';
            header.textContent = 'Tags';
            suggestionsDiv.appendChild(header);

            data.tags.forEach(item => {
                const sugg = crearItemSugerencia(item);
                suggestionsDiv.appendChild(sugg);
            });
        }

        if (!hayResultados) {
            suggestionsDiv.innerHTML = '<div class="no-results">No se encontraron resultados</div>';
        }

        suggestionsDiv.classList.add('active');
    }

    // Crear un item de sugerencia (clic selecciona el tag — SOLO UNO)
    function crearItemSugerencia(item) {
        const div = document.createElement('div');
        div.className = 'suggestion-item';

        // Mostrar count solo si existe
        const countHtml = (typeof item.count !== 'undefined') ? `<span class="suggestion-count">(${item.count})</span>` : '';

        div.innerHTML = `
            <span class="suggestion-tag">${escapeHtml(item.tag)}</span>
            ${countHtml}
        `;

        div.addEventListener('click', () => {
            // Selecciona este tag (reemplaza cualquier selección anterior)
            agregarTag(item);
            // vaciar input para que no confunda; mantener tag seleccionado visualmente
            searchInput.value = '';
            suggestionsDiv.classList.remove('active');
        });

        return div;
    }

    // Agregar (o reemplazar) tag seleccionado
    function agregarTag(item) {
        // item: { id, tag, name, ... }
        searchConfig.selectedTag = {
            id: item.id,
            tag: item.tag,
            name: item.name || item.tag
        };
        actualizarTagsVisuales();
    }

    // Remover tag seleccionado
    function removerTag() {
        searchConfig.selectedTag = null;
        actualizarTagsVisuales();
    }

    // Actualizar UI de tag seleccionado (solo uno)
    function actualizarTagsVisuales() {
        selectedTagsDiv.innerHTML = '';

        if (!searchConfig.selectedTag) return;

        const tagObj = searchConfig.selectedTag;
        const tagDiv = document.createElement('div');
        tagDiv.className = 'selected-tag';
        tagDiv.innerHTML = `
            <span>${escapeHtml(tagObj.tag)}</span>
            <span class="selected-tag-remove" id="remove-selected-tag">×</span>
        `;

        selectedTagsDiv.appendChild(tagDiv);

        const remover = document.getElementById('remove-selected-tag');
        if (remover) {
            remover.addEventListener('click', function() {
                removerTag();
            });
        }
    }

    function removerTag() {
        searchConfig.selectedTag = null;
        actualizarTagsVisuales();
    }

    // Procesar tags iniciales (pasadas en GET) — intentará mapearlas contra la tabla tags
    function procesarTagsIniciales(tags) {
        // tags es un array de strings (p.ej ["mi_tag"])
        tags.forEach(tag => {
            // pedimos al API para encontrar la coincidencia exacta en la tabla tags
            fetch(`${API_URL}&query=${encodeURIComponent(tag.replace(/_/g, ' '))}`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.success && data.data.tags) {
                        data.data.tags.forEach(item => {
                            if (item.tag === tag) {
                                // seleccionar la coincidencia exacta
                                agregarTag(item);
                            }
                        });
                    }
                })
                .catch(err => {
                    console.error('Error mapeando tag inicial:', err);
                });
        });
    }

    // Escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }

    // Debug helpers
    window.getSelectedTag = function() {
        return searchConfig.selectedTag;
    };

});
