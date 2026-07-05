<?php

use CodeIgniter\Router\RouteCollection;

use App\Controllers\ProximiteController;
use App\Controllers\AnalyseSpatialeController;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
$routes->get('module1', 'CartographieController::index');
$routes->get('module2', 'EtablissementSanteController::module2');
$routes->get('proximite', 'CartographieController::proximite');
$routes->get('proximite/calculerProximite', 'ProximiteController::calculerProximite');

// Routes API Module 1 - Cartographie
$routes->get('api/carte/etablissements', 'CartographieController::etablissements');
$routes->get('api/carte/types', 'CartographieController::types');
$routes->get('api/carte/arrondissements', 'CartographieController::arrondissements');

// Routes API Module 2
$routes->get('api/etablissement/rechercher', 'EtablissementSanteController::rechercher');
$routes->get('api/types', 'EtablissementSanteController::types');
$routes->get('api/arrondissements', 'EtablissementSanteController::arrondissements');

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

// Routes API Decision (Module 5)
$routes->post('api/decision/simulate', 'DecisionController::simulate');
$routes->get('api/decision/nearest', 'DecisionController::nearest');
$routes->get('api/etablissement/proximite','ProximiteController::calculerProximite');

$routes->group('analyse-spatiale', ['namespace' => 'App\Controllers'], function ($routes) {
    $routes->get('/', [AnalyseSpatialeController::class, 'index']);
    $routes->get('couverture', [AnalyseSpatialeController::class, 'couverture']);
    $routes->get('buffers', [AnalyseSpatialeController::class, 'buffers']);
    $routes->get('zones-non-couvertes', [AnalyseSpatialeController::class, 'zonesNonCouvertes']);
    $routes->get('zones-couvertes', [AnalyseSpatialeController::class, 'zonesCouvertes']); // ← AJOUT
    $routes->get('statistiques', [AnalyseSpatialeController::class, 'statistiques']);
    $routes->get('annees-recensement', [AnalyseSpatialeController::class, 'anneesRecensement']);
    $routes->get('pharmacies', [AnalyseSpatialeController::class, 'pharmacies']);
    $routes->post('simuler', [AnalyseSpatialeController::class, 'simuler']);
});