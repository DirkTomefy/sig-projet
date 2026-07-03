<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\EtablissementSanteModel;

class EtablissementSanteController extends BaseController
{
    public function rechercher(): ResponseInterface
    {
        $nom = $this->request->getGet('nom');

        $id_type = $this->request->getGet('id_type');
        $id_type = ($id_type !== null && $id_type !== '') ? (int)$id_type : null;

        $id_arrondissement = $this->request->getGet('id_arrondissement');
        $id_arrondissement = ($id_arrondissement !== null && $id_arrondissement !== '') ? (int)$id_arrondissement : null;

        $etablissementSanteModel = new EtablissementSanteModel();

        $resultats = $etablissementSanteModel->rechercher(
            $nom,
            $id_type,
            $id_arrondissement
        );

        return $this->response->setJSON($resultats);
    }
}