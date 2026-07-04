<?php

namespace App\Models;

use CodeIgniter\Model;

class RouteSegmentModel extends Model
{
    protected $table = 'route_segments';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'id_arrondissement',
        'arrondissement_nom',
        'nom',
        'type_route',
        'surface',
        'smoothness',
        'largeur_m',
        'nb_voies',
        'sens_unique',
        'bridge',
        'layer_value',
        'source',
        'target',
        'longueur_m',
        'cost',
        'reverse_cost',
        'geom',
    ];

    public function countTotal(): int
    {
        return (int) $this->db
            ->table($this->table)
            ->countAllResults();
    }

    public function getPourCarte(?int $idArrondissement = null): array
    {
        $builder = $this->db
            ->table($this->table . ' rs')
            ->select('rs.id, rs.id_arrondissement, rs.arrondissement_nom')
            ->select('rs.nom, rs.type_route, rs.surface, rs.smoothness')
            ->select('rs.largeur_m, rs.nb_voies, rs.sens_unique, rs.bridge')
            ->select('rs.longueur_m')
            ->select('ST_AsGeoJSON(rs.geom) AS geojson', false)
            ->orderBy('rs.type_route', 'ASC')
            ->orderBy('rs.nom', 'ASC');

        if ($idArrondissement !== null) {
            $builder->where('rs.id_arrondissement', $idArrondissement);
        }

        return $builder
            ->get()
            ->getResultArray();
    }

    public function getGeoJSON(?int $idArrondissement = null): array
    {
        $rows = $this->getPourCarte($idArrondissement);
        $features = [];

        foreach ($rows as $row) {
            if ($row['geojson'] === null) {
                continue;
            }

            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($row['geojson'], true),
                'properties' => [
                    'id' => $row['id'],
                    'id_arrondissement' => $row['id_arrondissement'],
                    'arrondissement_nom' => $row['arrondissement_nom'],
                    'nom' => $row['nom'],
                    'type_route' => $row['type_route'],
                    'surface' => $row['surface'],
                    'smoothness' => $row['smoothness'],
                    'largeur_m' => $row['largeur_m'],
                    'nb_voies' => $row['nb_voies'],
                    'sens_unique' => $row['sens_unique'],
                    'bridge' => $row['bridge'],
                    'longueur_m' => $row['longueur_m'],
                ],
            ];
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }

    public function getTypesRoute(): array
    {
        return $this
            ->select('type_route')
            ->where('type_route IS NOT NULL')
            ->distinct()
            ->orderBy('type_route', 'ASC')
            ->findAll();
    }

    public function countByArrondissement(): array
    {
        return $this->db
            ->table('arrondissement a')
            ->select('a.id')
            ->select('a.code')
            ->select('a.nom')
            ->selectCount('rs.id', 'total_segments')
            ->selectSum('rs.longueur_m', 'longueur_totale_m')
            ->join($this->table . ' rs', 'rs.id_arrondissement = a.id', 'left')
            ->groupBy([
                'a.id',
                'a.code',
                'a.nom',
            ])
            ->orderBy('a.nom', 'ASC')
            ->get()
            ->getResultArray();
    }
}
