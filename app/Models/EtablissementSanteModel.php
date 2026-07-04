<?php

namespace App\Models;

use CodeIgniter\Model;

class EtablissementSanteModel extends Model
{
    protected $table = 'etablissement_sante';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'nom',
        'id_type',
        'id_arrondissement',
        'adresse',
        'contact',
        'longitude',
        'latitude',
        'geom',
    ];

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
  
    public function countTotal(): int
    {
        return (int) $this->db
            ->table($this->table)
            ->countAllResults();
    }

    /**
     * Tous les établissements avec leur type (libellé + couleur) et leur
     * arrondissement, pour l'affichage cartographique du Module 1.
     */
    public function getPourCarte(): array
    {
        return $this->db
            ->table($this->table . ' es')
            ->select('es.id, es.nom, es.adresse, es.contact')
            ->select('es.latitude, es.longitude')
            ->select('es.id_type, es.id_arrondissement')
            ->select('tes.libelle AS type_libelle')
            ->select('tes.couleur_carte')
            ->select('a.nom AS arrondissement_nom')
            ->join('type_etablissement_sante tes', 'tes.id = es.id_type', 'left')
            ->join('arrondissement a', 'a.id = es.id_arrondissement', 'left')
            ->orderBy('tes.libelle', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function countByType(): array
    {
        return $this->db
            ->table('type_etablissement_sante tes')
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

    public function countByArrondissement(): array
    {
        return $this->db
            ->table('arrondissement a')
            ->select('a.id')
            ->select('a.code')
            ->select('a.nom')
            ->select('a.superficie_km2')
            ->selectCount('es.id', 'total_etablissements')
            ->join('etablissement_sante es', 'es.id_arrondissement = a.id', 'left')
            ->groupBy([
                'a.id',
                'a.code',
                'a.nom',
                'a.superficie_km2',
            ])
            ->orderBy('a.nom', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function countTypeByArrondissement(): array
    {
        return $this->db
            ->table('arrondissement a')
            ->select('a.id AS id_arrondissement')
            ->select('a.nom AS arrondissement')
            ->select('tes.id AS id_type')
            ->select('tes.libelle AS type_etablissement')
            ->select('tes.couleur_carte')
            ->selectCount('es.id', 'total')
            ->join('type_etablissement_sante tes', '1 = 1', 'inner')
            ->join(
                'etablissement_sante es',
                'es.id_arrondissement = a.id AND es.id_type = tes.id',
                'left'
            )
            ->groupBy([
                'a.id',
                'a.nom',
                'tes.id',
                'tes.libelle',
                'tes.couleur_carte',
            ])
            ->orderBy('a.nom', 'ASC')
            ->orderBy('tes.libelle', 'ASC')
            ->get()
            ->getResultArray();
    }
}