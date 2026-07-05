<?php

namespace App\Controllers;

use App\Services\AnalyseSpatialeService;

class AnalyseSpatialeController extends BaseController
{
    protected AnalyseSpatialeService $analyseSpatialeService;

    public function __construct()
    {
        $this->analyseSpatialeService = new AnalyseSpatialeService();
    }

    
    public function index()
    {
        return redirect()->to(site_url('carte'));
    }

    public function couverture()
    {
        $annee = $this->getAnneeFromRequest();
        return $this->response->setJSON([
            'success' => true,
            'data' => $this->analyseSpatialeService->getCouvertureParArrondissement($annee),
        ]);
    }

    
    public function buffers()
    {
        return $this->response->setJSON([
            'success' => true,
            'data' => $this->analyseSpatialeService->getBuffersGeoJSON(),
        ]);
    }

   
    public function zonesNonCouvertes()
    {
        return $this->response->setJSON([
            'success' => true,
            'data' => $this->analyseSpatialeService->getZonesNonCouvertesGeoJSON(),
        ]);
    }

   
    public function statistiques()
    {
        $annee = $this->getAnneeFromRequest();
        return $this->response->setJSON([
            'success' => true,
            'data' => $this->analyseSpatialeService->getStatistiquesGlobales($annee),
        ]);
    }

   
    public function anneesRecensement()
    {
        return $this->response->setJSON([
            'success' => true,
            'data' => $this->analyseSpatialeService->getAnneesRecensement(),
        ]);
    }

    private function getAnneeFromRequest(): ?int
    {
        $annee = $this->request->getGet('annee');
        if ($annee === null || $annee === '' || !is_numeric($annee)) {
            return null;
        }
        return (int) $annee;
    }

    public function pharmacies()
{
    return $this->response->setJSON([
        'success' => true,
        'data' => $this->analyseSpatialeService->getToutesLesPharmacies(),
    ]);
}

public function zonesCouvertes()
{
    return $this->response->setJSON([
        'success' => true,
        'data' => $this->analyseSpatialeService->getZonesCouvertesGeoJSON(),
    ]);
}

   public function simuler()
{
    $nom = $this->request->getPost('nom');
    $longitude = (float) $this->request->getPost('longitude');
    $latitude = (float) $this->request->getPost('latitude');

    if (empty($nom) || !$longitude || !$latitude) {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Paramètres manquants (nom, longitude, latitude)'
        ]);
    }

    $result = $this->analyseSpatialeService->ajouterPharmacieSimulee($nom, $longitude, $latitude);
    return $this->response->setJSON($result);
}


}
