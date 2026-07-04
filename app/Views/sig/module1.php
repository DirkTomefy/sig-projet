<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIG Santé — Cartographie</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= base_url('assets/css/module1.css') ?>">
</head>

<body>

<div id="map"></div>

<!-- ================= BARRE DE RECHERCHE ================= -->
<div id="searchbar">
    <button class="icon-btn" id="btn-menu" title="Menu">
        <svg width="24" height="24" viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
    </button>
    <input type="text" id="recherche" placeholder="Rechercher un établissement de santé" autocomplete="off">
    <select id="filtre-arrondissement">
        <option value="">Tous les arrondissements</option>
    </select>
    <button class="icon-btn" id="btn-search" title="Rechercher">
        <svg width="22" height="22" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 10-.7.7l.27.28v.79l5 4.99L20.49 19zm-6 0A4.5 4.5 0 1114 9.5 4.5 4.5 0 019.5 14z"/></svg>
    </button>
    <div class="sep"></div>
    <button class="icon-btn" id="btn-directions" title="Réinitialiser">
        <svg width="22" height="22" viewBox="0 0 24 24"><path d="M21.71 11.29l-9-9a.996.996 0 00-1.41 0l-9 9a.996.996 0 000 1.41l9 9c.39.39 1.02.39 1.41 0l9-9a.996.996 0 000-1.41zM14 14.5V12h-4v3H8v-4c0-.55.45-1 1-1h5V7.5l3.5 3.5-3.5 3.5z"/></svg>
    </button>
</div>

<div id="chips"></div>
<div id="overlay"></div>
<div id="drawer">
    <div class="drawer-head">
        <div class="logo">✚</div>
        <div>
            <h1>SIG Santé</h1>
            <p>Commune Urbaine d'Antananarivo</p>
        </div>
    </div>
    <div class="drawer-body">
        <div class="drawer-titre">Couches</div>
        <label class="drawer-item">
            <input type="checkbox" id="chk-arrondissements" checked>
            Arrondissements
        </label>
        <div id="drawer-types"></div>

        <div class="drawer-sep"></div>

        <div class="drawer-titre">Légende</div>
        <div id="drawer-legende"></div>

        <div class="drawer-sep"></div>
        <div class="drawer-item" id="drawer-reset">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="#5f6368"><path d="M17.65 6.35A7.958 7.958 0 0012 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08A5.99 5.99 0 0112 18c-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
            Réinitialiser les filtres
        </div>
    </div>
</div>

<!-- ================= FOND DE CARTE ================= -->
<div id="basemap">
    <button data-base="plan" class="active">Plan</button>
    <button data-base="satellite">Satellite</button>
</div>


<div id="loader"><div><div class="spinner"></div>Chargement…</div></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>


const API_BASE = '';
const COULEUR_DEFAUT = '#1a73e8';

const LIBELLES_FR = {
    PHARMACY: 'Pharmacie', HOSPITAL: 'Hôpital', CLINIC: 'Clinique',
    DOCTOR: 'Médecins / centre médical', DENTIST: 'Dentiste',
};
function libelleFr(l){ return l ? (LIBELLES_FR[String(l).toUpperCase()] || l) : ''; }

const marqueurs = [];              // { data, marker }
const typesActifs = new Set();     // id_type affichés
let mapTypes = {};                 // id_type -> {libelle, couleur}


const map = L.map('map', { zoomControl: false }).setView([-18.8792, 47.5079], 12.5);
L.control.zoom({ position: 'bottomright' }).addTo(map);

const fondPlan = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; OpenStreetMap &copy; CARTO', maxZoom: 20
});
const fondSat = L.tileLayer(
    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
    { attribution: 'Esri', maxZoom: 20 }
);
fondPlan.addTo(map);

const coucheArrondissements = L.layerGroup().addTo(map);
const coucheMarqueurs = L.layerGroup().addTo(map);


function escapeHtml(s){
    if (s === null || s === undefined) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
function pinIcon(couleur){
    const svg = `<svg class="pin" width="17" height="24" viewBox="0 0 24 32">
        <path d="M12 0C5.4 0 0 5.4 0 12c0 8.4 12 20 12 20s12-11.6 12-20C24 5.4 18.6 0 12 0z" fill="${couleur}"/>
        <circle cx="12" cy="12" r="4.5" fill="#fff"/></svg>`;
    return L.divIcon({ html: svg, className: '', iconSize: [17,24], iconAnchor: [8.5,24], popupAnchor: [0,-23] });
}


function chargerArrondissements(){
    return $.get(API_BASE + "/api/carte/arrondissements").done(function(geojson){
        L.geoJSON(geojson, {
            style: { color:'#5f6368', weight:2, fillColor:'#1a73e8', fillOpacity:0.04 },
            onEachFeature: (f, layer) => {
                const p = f.properties || {};
                layer.bindPopup(
                    `<div class="gpop"><div class="gpop-titre">${escapeHtml(p.nom)}</div>` +
                    `<div class="gpop-row">Code : ${escapeHtml(p.code)}</div>` +
                    `<div class="gpop-row">Superficie : ${escapeHtml(p.superficie_km2)} km²</div></div>`
                );
                layer.on('mouseover', () => layer.setStyle({ fillOpacity:.14 }));
                layer.on('mouseout',  () => layer.setStyle({ fillOpacity:.04 }));
            }
        }).addTo(coucheArrondissements);
    });
}


function chargerTypes(){
    return $.get(API_BASE + "/api/carte/types").done(function(types){
        const $chips = $('#chips'), $dt = $('#drawer-types'), $lg = $('#drawer-legende');
        types.forEach(t => {
            const couleur = t.couleur_carte || COULEUR_DEFAUT;
            const libelle = libelleFr(t.libelle);
            mapTypes[t.id] = { libelle, couleur };
            typesActifs.add(String(t.id));

            $chips.append(
                `<div class="chip active" data-type="${t.id}">
                    <span class="dot" style="background:${escapeHtml(couleur)}"></span>${escapeHtml(libelle)}
                 </div>`);

            $dt.append(
                `<label class="drawer-item"><input type="checkbox" class="chk-type" data-type="${t.id}" checked>
                    ${escapeHtml(libelle)}</label>`);

            $lg.append(
                `<div class="drawer-item" style="cursor:default">
                    <span class="dot" style="background:${escapeHtml(couleur)}"></span>${escapeHtml(libelle)}</div>`);
        });
    });
}


/* =========================
   FILTRE ARRONDISSEMENT (repris de Module 2)
   Utilise l'endpoint /api/arrondissements (liste simple id/nom),
   distinct de /api/carte/arrondissements qui renvoie du GeoJSON.
========================= */
function chargerArrondissementsFiltre(){
    return $.get(API_BASE + "/api/arrondissements").done(function(data){
        const $sel = $('#filtre-arrondissement');
        data.forEach(a => {
            $sel.append(`<option value="${a.id}">${escapeHtml(a.nom)}</option>`);
        });
    }).fail(function(){
        console.error("Erreur lors du chargement du filtre arrondissement");
    });
}


function chargerEtablissements(){
    return $.get(API_BASE + "/api/carte/etablissements").done(function(data){
        data.forEach(item => {
            const lat = parseFloat(item.latitude), lng = parseFloat(item.longitude);
            if (isNaN(lat) || isNaN(lng)) return;
            const couleur = item.couleur_carte || COULEUR_DEFAUT;
            const marker = L.marker([lat,lng], { icon: pinIcon(couleur) }).bindPopup(popupHtml(item, couleur));
            marqueurs.push({ data: item, marker });
        });
        appliquerFiltres();
    });
}
function popupHtml(item, couleur){
    const icAdr = `<svg width="16" height="16" viewBox="0 0 24 24"><path d="M12 2a7 7 0 00-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 00-7-7zm0 9.5A2.5 2.5 0 1112 6.5a2.5 2.5 0 010 5z"/></svg>`;
    const icTel = `<svg width="16" height="16" viewBox="0 0 24 24"><path d="M6.6 10.8a15 15 0 006.6 6.6l2.2-2.2a1 1 0 011-.24 11 11 0 003.5.56 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1 11 11 0 00.56 3.5 1 1 0 01-.24 1z"/></svg>`;
    const icArr = `<svg width="16" height="16" viewBox="0 0 24 24"><path d="M12 2L2 7v13h6v-6h4v6h6V7z"/></svg>`;
    return `<div class="gpop">
        <div class="gpop-titre">${escapeHtml(item.nom)}</div>
        <span class="gpop-type" style="background:${escapeHtml(couleur)}">${escapeHtml(libelleFr(item.type_libelle))}</span>
        ${item.adresse ? `<div class="gpop-row">${icAdr}<span>${escapeHtml(item.adresse)}</span></div>` : ''}
        ${item.contact ? `<div class="gpop-row">${icTel}<span>${escapeHtml(item.contact)}</span></div>` : ''}
        ${item.arrondissement_nom ? `<div class="gpop-row">${icArr}<span>${escapeHtml(item.arrondissement_nom)}</span></div>` : ''}
    </div>`;
}


/* =========================
   FILTRES + ZOOM AUTOMATIQUE (repris de Module 2)
========================= */
function appliquerFiltres(){
    const texte = $('#recherche').val().trim().toLowerCase();
    const idArrondissement = $('#filtre-arrondissement').val();

    coucheMarqueurs.clearLayers();
    const bounds = [];

    marqueurs.forEach(({data, marker}) => {
        const okType = typesActifs.has(String(data.id_type));
        const okTxt  = !texte || String(data.nom).toLowerCase().includes(texte);
        const okArr  = !idArrondissement || String(data.id_arrondissement) === String(idArrondissement);

        if (okType && okTxt && okArr) {
            coucheMarqueurs.addLayer(marker);
            bounds.push(marker.getLatLng());
        }
    });

    // Zoom automatique dès qu'un filtre nom ou arrondissement est actif (comme Module 2)
    if ((texte || idArrondissement) && bounds.length > 0) {
        map.fitBounds(bounds, { padding: [60, 60], maxZoom: 16 });
    }
}

// Synchronise chips <-> cases du menu
function setType(id, actif){
    id = String(id);
    if (actif) typesActifs.add(id); else typesActifs.delete(id);
    $(`.chip[data-type="${id}"]`).toggleClass('active', actif).toggleClass('off', !actif);
    $(`.chk-type[data-type="${id}"]`).prop('checked', actif);
    appliquerFiltres();
}


let deb;
$('#recherche').on('input', () => { clearTimeout(deb); deb = setTimeout(appliquerFiltres, 200); });
$('#btn-search').on('click', appliquerFiltres);
$('#filtre-arrondissement').on('change', appliquerFiltres);

$('#chips').on('click', '.chip', function(){
    const id = String($(this).data('type'));
    setType(id, !typesActifs.has(id));
});
$('#drawer-types').on('change', '.chk-type', function(){
    setType($(this).data('type'), this.checked);
});

// Menu latéral
function ouvrirMenu(o){
    $('#drawer').toggleClass('open', o);
    $('#overlay').toggleClass('show', o);
    $('body').toggleClass('menu-open', o);

    if (o) {
        // La barre de recherche vient se placer en haut du menu
        $('#searchbar').addClass('in-drawer').prependTo('#drawer');
    } else {
        // On la remet flottante après la fin de l'animation du drawer
        setTimeout(() => $('#searchbar').removeClass('in-drawer').prependTo('body'), 260);
    }
}
$('#btn-menu').on('click', () => ouvrirMenu(!$('#drawer').hasClass('open')));
$('#overlay').on('click', () => ouvrirMenu(false));

// Arrondissements
$('#chk-arrondissements').on('change', function(){
    this.checked ? map.addLayer(coucheArrondissements) : map.removeLayer(coucheArrondissements);
});

// Reset
$('#drawer-reset, #btn-directions').on('click', function(){
    $('#recherche').val('');
    $('#filtre-arrondissement').val('');
    Object.keys(mapTypes).forEach(id => setType(id, true));
    appliquerFiltres();
});

// Fond de carte
$('#basemap button').on('click', function(){
    $('#basemap button').removeClass('active'); $(this).addClass('active');
    if ($(this).data('base') === 'satellite'){ map.removeLayer(fondPlan); fondSat.addTo(map); }
    else { map.removeLayer(fondSat); fondPlan.addTo(map); }
});


$(document).ready(function(){
    $.when(chargerArrondissements(), chargerTypes(), chargerArrondissementsFiltre())
        .then(chargerEtablissements)
        .fail(function() {
            console.error("Erreur lors du chargement des données cartographiques");
        })
        .always(() => $('#loader').addClass('hide'));
});
</script>

</body>
</html>