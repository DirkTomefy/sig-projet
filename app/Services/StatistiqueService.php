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
        $totalTypes = $this->typeModel->countAllResults();
        $totalArrondissements = $this->arrondissementModel->countTotal();
        $superficieTotale = $this->arrondissementModel->getSuperficieTotale();
        $populationTotale = $this->getPopulationTotale($annee);

        return [
            'total_etablissements' => $totalEtablissements,
            'total_types' => $totalTypes,
            'total_arrondissements' => $totalArrondissements,
            'superficie_totale_km2' => round($superficieTotale, 2),
            'population_totale' => $populationTotale,
            'etablissements_par_km2' => $this->calculerEtablissementsParKm2(
                $totalEtablissements,
                $superficieTotale
            ),
            'etablissements_par_100k_habitants' => $this->calculerEtablissementsPar100kHabitants(
                $totalEtablissements,
                $populationTotale
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
        $stats = $this->etablissementModel->countByArrondissement();
        $populations = $this->getPopulationsByArrondissement($annee);

        return $this->construireCouvertureSanitaire($stats, $populations);
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

    private function construireCouvertureSanitaire(array $stats, array $populations): array
    {
        $populationParArrondissement = $this->indexerParIdArrondissement($populations);
        $result = [];

        foreach ($stats as $row) {
            $idArrondissement = (int) $row['id'];
            $populationRow = $populationParArrondissement[$idArrondissement] ?? null;

            $totalEtablissements = (int) $row['total_etablissements'];
            $superficie = (float) $row['superficie_km2'];
            $population = $populationRow !== null ? (int) $populationRow['population'] : null;

            $result[] = [
                'id' => $idArrondissement,
                'code' => $row['code'],
                'nom' => $row['nom'],
                'superficie_km2' => $superficie,
                'total_etablissements' => $totalEtablissements,
                'annee' => $populationRow['annee'] ?? null,
                'population' => $population,
                'etablissements_par_km2' => $this->calculerEtablissementsParKm2(
                    $totalEtablissements,
                    $superficie
                ),
                'etablissements_par_100k_habitants' => $this->calculerEtablissementsPar100kHabitants(
                    $totalEtablissements,
                    $population
                ),
                'habitants_par_etablissement' => $this->calculerHabitantsParEtablissement(
                    $population,
                    $totalEtablissements
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

    private function calculerEtablissementsParKm2(int $totalEtablissements, float $superficie): ?float
    {
        if ($superficie <= 0) {
            return null;
        }

        return round($totalEtablissements / $superficie, 2);
    }

    private function calculerEtablissementsPar100kHabitants(
        int $totalEtablissements,
        ?int $population
    ): ?float {
        if ($population === null || $population <= 0) {
            return null;
        }

        return round(($totalEtablissements * 100000) / $population, 2);
    }

    private function calculerHabitantsParEtablissement(
        ?int $population,
        int $totalEtablissements
    ): ?float {
        if ($population === null || $population <= 0 || $totalEtablissements <= 0) {
            return null;
        }

        return round($population / $totalEtablissements, 0);
    }
}