<?php

namespace App\Controllers;

use App\Services\DecisionService;

class DecisionController extends BaseController
{
	protected DecisionService $decisionService;

	public function __construct()
	{
		$this->decisionService = new DecisionService();
	}

	/**
	 * GET /api/decision/nearest?lat=..&lng=..&k=..
	 */
	public function nearest()
	{
		$lat = $this->request->getGet('lat') !== null ? (float) $this->request->getGet('lat') : null;
		$lng = $this->request->getGet('lng') !== null ? (float) $this->request->getGet('lng') : null;
		$k = $this->request->getGet('k') !== null ? (int) $this->request->getGet('k') : 5;
		$type = (string) ($this->request->getGet('type') ?? 'PHARMACY');

		if ($lat === null || $lng === null) {
			return $this->response->setStatusCode(422)->setJSON(['error' => 'lat et lng requis']);
		}

		$nearest = $this->decisionService->findNearestByType($lat, $lng, $type, $k);
		return $this->response->setJSON(['success' => true, 'data' => $nearest]);
	}
}
