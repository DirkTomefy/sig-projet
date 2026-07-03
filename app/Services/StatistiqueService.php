<?php

namespace App\Services;

use App\Models\ArrondissementModel;
use App\Models\EtablissementSanteModel;
use App\Models\RecensementModel;
use App\Models\TypeEtablissementSanteModel;
use Throwable;

class StatistiqueService
{
    protected EtablissementSanteModel $etablissementModel;
    protected TypeEtablissementSanteModel $typeModel;
    protected ArrondissementModel $arrondissementModel;
    protected RecensementModel $recensementModel;

    public function __construct()
    {
        $this->etablissementModel = new EtablissementSanteModel();
        $this->typeModel = new TypeEtablissementSanteModel();
        $this->arrondissementModel = new ArrondissementModel();
        $this->recensementModel = new RecensementModel();
    }

    public function getDashboard(?int $annee = null): array
    {
        return [
            'resume' => $this->getResume($annee),
            'etablissements_par_type' => $this->getEtablissementsParType(),
            'etablissements_par_arrondissement' => $this->getEtablissementsParArrondissement(),
            'couverture_par_arrondissement' => $this->getCouvertureParArrondissement($annee),
            'repartition_type_arrondissement' => $this->getRepartitionTypeParArrondissement(),
            'annee_recensement' => $annee,
        ];
    }

    public function getResume(?int $annee = null): array
    {
        $totalEtablissements = $this->etablissementModel->countTotal();
        $totalTypes = $this->typeModel->countTotal();
        $totalArrondissements = $this->arrondissementModel->countTotal();
        $superficieTotale = $this->arrondissementModel->getSuperficieTotale();
        $populationTotale = $this->getPopulationTotale($annee);

        $statsParType = $this->getEtablissementsParType();

        $totalPharmacies = $this->extraireTotalParType($statsParType, [
            'PHARMACY',
        ]);

        $totalHopitauxCliniques = $this->extraireTotalParType($statsParType, [
            'HOSPITAL',
            'CLINIC',
        ]);

        return [
            'total_etablissements' => $totalEtablissements,
            'total_types' => $totalTypes,
            'total_arrondissements' => $totalArrondissements,
            'superficie_totale_km2' => round($superficieTotale, 2),
            'population_totale' => $populationTotale,

            'total_pharmacies' => $totalPharmacies,
            'total_hopitaux_cliniques' => $totalHopitauxCliniques,

            'etablissements_par_km2' => $this->calculerEtablissementsParKm2(
                $totalEtablissements,
                $superficieTotale
            ),

            'pharmacies_par_100k_habitants' => $this->calculerPar100kHabitants(
                $totalPharmacies,
                $populationTotale
            ),

            'hopitaux_cliniques_par_100k_habitants' => $this->calculerPar100kHabitants(
                $totalHopitauxCliniques,
                $populationTotale
            ),

            'habitants_par_pharmacie' => $this->calculerHabitantsParStructure(
                $populationTotale,
                $totalPharmacies
            ),

            'habitants_par_hopital_clinique' => $this->calculerHabitantsParStructure(
                $populationTotale,
                $totalHopitauxCliniques
            ),
        ];
    }

    public function getEtablissementsParType(): array
    {
        return $this->etablissementModel->countByType();
    }

    public function getEtablissementsParArrondissement(): array
    {
        $rows = $this->etablissementModel->countByArrondissement();

        foreach ($rows as &$row) {
            $row['etablissements_par_km2'] = $this->calculerEtablissementsParKm2(
                (int) $row['total_etablissements'],
                (float) $row['superficie_km2']
            );
        }

        return $rows;
    }

    public function getCouvertureParArrondissement(?int $annee = null): array
    {
        $statsArrondissements = $this->etablissementModel->countByArrondissement();
        $populations = $this->getPopulationsByArrondissement($annee);
        $statsTypesArrondissements = $this->etablissementModel->countTypeByArrondissement();

        return $this->construireCouvertureSanitaire(
            $statsArrondissements,
            $populations,
            $statsTypesArrondissements
        );
    }

    public function getRepartitionTypeParArrondissement(): array
    {
        return $this->etablissementModel->countTypeByArrondissement();
    }

    public function getAnneesRecensement(): array
    {
        try {
            return $this->recensementModel->getAnneesDisponibles();
        } catch (Throwable $e) {
            return [];
        }
    }

    private function construireCouvertureSanitaire(
        array $statsArrondissements,
        array $populations,
        array $statsTypesArrondissements
    ): array {
        $populationParArrondissement = $this->indexerParIdArrondissement($populations);
        $typesParArrondissement = $this->grouperTypesParArrondissement($statsTypesArrondissements);

        $result = [];

        foreach ($statsArrondissements as $row) {
            $idArrondissement = (int) $row['id'];
            $populationRow = $populationParArrondissement[$idArrondissement] ?? null;
            $types = $typesParArrondissement[$idArrondissement] ?? [];

            $totalEtablissements = (int) $row['total_etablissements'];
            $superficie = (float) $row['superficie_km2'];
            $population = $populationRow !== null ? (int) $populationRow['population'] : null;

            $totalPharmacies = $this->extraireTotalParType($types, [
                'PHARMACY',
            ]);

            $totalHopitauxCliniques = $this->extraireTotalParType($types, [
                'HOSPITAL',
                'CLINIC',
            ]);

            $result[] = [
                'id' => $idArrondissement,
                'code' => $row['code'],
                'nom' => $row['nom'],
                'superficie_km2' => $superficie,
                'population' => $population,
                'annee' => $populationRow['annee'] ?? null,

                'total_etablissements' => $totalEtablissements,
                'total_pharmacies' => $totalPharmacies,
                'total_hopitaux_cliniques' => $totalHopitauxCliniques,

                'etablissements_par_km2' => $this->calculerEtablissementsParKm2(
                    $totalEtablissements,
                    $superficie
                ),

                'pharmacies_par_100k_habitants' => $this->calculerPar100kHabitants(
                    $totalPharmacies,
                    $population
                ),

                'hopitaux_cliniques_par_100k_habitants' => $this->calculerPar100kHabitants(
                    $totalHopitauxCliniques,
                    $population
                ),

                'habitants_par_pharmacie' => $this->calculerHabitantsParStructure(
                    $population,
                    $totalPharmacies
                ),

                'habitants_par_hopital_clinique' => $this->calculerHabitantsParStructure(
                    $population,
                    $totalHopitauxCliniques
                ),
            ];
        }

        return $result;
    }

    private function getPopulationsByArrondissement(?int $annee = null): array
    {
        try {
            return $this->recensementModel->getPopulationByArrondissement($annee);
        } catch (Throwable $e) {
            return [];
        }
    }

    private function getPopulationTotale(?int $annee = null): ?int
    {
        try {
            return $this->recensementModel->getPopulationTotale($annee);
        } catch (Throwable $e) {
            return null;
        }
    }

    private function indexerParIdArrondissement(array $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            $idArrondissement = (int) $row['id_arrondissement'];
            $result[$idArrondissement] = $row;
        }

        return $result;
    }

    private function grouperTypesParArrondissement(array $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            $idArrondissement = (int) $row['id_arrondissement'];

            if (!isset($result[$idArrondissement])) {
                $result[$idArrondissement] = [];
            }

            $result[$idArrondissement][] = [
                'libelle' => $row['type_etablissement'] ?? '',
                'total_etablissements' => (int) ($row['total'] ?? 0),
            ];
        }

        return $result;
    }

    private function extraireTotalParType(array $statsParType, array $motsCles): int
    {
        $total = 0;

        foreach ($statsParType as $row) {
            $libelle = $this->normaliserTexte($row['libelle'] ?? '');

            foreach ($motsCles as $motCle) {
                $motCleNormalise = $this->normaliserTexte($motCle);

                if (str_contains($libelle, $motCleNormalise)) {
                    $total += (int) ($row['total_etablissements'] ?? $row['total'] ?? 0);
                    break;
                }
            }
        }

        return $total;
    }

    private function normaliserTexte(string $texte): string
    {
        $texte = mb_strtolower($texte);

        return strtr($texte, [
            'à' => 'a',
            'â' => 'a',
            'ä' => 'a',
            'á' => 'a',
            'ã' => 'a',
            'å' => 'a',

            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',

            'î' => 'i',
            'ï' => 'i',
            'í' => 'i',
            'ì' => 'i',

            'ô' => 'o',
            'ö' => 'o',
            'ó' => 'o',
            'ò' => 'o',
            'õ' => 'o',

            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ú' => 'u',

            'ç' => 'c',
        ]);
    }

    private function calculerEtablissementsParKm2(int $totalEtablissements, float $superficie): ?float
    {
        if ($superficie <= 0) {
            return null;
        }

        return round($totalEtablissements / $superficie, 2);
    }

    private function calculerPar100kHabitants(int $total, ?int $population): ?float
    {
        if ($population === null || $population <= 0) {
            return null;
        }

        return round(($total * 100000) / $population, 2);
    }

    private function calculerHabitantsParStructure(?int $population, int $total): ?float
    {
        if ($population === null || $population <= 0 || $total <= 0) {
            return null;
        }

        return round($population / $total, 0);
    }
}