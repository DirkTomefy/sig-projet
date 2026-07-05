(function (window, $, L) {
    const SanteCarte = window.SanteCarte;
    const config = window.SANTE_CARTE_CONFIG;
    const utils = SanteCarte.utils;
    let routingControl = null;
    let routeMode = false;
    let start = null;

    function initEvents() {
        $('#action-routing, #btn-mode-itineraire').on('click', function () {
            routeMode = !routeMode;
            start = null;
            $('#action-routing, #btn-mode-itineraire').toggleClass('active', routeMode);
            showInfo(routeMode ? 'Cliquez sur le point de depart, puis sur la destination.' : '');
            if (!routeMode) clearRoute();
        });

        $('#action-nearest-pharmacy, #btn-pharmacies-proches').on('click', function () {
            const item = SanteCarte.core.state.selectedEtablissement;
            if (!item) {
                showInfo('Selectionnez d abord un etablissement non pharmacie.');
                return;
            }
            findNearestPharmacy(item);
        });

        $(document).on('sante:marker-click', function (_event, item) {
            if (routeMode) handleRouteSelection(item);
        });
        $(document).on('sante:nearest-pharmacy', function (_event, item) { findNearestPharmacy(item); });
    }

    function handleRouteSelection(item) {
        if (!start) {
            start = item;
            showInfo(`Depart : <strong>${utils.escapeHtml(item.nom)}</strong><br>Choisissez la destination.`);
            return;
        }

        if (String(start.id) === String(item.id)) {
            start = null;
            showInfo('Depart annule. Cliquez sur un nouveau point de depart.');
            return;
        }

        drawRoute(start, item, `Trajet entre <strong>${utils.escapeHtml(start.nom)}</strong> et <strong>${utils.escapeHtml(item.nom)}</strong>`);
        start = null;
    }

    function findNearestPharmacy(item) {
        if (String(item.type_libelle).toUpperCase() === 'PHARMACY') {
            showInfo('Selectionnez un etablissement autre qu une pharmacie.');
            return;
        }

        $.get(config.apiBase + '/api/decision/nearest', {
            lat: item.latitude,
            lng: item.longitude,
            type: 'PHARMACY',
            k: 20,
        }).done(function (res) {
            if (!res.success || !res.data.length) {
                showInfo('Aucune pharmacie trouvee.');
                return;
            }

            showInfo('Calcul des trajets routiers OSRM en cours...');
            rankByRoadDistance(item, res.data, 1).then(function (ranked) {
                if (!ranked.length) {
                    showInfo('OSRM ne peut calculer aucun trajet vers les pharmacies candidates.');
                    return;
                }

                const nearest = ranked[0].item;
                drawRoute(item, nearest, `Pharmacie la plus proche par route : <strong>${utils.escapeHtml(nearest.nom)}</strong>`);
            });
        });
    }

    function drawRoute(from, to, title) {
        clearRoute();
        routingControl = L.Routing.control({
            waypoints: [
                L.latLng(parseFloat(from.latitude), parseFloat(from.longitude)),
                L.latLng(parseFloat(to.latitude), parseFloat(to.longitude)),
            ],
            router: createOsrmRouter(),
            addWaypoints: false,
            draggableWaypoints: false,
            fitSelectedRoutes: true,
            show: false,
            lineOptions: { styles: [{ color: '#198754', opacity: 0.9, weight: 5 }] },
        }).on('routesfound', function (event) {
            const route = event.routes[0];
            const distance = route ? (route.summary.totalDistance / 1000).toFixed(2) : '-';
            showInfo(`${title}<br><strong>${distance} km</strong> via OSRM`);
        }).on('routingerror', function () {
            showInfo('OSRM ne peut pas calculer ce trajet pour le moment.');
        }).addTo(SanteCarte.core.map);
    }

    function createOsrmRouter() {
        return L.Routing.osrmv1({
            serviceUrl: 'https://router.project-osrm.org/route/v1'
        });
    }

    function calculateRoadRoute(from, to) {
        const router = createOsrmRouter();
        const waypoints = [
            L.Routing.waypoint(L.latLng(parseFloat(from.latitude), parseFloat(from.longitude))),
            L.Routing.waypoint(L.latLng(parseFloat(to.latitude), parseFloat(to.longitude))),
        ];

        return new Promise(function (resolve) {
            router.route(waypoints, function (error, routes) {
                if (error || !routes || !routes.length) {
                    resolve(null);
                    return;
                }

                resolve(routes[0]);
            });
        });
    }

    function rankByRoadDistance(from, candidates, limit) {
        const jobs = candidates.map(function (item) {
            return calculateRoadRoute(from, item).then(function (route) {
                if (!route || !route.summary) return null;
                return {
                    item,
                    route,
                    distanceMeters: route.summary.totalDistance,
                    durationSeconds: route.summary.totalTime,
                };
            });
        });

        return Promise.all(jobs).then(function (results) {
            return results
                .filter(Boolean)
                .sort(function (a, b) { return a.distanceMeters - b.distanceMeters; })
                .slice(0, limit);
        });
    }

    function clearRoute() {
        if (routingControl) {
            SanteCarte.core.map.removeControl(routingControl);
            routingControl = null;
        }
    }

    function reset() {
        routeMode = false;
        start = null;
        clearRoute();
        $('#action-routing, #btn-mode-itineraire').removeClass('active');
    }

    function showInfo(html) {
        const $panel = $('#info-panel');
        if (!html) $panel.addClass('hidden').empty();
        else $panel.html(html).removeClass('hidden');
    }

    SanteCarte.routing = { initEvents, clearRoute, drawRoute, calculateRoadRoute, rankByRoadDistance, reset };
})(window, jQuery, L);
