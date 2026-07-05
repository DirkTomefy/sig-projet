<?php

use CodeIgniter\Router\RouteCollection;

use App\Controllers\AnalyseSpatialeController;

/** @var RouteCollection $routes */
$routes->get('/', 'CartographieController::accueil');
$routes->get('carte', 'CartographieController::index');

// Routes API Cartographie
$routes->get('api/carte/etablissements', 'CartographieController::etablissements');
$routes->get('api/carte/types', 'CartographieController::types');
$routes->get('api/carte/arrondissements', 'CartographieController::arrondissements');
$routes->get('api/carte/arrondissements-liste', 'CartographieController::arrondissementsFiltre');
$routes->get('api/recherche/autocomplete', 'RechercheController::autocomplete');

// Routes API stats
$routes->group('api/statistiques', static function ($routes) {
    $routes->get('dashboard', 'StatistiqueController::dashboard');
    $routes->get('resume', 'StatistiqueController::resume');
    $routes->get('etablissements-par-type', 'StatistiqueController::etablissementsParType');
    $routes->get('etablissements-par-arrondissement', 'StatistiqueController::etablissementsParArrondissement');
    $routes->get('couverture-par-arrondissement', 'StatistiqueController::couvertureParArrondissement');
    $routes->get('repartition-type-arrondissement', 'StatistiqueController::repartitionTypeParArrondissement');
    $routes->get('annees-recensement', 'StatistiqueController::anneesRecensement');
});

// Routes API Decision
$routes->get('api/decision/nearest', 'DecisionController::nearest');

$routes->group('analyse-spatiale', ['namespace' => 'App\Controllers'], function ($routes) {
    $routes->get('buffers', [AnalyseSpatialeController::class, 'buffers']);
    $routes->get('zones-non-couvertes', [AnalyseSpatialeController::class, 'zonesNonCouvertes']);
});
