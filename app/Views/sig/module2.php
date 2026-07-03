<!DOCTYPE html>
<html>
<head>
    <title>Module 2 - SIG Santé</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>

    <style>
        body { margin: 0; font-family: Arial; }

        .panel {
            padding: 10px;
            background: #f5f5f5;
        }

        #map {
            height: 90vh;
            width: 100%;
        }

        input, select {
            padding: 5px;
            margin-right: 10px;
        }
    </style>
</head>

<body>

<div class="panel">

    <input type="text" id="nom" placeholder="Recherche nom">

    <select id="type">
        <option value="">Type</option>
    </select>

    <select id="arrondissement">
        <option value="">Arrondissement</option>
    </select>

</div>

<div id="map"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
/* =========================
   0. CONFIG API (URL relative -> évite le CORS)
========================= */
const API_BASE = ''; // '' si le front est servi par CodeIgniter lui-même

/* =========================
   1. CARTE
========================= */
let map = L.map('map').setView([-18.8792, 47.5079], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: 'SIG Santé'
}).addTo(map);
let markersLayer = L.layerGroup().addTo(map);

/* =========================
   Utilitaire anti-XSS
========================= */
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/* =========================
   2. CHARGER TYPES
========================= */
function loadTypes() {
    $.get(API_BASE + "/api/types")
        .done(function (data) {
            $('#type').empty().append('<option value="">Type</option>');
            data.forEach(t => {
                $('#type').append(
                    `<option value="${t.id}">${escapeHtml(t.libelle)}</option>`
                );
            });
        })
        .fail(function () {
            console.error("Erreur lors du chargement des types");
        });
}

/* =========================
   3. CHARGER ARRONDISSEMENTS
========================= */
function loadArrondissements() {
    $.get(API_BASE + "/api/arrondissements")
        .done(function (data) {
            $('#arrondissement').empty().append('<option value="">Arrondissement</option>');
            data.forEach(a => {
                $('#arrondissement').append(
                    `<option value="${a.id}">${escapeHtml(a.nom)}</option>`
                );
            });
        })
        .fail(function () {
            console.error("Erreur lors du chargement des arrondissements");
        });
}

/* =========================
   4. RECHERCHE API
========================= */
function rechercher() {
    $.ajax({
        url: API_BASE + "/api/etablissement/rechercher",
        method: "GET",
        data: {
            nom: $('#nom').val(),
            id_type: $('#type').val(),
            id_arrondissement: $('#arrondissement').val()
        },
        success: function (data) {
            afficherCarte(data);
        },
        error: function (xhr) {
            console.error("Erreur recherche :", xhr.status, xhr.statusText);
            markersLayer.clearLayers();
        }
    });
}

/* =========================
   5. AFFICHAGE + ZOOM
========================= */
function afficherCarte(data) {
    markersLayer.clearLayers();
    let bounds = [];

    data.forEach(item => {
        if (!item.latitude || !item.longitude) return;

        let marker = L.marker([item.latitude, item.longitude])
            .bindPopup(
                `<b>${escapeHtml(item.nom)}</b><br>${escapeHtml(item.adresse || '')}`
            );

        markersLayer.addLayer(marker);
        bounds.push([item.latitude, item.longitude]);
    });

    if (bounds.length > 0) {
        map.fitBounds(bounds, {
            padding: [50, 50],
            maxZoom: 16
        });
    }
}

/* =========================
   6. EVENTS (avec debounce sur la saisie texte)
========================= */
let debounceTimer;
$('#nom').on('keyup', function () {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(rechercher, 300);
});
$('#type, #arrondissement').on('change', rechercher);

/* =========================
   7. INIT
========================= */
$(document).ready(function () {
    loadTypes();
    loadArrondissements();
    rechercher(); // charger tout au début
});
</script>

</body>
</html>