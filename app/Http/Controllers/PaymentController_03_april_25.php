<?php

namespace App\Http\Controllers;

use App\Models\InstallmentPayment;
use App\Models\InstallmentPaymentDetail;
use App\Models\PaymentRequest;
use App\Utils\Helpers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private $merchantId;
    private $saltKey;
    private $saltIndex;
    private $baseUrl;
    private $callbackUrl;

    public function index()
    {
        return view('payment-form');
    }
    public function __construct()
    {
        $this->merchantId = env('PHONEPE_MERCHANT_ID');
        $this->saltKey = env('PHONEPE_SALT_KEY');
        $this->saltIndex = env('PHONEPE_SALT_INDEX');
        $this->callbackUrl = env('PHONEPE_CALLBACK_URL');

        // Base URL based on environment
        $this->baseUrl = env('PHONEPE_ENV') === 'sandbox'
            ? 'https://api-preprod.phonepe.com/apis/hermes'
            : 'https://api.phonepe.com/apis/hermes';
    }
    private $environment = "prod"; // Change to "prod" for production

    private $authUrls = [
        "prod" => "https://api.phonepe.com/apis/identity-manager/v1/oauth/token",
        "uat"  => "https://api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token"
    ];

    private $paymentUrls = [
        "prod" => "https://api.phonepe.com/apis/pg/checkout/v2/pay",
        "uat"  => "https://api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/pay"
    ];

    // private function generateAccessToken()
    // {
    //     $auth_url = $this->authUrls[$this->environment];

    //     $payload = [
    //         "client_id" => 'MANISHJEWELUAT_250328183',
    //         "client_version" => 1,
    //         "client_secret" => 'MDQ3NjFlMDAtODc5Zi00MjYwLTkzMmYtNzM3NjgwODAzZDZi',
    //         "grant_type" => "client_credentials"
    //     ];

    //     $response = Http::asForm()->post($auth_url, $payload);

    //     $result = $response->json();

    //     if (!isset($result['access_token']) || !isset($result['expires_at'])) {
    //         return response()->json([
    //             "error" => "Failed to fetch access token",
    //             "response" => $result
    //         ], 500);
    //     }

    //     return [
    //         "access_token" => $result['access_token'],
    //         "expires_at" => $result['expires_at']
    //     ];
    // }

    private function generateAccessToken()
    {
        $auth_url = $this->authUrls[$this->environment];

        $payload = [
            "client_id" => 'SU2503262032467454793007',
            "client_version" => 1,
            "client_secret" => '133ab37b-7fd5-4342-98c1-9eae81d7f2cf',
            "grant_type" => "client_credentials"
        ];

        $response = Http::asForm()->post($auth_url, $payload);

        $result = $response->json();

        if (!isset($result['access_token']) || !isset($result['expires_at'])) {
            return response()->json([
                "error" => "Failed to fetch access token",
                "response" => $result
            ], 500);
        }

        return [
            "access_token" => $result['access_token'],
            "expires_at" => $result['expires_at']
        ];
    }

    public function initiatePayment(Request $request)
    {

        $tokenData = $this->generateAccessToken();

        if (!isset($tokenData['access_token'])) {
            return response()->json(["error" => "Access token not received"], 500);
        }

        $accessToken = $tokenData['access_token'];
        $payment_url = $this->paymentUrls[$this->environment];


        // Retrieve user ID from the token
        $user =  Helpers::getCustomerInformation($request);
        $receiverId = 1; // Fixed receiver ID for now
        $amount = $request->input('plan_amount', 0);

        // Store payment request
        $paymentRequest = PaymentRequest::createPaymentRequest([
            'payer_id' => $user->id,
            'receiver_id' => $receiverId,
            'payment_amount' => $amount * 100,
            'gateway_callback_url' => '',
            'success_hook' => 'digital_phone_pay_payment_success',
            'failure_hook' => 'digital_payment_fail',
            'payer_information' => json_encode($user),
            'transaction_id' => null,
            'payment_method' => "PhonePay",
            'additional_data' => json_encode($request->all()),
            'is_paid' => 0,
        ]);

        $merchantOrderId = $paymentRequest->id;

        // Payment Request Payload
        $postData = [
            "merchantOrderId" =>  $merchantOrderId,
            "amount" => $request->input('amount', 1000), // Default ₹10.00 (1000 paise)
            "expiresAfter" => 1200,
            "metaInfo" => [
                "udf1" => "additional-information-1",
                "udf2" => "additional-information-2",
                "udf3" => "additional-information-3",
                "udf4" => "additional-information-4",
                "udf5" => "additional-information-5"
            ],
            "paymentFlow" => [
                "type" => "PG_CHECKOUT",
                "message" => "Payment message used for collect requests",
                "merchantUrls" => [
                    "redirectUrl" =>  route('payment.callback', ['merchantOrderId' => $merchantOrderId])
                ]
            ]
        ];

        $response = Http::withHeaders([
            "Content-Type" => "application/json",
            "Authorization" => "O-Bearer $accessToken"
        ])->post($payment_url, $postData);

        $result = $response->json();

        // Redirect to Payment Page if URL is received
        if (isset($result['redirectUrl'])) {
            return response()->json([
                "redirectUrl" => $result['redirectUrl']
            ]);
        } else {
            return response()->json([
                "error" => "Redirect URL not received",
                "response" => $result
            ], 400);
        }
    }

    public function checkOrderStatus($merchantOrderId)
    {
        // Generate Access Token
        $tokenData = $this->generateAccessToken();

        if (!isset($tokenData['access_token'])) {
            return response()->json(["error" => "Access token not received"], 500);
        }

        $accessToken = $tokenData['access_token'];

        // Determine the appropriate environment URL
        $baseUrl = $this->environment === 'prod'
            ? 'https://api.phonepe.com/apis/pg'
            : 'https://api-preprod.phonepe.com/apis/pg-sandbox';

        // Construct the Order Status API URL
        $orderStatusUrl = "{$baseUrl}/checkout/v2/order/{$merchantOrderId}/status";

        // Make the GET request to PhonePe's Order Status API
        $response = Http::withHeaders([
            "Content-Type" => "application/json",
            "Authorization" => "O-Bearer $accessToken",
        ])->get($orderStatusUrl);

        // Parse the response
        $result = $response->json();

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
                    PaymentRequest::where('id', $merchantOrderId)->update([
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
    }

    public function paymentCallback(Request $request)
    {
        $merchantOrderId = $request->input('merchantOrderId');

        Log::info("Payment Callback received for Order ID: {$merchantOrderId}", $request->all());

        return $this->checkOrderStatus($merchantOrderId);
        return response()->json([
            "message" => "Payment Callback Received",
            "request_data" => $request->all()
        ]);
    }

    // public function paymentCallback(Request $request)
    // {
    //     DB::table('payment_data')->insert([
    //         'is_paid' => 1,
    //         'additional_data' => json_encode($request->all()), // Ensure it's stored as JSON
    //         'created_at' => now(),
    //         'updated_at' => now(),
    //     ]);


    //     // Retrieve the merchantOrderId from the request
    //     $merchantOrderId = $request->input('merchantOrderId');

    //     // Fetch payment request from the database
    //     $paymentRequest = PaymentRequest::find($merchantOrderId);

    //     if (!$paymentRequest) {
    //         return response()->json([
    //             "error" => "Payment request not found"
    //         ], 404);
    //     }

    //     // Decode the additional_data JSON field
    //     $additionalData = json_decode($paymentRequest->additional_data, true);

    //     if (!$additionalData) {
    //         return response()->json([
    //             "error" => "Additional data not found or invalid"
    //         ], 400);
    //     }

    //     // Prepare data for createWithDetails function
    //     $data = [
    //         'payer_id' => $paymentRequest->payer_id,
    //         'receiver_id' => $paymentRequest->receiver_id,
    //         'payment_amount' => $paymentRequest->payment_amount,
    //         'payment_method' => $paymentRequest->payment_method,
    //         'transaction_id' => $request->input('transaction_id', null), // Capture transaction ID from callback
    //         'is_paid' => 1, // Mark as paid
    //         'payment_platform' => $paymentRequest->payment_platform,
    //     ];

    //     $details = $additionalData['details'] ?? [];

    //     // Insert into the table using createWithDetails
    //     $installmentPayment = InstallmentPayment::createWithDetails($data, $details);

    //     return response()->json([
    //         "message" => "Payment processed successfully",
    //         "installmentPayment" => $installmentPayment
    //     ]);
    // }


    public function confirmPayment(Request $request)
    {

        if ($request->code == 'PAYMENT_SUCCESS') {
            $transactionId = $request->transactionId;
            $merchantId = $request->merchantId;
            $providerReferenceId = $request->providerReferenceId;
            $merchantOrderId = $request->merchantOrderId;
            $checksum = $request->checksum;
            $status = $request->code;

            //Transaction completed, You can add transaction details into database


            $data = [
                'providerReferenceId' => $providerReferenceId,
                'checksum' => $checksum,

            ];
            if ($merchantOrderId != '') {
                $data['merchantOrderId'] = $merchantOrderId;
            }

            // Payment::where('transaction_id', $transactionId)->update($data);

            return view('confirm_payment', compact('providerReferenceId', 'transactionId'));
        } else {

            //HANDLE YOUR ERROR MESSAGE HERE
            dd('ERROR : ' . $request->code . ', Please Try Again Later.');
        }
    }

    public function digitalPaymentSuccess(Request $request)
    {
        // // Call the helper function
        $response = installment_phone_paye_payment_success($request->all());
    }
}
