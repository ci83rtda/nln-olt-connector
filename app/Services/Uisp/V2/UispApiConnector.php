<?php

namespace App\Services\Uisp\V2;

use GuzzleHttp\Client;

class UispApiConnector
{

    protected $baseUrl;
    protected $client;

    public function __construct()
    {
        $this->baseUrl = config('services.uisp.api.v2.url');
        $this->client = new Client();
    }

    public function makeRequest($method, $endpoint, $data = [], $raw = false)
    {
        $url = $this->baseUrl . $endpoint;

        $response = $this->client->request($method, $url, [
            'json' => $data,
            'headers' => [
                'x-auth-token' => config('services.uisp.api.v2.token'),
            ],
        ]);

        if ($raw){
            return $response;
        }
        return json_decode($response->getBody(), true);
    }

}
