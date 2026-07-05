<?php

namespace App\Services;

use App\Models\EtablissementSanteModel;
use App\Models\RecensementModel;
use CodeIgniter\Database\ConnectionInterface;

class DecisionService
{
    protected EtablissementSanteModel $etabModel;
    protected RecensementModel $recensementModel;
    protected ConnectionInterface $db;

    public function __construct()
    {
        $this->etabModel = new EtablissementSanteModel();
        $this->recensementModel = new RecensementModel();
        $this->db = \Config\Database::connect();
    }

    public function findNearestPharmacies(float $lat, float $lng, int $limit = 5): array
    {
        $lngF = (float) $lng;
        $latF = (float) $lat;
        $limI = (int) $limit;

        $sql = "SELECT es.id, es.nom, es.latitude, es.longitude, tes.libelle AS type_libelle, tes.couleur_carte, a.nom AS arrondissement_nom, " .
               "ST_Distance(es.geom::geography, ST_SetSRID(ST_MakePoint($lngF, $latF), 4326)::geography) AS distance_m " .
               "FROM etablissement_sante es " .
               "LEFT JOIN type_etablissement_sante tes ON tes.id = es.id_type " .
               "LEFT JOIN arrondissement a ON a.id = es.id_arrondissement " .
               "ORDER BY distance_m ASC LIMIT $limI";

        $query = $this->db->query($sql);

        return $query->getResultArray();
    }

    public function populationInRadius(float $lat, float $lng, int $radiusMeters, ?int $annee = null): int
    {
        if ($annee !== null) {
                 $lngF = (float) $lng; $latF = (float) $lat; $radI = (int) $radiusMeters; $anneeI = (int) $annee;

                 $sql = "SELECT COALESCE(SUM(r.population),0) AS total FROM arrondissement a " .
                     "JOIN recensement r ON r.id_arrondissement = a.id AND r.annee = $anneeI " .
                     "WHERE ST_DWithin(a.geom::geography, ST_SetSRID(ST_MakePoint($lngF, $latF),4326)::geography, $radI)";

                 $row = $this->db->query($sql)->getRowArray();
                 return (int) ($row['total'] ?? 0);
        }

         $lngF = (float) $lng; $latF = (float) $lat; $radI = (int) $radiusMeters;

         $sql = "SELECT COALESCE(SUM(r.population),0) AS total FROM arrondissement a " .
             "JOIN recensement r ON r.id_arrondissement = a.id AND r.annee = (SELECT MAX(r2.annee) FROM recensement r2 WHERE r2.id_arrondissement = a.id) " .
             "WHERE ST_DWithin(a.geom::geography, ST_SetSRID(ST_MakePoint($lngF, $latF),4326)::geography, $radI)";

         $row = $this->db->query($sql)->getRowArray();
         return (int) ($row['total'] ?? 0);
    }

    public function computeQuota(int $population, int $nbPharmacies): array
    {
        $nb = max(1, $nbPharmacies);
        $perPharmacy = (int) ceil($population / $nb);

        return [
            'population' => $population,
            'nb_pharmacies' => $nbPharmacies,
            'per_pharmacy' => $perPharmacy,
        ];
    }
}

