<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIG Santé - Carte des établissements</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/carte-sante.css') ?>">
</head>

<body>
    <div id="map"></div>

    <div id="searchbar">
        <button class="icon-btn" id="btn-menu" title="Menu">
            <svg width="24" height="24" viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
        </button>
        <div class="search-field">
            <input type="text" id="recherche" placeholder="Rechercher un établissement de santé" autocomplete="off">
            <div id="suggestions-recherche"></div>
        </div>
        <select id="filtre-arrondissement">
            <option value="">Tous les arrondissements</option>
        </select>
        <button class="icon-btn" id="btn-search" title="Rechercher">
            <svg width="22" height="22" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 10-.7.7l.27.28v.79l5 4.99L20.49 19zm-6 0A4.5 4.5 0 1114 9.5 4.5 4.5 0 019.5 14z"/></svg>
        </button>
        <div class="sep"></div>
        <button class="icon-btn" id="btn-reset" title="Réinitialiser">
            <svg width="22" height="22" viewBox="0 0 24 24"><path d="M17.65 6.35A7.958 7.958 0 0012 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08A5.99 5.99 0 0112 18c-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
        </button>
    </div>

    <div id="action-panel">
        <button type="button" id="action-nearest-pharmacy" title="Pharmacie la plus proche">
            <span>Pharmacie proche</span>
        </button>
        <button type="button" id="action-routing" title="Trajet le plus court">
            <span>Trajet</span>
        </button>
        <button type="button" id="action-conformity" title="Conformité des pharmacies">
            <span>Conformité des pharmacies</span>
        </button>
        <button type="button" id="action-simulation" title="Aide à la décision">
            <span>Simulation</span>
        </button>
        <button type="button" id="action-statistics" title="Statistiques sanitaires">
            <span>Statistiques</span>
        </button>
        <button type="button" id="action-reset-all" title="Réinitialiser tout">
            <span>Réinitialiser</span>
        </button>
    </div>

    <div id="chips"></div>
    <div id="overlay"></div>

    <div id="drawer">
        <div class="drawer-head">
            <div class="logo">+</div>
            <div>
                <h1>SIG Santé</h1>
                <p>Commune Urbaine d'Antananarivo</p>
            </div>
        </div>
        <div class="drawer-body">
            <div class="drawer-titre">Couches</div>
            <label class="drawer-item"><input type="checkbox" id="chk-arrondissements" checked>Arrondissements</label>
            <div id="drawer-types"></div>

            <div class="drawer-sep"></div>
            <div class="drawer-titre">Légende</div>
            <div id="drawer-legende"></div>

            <div class="drawer-sep"></div>
            <div class="drawer-item" id="drawer-reset">Réinitialiser les filtres</div>
        </div>
    </div>

    <div id="basemap">
        <button data-base="plan" class="active">Plan</button>
        <button data-base="satellite">Satellite</button>
    </div>

    <div id="info-panel" class="hidden"></div>
    <div id="loader"><div><div class="spinner"></div>Chargement...</div></div>

    <div id="sim-panel" class="hidden">
        <div class="sim-head">
            <h2>Aide à la décision</h2>
            <div class="sim-sub">Cliquez sur la carte pour définir votre position</div>
        </div>
        <div class="sim-body">
            <button id="btn-mode-simulation" class="btn primary">Activer la simulation</button>
            <div class="sim-row"><label>Coordonnées</label><div class="sim-coords"><input id="sim-lat" placeholder="Latitude"><input id="sim-lng" placeholder="Longitude"></div></div>
            <div class="decision-actions">
                <button data-type="PHARMACY" class="btn decision-nearest">5 pharmacies</button>
                <button data-type="CLINIC" class="btn decision-nearest">5 cliniques</button>
                <button data-type="HOSPITAL" class="btn decision-nearest">5 hôpitaux</button>
                <button data-type="DOCTOR" class="btn decision-nearest">5 docteurs</button>
            </div>
            <ul id="sim-list"></ul>
        </div>
    </div>

    <?= view('statistiques/_modal', ['afficherBoutonStatistiques' => false]) ?>

    <script>
        window.SANTE_CARTE_CONFIG = {
            apiBase: '',
            osrmServiceUrl: 'https://router.project-osrm.org/route/v1',
            defaultColor: '#1a73e8'
        };
    </script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?= base_url('assets/js/carte/utils.js') ?>"></script>
    <script src="<?= base_url('assets/js/carte/map-core.js') ?>"></script>
    <script src="<?= base_url('assets/js/carte/filters-search.js') ?>"></script>
    <script src="<?= base_url('assets/js/carte/routing.js') ?>"></script>
    <script src="<?= base_url('assets/js/carte/spatial-analysis.js') ?>"></script>
    <script src="<?= base_url('assets/js/carte/decision.js') ?>"></script>
    <script src="<?= base_url('assets/js/statistiques.js') ?>"></script>
    <script src="<?= base_url('assets/js/carte/main.js') ?>"></script>
</body>

</html>
