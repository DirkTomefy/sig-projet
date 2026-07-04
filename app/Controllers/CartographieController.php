<?php

namespace App\Controllers;

use App\Models\EtablissementSanteModel;
use App\Models\TypeEtablissementSanteModel;
use App\Models\ArrondissementModel;

/**
 * Module 1 : Cartographie
 *  - affichage de la carte
 *  - affichage des établissements (pharmacies, hôpitaux, cliniques…) par type
 *  - popups d'informations
 */
class CartographieController extends BaseController
{
    /**
     * Page de la carte.
     */
    public function index()
    {
        return view('sig/module1');
    }

    /**
     * API : tous les établissements de santé, avec type + couleur, pour la carte.
     */
    public function etablissements()
    {
        $data = (new EtablissementSanteModel())->getPourCarte();

        return $this->response->setJSON($data);
    }

    /**
     * API : liste des types (libellé + couleur) pour construire les couches et la légende.
     */
    public function types()
    {
        $data = (new TypeEtablissementSanteModel())->getTypesPourCarte();

        return $this->response->setJSON($data);
    }

    /**
     * API : contours des arrondissements au format GeoJSON (FeatureCollection).
     */
    public function arrondissements()
    {
        $rows = (new ArrondissementModel())->getContoursGeoJSON();

        $features = [];
        foreach ($rows as $row) {
            if (empty($row['geojson'])) {
                continue;
            }

            $features[] = [
                'type'       => 'Feature',
                'geometry'   => json_decode($row['geojson'], true),
                'properties' => [
                    'id'             => (int) $row['id'],
                    'code'           => $row['code'],
                    'nom'            => $row['nom'],
                    'superficie_km2' => $row['superficie_km2'],
                ],
            ];
        }

        return $this->response->setJSON([
            'type'     => 'FeatureCollection',
            'features' => $features,
        ]);
    }
}
