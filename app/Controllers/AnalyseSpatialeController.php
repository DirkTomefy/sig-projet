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
        return view('analyse_spatiale/index');
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
        'data' => $this->analyseSpatialeService->getPharmacies(),
    ]);
}
}