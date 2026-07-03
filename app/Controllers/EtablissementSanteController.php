<?php
namespace App\Controllers;

use App\Models\EtablissementSanteModel;
use App\Models\TypeEtablissementSanteModel;
use App\Models\ArrondissementModel;

class EtablissementSanteController extends BaseController
{
    public function module2()
    {
        return view('sig/module2');
    }

    public function rechercher()
    {
        $model = new EtablissementSanteModel();

        $nom = $this->request->getGet('nom');
        $idType = $this->toIntOrNull($this->request->getGet('id_type'));
        $idArrondissement = $this->toIntOrNull($this->request->getGet('id_arrondissement'));

        return $this->response->setJSON(
            $model->rechercher($nom, $idType, $idArrondissement)
        );
    }

    public function types()
    {
        return $this->response->setJSON(
            (new TypeEtablissementSanteModel())->findAll()
        );
    }

    public function arrondissements()
    {
        return $this->response->setJSON(
            (new ArrondissementModel())->findAll()
        );
    }

    /**
     * Convertit une valeur GET ("" ou null inclus) en int ou null.
     */
    private function toIntOrNull($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int) $value;
    }
}