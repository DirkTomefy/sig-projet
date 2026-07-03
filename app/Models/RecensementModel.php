<?php

namespace App\Models;

use CodeIgniter\Model;

class RecensementModel extends Model
{
    protected $table = 'recensement';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'id_arrondissement',
        'annee',
        'population',
        'source',
        'observation',
    ];

    public function getAnneesDisponibles(): array
    {
        return $this
            ->select('annee')
            ->distinct()
            ->orderBy('annee', 'DESC')
            ->findAll();
    }

    public function getPopulationByArrondissement(?int $annee = null): array
    {
        $builder = $this
            ->select('id_arrondissement')
            ->select('annee')
            ->select('population')
            ->orderBy('id_arrondissement', 'ASC')
            ->orderBy('annee', 'DESC');

        if ($annee !== null) {
            $builder->where('annee', $annee);
        }

        $rows = $builder->findAll();

        return $this->garderDernierRecensementParArrondissement($rows);
    }

    public function getPopulationTotale(?int $annee = null): int
    {
        $rows = $this->getPopulationByArrondissement($annee);

        $total = 0;

        foreach ($rows as $row) {
            $total += (int) $row['population'];
        }

        return $total;
    }

    private function garderDernierRecensementParArrondissement(array $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            $idArrondissement = (int) $row['id_arrondissement'];

            if (!isset($result[$idArrondissement])) {
                $result[$idArrondissement] = $row;
            }
        }

        return array_values($result);
    }
}