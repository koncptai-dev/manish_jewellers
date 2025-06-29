<?php

namespace App\Services;

use App\Models\PaymentRequest;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class PhonePeService
{
    protected $baseUrl;
    protected $clientId;
    protected $client; // Define client property

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
        // $authUrl = $this->baseUrl . "/identity-manager/v1/oauth/token"; // Production Mode
        $authUrl = 'https://api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token';

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
    public function initiatePayment($amount, $redirectUrl, $orderId)
    {
        if (!$this->accessToken || time() >= $this->expiresAt - 600) {
            $this->generateAccessToken();
        }

        // $paymentUrl = $this->baseUrl . "/pg/checkout/v2/pay"; //Production Link
        $paymentUrl = $this->baseUrl . "/checkout/v2/pay";

        // $orderId = "TX_" . uniqid();

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
    // public function checkPaymentStatus($orderId)
    // {

    //     if (!$this->accessToken || time() >= $this->expiresAt - 600) {
    //         $this->generateAccessToken();
    //     }

    //     // $statusUrl = $this->baseUrl . "/pg/v1/status/{$this->merchantId}/$orderId";
    //     $statusUrl = "https://api.phonepe.com/apis/pg/checkout/v2/order/{$orderId}/status";

    //     $response = Http::withHeaders([
    //         "Content-Type" => "application/json",
    //         "Authorization" => "O-Bearer " . $this->accessToken
    //     ])->get($statusUrl);

    //     return $response->json();
    // }

    public function paymentCallback($orderId)
    {
        if (!$this->accessToken || time() >= $this->expiresAt - 600) {
            $this->generateAccessToken();
        }

        // Define API endpoint
        $url = "https://api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/order/{$orderId}/status";

        // Set headers
        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => "O-Bearer $this->accessToken",
        ];

        // Send GET request
        $result = Http::withHeaders($headers)->get($url);

        DB::table('payment_data')->insert([
            'is_paid' => 1,
            'additional_data' => json_encode($result), // Ensure it's stored as JSON
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Handle the response based on the 'state' parameter
        if (isset($result['state'])) {
            switch ($result['state']) {
                case 'COMPLETED':
                    // Update your database to mark the payment as successful
                    PaymentRequest::where('id', $orderId)->update([
                        'is_paid' => 1,
                        'transaction_id' => $result['paymentDetails'][0]['transactionId'] ?? null,
                    ]);
                    return response()->json(['message' => 'Payment successful', 'data' => $result]);

                case 'PENDING':
                    // Handle pending status appropriately
                    return response()->json(['message' => 'Payment is still pending', 'data' => $result]);

                case 'FAILED':
                    // Handle failed payment
                    return response()->json(['message' => 'Payment failed', 'data' => $result], 400);

                default:
                    return response()->json(['error' => 'Unknown payment status', 'data' => $result], 400);
            }
        } else {
            return response()->json(['error' => 'Invalid response from PhonePe', 'data' => $result], 400);
        }

        // // Check response status
        // if ($response->successful()) {
        //     return response()->json([
        //         'status'  => 'success',
        //         'data'    => $response->json(),
        //     ]);
        // } else {
        //     return response()->json([
        //         'status'  => 'error',
        //         'message' => $response->body(),
        //     ], $response->status());
        // }
    }

    private $environment = "uat"; // Change to "prod" for production

    // public function paymentCallback($merchantOrderId)
    // {

    //     if (!$this->accessToken || time() >= $this->expiresAt - 600) {
    //         $this->generateAccessToken();
    //     }

    //     // Determine the appropriate environment URL
    //     $baseUrl = $this->environment === 'uat'
    //         ? 'https://api.phonepe.com/apis/pg'
    //         : 'https://api-preprod.phonepe.com/apis/pg-sandbox';


    //     // Construct the Order Status API URL
    //     $orderStatusUrl = "{$baseUrl}/checkout/v2/order/{$merchantOrderId}/status";

    //     // Make the GET request to PhonePe's Order Status API
    //     $response = Http::withHeaders([
    //         "Content-Type" => "application/json",
    //         "Authorization" => "O-Bearer $this->accessToken",
    //     ])->get($orderStatusUrl);

    //     // Parse the response
    //     $result = $response->json();

    //     DB::table('payment_data')->insert([
    //         'is_paid' => 1,
    //         'additional_data' => json_encode($result), // Ensure it's stored as JSON
    //         'created_at' => now(),
    //         'updated_at' => now(),
    //     ]);

    //     // Handle the response based on the 'state' parameter
    //     if (isset($result['state'])) {
    //         switch ($result['state']) {
    //             case 'COMPLETED':
    //                 // Update your database to mark the payment as successful
    //                 PaymentRequest::where('id', $merchantOrderId)->update([
    //                     'is_paid' => 1,
    //                     'transaction_id' => $result['paymentDetails'][0]['transactionId'] ?? null,
    //                 ]);
    //                 return response()->json(['message' => 'Payment successful', 'data' => $result]);

    //             case 'PENDING':
    //                 // Handle pending status appropriately
    //                 return response()->json(['message' => 'Payment is still pending', 'data' => $result]);

    //             case 'FAILED':
    //                 // Handle failed payment
    //                 return response()->json(['message' => 'Payment failed', 'data' => $result], 400);

    //             default:
    //                 return response()->json(['error' => 'Unknown payment status', 'data' => $result], 400);
    //         }
    //     } else {
    //         return response()->json(['error' => 'Invalid response from PhonePe', 'data' => $result], 400);
    //     }
    // }

    public function createPayment($amount, $merchantOrderId)
    {
        // Check if access token is expired
        if (!$this->accessToken || time() >= $this->expiresAt - 600) {
            $this->generateAccessToken();
        }

        // Convert amount to paise
        $amountInPaise = $amount * 100;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/sdk/order',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
          "merchantOrderId": "' . $merchantOrderId . '",
          "amount": ' . $amountInPaise . ',
          "expireAfter": 1200,
          "metaInfo": {
            "udf1": "<additional-information-1>",
            "udf2": "<additional-information-2>",
            "udf3": "<additional-information-3>",
            "udf4": "<additional-information-4>",
            "udf5": "<additional-information-5>"
          },
          "paymentFlow": {
            "type": "PG_CHECKOUT"
          }
        }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: O-Bearer ' . $this->accessToken . ''
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        // Decode JSON response
        $responseData = json_decode($response, true);

        // Append merchantOrderId to the response
        $responseData['merchantOrderId'] = $merchantOrderId;

        return $responseData;

        // return json_decode($response, true);
    }
}
