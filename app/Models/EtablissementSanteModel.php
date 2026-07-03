<?php

namespace App\Models;

use CodeIgniter\Model;

class EtablissementSanteModel extends Model
{
    protected $table            = 'etablissement_sante';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nom',
        'id_type',
        'id_arrondissement',
        'adresse',
        'contact',
        'longitude',
        'latitude',
        'geom'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

   
    public function rechercher(
        ?string $nom = null,
        ?int $id_type = null,
        ?int $id_arrondissement = null
    ): array {

        $builder = $this->builder();

        if (!empty($nom)) {

            $builder->like('nom', $nom);
        }

        if (!empty($id_type)) {
            $builder->where('id_type', $id_type);
        }

        if (!empty($id_arrondissement)) {
            $builder->where('id_arrondissement', $id_arrondissement);
        }

        return $builder->get()->getResultArray();
    }
}