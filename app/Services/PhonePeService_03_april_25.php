<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PhonePeService
{
    protected $baseUrl;
    protected $clientId;
    protected $clientSecret;
    protected $clientVersion;
    protected $grantType;
    protected $merchantId;
    protected $accessToken;
    protected $expiresAt;

    public function __construct()
    {
        $environment = env('PHONEPE_ENV', 'uat');
        $this->baseUrl = $environment === 'prod' ? env('PHONEPE_BASE_URL_PROD') : env('PHONEPE_BASE_URL_UAT');
        $this->clientId = env('PHONEPE_CLIENT_ID');
        $this->clientSecret = env('PHONEPE_CLIENT_SECRET');
        $this->clientVersion = env('PHONEPE_CLIENT_VERSION');
        $this->grantType = env('PHONEPE_GRANT_TYPE');
        $this->merchantId = env('PHONEPE_MERCHANT_ID');
    }

    // Function to get access token
    public function generateAccessToken()
    {
        $authUrl = $this->baseUrl . "/identity-manager/v1/oauth/token";

        $payload = [
            "client_id" => $this->clientId,
            "client_version" => $this->clientVersion,
            "client_secret" => $this->clientSecret,
            "grant_type" => $this->grantType
        ];

        $response = Http::asForm()->post($authUrl, $payload);

        $result = $response->json();

        if (!isset($result['access_token']) || !isset($result['expires_at'])) {
            return response()->json(['error' => 'Failed to fetch access token', 'response' => $result], 500);
        }

        $this->accessToken = $result['access_token'];
        $this->expiresAt = $result['expires_at'];

        return $this->accessToken;
    }

    // Function to initiate payment
    public function initiatePayment($amount, $redirectUrl)
    {
        if (!$this->accessToken || time() >= $this->expiresAt - 600) {
            $this->generateAccessToken();
        }
      
        $paymentUrl = $this->baseUrl . "/pg/checkout/v2/pay";

        $orderId = "TX_" . uniqid();

        $payload = [
            "merchantOrderId" => $orderId,
            "amount" => $amount * 100, // Convert to paisa
            "expiresAfter" => 1200,
            "metaInfo" => [
                "udf1" => "info1",
                "udf2" => "info2"
            ],
            "paymentFlow" => [
                "type" => "PG_CHECKOUT",
                "message" => "Transaction initiated",
                "merchantUrls" => [
                    "redirectUrl" => $redirectUrl
                ]
            ]
        ];

        $response = Http::withHeaders([
            "Content-Type" => "application/json",
            "Authorization" => "O-Bearer " . $this->accessToken
        ])->post($paymentUrl, $payload);

        return $response->json();
    }

    // Function to check payment status
    public function checkPaymentStatus($orderId)
    {
        if (!$this->accessToken || time() >= $this->expiresAt - 600) {
            $this->generateAccessToken();
        }

        $statusUrl = $this->baseUrl . "/pg/v1/status/{$this->merchantId}/$orderId";

        $response = Http::withHeaders([
            "Content-Type" => "application/json",
            "Authorization" => "O-Bearer " . $this->accessToken
        ])->get($statusUrl);

        return $response->json();
    }
}
