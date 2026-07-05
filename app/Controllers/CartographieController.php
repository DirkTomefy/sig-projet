<?php

namespace App\Controllers;

use App\Services\CartographieService;

/**
 * Module 1 : Cartographie
 *  - affichage de la carte
 *  - affichage des établissements (pharmacies, hôpitaux, cliniques…) par type
 *  - popups d'informations
 */
class CartographieController extends BaseController
{
    protected CartographieService $cartographieService;

    public function __construct()
    {
        $this->cartographieService = new CartographieService();
    }

    /**
     * Redirige la racine de l'application vers la carte principale.
     */
    public function accueil()
    {
        return redirect()->to(site_url('carte'));
    }

    /**
     * Page de la carte.
     */
    public function index()
    {
        return view('sig/carte_sante');
    }

    /**
     * API : tous les établissements de santé, avec type + couleur, pour la carte.
     */
    public function etablissements()
    {
        return $this->response->setJSON($this->cartographieService->getEtablissementsPourCarte());
    }

    /**
     * API : liste des types (libellé + couleur) pour construire les couches et la légende.
     */
    public function types()
    {
        return $this->response->setJSON($this->cartographieService->getTypesPourCarte());
    }

    public function arrondissementsFiltre()
    {
        return $this->response->setJSON($this->cartographieService->getArrondissementsPourFiltre());
    }

    /**
     * API : contours des arrondissements au format GeoJSON (FeatureCollection).
     */
    public function arrondissements()
    {
        return $this->response->setJSON($this->cartographieService->getArrondissementsGeoJSON());
    }
}
