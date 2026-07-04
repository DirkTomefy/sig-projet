<?php

namespace App\Services;

use App\Models\ArrondissementModel;
use App\Models\EtablissementSanteModel;
use App\Models\RecensementModel;
use App\Models\TypeEtablissementSanteModel;

class AnalyseSpatialeService
{
    protected EtablissementSanteModel $etablissementModel;
    protected ArrondissementModel $arrondissementModel;
    protected RecensementModel $recensementModel;
    protected TypeEtablissementSanteModel $typeModel;

    public function __construct()
    {
        $this->etablissementModel = new EtablissementSanteModel();
        $this->arrondissementModel = new ArrondissementModel();
        $this->recensementModel = new RecensementModel();
        $this->typeModel = new TypeEtablissementSanteModel();
    }

    public function getCouvertureParArrondissement(?int $annee = null): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('v_couverture_arrondissement');
        $results = $builder->get()->getResultArray();

        $populations = $this->recensementModel->getPopulationByArrondissement($annee);
        $populationIndex = [];
        foreach ($populations as $p) {
            $populationIndex[(int)$p['id_arrondissement']] = (int)$p['population'];
        }

        foreach ($results as &$row) {
            $id = (int)$row['id'];
            $row['population'] = $populationIndex[$id] ?? null;
            if ($row['population'] !== null && $row['pourcentage_couvert'] !== null) {
                $row['population_couverte'] = round($row['population'] * ($row['pourcentage_couvert'] / 100));
                $row['population_non_couverte'] = $row['population'] - $row['population_couverte'];
            } else {
                $row['population_couverte'] = null;
                $row['population_non_couverte'] = null;
            }
        }

        return $results;
    }

    public function getBuffersGeoJSON(): array
    {
        $db = \Config\Database::connect();
        $query = $db->query("
            SELECT
                eb.id_etablissement,
                eb.nom,
                eb.id_type,
                eb.id_arrondissement,
                ST_AsGeoJSON(eb.buffer_geom) AS geojson
            FROM v_etablissement_buffer eb
        ");
        $rows = $query->getResultArray();
        $features = [];
        foreach ($rows as $row) {
            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($row['geojson'], true),
                'properties' => [
                    'id' => $row['id_etablissement'],
                    'nom' => $row['nom'],
                    'id_type' => $row['id_type'],
                    'id_arrondissement' => $row['id_arrondissement'],
                ],
            ];
        }
        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }

    public function getZonesNonCouvertesGeoJSON(): array
    {
        $db = \Config\Database::connect();
        $query = $db->query("
            SELECT
                a.id,
                a.nom,
                ST_AsGeoJSON(
                    ST_Difference(a.geom, COALESCE(bu.buffer_union, ST_GeomFromText('POLYGON EMPTY')))
                ) AS geojson
            FROM arrondissement a
            LEFT JOIN v_buffer_union_par_arrondissement bu ON bu.id_arrondissement = a.id
        ");
        $rows = $query->getResultArray();
        $features = [];
        foreach ($rows as $row) {
            if ($row['geojson'] === null) continue;
            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($row['geojson'], true),
                'properties' => [
                    'id' => $row['id'],
                    'nom' => $row['nom'],
                ],
            ];
        }
        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }

    public function getZonesCouvertesGeoJSON(): array
    {
        $db = \Config\Database::connect();
        $query = $db->query("
            SELECT
                a.id,
                a.nom,
                ST_AsGeoJSON(
                    ST_Intersection(a.geom, COALESCE(bu.buffer_union, ST_GeomFromText('POLYGON EMPTY')))
                ) AS geojson
            FROM arrondissement a
            LEFT JOIN v_buffer_union_par_arrondissement bu ON bu.id_arrondissement = a.id
        ");
        $rows = $query->getResultArray();
        $features = [];
        foreach ($rows as $row) {
            if ($row['geojson'] === null) continue;
            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($row['geojson'], true),
                'properties' => [
                    'id' => $row['id'],
                    'nom' => $row['nom'],
                ],
            ];
        }
        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }

    public function getStatistiquesGlobales(?int $annee = null): array
    {
        $db = \Config\Database::connect();
        $row = $db->query("
            SELECT
                SUM(superficie_totale) AS superficie_totale,
                SUM(superficie_couverte) AS superficie_couverte,
                SUM(superficie_non_couverte) AS superficie_non_couverte,
                AVG(pourcentage_couvert) AS pourcentage_moyen,
                SUM(nb_etablissements) AS total_etablissements
            FROM v_couverture_arrondissement
        ")->getRowArray();

        $populationTotale = $this->recensementModel->getPopulationTotale($annee);
        $row['population_totale'] = $populationTotale;
        if ($populationTotale !== null && $row['pourcentage_moyen'] !== null) {
            $row['population_couverte'] = round($populationTotale * ($row['pourcentage_moyen'] / 100));
            $row['population_non_couverte'] = $populationTotale - $row['population_couverte'];
        } else {
            $row['population_couverte'] = null;
            $row['population_non_couverte'] = null;
        }

        return $row;
    }

    public function getAnneesRecensement(): array
    {
        return $this->recensementModel->getAnneesDisponibles();
    }

   public function getPharmacies(): array
{
    $db = \Config\Database::connect();
    $query = $db->query("
        SELECT id, nom, ST_Y(geom) AS latitude, ST_X(geom) AS longitude
        FROM etablissement_sante
        WHERE id_type = 5
    ");
    return $query->getResultArray();
}
}