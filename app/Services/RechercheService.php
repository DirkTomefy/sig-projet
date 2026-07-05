<?php

namespace App\Services;

use App\Models\EtablissementSanteModel;

class RechercheService
{
    public function rechercher(?string $nom, ?int $idType = null, ?int $idArrondissement = null): array
    {
        return (new EtablissementSanteModel())->rechercher($nom, $idType, $idArrondissement);
    }

    public function autocomplete(string $terme, int $limit = 8): array
    {
        if (trim($terme) === '') {
            return [];
        }

        $rows = (new EtablissementSanteModel())->rechercher($terme, null, null);
        $rows = array_slice($rows, 0, max(1, $limit));

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'nom' => $row['nom'],
            'latitude' => $row['latitude'] ?? null,
            'longitude' => $row['longitude'] ?? null,
        ], $rows);
    }
}
