<?php if ($afficherBoutonStatistiques ?? true): ?>
    <!-- Bouton d'ouverture du modal statistiques -->
    <button
        type="button"
        class="btn btn-primary"
        data-bs-toggle="modal"
        data-bs-target="#statistiquesModal"
    >
        Voir les statistiques
    </button>
<?php endif; ?>

<!-- Modal statistiques -->
<div class="modal fade" id="statistiquesModal" tabindex="-1" aria-labelledby="statistiquesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="statistiquesModalLabel">
                        Statistiques sanitaires
                    </h5>
                    <p class="text-muted mb-0 small">
                        Analyse des pharmacies, hôpitaux et cliniques par rapport à la population.
                    </p>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <div class="modal-body">

                <!-- Filtre année -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="mb-0">Tableau de bord statistique</h6>
                        <div class="small text-muted">
                            Données calculées à partir des établissements, arrondissements et recensements.
                        </div>
                    </div>

                    <div style="min-width: 260px;">
                        <label for="filtre-annee-recensement" class="form-label small mb-1">
                            Année de recensement
                        </label>
                        <select id="filtre-annee-recensement" class="form-select form-select-sm">
                            <option value="">Dernier recensement disponible</option>
                        </select>
                    </div>
                </div>

                <!-- Erreur -->
                <div id="statistiques-error" class="alert alert-danger d-none"></div>

                <!-- Résumé principal -->
                <div class="row g-3 mb-4">

                    <div class="col-md-3">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <div class="text-muted small mb-1">Établissements</div>
                                <div id="stat-total-etablissements" class="h4 mb-0">—</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <div class="text-muted small mb-1">Types</div>
                                <div id="stat-total-types" class="h4 mb-0">—</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <div class="text-muted small mb-1">Arrondissements</div>
                                <div id="stat-total-arrondissements" class="h4 mb-0">—</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <div class="text-muted small mb-1">Superficie totale</div>
                                <div id="stat-superficie-totale" class="h4 mb-0">—</div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Indicateurs utiles -->
                <div class="row g-3 mb-4">

                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <div class="text-muted small mb-1">Population totale</div>
                                <div id="stat-population-totale" class="h5 mb-0">—</div>
                                <div class="small text-muted mt-2">
                                    Selon l’année de recensement sélectionnée.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <div class="text-muted small mb-1">Pharmacies / 100 000 habitants</div>
                                <div id="stat-pharmacies-100k" class="h5 mb-0">—</div>
                                <div class="small text-muted mt-2">
                                    <span id="stat-habitants-pharmacie">—</span> habitants par pharmacie.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <div class="text-muted small mb-1">Hôpitaux + cliniques / 100 000 habitants</div>
                                <div id="stat-hopitaux-cliniques-100k" class="h5 mb-0">—</div>
                                <div class="small text-muted mt-2">
                                    <span id="stat-habitants-hopital-clinique">—</span> habitants par hôpital/clinique.
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Graphiques -->
                <div class="row g-3 mb-4">

                    <div class="col-lg-5">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white">
                                <strong>Répartition par type</strong>
                            </div>
                            <div class="card-body">
                                <canvas id="chart-etablissements-type" height="260"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white">
                                <strong>Répartition par arrondissement</strong>
                            </div>
                            <div class="card-body">
                                <canvas id="chart-etablissements-arrondissement" height="260"></canvas>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Tableau de couverture sanitaire -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <strong>Indicateurs par arrondissement</strong>
                        <div class="small text-muted">
                            Focus sur les pharmacies, hôpitaux et cliniques par rapport à la population.
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Arrondissement</th>
                                    <th class="text-end">Population</th>
                                    <th class="text-end">Pharmacies</th>
                                    <th class="text-end">Hôp. + cliniques</th>
                                    <th class="text-end">Pharmacies / 100k hab.</th>
                                    <th class="text-end">Hôp. + cliniques / 100k hab.</th>
                                    <th class="text-end">Hab. / pharmacie</th>
                                    <th class="text-end">Hab. / hôp.-clinique</th>
                                </tr>
                            </thead>

                            <tbody id="table-couverture-body">
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        Ouvrez le modal pour charger les statistiques.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Fermer
                </button>
            </div>

        </div>
    </div>
</div>

<script>
    window.STAT_API_BASE = "<?= site_url('api/statistiques') ?>";
</script>
