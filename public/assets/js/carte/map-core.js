(function (window, $, L) {
    const SanteCarte = window.SanteCarte;
    const config = window.SANTE_CARTE_CONFIG;
    const utils = SanteCarte.utils;

    const state = {
        markers: [],
        activeTypes: new Set(),
        types: {},
        selectedEtablissement: null,
        simulationMode: false,
    };

    const map = L.map('map', { zoomControl: false }).setView([-18.8792, 47.5079], 12.5);
    L.control.zoom({ position: 'bottomright' }).addTo(map);

    const baseLayers = {
        plan: L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap &copy; CARTO',
            maxZoom: 20,
        }),
        satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Esri',
            maxZoom: 20,
        }),
    };

    baseLayers.plan.addTo(map);

    const layers = {
        arrondissements: L.layerGroup().addTo(map),
        markers: L.layerGroup().addTo(map),
        analysis: L.layerGroup().addTo(map),
        decision: L.layerGroup().addTo(map),
    };

    function popupHtml(item, color) {
        const canFindPharmacy = String(item.type_libelle).toUpperCase() !== 'PHARMACY';

        return `<div class="gpop">
            <div class="gpop-titre">${utils.escapeHtml(item.nom)}</div>
            <span class="gpop-type" style="background:${utils.escapeHtml(color)}">${utils.escapeHtml(utils.labelFr(item.type_libelle))}</span>
            ${item.adresse ? `<div class="gpop-row">${utils.escapeHtml(item.adresse)}</div>` : ''}
            ${item.contact ? `<div class="gpop-row">${utils.escapeHtml(item.contact)}</div>` : ''}
            ${item.arrondissement_nom ? `<div class="gpop-row">${utils.escapeHtml(item.arrondissement_nom)}</div>` : ''}
            ${canFindPharmacy ? '<button class="popup-action js-nearest-pharmacy">Pharmacie la plus proche</button>' : ''}
        </div>`;
    }

    function loadArrondissements() {
        return $.get(config.apiBase + '/api/carte/arrondissements').done(function (geojson) {
            L.geoJSON(geojson, {
                interactive: false,
                style: { color: '#5f6368', weight: 2, fillColor: '#1a73e8', fillOpacity: 0.04 },
                onEachFeature: function (feature, layer) {
                    const p = feature.properties || {};
                    layer.bindTooltip(utils.escapeHtml(p.nom), {
                        permanent: true,
                        direction: 'center',
                        className: 'arrondissement-label',
                    });
                },
            }).addTo(layers.arrondissements);
        });
    }

    function loadTypes() {
        return $.get(config.apiBase + '/api/carte/types').done(function (types) {
            const $chips = $('#chips');
            const $drawerTypes = $('#drawer-types');
            const $legend = $('#drawer-legende');

            types.forEach(function (type) {
                const color = type.couleur_carte || config.defaultColor;
                const label = utils.labelFr(type.libelle);
                state.types[type.id] = { label, color, raw: type.libelle };
                state.activeTypes.add(String(type.id));

                $chips.append(`<div class="chip active" data-type="${type.id}"><span class="dot" style="background:${utils.escapeHtml(color)}"></span>${utils.escapeHtml(label)}</div>`);
                $drawerTypes.append(`<label class="drawer-item"><input type="checkbox" class="chk-type" data-type="${type.id}" checked>${utils.escapeHtml(label)}</label>`);
                $legend.append(`<div class="drawer-item legend-item"><span class="dot" style="background:${utils.escapeHtml(color)}"></span>${utils.escapeHtml(label)}</div>`);
            });
        });
    }

    function loadEtablissements() {
        return $.get(config.apiBase + '/api/carte/etablissements').done(function (data) {
            data.forEach(function (item) {
                const lat = parseFloat(item.latitude);
                const lng = parseFloat(item.longitude);
                if (Number.isNaN(lat) || Number.isNaN(lng)) return;

                const color = item.couleur_carte || config.defaultColor;
                const marker = L.marker([lat, lng], { icon: utils.pinIcon(color) }).bindPopup(popupHtml(item, color));
                marker.on('click', function () {
                    if (state.simulationMode) return;
                    state.selectedEtablissement = item;
                    $(document).trigger('sante:marker-click', [item, marker]);
                });
                marker.on('popupopen', function () {
                    $('.js-nearest-pharmacy').off('click').on('click', function () {
                        $(document).trigger('sante:nearest-pharmacy', [item]);
                    });
                });
                state.markers.push({ data: item, marker });
            });

            SanteCarte.filters.apply();
        });
    }

    function initLayoutEvents() {
        $('#btn-menu').on('click', function () { openMenu(!$('#drawer').hasClass('open')); });
        $('#overlay').on('click', function () { openMenu(false); });
        $('#chk-arrondissements').on('change', function () {
            this.checked ? map.addLayer(layers.arrondissements) : map.removeLayer(layers.arrondissements);
        });
        $('#basemap button').on('click', function () {
            $('#basemap button').removeClass('active');
            $(this).addClass('active');
            map.removeLayer(baseLayers.plan);
            map.removeLayer(baseLayers.satellite);
            baseLayers[$(this).data('base')].addTo(map);
        });
    }

    function openMenu(open) {
        $('#drawer').toggleClass('open', open);
        $('#overlay').toggleClass('show', open);
        $('body').toggleClass('menu-open', open);
        if (open) {
            $('#searchbar').addClass('in-drawer').prependTo('#drawer');
        } else {
            setTimeout(function () { $('#searchbar').removeClass('in-drawer').prependTo('body'); }, 260);
        }
    }

    SanteCarte.core = { map, layers, state, loadArrondissements, loadTypes, loadEtablissements, initLayoutEvents };
})(window, jQuery, L);
