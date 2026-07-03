<?php
namespace App\Models;

use CodeIgniter\Model;

class EtablissementSanteModel extends Model
{
    protected $table = 'etablissement_sante';

    public function rechercher(?string $nom, ?int $idType, ?int $idArrondissement): array
    {
        $builder = $this->db->table($this->table);

        if ($nom !== null && trim($nom) !== '') {
            // 5e paramètre = insensitiveSearch => génère LOWER(nom) LIKE LOWER(?)
            $builder->like('nom', trim($nom), 'both', null, true);
        }

        if ($idType !== null) {
            $builder->where('id_type', $idType);
        }

        if ($idArrondissement !== null) {
            $builder->where('id_arrondissement', $idArrondissement);
        }

        return $builder->get()->getResultArray();
    }
}