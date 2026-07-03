let chartTypes = null;
let chartArrondissements = null;
let statistiquesDejaChargees = false;

const STAT_API_BASE = window.STAT_API_BASE || '/api/statistiques';

document.addEventListener('DOMContentLoaded', function () {
    initialiserStatistiquesModal();
    initialiserFiltreAnnee();
});

function initialiserStatistiquesModal() {
    const statistiquesModal = document.getElementById('statistiquesModal');

    if (!statistiquesModal) {
        return;
    }

    statistiquesModal.addEventListener('shown.bs.modal', async function () {
        if (!statistiquesDejaChargees) {
            await chargerAnneesRecensement();
            await chargerDashboard();
            statistiquesDejaChargees = true;
        }

        redimensionnerCharts();
    });
}

function initialiserFiltreAnnee() {
    const selectAnnee = document.getElementById('filtre-annee-recensement');

    if (!selectAnnee) {
        return;
    }

    selectAnnee.addEventListener('change', async function () {
        await chargerDashboard(this.value);
    });
}

async function chargerAnneesRecensement() {
    const select = document.getElementById('filtre-annee-recensement');

    if (!select) {
        return;
    }

    try {
        const response = await fetch(`${STAT_API_BASE}/annees-recensement`);
        const json = await response.json();

        if (!json.success) {
            return;
        }

        select.innerHTML = '<option value="">Dernier recensement disponible</option>';

        json.data.forEach(function (row) {
            const option = document.createElement('option');
            option.value = row.annee;
            option.textContent = row.annee;
            select.appendChild(option);
        });

    } catch (error) {
        console.error('Erreur lors du chargement des années de recensement :', error);
    }
}

async function chargerDashboard(annee = '') {
    afficherChargementTableau();

    try {
        let url = `${STAT_API_BASE}/dashboard`;

        if (annee) {
            url += `?annee=${encodeURIComponent(annee)}`;
        }

        const response = await fetch(url);
        const json = await response.json();

        if (!json.success) {
            afficherErreurStatistiques('Impossible de charger les statistiques.');
            return;
        }

        masquerErreurStatistiques();

        const data = json.data;

        afficherResume(data.resume);
        afficherChartTypes(data.etablissements_par_type || []);
        afficherChartArrondissements(data.etablissements_par_arrondissement || []);
        afficherTableCouverture(data.couverture_par_arrondissement || []);

        redimensionnerCharts();

    } catch (error) {
        console.error('Erreur lors du chargement du dashboard statistique :', error);
        afficherErreurStatistiques('Erreur de connexion au serveur.');
    }
}

function afficherResume(resume) {
    if (!resume) {
        return;
    }

    setText('stat-total-etablissements', formatNombre(resume.total_etablissements));
    setText('stat-total-types', formatNombre(resume.total_types));
    setText('stat-total-arrondissements', formatNombre(resume.total_arrondissements));
    setText('stat-superficie-totale', `${formatNombre(resume.superficie_totale_km2)} km²`);

    setText(
        'stat-population-totale',
        resume.population_totale !== null
            ? formatNombre(resume.population_totale)
            : 'Non disponible'
    );

    setText(
        'stat-etablissements-km2',
        resume.etablissements_par_km2 !== null
            ? formatNombre(resume.etablissements_par_km2)
            : 'Non disponible'
    );

    setText(
        'stat-etablissements-100k',
        resume.etablissements_par_100k_habitants !== null
            ? formatNombre(resume.etablissements_par_100k_habitants)
            : 'Non disponible'
    );
}

function afficherChartTypes(rows) {
    const canvas = document.getElementById('chart-etablissements-type');

    if (!canvas || typeof Chart === 'undefined') {
        return;
    }

    const labels = rows.map(row => row.libelle);
    const valeurs = rows.map(row => Number(row.total_etablissements ?? row.total ?? 0));
    const couleurs = rows.map(row => row.couleur_carte).filter(Boolean);

    if (chartTypes) {
        chartTypes.destroy();
    }

    const dataset = {
        label: 'Établissements',
        data: valeurs
    };

    if (couleurs.length === rows.length) {
        dataset.backgroundColor = rows.map(row => row.couleur_carte);
    }

    chartTypes = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [dataset]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: false
                }
            }
        }
    });
}

function afficherChartArrondissements(rows) {
    const canvas = document.getElementById('chart-etablissements-arrondissement');

    if (!canvas || typeof Chart === 'undefined') {
        return;
    }

    const labels = rows.map(row => row.nom);
    const valeurs = rows.map(row => Number(row.total_etablissements ?? 0));

    if (chartArrondissements) {
        chartArrondissements.destroy();
    }

    chartArrondissements = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Nombre d’établissements',
                data: valeurs
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true
                },
                title: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}

function afficherTableCouverture(rows) {
    const tbody = document.getElementById('table-couverture-body');

    if (!tbody) {
        return;
    }

    tbody.innerHTML = '';

    if (!rows || rows.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    Aucune donnée disponible.
                </td>
            </tr>
        `;
        return;
    }

    rows.forEach(function (row) {
        const tr = document.createElement('tr');

        tr.innerHTML = `
            <td>${escapeHtml(row.code ?? '')}</td>
            <td>${escapeHtml(row.nom ?? '')}</td>
            <td class="text-end">${formatNombre(row.total_etablissements)}</td>
            <td class="text-end">${formatNombre(row.superficie_km2)}</td>
            <td class="text-end">${row.population !== null ? formatNombre(row.population) : '—'}</td>
            <td class="text-end">${row.etablissements_par_km2 !== null ? formatNombre(row.etablissements_par_km2) : '—'}</td>
            <td class="text-end">${row.etablissements_par_100k_habitants !== null ? formatNombre(row.etablissements_par_100k_habitants) : '—'}</td>
            <td class="text-end">${row.habitants_par_etablissement !== null ? formatNombre(row.habitants_par_etablissement) : '—'}</td>
        `;

        tbody.appendChild(tr);
    });
}

function afficherChargementTableau() {
    const tbody = document.getElementById('table-couverture-body');

    if (!tbody) {
        return;
    }

    tbody.innerHTML = `
        <tr>
            <td colspan="8" class="text-center text-muted py-4">
                Chargement des statistiques...
            </td>
        </tr>
    `;
}

function afficherErreurStatistiques(message) {
    const zoneErreur = document.getElementById('statistiques-error');

    if (!zoneErreur) {
        alert(message);
        return;
    }

    zoneErreur.textContent = message;
    zoneErreur.classList.remove('d-none');
}

function masquerErreurStatistiques() {
    const zoneErreur = document.getElementById('statistiques-error');

    if (!zoneErreur) {
        return;
    }

    zoneErreur.textContent = '';
    zoneErreur.classList.add('d-none');
}

function redimensionnerCharts() {
    if (chartTypes) {
        chartTypes.resize();
    }

    if (chartArrondissements) {
        chartArrondissements.resize();
    }
}

function setText(id, value) {
    const element = document.getElementById(id);

    if (element) {
        element.textContent = value;
    }
}

function formatNombre(value) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    const number = Number(value);

    if (Number.isNaN(number)) {
        return '—';
    }

    return new Intl.NumberFormat('fr-FR').format(number);
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}