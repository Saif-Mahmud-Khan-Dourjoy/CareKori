<?php

namespace App\Services;

use GuzzleHttp\Client;

class SslCommerzService
{
    protected string $storeId;
    protected string $storePassword;
    protected bool $sandbox;
    protected string $baseUrl;
    protected Client $http;

    public function __construct()
    {
        $this->storeId = config('services.sslcommerz.store_id');
        $this->storePassword = config('services.sslcommerz.store_password');
        $this->sandbox = config('services.sslcommerz.sandbox', true);

        $this->baseUrl = $this->sandbox
            ? 'https://sandbox.sslcommerz.com'
            : 'https://securepay.sslcommerz.com';

        $this->http = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 10,
        ]);
    }

    public function initiatePayment(array $payload): array
    {
        $endpoint = env('SSLCZ_ENDPOINT', '/gwprocess/v3/api.php');

        $payload = array_merge($payload, [
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePassword,
        ]);

        $response = $this->http->post($endpoint, [
            'form_params' => $payload,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function refundTransaction(array $params): array
    {
        $baseUrl = $this->sandbox
            ? 'https://sandbox.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php'
            : 'https://securepay.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php';

        // Add store credentials to params
        $params = array_merge($params, [
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePassword,
            'format' => 'json',
        ]);

        $response = $this->http->get($baseUrl, [
            'query' => $params,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function refundStatus(array $params): array
    {
        $baseUrl = $this->sandbox
            ? 'https://sandbox.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php'
            : 'https://securepay.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php';

        // Add store credentials to params
        $params = array_merge($params, [
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePassword,
        
        ]);

        $response = $this->http->get($baseUrl, [
            'query' => $params,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}