<?php
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
$routes->get('module1', 'CartographieController::index');
$routes->get('module2', 'EtablissementSanteController::module2');

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
