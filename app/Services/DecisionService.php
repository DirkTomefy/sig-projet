<?php

namespace App\Services;

use App\Models\EtablissementSanteModel;
use CodeIgniter\Database\ConnectionInterface;

class DecisionService
{
    protected EtablissementSanteModel $etabModel;
    protected ConnectionInterface $db;

    public function __construct()
    {
        $this->etabModel = new EtablissementSanteModel();
        $this->db = \Config\Database::connect();
    }

    public function findNearestByType(float $lat, float $lng, string $typeLibelle, int $limit = 5): array
    {
        $lngF = (float) $lng;
        $latF = (float) $lat;
        $limI = (int) $limit;
        $type = strtoupper($typeLibelle);

        $sql = "SELECT es.id, es.nom, es.latitude, es.longitude, tes.libelle AS type_libelle, tes.couleur_carte, a.nom AS arrondissement_nom, " .
               "ST_Distance(es.geom::geography, ST_SetSRID(ST_MakePoint($lngF, $latF), 4326)::geography) AS distance_m " .
               "FROM etablissement_sante es " .
               "LEFT JOIN type_etablissement_sante tes ON tes.id = es.id_type " .
               "LEFT JOIN arrondissement a ON a.id = es.id_arrondissement " .
               "WHERE UPPER(tes.libelle) = " . $this->db->escape($type) . " " .
               "ORDER BY distance_m ASC LIMIT $limI";

        $query = $this->db->query($sql);

        return $query->getResultArray();
    }
}
