<?php

namespace App\Services\Uisp\V1;

use Exception;
use GuzzleHttp\Client;

class UispV1Access
{

    /**
     *
     * This is the API V1 for UISP
     *
     * @param string $url
     * @param string $method
     * @param array $post
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws Exception
     */
    public static function doRequest($url, $method = 'GET', $post = [])
    {
        $method = strtoupper($method);

        $api_url = config('services.uisp.api.v1.url');
        $api_key = config('services.uisp.api.v1.token');

        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $api_url,
            // You can set any number of default request options.
//            'timeout'  => 2.0,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Auth-App-Key' => $api_key,
            ],
        ]);

        if ($method === 'POST') {
            $response = $client->post($url,['json' => $post]);
        } elseif ($method == 'GET') {
            $response =  $client->get($url);
        } elseif ($method == 'PATCH') {
            $response = $client->patch($url,['json' => $post]);
        }else{
            throw new Exception("HTTP Error: invalid method provided.");
        }


        $statusCode = $response->getStatusCode();
        if ($statusCode >= 400 && $statusCode <= 599) {
            throw new Exception("HTTP Error: $statusCode ".$response->getBody()->getContents());
        }

        return $response;
    }
}
