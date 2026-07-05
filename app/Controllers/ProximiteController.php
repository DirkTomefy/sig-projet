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
        $itineraire = $model->calculerItineraireRoutier((int)$idDepart, (int)$idDestination);

        return $this->response->setJSON([
            'succes'          => true,
            'distance_metres' => (float) $itineraire['distance_route_metres'],
            'route_geom'      => $itineraire['geojson_route'] ? json_decode($itineraire['geojson_route']) : null
        ]);
    }
}