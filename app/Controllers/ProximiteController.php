<?php

namespace App\Controllers;

use App\Models\EtablissementSanteModel;
use CodeIgniter\Controller;

class ProximiteController extends Controller
{
    public function calculerProximite()
    {
        $idDepart      = $this->request->getGet('id_depart');
        $idDestination = $this->request->getGet('id_destination');

        if (!$idDepart || !$idDestination) {
            return $this->response->setJSON([
                'succes'  => false,
                'message' => 'Veuillez sélectionner deux établissements.'
            ])->setStatusCode(400);
        }

        $model = new EtablissementSanteModel();
        $calcul = $model->calculerDistanceEntreDeuxEtablissements((int)$idDepart, (int)$idDestination);

        return $this->response->setJSON([
            'succes'          => true,
            'distance_metres' => $calcul['distance_metres']
        ]);
    }
}