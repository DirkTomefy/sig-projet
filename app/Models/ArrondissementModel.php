<?php

namespace App\Models;

use CodeIgniter\Model;

class ArrondissementModel extends Model
{
    protected $table = 'arrondissement';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'code',
        'nom',
        'superficie_km2',
        'geom',
    ];

    public function countTotal(): int
    {
        return (int) $this->countAllResults();
    }

    /**
     * Contours des arrondissements convertis en GeoJSON (PostGIS),
     * pour tracer les limites sur la carte du Module 1.
     */
    public function getContoursGeoJSON(): array
    {
        return $this->db
            ->table($this->table)
            ->select('id, code, nom, superficie_km2')
            ->select('ST_AsGeoJSON(geom) AS geojson', false)
            ->orderBy('nom', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getSuperficieTotale(): float
    {
        $row = $this
            ->selectSum('superficie_km2', 'superficie_totale')
            ->get()
            ->getRowArray();

        return (float) ($row['superficie_totale'] ?? 0);
    }
}