<?php

namespace App\Services;

use App\Models\ArrondissementModel;
use App\Models\EtablissementSanteModel;
use App\Models\TypeEtablissementSanteModel;

class CartographieService
{
    public function getEtablissementsPourCarte(): array
    {
        return (new EtablissementSanteModel())->getPourCarte();
    }

    public function getTypesPourCarte(): array
    {
        return (new TypeEtablissementSanteModel())->getTypesPourCarte();
    }

    public function getArrondissementsPourFiltre(): array
    {
        return (new ArrondissementModel())
            ->select('id, code, nom, superficie_km2')
            ->orderBy('nom', 'ASC')
            ->findAll();
    }

    public function getArrondissementsGeoJSON(): array
    {
        $features = [];

        foreach ((new ArrondissementModel())->getContoursGeoJSON() as $row) {
            if (empty($row['geojson'])) {
                continue;
            }

            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($row['geojson'], true),
                'properties' => [
                    'id' => (int) $row['id'],
                    'code' => $row['code'],
                    'nom' => $row['nom'],
                    'superficie_km2' => $row['superficie_km2'],
                ],
            ];
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }
}
