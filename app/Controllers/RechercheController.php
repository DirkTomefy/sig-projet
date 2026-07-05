<?php

namespace App\Controllers;

use App\Services\RechercheService;

class RechercheController extends BaseController
{
    protected RechercheService $rechercheService;

    public function __construct()
    {
        $this->rechercheService = new RechercheService();
    }

    public function autocomplete()
    {
        $terme = (string) ($this->request->getGet('q') ?? '');
        $limit = (int) ($this->request->getGet('limit') ?? 8);

        return $this->response->setJSON(
            $this->rechercheService->autocomplete($terme, $limit)
        );
    }
}
