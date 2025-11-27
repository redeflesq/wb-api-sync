<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WbApiService
{
    private string $apiHost;
    private string $apiKey;

    public function __construct()
    {
        $this->apiHost = config('wb.api_host', getenv('WB_API_HOST'));
        $this->apiKey = config('wb.api_key', getenv('WB_API_KEY'));
    }

    public function getSales(string $dateFrom, string $dateTo, int $page = 1, int $limit = 500): array
    {
        return $this->makeRequest('/api/sales', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    public function getOrders(string $dateFrom, string $dateTo, int $page = 1, int $limit = 500): array
    {
        return $this->makeRequest('/api/orders', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    public function getStocks(string $dateFrom, int $page = 1, int $limit = 500): array
    {
        return $this->makeRequest('/api/stocks', [
            'dateFrom' => $dateFrom,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    public function getIncomes(string $dateFrom, string $dateTo, int $page = 1, int $limit = 500): array
    {
        return $this->makeRequest('/api/incomes', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    private function makeRequest(string $endpoint, array $params = []): array
    {
        $params['key'] = $this->apiKey;
        $url = "http://{$this->apiHost}{$endpoint}";

        try {
            $response = Http::timeout(30)->get($url, $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("API request failed", [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['data' => [], 'error' => 'API request failed: ' . $response->status()];
        } catch (\Exception $e) {
            Log::error("API request exception", [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);
            return ['data' => [], 'error' => $e->getMessage()];
        }
    }
}
