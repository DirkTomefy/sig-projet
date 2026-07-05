(function (window, $, L) {
    const SanteCarte = window.SanteCarte;
    const utils = SanteCarte.utils;
    let active = false;

    function initEvents() {
        $('#action-conformity, #btn-conformite-pharmacies').on('click', togglePharmacyConformity);
    }

    function togglePharmacyConformity() {
        if (active) {
            reset();
            return;
        }

        runPharmacyConformity();
    }

    function runPharmacyConformity() {
        SanteCarte.core.layers.analysis.clearLayers();
        active = true;
        $('#action-conformity, #btn-conformite-pharmacies').addClass('active');
        filterOnlyPharmacies();

        $.when(
            $.get(window.SANTE_CARTE_CONFIG.apiBase + '/analyse-spatiale/buffers'),
            $.get(window.SANTE_CARTE_CONFIG.apiBase + '/analyse-spatiale/zones-non-couvertes')
        ).done(function (buffersResponse, zonesResponse) {
            const buffers = buffersResponse[0];
            const zones = zonesResponse[0];

            if (zones.success) {
                L.geoJSON(zones.data, {
                    style: {
                        color: '#d93025',
                        weight: 1,
                        fillColor: '#d93025',
                        fillOpacity: 0.18,
                    },
                    onEachFeature: function (_feature, layer) {
                        layer.bindPopup('<strong>Zone disponible</strong>');
                    },
                }).addTo(SanteCarte.core.layers.analysis);
            }

            if (!buffers.success) return;
            L.geoJSON(buffers.data, {
                style: {
                    color: '#e8710a',
                    weight: 2,
                    fillColor: '#fbbc04',
                    fillOpacity: 0.16,
                },
                onEachFeature: function (feature, layer) {
                    const p = feature.properties || {};
                    layer.bindPopup(`<strong>${utils.escapeHtml(p.nom)}</strong><br>Buffer 500 m`);
                },
            }).addTo(SanteCarte.core.layers.analysis);

            const bounds = SanteCarte.core.layers.analysis.getBounds();
            if (bounds.isValid()) SanteCarte.core.map.fitBounds(bounds, { padding: [50, 50] });
            showLegend();
        }).fail(function () {
            active = false;
            $('#action-conformity, #btn-conformite-pharmacies').removeClass('active');
            $('#info-panel').html('Erreur lors du chargement de l analyse de conformite.').removeClass('hidden');
        });
    }

    function filterOnlyPharmacies() {
        Object.keys(SanteCarte.core.state.types).forEach(function (id) {
            const type = SanteCarte.core.state.types[id];
            const active = String(type.raw).toUpperCase() === 'PHARMACY';
            if (active) SanteCarte.core.state.activeTypes.add(String(id));
            else SanteCarte.core.state.activeTypes.delete(String(id));
            $(`.chip[data-type="${id}"]`).toggleClass('active', active).toggleClass('off', !active);
            $(`.chk-type[data-type="${id}"]`).prop('checked', active);
        });
        SanteCarte.filters.apply();
    }

    function reset() {
        active = false;
        SanteCarte.core.layers.analysis.clearLayers();
        $('#action-conformity, #btn-conformite-pharmacies').removeClass('active');
        $('#info-panel').addClass('hidden').empty();
    }

    function showLegend() {
        $('#info-panel').html(`
            <strong>Conformite des pharmacies</strong>
            <div class="analysis-legend">
                <span><i class="legend-swatch buffer"></i>Buffer pharmacie 500 m</span>
                <span><i class="legend-swatch available"></i>Zone disponible</span>
            </div>
        `).removeClass('hidden');
    }

    SanteCarte.spatialAnalysis = { initEvents, runPharmacyConformity, reset };
})(window, jQuery, L);
