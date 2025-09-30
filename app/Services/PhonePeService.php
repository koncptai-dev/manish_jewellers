<?php

namespace App\Services;

use App\Models\InstallmentPayment;
use App\Models\InstallmentPaymentDetail;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Notifications\PaymentNotification;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
      
        $this->baseUrl = $environment === 'pro' ? env('PHONEPE_BASE_URL_PROD') : env('PHONEPE_BASE_URL_UAT');
       
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
        $authUrl =  "https://api.phonepe.com/apis/identity-manager/v1/oauth/token"; // Production Mode
        // $authUrl = 'https://api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token';

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

        $paymentUrl = $this->baseUrl . "/pg/checkout/v2/pay"; //Production Link
        // $paymentUrl = $this->baseUrl . "/checkout/v2/pay";
  
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
        $url = "https://api.phonepe.com/apis/pg/checkout/v2/order/{$orderId}/status";
        // $url = "https://api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/order/{$orderId}/status"; // UAT Mode
        
        // Set headers
        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => "O-Bearer $this->accessToken",
        ];

        // Send GET request
        $result = Http::withHeaders($headers)->get($url);
        $result = $result->json();
        DB::table('payment_data')->insert([
            'is_paid' => 1,
            'additional_data' => json_encode($result), // Ensure it's stored as JSON
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $orderIds = session()->get('order_ids', []);
        // Handle the response based on the 'state' parameter
        if (isset($result['state'])) {
            switch ($result['state']) {
                case 'COMPLETED':
                    // Update your database to mark the payment as successful
                    PaymentRequest::where('id', $orderId)->update([
                        'is_paid' => 1,
                        'transaction_id' => $result['paymentDetails'][0]['transactionId'] ?? null,
                    ]);
                    DB::table('orders')
                    ->whereIn('id', $orderIds)
                    ->update(['payment_status' => 'paid']);
                
                    DB::table('order_details')
                    ->whereIn('order_id', $orderIds)
                    ->update(['payment_status' => 'paid']);
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

    }
    public function orderPaymentStatusUpdate($merchantOrderId)
    {
        
        if (!$this->accessToken || time() >= $this->expiresAt - 600) {
            $this->generateAccessToken();
        }

        // Define API endpoint
        $url = "https://api.phonepe.com/apis/pg/checkout/v2/order/{$merchantOrderId}/status";
        // $url = "https://api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/order/{$merchantOrderId}/status"; // UAT Mode
        
        // Set headers
        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => "O-Bearer $this->accessToken",
        ];

        // Send GET request
        $result = Http::withHeaders($headers)->get($url);

        $result = $result->json();

        // Handle the response based on the 'state' parameter
        if (isset($result['state'])) {
            switch ($result['state']) {
                case 'COMPLETED':
                    // Update your database to mark the payment as successful
                    PaymentRequest::where('id', $merchantOrderId)->update([
                        'is_paid' => 1,
                        'transaction_id' => $result['paymentDetails'][0]['transactionId'] ?? null,
                    ]);
                    DB::table('orders')
                    ->where('id', $merchantOrderId)
                    ->update(['payment_status' => 'paid']);
                
                    DB::table('order_details')
                    ->where('order_id', $merchantOrderId)
                    ->update(['payment_status' => 'paid']);
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
    private $environment = "prod"; // Change to "prod" for production

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
            // CURLOPT_URL => 'https://api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/sdk/order',
            CURLOPT_URL => 'https://api.phonepe.com/apis/pg/checkout/v2/sdk/order',
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

    // public function checkOrderStatus($merchantOrderId)
    // {
    //     // Check if access token is expired
    //     if (!$this->accessToken || time() >= $this->expiresAt - 600) {
    //         $this->generateAccessToken();
    //     }

    //     // $url = "https://api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/order/{$merchantOrderId}/status?details=false"; // UAT Mode
    //     $url = "https://api.phonepe.com/apis/pg/checkout/v2/order/{$merchantOrderId}/status"; // Production Mode

    //     $curl = curl_init();

    //     curl_setopt_array($curl, [
    //         CURLOPT_URL => $url,
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_ENCODING => '',
    //         CURLOPT_MAXREDIRS => 10,
    //         CURLOPT_TIMEOUT => 0,
    //         CURLOPT_FOLLOWLOCATION => true,
    //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //         CURLOPT_CUSTOMREQUEST => 'GET',
    //         CURLOPT_HTTPHEADER => [
    //             'Content-Type: application/json',
    //             'Authorization: O-Bearer ' . $this->accessToken
    //         ],
    //     ]);

    //     $result = curl_exec($curl);
    //     curl_close($curl);
    //     if (isset($result['state'])) {
    //         switch ($result['state']) {
    //             case 'COMPLETED':
    //                 // Update your database to mark the payment as successful
    //                 // PaymentRequest::where('id', $merchantOrderId)->update([
    //                 //     'is_paid' => 1,
    //                 //     'transaction_id' => $result['paymentDetails'][0]['transactionId'] ?? null,
    //                 // ]);
    //                 $this->updatePaymentStatus($merchantOrderId, $result['paymentDetails'][0]['transactionId'] ?? null);

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
    //     // return json_decode($response, true);      
    // }


    public function updatePaymentStatus($merchantOrderId, $transactionId, $planAmount, $paymentMode)

    {
        //     // Update your database to mark the payment as successful
        // PaymentRequest::where('id', $merchantOrderId)->update([
        //     'is_paid' => 1,
        //     'transaction_id' => $transactionId,
        // ]);

        // Fetch payment request data
        $paymentRequest = PaymentRequest::find($merchantOrderId);

        if (!$paymentRequest) {
            return response()->json(['error' => 'Payment request not found'], 404);
        }

        // Decode additional_data JSON
        $additionalData = json_decode($paymentRequest->additional_data, true);

        // Update payment request as paid
        $paymentRequest->update([
            'is_paid' => 1,
            'transaction_id' =>  $transactionId,
        ]);

        $end_date = $additionalData['start_date'] ?? null;
        $start_date = $additionalData['start_date'] ?? null;

        $plan_category = $additionalData['plan_code'] ?? null;
        if ($plan_category === 'First Installment Plan') {
            $end_date =  Carbon::parse($start_date)->addYear();
        } elseif ($plan_category === 'Second Installment Plan') {
            $end_date = Carbon::parse($start_date)->addMonths(18);
        } elseif ($plan_category === 'Third Installment Plan') {
            $end_date =  Carbon::parse($start_date)->addYears(2);
        }

        // If payment is NOT UPI, deduct 2%
        if ($paymentMode !== 'UPI_COLLECT') {
            $planAmountAfterCharges = $planAmount - ($planAmount * 0.02); // Deduct 2%
        } else {
            $planAmountAfterCharges = $planAmount; // Keep original if UPI
        }



        // Check if installment exists
        if (is_null($paymentRequest->installment_id) || $paymentRequest->installment_id == 0) {
            // Create new InstallmentPayment
            $installmentPayment = InstallmentPayment::create([
                'uuid' =>  str::uuid(),
                'plan_code' => $additionalData['plan_code'] ?? null,
                'plan_category' => $plan_category,
                'total_yearly_payment' => $additionalData['total_yearly_payment'] ?? null,
                'total_gold_purchase' => $additionalData['total_gold_purchase'] ?? null,
                'start_date' =>  $start_date,
                'end_date' => $end_date,
                'user_id' => $paymentRequest->payer_id,
                'no_of_months' => $additionalData['no_of_months'] ?? null,
            ]);

            // Create InstallmentPaymentDetail
            InstallmentPaymentDetail::create([
                'installment_payment_id' => $installmentPayment->id,
                'monthly_payment' => $planAmountAfterCharges,
                'monthly_payment_actual_paid' => $planAmount,
                'purchase_gold_weight' => $additionalData['total_gold_purchase'] ?? 0,
                'payment_status' => 'paid',
                'payment_type' => 'online',
                'payment_method' => 'PhonePe',
                'transaction_ref' => $transactionId,
                'payment_by' => 'User',
                'payment_note' => 'Payment received via PhonePe',
                'acquired_gold_rate' => $paymentRequest['acquired_gold_rate'] ?? 0,

            ]);

            // Update payment request with installment ID
            $paymentRequest->update(['installment_id' => $installmentPayment->id]);

            // Update total yearly payment
            $installmentId = $installmentPayment->id;
            $totalYearlyPayment = InstallmentPaymentDetail::where('installment_payment_id', $installmentId)
                ->sum('monthly_payment');

            InstallmentPayment::where('id', $installmentId)->update(['total_yearly_payment' => $totalYearlyPayment]);

            // Notify user
            $user = User::find($installmentPayment->user_id);
            // if ($user) {
            //     $user->notify(new PaymentNotification([
            //         'message' => serialize([
            //             'notification_type' => "Payment Accepted",
            //             "message" => "Payment accepted successfully.",
            //             'total_payment' => $totalYearlyPayment
            //         ]),
            //         'notification_type' => "Payment Accepted"
            //     ]));
            // }
        } else {
            // Insert only InstallmentPaymentDetail for an existing installment
            InstallmentPaymentDetail::create([
                'installment_payment_id' => $paymentRequest->installment_id,
                'monthly_payment' => $additionalData['plan_amount'] ?? 0,
                'purchase_gold_weight' => $additionalData['total_gold_purchase'] ?? 0,
                'payment_status' => 'paid',
                'payment_type' => 'online',
                'payment_method' => 'PhonePe',
                'transaction_ref' => $transactionId,
                'payment_by' => 'User',
                'payment_note' => 'Payment received via PhonePe',

            ]);
        }
    }


    public function checkOrderStatus($merchantOrderId)
    {
        // Check if access token is expired
        if (!$this->accessToken || time() >= $this->expiresAt - 600) {
            $this->generateAccessToken();
        }

        $url = "https://api.phonepe.com/apis/pg/checkout/v2/order/{$merchantOrderId}/status"; // Production Mode
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: O-Bearer ' . $this->accessToken
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);
        echo "<pre>";
        print_r($response);
        echo "</pre>";
        die;
        // Decode the JSON response
        $result = json_decode($response, true); // ✅ Convert JSON string to an array

        // Debugging: Check what the response looks like
        if (!$result) {
            return response()->json(['error' => 'Failed to decode JSON response', 'raw_data' => $response], 400);
        }

        // Now we can safely check for 'state' key
        if (isset($result['state'])) {
            switch ($result['state']) {
                case 'COMPLETED':
                    // Update the payment status in the database
                    // $transactionId = $result['paymentDetails'][0]['transactionId'] ?? null;
                    // $amountInPaise = $result['paymentDetails'][0]['amount'] ?? 0;
                    // $amountInRupees = $amountInPaise / 100;
                    $transactionId = $result['paymentDetails'][0]['transactionId'] ?? null;
                    $amountInPaise = $result['paymentDetails'][0]['amount'] ?? 0;
                    $amountInRupees = $amountInPaise / 100;

                    $paymentMode = $result['paymentDetails'][0]['paymentMode'] ?? null;

                    // Call method to update payment info in DB
                    $this->updatePaymentStatus($merchantOrderId, $transactionId, $amountInRupees, $paymentMode);

                    // Call method to update payment info in DB
                    // $this->updatePaymentStatus($merchantOrderId, $transactionId, $amountInRupees);

                    //$this->updatePaymentStatus($merchantOrderId, $result['paymentDetails'][0]['transactionId'] ?? null);

                    // return response()->json(['message' => 'Payment successful', 'data' => $result]);
                    return ('Payment successful');

                case 'PENDING':
                    // return response()->json(['message' => 'Payment is still pending', 'data' => $result]);
                    return ('Payment is still pending');

                case 'FAILED':
                    // return response()->json(['message' => 'Payment failed', 'data' => $result], 400);
                    return ('Payment failed');

                default:
                    // return response()->json(['error' => 'Unknown payment status', 'data' => $result], 400);
                    return ('Unknown payment status');
            }
        } else {
            // return response()->json(['error' => 'Invalid response from PhonePe', 'data' => $result], 400);
            return ('Invalid response from PhonePe');
        }
    }
}
