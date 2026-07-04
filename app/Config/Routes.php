<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->group('api/statistiques', static function ($routes) {
    $routes->get('dashboard', 'StatistiqueController::dashboard');
    $routes->get('resume', 'StatistiqueController::resume');
    $routes->get('etablissements-par-type', 'StatistiqueController::etablissementsParType');
    $routes->get('etablissements-par-arrondissement', 'StatistiqueController::etablissementsParArrondissement');
    $routes->get('couverture-par-arrondissement', 'StatistiqueController::couvertureParArrondissement');
    $routes->get('repartition-type-arrondissement', 'StatistiqueController::repartitionTypeParArrondissement');
    $routes->get('annees-recensement', 'StatistiqueController::anneesRecensement');
});

$routes->get('proximite','ProximiteController::calculerProximite');