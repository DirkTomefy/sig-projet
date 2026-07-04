<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Analyse spatiale – Pharmacies (buffer 500 m)</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        #map { height: 600px; margin-bottom: 20px; }
        .controls { display: flex; gap: 20px; align-items: center; flex-wrap: wrap; margin-bottom: 15px; }
        .controls label { font-weight: bold; }
        table { border-collapse: collapse; width: 100%; font-size: 14px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #f4f4f4; }
        .legend { background: white; padding: 10px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.2); display: inline-flex; gap: 15px; flex-wrap: wrap; }
        .legend-item { display: flex; align-items: center; gap: 8px; }
        .color-box { width: 20px; height: 20px; border-radius: 3px; }
        .no-data { text-align: center; padding: 20px; color: #999; }
        .pharmacy-marker { background: #2ecc71; border-radius: 50%; width: 12px; height: 12px; border: 2px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.5); }
        .simulated-marker { background: #f1c40f; border-radius: 50%; width: 14px; height: 14px; border: 2px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.5); }
        #info-message { margin-top: 10px; padding: 10px; border-radius: 5px; display: none; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        #btnSimulation { padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        #btnSimulation.active { background: #dc3545; }
    </style>
</head>
<body>
    <h1>Analyse spatiale – Pharmacies (buffer 500 m)</h1>
    <button id="btnSimulation">Activer mode simulation</button>

    <div class="controls">
        <div>
            <label for="annee">Année de recensement :</label>
            <select id="annee">
                <option value="">Toutes</option>
            </select>
        </div>
        <button id="refreshBtn">Actualiser</button>
        <div class="legend">
            <div class="legend-item"><span class="color-box" style="background:rgba(0,0,255,0.3);"></span> Buffers (500m)</div>
            <div class="legend-item"><span class="color-box" style="background:rgba(0,255,0,0.3);"></span> Zones couvertes</div>
            <div class="legend-item"><span class="color-box" style="background:rgba(255,0,0,0.4);"></span> Zones non couvertes</div>
            <div class="legend-item"><span class="color-box" style="background:rgba(200,200,200,0.3); border:1px solid #999;"></span> Arrondissements</div>
            <div class="legend-item"><span class="color-box" style="background:#2ecc71; border-radius:50%; width:12px; height:12px; border:2px solid white;"></span> Pharmacies réelles</div>
            <div class="legend-item"><span class="color-box" style="background:#f1c40f; border-radius:50%; width:14px; height:14px; border:2px solid white;"></span> Pharmacies simulées</div>
        </div>
        <div style="font-size: 0.9em; color: #555;">
            💡 Activez le mode simulation puis cliquez sur une zone non couverte pour ajouter une pharmacie.
        </div>
    </div>

    <div id="map"></div>

    <div id="info-message"></div>

    <h2>Indicateurs par arrondissement</h2>
    <div id="tableContainer">
        <table>
            <thead>
                <tr>
                    <th>Arrondissement</th>
                    <th>Superficie (km²)</th>
                    <th>Couvert (%)</th>
                    <th>Population</th>
                    <th>Pop. couverte</th>
                    <th>Pop. non couverte</th>
                    <th>Nb établissements</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <tr><td colspan="7" class="no-data">Chargement des données...</td></tr>
            </tbody>
        </table>
    </div>

    <script>
        // Centrage sur Antananarivo
        const map = L.map('map').setView([-18.8792, 47.5079], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        let anneeSelectionnee = '';
        let modeSimulation = false;

        // Gestion du bouton mode simulation
        document.getElementById('btnSimulation').addEventListener('click', function() {
            modeSimulation = !modeSimulation;
            this.textContent = modeSimulation ? 'Désactiver mode simulation' : 'Activer mode simulation';
            this.classList.toggle('active', modeSimulation);
            map.getContainer().style.cursor = modeSimulation ? 'crosshair' : '';
        });

        // Chargement des années
        fetch('/analyse-spatiale/annees-recensement')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('annee');
                    data.data.forEach(item => {
                        const opt = document.createElement('option');
                        opt.value = item.annee;
                        opt.textContent = item.annee;
                        select.appendChild(opt);
                    });
                }
            });

        // Fonction de rafraîchissement
        function refreshData() {
            const annee = document.getElementById('annee').value;
            anneeSelectionnee = annee;

            // Nettoyer les couches (sauf fond)
            map.eachLayer(layer => {
                if (layer !== map._layers[Object.keys(map._layers)[0]]) {
                    map.removeLayer(layer);
                }
            });

            // 1. Buffers
            fetch('/analyse-spatiale/buffers')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        L.geoJSON(data.data, {
                            style: { color: 'blue', weight: 1, fillOpacity: 0.2 }
                        }).addTo(map);
                    }
                });

            // 2. Zones non couvertes
            fetch('/analyse-spatiale/zones-non-couvertes')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        L.geoJSON(data.data, {
                            style: { color: 'red', weight: 2, fillOpacity: 0.4 }
                        }).addTo(map);
                    }
                });

            // 3. Zones couvertes
            fetch('/analyse-spatiale/zones-couvertes')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        L.geoJSON(data.data, {
                            style: { color: 'green', weight: 1, fillOpacity: 0.3 }
                        }).addTo(map);
                    }
                })
                .catch(() => {});

            // 4. Marqueurs des pharmacies (réelles + simulées)
            fetch('/analyse-spatiale/pharmacies')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        data.data.forEach(pharmacy => {
                            if (pharmacy.latitude && pharmacy.longitude) {
                                const isSimulated = pharmacy.is_simulated || pharmacy.id === 999999;
                                const marker = L.marker([pharmacy.latitude, pharmacy.longitude], {
                                    icon: L.divIcon({
                                        className: isSimulated ? 'simulated-marker' : 'pharmacy-marker',
                                        iconSize: isSimulated ? [14, 14] : [12, 12]
                                    })
                                });
                                marker.addTo(map);
                                const label = isSimulated ? '🔶 ' : '';
                                marker.bindPopup(`<b>${label}${pharmacy.nom}</b>`);
                            }
                        });
                    }
                });

            // 5. Tableau des indicateurs
            const url = `/analyse-spatiale/couverture?annee=${annee}`;
            fetch(url)
                .then(r => r.json())
                .then(data => {
                    const tbody = document.getElementById('tableBody');
                    if (data.success && data.data.length > 0) {
                        tbody.innerHTML = '';
                        data.data.forEach(row => {
                            const tr = document.createElement('tr');
                            const superficie = row.superficie_km2 !== undefined ? parseFloat(row.superficie_km2).toFixed(2) : 'N/A';
                            const couvert = row.pourcentage_couvert !== null ? parseFloat(row.pourcentage_couvert).toFixed(1) + '%' : 'N/A';
                            const population = row.population ?? 'N/A';
                            const popCouverte = row.population_couverte ?? 'N/A';
                            const popNonCouverte = row.population_non_couverte ?? 'N/A';
                            const nbEtablissements = row.nb_etablissements ?? 0;
                            tr.innerHTML = `
                                <td>${row.nom || 'N/A'}</td>
                                <td>${superficie}</td>
                                <td>${couvert}</td>
                                <td>${population}</td>
                                <td>${popCouverte}</td>
                                <td>${popNonCouverte}</td>
                                <td>${nbEtablissements}</td>
                            `;
                            tbody.appendChild(tr);
                        });
                    } else {
                        tbody.innerHTML = `<tr><td colspan="7" class="no-data">Aucune donnée disponible</td></tr>`;
                    }
                })
                .catch(() => {
                    document.getElementById('tableBody').innerHTML = `<tr><td colspan="7" class="no-data">Erreur de chargement</td></tr>`;
                });
        }

        // Gestion du clic sur la carte (seulement en mode simulation)
        map.on('click', function(e) {
            if (!modeSimulation) return;

            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            const nom = prompt('Entrez le nom de la pharmacie simulée :', 'Pharmacie Test');
            if (!nom || nom.trim() === '') return;

            const messageDiv = document.getElementById('info-message');
            messageDiv.style.display = 'block';
            messageDiv.className = '';
            messageDiv.textContent = 'Vérification et ajout en cours...';

            fetch('/analyse-spatiale/simuler', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `nom=${encodeURIComponent(nom)}&longitude=${lng}&latitude=${lat}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    messageDiv.className = 'success';
                    messageDiv.textContent = '✅ ' + data.message;
                    // Rafraîchir toutes les données pour afficher le nouveau buffer
                    refreshData();
                } else {
                    messageDiv.className = 'error';
                    messageDiv.textContent = '❌ ' + data.message;
                }
            })
            .catch(err => {
                messageDiv.className = 'error';
                messageDiv.textContent = '❌ Erreur réseau : ' + err.message;
            });
        });

        document.getElementById('refreshBtn').addEventListener('click', refreshData);
        refreshData();
    </script>
</body>
</html>