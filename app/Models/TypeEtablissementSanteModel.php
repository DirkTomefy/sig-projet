<?php

namespace App\Models;

use CodeIgniter\Model;

class TypeEtablissementSanteModel extends Model
{
    protected $table = 'type_etablissement_sante';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'libelle',
        'description',
        'couleur_carte',
    ];

    public function countTotal(): int
    {
        return (int) $this->db
            ->table($this->table)
            ->countAllResults();
    }

    public function getTypesPourCarte(): array
    {
        return $this
            ->select('id')
            ->select('libelle')
            ->select('couleur_carte')
            ->orderBy('libelle', 'ASC')
            ->findAll();
    }
}