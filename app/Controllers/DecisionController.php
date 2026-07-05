<?php

namespace App\Controllers;

use App\Services\DecisionService;
use CodeIgniter\HTTP\ResponseInterface;

class DecisionController extends BaseController
{
	protected DecisionService $decisionService;

	public function __construct()
	{
		$this->decisionService = new DecisionService();
	}

	/**
	 * POST /api/decision/simulate
	 * Body: { lat, lng, radius, k, annee }
	 */
	public function simulate()
	{
		$input = $this->request->getJSON(true) ?? [];
		$lat = isset($input['lat']) ? (float) $input['lat'] : null;
		$lng = isset($input['lng']) ? (float) $input['lng'] : null;
		$radius = isset($input['radius']) ? (int) $input['radius'] : 1000;
		$k = isset($input['k']) ? (int) $input['k'] : 5;
		$annee = isset($input['annee']) ? (int) $input['annee'] : null;

		if ($lat === null || $lng === null) {
			return $this->response->setStatusCode(422)->setJSON(['error' => 'lat et lng requis']);
		}

		$nearest = $this->decisionService->findNearestPharmacies($lat, $lng, $k);
		$population = $this->decisionService->populationInRadius($lat, $lng, $radius, $annee);
		$quota = $this->decisionService->computeQuota($population, count($nearest));

		return $this->response->setJSON([
			'success' => true,
			'data' => [
				'nearest' => $nearest,
				'population' => $population,
				'quota' => $quota,
			]
		]);
	}

	/**
	 * GET /api/decision/nearest?lat=..&lng=..&k=..
	 */
	public function nearest()
	{
		$lat = $this->request->getGet('lat') !== null ? (float) $this->request->getGet('lat') : null;
		$lng = $this->request->getGet('lng') !== null ? (float) $this->request->getGet('lng') : null;
		$k = $this->request->getGet('k') !== null ? (int) $this->request->getGet('k') : 5;

		if ($lat === null || $lng === null) {
			return $this->response->setStatusCode(422)->setJSON(['error' => 'lat et lng requis']);
		}

		$nearest = $this->decisionService->findNearestPharmacies($lat, $lng, $k);
		return $this->response->setJSON(['success' => true, 'data' => $nearest]);
	}
}

