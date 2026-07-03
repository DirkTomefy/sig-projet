<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Analyse de Distance entre Etablissement de Santé</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        /* Conteneur principal de la carte prenant tout l'écran */
        #map {
            height: 100vh;
            width: 100vw;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1;
        }
        /* Barre de filtre positionnée de manière fixe tout en haut */
        .top-filter-bar {
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            width: 90%;
            max-width: 500px;
            background: white;
            padding: 10px 15px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        /* Style de la boîte d'affichage personnalisée pour le bas à gauche */
        .info-distance-box {
            background: white;
            padding: 12px 16px;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            font-size: 13px;
            max-width: 280px;
            border-left: 4px solid #0d6efd;
        }
    </style>
</head>
<body>

<div class="top-filter-bar">
    <div class="row align-items-center g-2">
        <div class="col-4">
            <label for="filtre-type" class="form-label small mb-0 fw-bold text-secondary">Type :</label>
        </div>
        <div class="col-8">
            <select id="filtre-type" class="form-select form-select-sm">
                <option value="">Tous les établissements</option>
                <?php if(!empty($types_etablissements)): ?>
                    <?php foreach($types_etablissements as $type): ?>
                        <option value="<?= $type['id'] ?>"><?= esc($type['libelle']) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
    </div>
</div>

<div id="map"></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Initialisation de la carte centrée sur Antananarivo
    var map = L.map('map').setView([-18.910, 47.525], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // 3. Ajout du panneau d'affichage Leaflet en BAS À GAUCHE (bottomleft)
    var panneauDistance = L.control({ position: 'bottomleft' });

    panneauDistance.onAdd = function (map) {
        var div = L.DomUtil.create('div', 'info-distance-box');
        div.innerHTML = `
            <h6 class="mb-1 text-primary fw-bold">Mesure de Proximité</h6>
            <div id="distance-status" class="text-muted small">
                Cliquez sur un premier établissement pour définir le départ...
            </div>
        `;
        return div;
    };
    panneauDistance.addTo(map);

    // Variables de contrôle d'état pour les sélections
    let idEtabDepart = null;
    let idEtabDestination = null;
    let coordonnesDepart = null;
    let ligneMesure = null;
    let listeMarqueurs = [];

    // Données des établissements passées depuis le contrôleur PHP
    const etablissementsData = <?= json_encode($etablissements ?? []) ?>;

    // Fonction pour générer les marqueurs selon le filtre actif
    function afficherEtablissements(idTypeFiltre = null) {
        // Nettoyage des anciens marqueurs
        listeMarqueurs.forEach(m => map.removeLayer(m));
        listeMarqueurs = [];

        etablissementsData.forEach(etab => {
            // Filtrage côté client pour éviter des requêtes inutiles
            if (idTypeFiltre && etab.id_type != idTypeFiltre) return;

            // Création d'un marqueur circulaire simple ou personnalisé
            let marqueur = L.circleMarker([parseFloat(etab.latitude), parseFloat(etab.longitude)], {
                radius: 8,
                fillColor: etab.couleur_carte || '#0d6efd',
                color: '#fff',
                weight: 2,
                fillOpacity: 0.9
            }).addTo(map).bindPopup(`<b>${etab.nom}</b><br><small>${etab.adresse || ''}</small>`);

            // On injecte l'identifiant de la BDD dans l'objet Leaflet
            marqueur.id_etab = etab.id;
            marqueur.nom_etab = etab.nom;

            // Événement exclusif au clic sur un établissement
            marqueur.on('click', function(e) {
                L.DomEvent.stopPropagation(e); // Empêche le clic de propager sur le fond de carte
                gererClicEtablissement(this);
            });

            listeMarqueurs.push(marqueur);
        });
    }

    // Gestion de l'algorithme de clic alternatif (Départ -> Destination)
    function gererClicEtablissement(marker) {
        const id = marker.id_etab;
        const nom = marker.nom_etab;
        const latlng = marker.getLatLng();

        const statusDiv = document.getElementById('distance-status');

        // Cas 1 : Aucun point sélectionné -> C'est le point de départ
        if (!idEtabDepart) {
            idEtabDepart = id;
            coordonnesDepart = latlng;
            
            statusDiv.innerHTML = `
                <span class="badge bg-success mb-1">Départ</span><br>
                <strong class="text-dark">${nom}</strong>
                <hr class="my-1">
                <span class="text-primary animate-pulse small">👉 Sélectionnez l'établissement de destination...</span>
            `;

            if (ligneMesure) map.removeLayer(ligneMesure);
            return;
        }

        // Cas 2 : Le départ est déjà sélectionné -> C'est la destination
        if (idEtabDepart && !idEtabDestination) {
            if (id === idEtabDepart) {
                reinitialiserAnalyse();
                return;
            }

            idEtabDestination = id;

            // Tracer le vecteur direct reliant les deux sur la carte
            ligneMesure = L.polyline([coordonnesDepart, latlng], {
                color: '#198754',
                weight: 3,
                dashArray: '6, 8'
            }).addTo(map);

            // Appel AJAX de calcul de distance vers ton contrôleur CodeIgniter
            fetch(`<?= site_url('proximite/calculerProximite') ?>?id_depart=${idEtabDepart}&id_destination=${idEtabDestination}`)
                .then(res => res.json())
                .then(res => {
                    if (res.succes) {
                        const distKm = (res.distance_metres / 1000).toFixed(2);
                        const distM = Math.round(res.distance_metres);

                        statusDiv.innerHTML = `
                            <div class="mb-1"><span class="badge bg-success">Départ</span> <span class="small text-truncate">${listeMarqueurs.find(m => m.id_etab == idEtabDepart).nom_etab}</span></div>
                            <div class="mb-2"><span class="badge bg-danger">Arrivée</span> <span class="small text-truncate">${nom}</span></div>
                            <div class="p-2 bg-light border rounded text-center">
                                <span class="fs-5 fw-bold text-dark">${distKm} km</span><br>
                                <small class="text-muted">(${distM.toLocaleString()} mètres)</small>
                            </div>
                            <button class="btn btn-sm btn-outline-danger w-100 mt-2 py-0" onclick="reinitialiserAnalyse()" style="font-size: 11px;">Annuler la sélection</button>
                        `;
                    }
                })
                .catch(err => console.error("Erreur de calcul spatial :", err));
        }
    }

    function reinitialiserAnalyse() {
        idEtabDepart = null;
        idEtabDestination = null;
        coordonnesDepart = null;
        if (ligneMesure) map.removeLayer(ligneMesure);
        document.getElementById('distance-status').innerHTML = 'Cliquez sur un premier établissement pour définir le départ...';
    }

    // Gestion du filtre en haut
    document.getElementById('filtre-type').addEventListener('change', function() {
        reinitialiserAnalyse();
        afficherEtablissements(this.value);
    });

    // Chargement initial
    afficherEtablissements();
</script>
</body>
</html>