(function (window, $, L) {
    const SanteCarte = window.SanteCarte;
    const config = window.SANTE_CARTE_CONFIG;
    const utils = SanteCarte.utils;

    function loadArrondissementsFilter() {
        return $.get(config.apiBase + '/api/carte/arrondissements-liste').done(function (data) {
            data.forEach(function (arrondissement) {
                $('#filtre-arrondissement').append(`<option value="${arrondissement.id}">${utils.escapeHtml(arrondissement.nom)}</option>`);
            });
        });
    }

    function apply() {
        const text = $('#recherche').val().trim().toLowerCase();
        const arrondissementId = $('#filtre-arrondissement').val();
        const bounds = [];

        SanteCarte.core.layers.markers.clearLayers();
        SanteCarte.core.state.markers.forEach(function (entry) {
            const item = entry.data;
            const matchesType = SanteCarte.core.state.activeTypes.has(String(item.id_type));
            const matchesText = !text || String(item.nom).toLowerCase().includes(text);
            const matchesArrondissement = !arrondissementId || String(item.id_arrondissement) === String(arrondissementId);

            if (matchesType && matchesText && matchesArrondissement) {
                SanteCarte.core.layers.markers.addLayer(entry.marker);
                bounds.push(entry.marker.getLatLng());
            }
        });

        if ((text || arrondissementId) && bounds.length > 0) {
            SanteCarte.core.map.fitBounds(bounds, { padding: [60, 60], maxZoom: 16 });
        }
    }

    function selectType(id, active) {
        const key = String(id);
        if (active) SanteCarte.core.state.activeTypes.add(key);
        else SanteCarte.core.state.activeTypes.delete(key);

        $(`.chip[data-type="${key}"]`).toggleClass('active', active).toggleClass('off', !active);
        $(`.chk-type[data-type="${key}"]`).prop('checked', active);
        apply();
    }

    function selectSearchResult(id) {
        const entry = SanteCarte.core.state.markers.find(function (markerEntry) {
            return String(markerEntry.data.id) === String(id);
        });
        if (!entry) return;

        $('#recherche').val(entry.data.nom);
        $('#suggestions-recherche').empty().removeClass('show');
        SanteCarte.core.map.setView(entry.marker.getLatLng(), 17);
        entry.marker.openPopup();
        apply();
    }

    function initEvents() {
        let debounce;

        $('#recherche').on('input', function () {
            clearTimeout(debounce);
            const term = this.value.trim();
            debounce = setTimeout(function () {
                apply();
                autocomplete(term);
            }, 200);
        });

        $('#recherche').on('keydown', function (event) {
            if (event.key === 'Enter') {
                const first = $('#suggestions-recherche button').first();
                if (first.length) selectSearchResult(first.data('id'));
                else apply();
            }
        });

        $('#btn-search').on('click', function () {
            const first = $('#suggestions-recherche button').first();
            if (first.length) selectSearchResult(first.data('id'));
            else apply();
        });

        $('#filtre-arrondissement').on('change', apply);
        $('#chips').on('click', '.chip', function () {
            const id = String($(this).data('type'));
            selectType(id, !SanteCarte.core.state.activeTypes.has(id));
        });
        $('#drawer-types').on('change', '.chk-type', function () { selectType($(this).data('type'), this.checked); });
        $('#drawer-reset, #btn-reset').on('click', reset);
        $('#suggestions-recherche').on('click', 'button', function () { selectSearchResult($(this).data('id')); });
    }

    function autocomplete(term) {
        const $suggestions = $('#suggestions-recherche');
        if (term.length < 2) {
            $suggestions.empty().removeClass('show');
            return;
        }

        $.get(config.apiBase + '/api/recherche/autocomplete', { q: term, limit: 8 }).done(function (rows) {
            $suggestions.empty();
            rows.forEach(function (row) {
                $suggestions.append(`<button type="button" data-id="${row.id}">${utils.escapeHtml(row.nom)}</button>`);
            });
            $suggestions.toggleClass('show', rows.length > 0);
        });
    }

    function reset() {
        $('#recherche').val('');
        $('#filtre-arrondissement').val('');
        Object.keys(SanteCarte.core.state.types).forEach(function (id) { selectType(id, true); });
        $('#suggestions-recherche').empty().removeClass('show');
        SanteCarte.core.layers.analysis.clearLayers();
        SanteCarte.core.layers.decision.clearLayers();
        SanteCarte.core.state.selectedEtablissement = null;
        $('#info-panel').addClass('hidden').empty();
        apply();
    }

    SanteCarte.filters = { apply, loadArrondissementsFilter, initEvents, reset };
})(window, jQuery, L);
