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

    public function getSuperficieTotale(): float
    {
        $row = $this
            ->selectSum('superficie_km2', 'superficie_totale')
            ->get()
            ->getRowArray();

        return (float) ($row['superficie_totale'] ?? 0);
    }
}