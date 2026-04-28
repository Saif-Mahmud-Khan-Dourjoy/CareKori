<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        $this->sandbox = (bool) config('services.sslcommerz.sandbox', true);

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
        try {
            $endpoint = config('services.sslcommerz.endpoint', '/gwprocess/v4/api.php');

            $payload = array_merge($payload, [
                'store_id' => $this->storeId,
                'store_passwd' => $this->storePassword,
            ]);

            $response = $this->http->post($endpoint, [
                'form_params' => $payload,
            ]);

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (\Throwable $e) {
            Log::error('SSLCommerz session create failed', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function refundTransaction(array $params): array
    {
        try {
            $url = $this->baseUrl . '/validator/api/merchantTransIDvalidationAPI.php';

            $params = array_merge($params, [
                'store_id' => $this->storeId,
                'store_passwd' => $this->storePassword,
                'format' => 'json',
            ]);

            $response = Http::timeout(15)->get($url, $params);

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error('SSLCommerz refund failed', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function refundStatus(array $params): array
    {
        try {
            $url = $this->baseUrl . '/validator/api/merchantTransIDvalidationAPI.php';

            $params = array_merge($params, [
                'store_id' => $this->storeId,
                'store_passwd' => $this->storePassword,
                'format' => 'json',
            ]);

            $response = Http::timeout(15)->get($url, $params);

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error('SSLCommerz refund status failed', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function validatePayment(string $valId): ?array
    {
        try {
            $endpoint = config(
                'services.sslcommerz.validation_endpoint',
                '/validator/api/validationserverAPI.php'
            );

            $url = $this->baseUrl . $endpoint;

            $response = Http::timeout(15)->get($url, [
                'val_id' => $valId,
                'store_id' => $this->storeId,
                'store_passwd' => $this->storePassword,
                'format' => 'json',
            ]);

            if ($response->failed()) {
                Log::error('SSLCommerz validation API failed', [
                    'val_id' => $valId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            $validStatuses = ['VALID', 'VALIDATED'];

            if (
                isset($data['status']) &&
                in_array(strtoupper($data['status']), $validStatuses, true)
            ) {
                return $data;
            }

            Log::warning('SSLCommerz payment validation rejected', [
                'response' => $data,
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('SSLCommerz validation exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}