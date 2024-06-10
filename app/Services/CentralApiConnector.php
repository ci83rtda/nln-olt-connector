<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CentralApiConnector
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct($baseUrl, $apiKey)
    {
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
    }

    // Placeholder for sending data to the central API
    public function sendToCentralApi($endpoint, $data)
    {
        $url = $this->baseUrl . '/' . $endpoint;
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->post($url, $data);

        return $response->json();
    }

    // Placeholder for querying the central API
    public function queryCentralApi($endpoint)
    {
        $url = $this->baseUrl . '/' . $endpoint;
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->get($url);

        return $response->json();
    }
}
