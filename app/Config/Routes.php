<?php
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
$routes->get('module2', 'EtablissementSanteController::module2');

// Routes API Module 2
$routes->get('api/etablissement/rechercher', 'EtablissementSanteController::rechercher');
$routes->get('api/types', 'EtablissementSanteController::types');
$routes->get('api/arrondissements', 'EtablissementSanteController::arrondissements');