<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\StatistiqueService;

class StatistiqueController extends BaseController
{
    protected StatistiqueService $statistiqueService;

    public function __construct()
    {
        $this->statistiqueService = new StatistiqueService();
    }

    public function index()
    {
        return view('statistiques/index');
    }

    public function dashboard()
    {
        $annee = $this->getAnneeFromRequest();

        return $this->response->setJSON([
            'success' => true,
            'data' => $this->statistiqueService->getDashboard($annee),
        ]);
    }

    public function resume()
    {
        $annee = $this->getAnneeFromRequest();

        return $this->response->setJSON([
            'success' => true,
            'data' => $this->statistiqueService->getResume($annee),
        ]);
    }

    public function etablissementsParType()
    {
        return $this->response->setJSON([
            'success' => true,
            'data' => $this->statistiqueService->getEtablissementsParType(),
        ]);
    }

    public function etablissementsParArrondissement()
    {
        return $this->response->setJSON([
            'success' => true,
            'data' => $this->statistiqueService->getEtablissementsParArrondissement(),
        ]);
    }

    public function couvertureParArrondissement()
    {
        $annee = $this->getAnneeFromRequest();

        return $this->response->setJSON([
            'success' => true,
            'data' => $this->statistiqueService->getCouvertureParArrondissement($annee),
        ]);
    }

    public function repartitionTypeParArrondissement()
    {
        return $this->response->setJSON([
            'success' => true,
            'data' => $this->statistiqueService->getRepartitionTypeParArrondissement(),
        ]);
    }

    public function anneesRecensement()
    {
        return $this->response->setJSON([
            'success' => true,
            'data' => $this->statistiqueService->getAnneesRecensement(),
        ]);
    }

    private function getAnneeFromRequest(): ?int
    {
        $annee = $this->request->getGet('annee');

        if ($annee === null || $annee === '') {
            return null;
        }

        if (!is_numeric($annee)) {
            return null;
        }

        return (int) $annee;
    }
}