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
        return (int) $this->countAllResults();
    }

    public function getTypesAvecNombreEtablissements(): array
    {
        return $this->db
            ->table($this->table . ' tes')
            ->select('tes.id')
            ->select('tes.libelle')
            ->select('tes.description')
            ->select('tes.couleur_carte')
            ->selectCount('es.id', 'total_etablissements')
            ->join('etablissement_sante es', 'es.id_type = tes.id', 'left')
            ->groupBy([
                'tes.id',
                'tes.libelle',
                'tes.description',
                'tes.couleur_carte',
            ])
            ->orderBy('total_etablissements', 'DESC')
            ->get()
            ->getResultArray();
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