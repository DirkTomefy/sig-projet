(function (window, $, L) {
    const SanteCarte = window.SanteCarte;
    const config = window.SANTE_CARTE_CONFIG;
    const utils = SanteCarte.utils;
    let userMarker = null;

    function initEvents() {
        $('#action-simulation').on('click', function () {
            if (SanteCarte.core.state.simulationMode) {
                toggleSimulation();
            } else {
                $('#sim-panel').removeClass('hidden');
                toggleSimulation();
            }
        });
        $('#btn-mode-simulation').on('click', toggleSimulation);
        $('.decision-nearest').on('click', function () { findNearest($(this).data('type')); });

        SanteCarte.core.map.on('click', function (event) {
            if (!SanteCarte.core.state.simulationMode) return;
            setPosition(event.latlng.lat, event.latlng.lng);
        });
    }

    function toggleSimulation() {
        const active = !SanteCarte.core.state.simulationMode;
        SanteCarte.core.state.simulationMode = active;
        $('body').toggleClass('simulation-active', active);
        $('#btn-mode-simulation').toggleClass('active', active).text(active ? 'Desactiver la simulation' : 'Activer la simulation');
        $('#info-panel').html(active ? 'Mode simulation actif : cliquez sur la carte.' : '').toggleClass('hidden', !active);
        $('#action-simulation').toggleClass('active', active);
        if (!active) {
            SanteCarte.core.layers.decision.clearLayers();
            $('#sim-panel').addClass('hidden');
        }
    }

    function setPosition(lat, lng) {
        $('#sim-lat').val(lat.toFixed(6));
        $('#sim-lng').val(lng.toFixed(6));
        SanteCarte.core.layers.decision.clearLayers();
        userMarker = L.marker([lat, lng], { icon: utils.pinIcon('#1a73e8') }).addTo(SanteCarte.core.layers.decision);
    }

    function findNearest(type) {
        const lat = parseFloat($('#sim-lat').val());
        const lng = parseFloat($('#sim-lng').val());
        if (Number.isNaN(lat) || Number.isNaN(lng)) {
            $('#info-panel').html('Definissez d abord votre position sur la carte.').removeClass('hidden');
            return;
        }

        $('#info-panel').html('Calcul des trajets routiers OSRM en cours...').removeClass('hidden');

        $.get(config.apiBase + '/api/decision/nearest', { lat, lng, type, k: 20 }).done(function (res) {
            const $list = $('#sim-list').empty();
            SanteCarte.core.layers.decision.clearLayers();

            if (!res.success || !res.data.length) {
                $list.append('<li>Aucun resultat.</li>');
                $('#info-panel').html('Aucun candidat trouve pour ce type.').removeClass('hidden');
                return;
            }

            const origin = { latitude: lat, longitude: lng };
            SanteCarte.routing.rankByRoadDistance(origin, res.data, 5).then(function (ranked) {
                userMarker = L.marker([lat, lng], { icon: utils.pinIcon('#1a73e8') }).addTo(SanteCarte.core.layers.decision);

                if (!ranked.length) {
                    $list.append('<li>Aucun trajet routier trouve par OSRM.</li>');
                    $('#info-panel').html('OSRM ne peut calculer aucun trajet vers les candidats.').removeClass('hidden');
                    return;
                }

                const bounds = [userMarker.getLatLng()];
                ranked.forEach(function (entry, index) {
                    const item = entry.item;
                    const km = (entry.distanceMeters / 1000).toFixed(2);
                    const marker = L.marker([parseFloat(item.latitude), parseFloat(item.longitude)], {
                        icon: utils.pinIcon(item.couleur_carte || config.defaultColor),
                    }).bindPopup(`<strong>${utils.escapeHtml(item.nom)}</strong><br>${km} km par route`).addTo(SanteCarte.core.layers.decision);

                    const routeLine = L.polyline(entry.route.coordinates, {
                        color: index === 0 ? '#198754' : '#1a73e8',
                        weight: index === 0 ? 5 : 3,
                        opacity: index === 0 ? 0.9 : 0.45,
                    }).addTo(SanteCarte.core.layers.decision);

                    bounds.push(marker.getLatLng());
                    routeLine.getLatLngs().forEach(function (latLng) { bounds.push(latLng); });
                    $list.append(`<li>${utils.escapeHtml(item.nom)} - ${km} km par route</li>`);
                });

                SanteCarte.core.map.fitBounds(bounds, { padding: [60, 60], maxZoom: 16 });
                $('#info-panel').html('Distances calculees avec OSRM sur le reseau routier.').removeClass('hidden');
            });
        });
    }

    function reset() {
        SanteCarte.core.state.simulationMode = false;
        $('body').removeClass('simulation-active');
        SanteCarte.core.layers.decision.clearLayers();
        $('#sim-panel').addClass('hidden');
        $('#btn-mode-simulation').removeClass('active').text('Activer la simulation');
        $('#action-simulation').removeClass('active');
        $('#sim-lat, #sim-lng').val('');
        $('#sim-list').empty();
        userMarker = null;
    }

    SanteCarte.decision = { initEvents, reset };
})(window, jQuery, L);
