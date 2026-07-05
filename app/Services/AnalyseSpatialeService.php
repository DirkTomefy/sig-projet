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

    // La simulation est stockée en session
    private const SESSION_KEY = 'simulation_pharmacie';

    public function __construct()
    {
        $this->etablissementModel = new EtablissementSanteModel();
        $this->arrondissementModel = new ArrondissementModel();
        $this->recensementModel = new RecensementModel();
        $this->typeModel = new TypeEtablissementSanteModel();
    }

    // Récupère la simulation depuis la session
    private function getSimulation(): ?array
    {
        $session = session();
        return $session->get(self::SESSION_KEY) ?? null;
    }

    // Sauvegarde la simulation en session
    private function setSimulation(?array $data): void
    {
        $session = session();
        $session->set(self::SESSION_KEY, $data);
    }

    // --- Gestion de la simulation ---

    public function ajouterPharmacieSimulee(string $nom, float $longitude, float $latitude): array
    {
        // Vérifier si le point est non couvert (en incluant la simulation actuelle)
        if (!$this->estZoneNonCouverte($longitude, $latitude)) {
            return ['success' => false, 'message' => 'Cette zone est déjà couverte par une pharmacie.'];
        }

        // Stocker la simulation (écrase l'ancienne)
        $this->setSimulation([
            'nom' => $nom,
            'longitude' => $longitude,
            'latitude' => $latitude,
        ]);

        return ['success' => true, 'message' => 'Pharmacie simulée ajoutée avec succès.'];
    }

    public function resetSimulation(): void
    {
        $this->setSimulation(null);
    }

    private function estZoneNonCouverte(float $longitude, float $latitude): bool
    {
        $db = \Config\Database::connect();
        $sql = $this->buildBufferUnionQuery();
        $query = $db->query("
            SELECT NOT EXISTS (
                SELECT 1 FROM (
                    $sql
                ) AS buffers
                WHERE ST_Intersects(buffer_geom, ST_SetSRID(ST_MakePoint(?, ?), 4326))
            ) AS non_couvert
        ", [$longitude, $latitude]);
        $row = $query->getRowArray();
        return (bool) $row['non_couvert'];
    }

    // --- Méthodes publiques ---

    public function getCouvertureParArrondissement(?int $annee = null): array
    {
        $db = \Config\Database::connect();
        $sql = $this->buildCouvertureQuery();
        $results = $db->query($sql)->getResultArray();

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
        $sql = $this->buildBuffersQuery();
        $query = $db->query("
            SELECT
                id_etablissement,
                nom,
                id_type,
                id_arrondissement,
                ST_AsGeoJSON(buffer_geom) AS geojson
            FROM (
                $sql
            ) AS buffers
        ");
        $rows = $query->getResultArray();
        $features = [];
        foreach ($rows as $row) {
            if ($row['geojson'] === null) continue;
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
        return ['type' => 'FeatureCollection', 'features' => $features];
    }

    public function getZonesNonCouvertesGeoJSON(): array
    {
        $db = \Config\Database::connect();
        $sql = $this->buildBufferUnionQuery();
        $query = $db->query("
            SELECT
                a.id,
                a.nom,
                ST_AsGeoJSON(
                    ST_Difference(a.geom, COALESCE(bu.buffer_union, ST_GeomFromText('POLYGON EMPTY')))
                ) AS geojson
            FROM arrondissement a
            LEFT JOIN (
                SELECT id_arrondissement, ST_Union(buffer_geom) AS buffer_union
                FROM ($sql) AS buffers
                GROUP BY id_arrondissement
            ) bu ON bu.id_arrondissement = a.id
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
        return ['type' => 'FeatureCollection', 'features' => $features];
    }

    public function getZonesCouvertesGeoJSON(): array
    {
        $db = \Config\Database::connect();
        $sql = $this->buildBufferUnionQuery();
        $query = $db->query("
            SELECT
                a.id,
                a.nom,
                ST_AsGeoJSON(
                    ST_Intersection(a.geom, COALESCE(bu.buffer_union, ST_GeomFromText('POLYGON EMPTY')))
                ) AS geojson
            FROM arrondissement a
            LEFT JOIN (
                SELECT id_arrondissement, ST_Union(buffer_geom) AS buffer_union
                FROM ($sql) AS buffers
                GROUP BY id_arrondissement
            ) bu ON bu.id_arrondissement = a.id
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
        return ['type' => 'FeatureCollection', 'features' => $features];
    }

    public function getStatistiquesGlobales(?int $annee = null): array
    {
        $db = \Config\Database::connect();
        $sql = $this->buildCouvertureQuery();
        $row = $db->query("
            SELECT
                SUM(superficie_totale) AS superficie_totale,
                SUM(superficie_couverte) AS superficie_couverte,
                SUM(superficie_non_couverte) AS superficie_non_couverte,
                AVG(pourcentage_couvert) AS pourcentage_moyen,
                SUM(nb_etablissements) AS total_etablissements
            FROM ($sql) AS couv
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

    public function getToutesLesPharmacies(): array
    {
        $db = \Config\Database::connect();
        // Pharmacies réelles
        $reelles = $db->query("
            SELECT id, nom, ST_Y(geom) AS latitude, ST_X(geom) AS longitude
            FROM etablissement_sante
            WHERE id_type = 5
        ")->getResultArray();

        // Pharmacie simulée (depuis la session)
        $sim = $this->getSimulation();
        if ($sim !== null) {
            $reelles[] = [
                'id' => 999999,
                'nom' => $sim['nom'],
                'latitude' => $sim['latitude'],
                'longitude' => $sim['longitude'],
                'is_simulated' => true,
            ];
        }
        return $reelles;
    }

    // --- Requêtes SQL dynamiques avec CTE ---

    private function buildBufferUnionQuery(): string
    {
        // Pharmacies réelles (depuis la vue)
        $sql = "
            SELECT
                id_etablissement,
                nom,
                id_type,
                id_arrondissement,
                buffer_geom
            FROM v_etablissement_buffer
        ";

        // Ajouter la simulation si elle existe en session
        $sim = $this->getSimulation();
        if ($sim !== null) {
            $nom = addslashes($sim['nom']); // protection basique
            $lon = $sim['longitude'];
            $lat = $sim['latitude'];
            $sql .= "
                UNION ALL
                SELECT
                    -1 AS id_etablissement,
                    '{$nom}' AS nom,
                    5 AS id_type,
                    (SELECT a.id FROM arrondissement a WHERE ST_Intersects(a.geom, ST_SetSRID(ST_MakePoint({$lon}, {$lat}), 4326)) LIMIT 1) AS id_arrondissement,
                    ST_Buffer(ST_SetSRID(ST_MakePoint({$lon}, {$lat}), 4326)::geography, 500)::geometry AS buffer_geom
            ";
        }

        return $sql;
    }

    private function buildCouvertureQuery(): string
    {
        $union = $this->buildBufferUnionQuery();
        return "
            WITH buffers AS (
                $union
            ),
            buffer_union AS (
                SELECT id_arrondissement, ST_Union(buffer_geom) AS buffer_union
                FROM buffers
                GROUP BY id_arrondissement
            )
            SELECT
                a.id,
                a.code,
                a.nom,
                a.superficie_km2,
                ST_Area(a.geom) AS superficie_totale,
                COALESCE(ST_Area(ST_Intersection(a.geom, bu.buffer_union)), 0) AS superficie_couverte,
                COALESCE(ST_Area(a.geom) - ST_Area(ST_Intersection(a.geom, bu.buffer_union)), ST_Area(a.geom)) AS superficie_non_couverte,
                CASE
                    WHEN ST_Area(a.geom) > 0 THEN
                        (COALESCE(ST_Area(ST_Intersection(a.geom, bu.buffer_union)), 0) / ST_Area(a.geom)) * 100
                    ELSE 0
                END AS pourcentage_couvert,
                (SELECT COUNT(*) FROM buffers b WHERE b.id_arrondissement = a.id) AS nb_etablissements
            FROM arrondissement a
            LEFT JOIN buffer_union bu ON bu.id_arrondissement = a.id
        ";
    }

    private function buildBuffersQuery(): string
    {
        return $this->buildBufferUnionQuery();
    }
}