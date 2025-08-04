<?php
namespace App\Http\Controllers;

use App\Library\Payer;
use App\Library\Payment as PaymentInfo;
use App\Library\Receiver;
use App\Models\Currency;
use App\Models\InstallmentPayment;
use App\Models\PaymentRequest;
use App\Models\ShippingAddress;
use App\Models\SubscriptionMandate;
use App\Utils\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PhonePeSubscriptionController extends Controller
{
    private $accessToken;
    private $baseUrl;
    private $environment;
    protected $expiresAt;
    protected $clientId;
    protected $clientSecret;
    protected $clientVersion;
    protected $grantType;
    protected $merchantId;

    public function __construct()
    {
        $environment   = env('PHONEPE_ENV', 'uat');
        $this->baseUrl = $environment === 'pro' ? env('PHONEPE_BASE_URL_PROD') : env('PHONEPE_BASE_URL_UAT');

        $this->clientId      = env('PHONEPE_CLIENT_ID');
        $this->clientSecret  = env('PHONEPE_CLIENT_SECRET');
        $this->clientVersion = env('PHONEPE_CLIENT_VERSION');
        $this->grantType     = env('PHONEPE_GRANT_TYPE');
        $this->merchantId    = env('PHONEPE_MERCHANT_ID');

    }

    public function generateAccessToken()
    {
        $authUrl = "https://api.phonepe.com/apis/identity-manager/v1/oauth/token"; // Production Mode
                                                                                   // $authUrl = 'https://api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token';

        $payload = [
            "client_id"      => $this->clientId,
            "client_version" => $this->clientVersion,
            "client_secret"  => $this->clientSecret,
            "grant_type"     => $this->grantType,
        ];

        $response = Http::withHeaders([
            "Content-Type" => "application/x-www-form-urlencoded",
        ])->asForm()->post($authUrl, $payload);

        $result = $response->json();

        if (! isset($result['access_token']) || ! isset($result['expires_at'])) {
            return response()->json(['error' => 'Failed to fetch access token', 'response' => $result], 500);
        }

        $this->accessToken = $result['access_token'];
        $this->expiresAt   = $result['expires_at'];

        return $this->accessToken;
    }

    public function createMandate(Request $request)
    {
        $user = Helpers::getCustomerInformation($request);

        if (! $user || $user === 'offline') {
            return response()->json(['error' => 'Unauthorized user or guest not allowed'], 401);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'plan_code'                      => 'required|string',
            'plan_category'                  => 'required|string',
            'total_yearly_payment'           => 'required|numeric',
            'total_gold_purchase'            => 'required|numeric',
            'start_date'                     => 'required|date',
            'details'                        => 'required|array', // Ensure details are provided
            'details.*.monthly_payment'      => 'required|numeric',
            'details.*.purchase_gold_weight' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if (! $this->accessToken || time() >= $this->expiresAt - 600) {
            $this->generateAccessToken();
        }
        if (now()->timestamp >= $this->expiresAt) {
            return response()->json(['error' => 'Access token has expired'], 401);
        }
        // $setupUrl = $this->baseUrl . "/subscriptions/v2/setup";
        $setupUrl = "https://api.phonepe.com/apis/pg/subscriptions/v2/setup";

        $merchantOrderId        = 'MO' . now()->timestamp;
        $merchantSubscriptionId = 'MS' . now()->timestamp;

        $amount    = $request->input('amount', 100);          // Default 100
        $frequency = $request->input('frequency', 'MONTHLY'); // Default monthly
        $deviceOS  = $request->input('deviceOS', 'ANDROID');  // Default Android

        // Constructing the exact payload
        $payload = [
            "merchantOrderId" => $merchantOrderId,
            "amount"          => $amount * 100,
            "expireAt"        => now()->addMinutes(30)->timestamp * 1000, // Set expiration for 30 mins from now
            "metaInfo"        => [
                "udf1" => "some meta info of max length 256",
                "udf2" => "some meta info of max length 256",
                "udf3" => "some meta info of max length 256",
                "udf4" => "some meta info of max length 256",
                "udf5" => "some meta info of max length 256",
            ],
            "paymentFlow"     => [
                "type"                   => "SUBSCRIPTION_SETUP",
                "merchantSubscriptionId" => $merchantSubscriptionId,
                "authWorkflowType"       => "TRANSACTION",
                "amountType"             => "VARIABLE",
                "maxAmount"              => $amount * 100,
                "frequency"              => strtoupper($frequency),
                "expireAt"               => $this->getExpiryDate($request['plan_code']),
                "paymentMode"            => [
                    "type"      => "UPI_INTENT",
                    "targetApp" => "com.phonepe.app",
                ],
            ],
            "deviceContext"   => [
                "deviceOS" => $deviceOS,
            ],
        ];

        // Send the request to PhonePe
        $response = Http::withHeaders([
            "Content-Type"  => "application/json",
            "Authorization" => "O-Bearer " . $this->accessToken,
        ])->post($setupUrl, $payload);
        // Store in database if request is successful
        if ($response->successful()) {
            $installmentPayment = $this->createPaymentInstallment($user, $request);

            SubscriptionMandate::create([
                'user_id'           => $user->id,
                'installment_id'    => $installmentPayment->id,
                'transaction_id'    => $merchantOrderId,
                'mandate_id'        => $merchantSubscriptionId,
                'status'            => $response->json()['state'] ?? 'PENDING',
                'amount'            => $amount * 100,
                'frequency'         => strtoupper($frequency),
                'start_time'        => now(),
                'last_deduction_at' => now(),
            ]);

            return response()->json([
                'message'    => 'Mandate setup successful',
                'response'   => $response->json(),
                'mandate_id' => $merchantSubscriptionId,
            ]);
        } else {
            return response()->json([
                'error'   => 'Mandate setup failed',
                'details' => $response->json(),
            ], 500);
        }
    }

    public function getExpiryDate(string $planCode, string $startDate = 'now'): string
    {
        $durationMonths = match ($planCode) {
            'INR' => 12,
            'SNR' => 18,
            'TNR' => 24,
            default => 1,
        };

        // Calculate the expiry date
        return \Carbon\Carbon::parse($startDate)
            ->addMonths($durationMonths)
            ->timestamp * 1000;
    }

    public function createPaymentInstallment($user, $request)
    {
        $user = Helpers::getCustomerInformation($request);
        if (! $user || $user === 'offline') {
            return response()->json(['error' => 'Unauthorized user or guest not allowed'], 401);
        }

        $currency_model = getWebConfig(name: 'currency_model');

        if ($currency_model == 'multi_currency') {
            $currency_code = 'USD';
        } else {
            $default       = getWebConfig(name: 'system_default_currency');
            $currency_code = Currency::find($default)->code;
        }
        $additionalData                = [];
        $additionalData['customer_id'] = $user->id;
        $additionalData['request']     = $user;
        if (in_array($request['payment_request_from'], ['app'])) {

            $additionalData['payment_of']             = "installment_payment";
            $additionalData['installment_payment_id'] = isset($request['installment_payment_id']) ? $request['installment_payment_id'] : 0;

            $additionalData['plan_code']   = $request['plan_code'];
            $additionalData['plan_amount'] = $request['amount'];
            // $additionalData['order_note'] = $request['order_note'];
            $additionalData['plan_category']        = $request['plan_category'];
            $additionalData['total_yearly_payment'] = $request['payment_amount'];
            $additionalData['total_gold_purchase']  = $request['total_gold_purchase'];
            $additionalData['user_id']              = $user['user_id'];

            $additionalData['plan_category']        = $request['plan_category'];
            $additionalData['payment_request_from'] = $request['payment_request_from'];
            $additionalData['details']              = $request['details'];
        }

        $paymentAmount = $request->amount; //$cart_amount - $request['coupon_discount'] - $shippingCostSaved;
        $customer      = Helpers::getCustomerInformation($request);

        if ($customer == 'offline') {
            $address = ShippingAddress::where(['customer_id' => $request['customer_id'], 'is_guest' => 1])->latest()->first();
            if ($address) {
                $payer = new Payer(
                    $address->contact_person_name,
                    $address->email,
                    $address->phone,
                    ''
                );
            } else {
                $payer = new Payer(
                    'Contact person name',
                    '',
                    '',
                    ''
                );
            }
        } else {
            $payer = new Payer(
                $customer['f_name'] . ' ' . $customer['l_name'],
                $customer['email'],
                $customer['phone'],
                ''
            );
        }

        $paymentInfo = new PaymentInfo(
            success_hook: 'installment_payment_success',
            failure_hook: 'digital_payment_fail',
            currency_code: $currency_code,
            payment_method: $request['payment_method'],
            payment_platform: $request['payment_platform'],
            payer_id: $customer == 'offline' ? $request['customer_id'] : $customer['id'],
            receiver_id: '100',
            additional_data: $additionalData,
            payment_amount: $paymentAmount,
            external_redirect_link: $request['payment_platform'] == 'web' ? $request['external_redirect_link'] : null,
            attribute: 'order',
            attribute_id: idate("U")
        );

        $receiverInfo                    = new Receiver('receiver_name', 'example.png');
        $payment                         = new PaymentRequest();
        $payment->payment_amount         = $paymentAmount * 100;
        $payment->success_hook           = "installment_payment_success";
        $payment->failure_hook           = "digital_payment_fail";
        $payment->payer_id               = $user;
        $payment->receiver_id            = 1;
        $payment->currency_code          = $currency_code;
        $payment->payment_method         = "PhonePe";
        $payment->additional_data        = json_encode($request->all());
        $payment->payer_information      = json_encode($payer->information());
        $payment->receiver_information   = json_encode($receiverInfo->information());
        $payment->external_redirect_link = $paymentInfo->getExternalRedirectLink();
        $payment->attribute              = $paymentInfo->getAttribute();
        $payment->attribute_id           = $paymentInfo->getAttributeId();
        $payment->payment_platform       = $paymentInfo->getPaymentPlatForm();
        session()->put('payment_platform', $paymentInfo->getPaymentPlatForm());
        $payment->save();

        $data = [
            'payment_of'           => 'installment-payment',
            'user_id'              => $user->id,
            'plan_code'            => $request->plan_code,
            'plan_amount'          => $paymentAmount,
            'plan_category'        => $request->plan_category,
            'total_yearly_payment' => $paymentAmount,
            'total_gold_purchase'  => $request->total_gold_purchase,
            'start_date'           => $request->start_date,
        ];

        $installmentPayment = InstallmentPayment::create($data);

        return $installmentPayment;
    }
    public function checkSubscriptionStatus($transactionId)
    {
        if (! $this->accessToken) {
            $this->accessToken = $this->generateAccessToken();
        }

                                                                                                 // $statusUrl = $this->baseUrl . "/subscriptions/v2/$transactionId/status?details=true";
        $statusUrl = $this->baseUrl . "/pg/subscriptions/v2/$transactionId/status?details=true"; //PRODUCTION

        $response = Http::withHeaders([
            "Content-Type"  => "application/json",
            "Authorization" => "O-Bearer " . $this->accessToken,
        ])->get($statusUrl);

        if ($response->successful()) {
            $data = $response->json();

            //Update the status in DB for tracking
            SubscriptionMandate::where('mandate_id', $transactionId)
                ->update(['status' => $data['state'] ?? 'UNKNOWN']);

            // Fetch related installment ID
            $subscription = SubscriptionMandate::where('mandate_id', $transactionId)->first();
            if ($subscription) {
                $installmentId = $subscription->installment_id;

                // Update payment details in installment_payment_details
                DB::table('installment_payment_details')
                    ->where('installment_payment_id', $installmentId)
                    ->update([
                        'payment_status'  => $data['state'] == "ACTIVE" ? 'paid' : 'pending',
                        'payment_method'  => 'Phonepe',
                        'transaction_ref' => $subscription->transaction_id,
                        'payment_by'      => 'User',
                        'payment_note'    => 'Auto updated by subscription status check',
                        'payment_type'    => 1,
                        'updated_at'      => now(),
                    ]);
            }

            return response()->json($data);
        } else {
            return response()->json([
                'error'   => 'Failed to fetch subscription status',
                'details' => $response->json(),
            ], 500);
        }
    }

    public function subscriptionCancel($subscriptionId)
    {

        if (! $this->accessToken) {
            $this->accessToken = $this->generateAccessToken();
        }

        $cancelUrl = "https://api.phonepe.com/apis/pg/subscriptions/v2/$subscriptionId/cancel";

        $response = Http::withHeaders([
            "Content-Type"  => "application/json",
            "Authorization" => "O-Bearer " . $this->accessToken,
        ])->post($cancelUrl);

        if ($response->successful()) {
            SubscriptionMandate::where('mandate_id', $subscriptionId)->update(['status' => 'CANCELLED']);
            return response()->json(['message' => 'Subscription cancelled successfully']);
        } else {
            return response()->json(['error' => 'Failed to cancel subscription'], 500);
        }
    }

    public function autoDeduct()
    {
        $deductionResults = [
            'success' => [],
            'failed'  => [],
            'skipped' => [],
        ];

        $subscriptions = SubscriptionMandate::with([
            'installment' => function ($query) {
                $query->with([
                    'details' => function ($detailQuery) {
                        $detailQuery->where('payment_status', 'pending');
                    },
                ]);
            },
        ])
            ->where('status', 'ACTIVE')
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('frequency', 'DAILY')
                        ->where(function ($subQ) {
                            $subQ->whereNull('last_deduction_at')
                                ->orWhereDate('last_deduction_at', '<', now()->startOfDay());
                        });
                })->orWhere(function ($q) {
                    $q->where('frequency', 'WEEKLY')
                        ->where(function ($subQ) {
                            $subQ->whereNull('last_deduction_at')
                                ->orWhereRaw('DATE_ADD(last_deduction_at, INTERVAL 1 WEEK) <= ?', [now()]);
                        });
                })->orWhere(function ($q) {
                    $q->where('frequency', 'MONTHLY')
                        ->where(function ($subQ) {
                            $subQ->whereNull('last_deduction_at')
                                ->orWhereRaw('DATE_ADD(last_deduction_at, INTERVAL 1 MONTH) <= ?', [now()]);
                        });
                })->orWhere(function ($q) {
                    $q->where('frequency', 'YEARLY')
                        ->where(function ($subQ) {
                            $subQ->whereNull('last_deduction_at')
                                ->orWhereRaw('DATE_ADD(last_deduction_at, INTERVAL 1 YEAR) <= ?', [now()]);
                        });
                });
            })
            ->get();

        foreach ($subscriptions as $subscription) {
            try {
                if (! $subscription->installment) {
                    $deductionResults['skipped'][] = [
                        'subscription_id' => $subscription->id,
                        'mandate_id'      => $subscription->mandate_id,
                        'amount'          => $subscription->amount,
                        'status'          => 'Skipped - No Installment Found',
                    ];
                    continue;
                }

                if ($subscription->mandate_id != "") {
                    $this->generateAccessToken();
                    $redemptionUrl = $this->baseUrl . "/pg/subscriptions/v2/redeem";

                    // Get the transaction_ref from the related details
                    $transactionRef = $subscription->installment->details->first()->transaction_ref ?? 'MO' . now()->timestamp;

                    $response = Http::withHeaders([
                        "Content-Type"  => "application/json",
                        "Authorization" => "O-Bearer " . $this->accessToken,
                    ])->post($redemptionUrl, [
                        "merchantOrderId" => $transactionRef, // Using transaction_ref
                        "amount"          => $subscription->amount * 100,
                        "mandateId"       => $subscription->mandate_id,
                    ]);

                    if ($response->successful()) {
                        $subscription->update(['last_deduction_at' => now()]);

                        // Add new entry in installment_payment_details
                        DB::table('installment_payment_details')->insert([
                            'installment_id'  => $subscription->installment_id,
                            'payment_status'  => 'paid',
                            'payment_method'  => 'Phonepe',
                            'transaction_ref' => $transactionRef,
                            'payment_by'      => 'User',
                            'payment_note'    => 'Auto deducted via subscription',
                            'payment_type'    => 1,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);

                        $deductionResults['success'][] = [
                            'subscription_id' => $subscription->id,
                            'mandate_id'      => $subscription->mandate_id,
                            'amount'          => $subscription->amount,
                            'status'          => 'Success',
                        ];
                    } else {
                        $deductionResults['failed'][] = [
                            'subscription_id' => $subscription->id,
                            'mandate_id'      => $subscription->mandate_id,
                            'amount'          => $subscription->amount,
                            'status'          => 'Failed',
                            'error'           => $response->body(),
                        ];
                    }
                } else {
                    $deductionResults['skipped'][] = [
                        'subscription_id' => $subscription->id,
                        'mandate_id'      => $subscription->mandate_id,
                        'amount'          => $subscription->amount,
                        'status'          => 'Skipped - No Mandate ID Found',
                    ];
                }
            } catch (\Exception $e) {
                $deductionResults['failed'][] = [
                    'subscription_id' => $subscription->id,
                    'mandate_id'      => $subscription->mandate_id,
                    'amount'          => $subscription->amount,
                    'status'          => 'Failed',
                    'error'           => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'message' => 'Auto Deduction Process Completed',
            'results' => $deductionResults,
        ]);
    }

    public function notifyUser()
    {
        $subscriptions = SubscriptionMandate::with('installment')
        ->where('status', 'ACTIVE')
        ->where(function ($query) {
            $query->where(function ($q) {
                $q->whereRaw('LOWER(frequency) = ?', ['daily'])
                    ->where(function ($subQ) {
                        $subQ->whereNull('last_deduction_at')
                            ->orWhereDate('last_deduction_at', '<=', now()->startOfDay());
                    });
            })->orWhere(function ($q) {
                $q->whereRaw('LOWER(frequency) = ?', ['weekly'])
                    ->where(function ($subQ) {
                        $subQ->whereNull('last_deduction_at')
                            ->orWhere('last_deduction_at', '<=', now()->subWeek());
                    });
            })->orWhere(function ($q) {
                $q->whereRaw('LOWER(frequency) = ?', ['monthly'])
                    ->where(function ($subQ) {
                        $subQ->whereNull('last_deduction_at')
                            ->orWhere('last_deduction_at', '<=', now()->subMonth());
                    });
            })->orWhere(function ($q) {
                $q->whereRaw('LOWER(frequency) = ?', ['yearly'])
                    ->where(function ($subQ) {
                        $subQ->whereNull('last_deduction_at')
                            ->orWhere('last_deduction_at', '<=', now()->subYear());
                    });
            });
        })
        ->get();

        $results = [
            'notified' => [],
            'failed'   => [],
            'skipped'  => [],
        ];

        foreach ($subscriptions as $subscription) {
            if (empty($subscription->mandate_id)) {
                $results['skipped'][] = [
                    'subscription_id' => $subscription->id,
                    'reason'          => 'No Mandate ID',
                ];
                continue;
            }

            try {
                $this->generateAccessToken();
                $redemptionUrl = $this->baseUrl . "/pg/subscriptions/v2/notify";

                $merchantOrderId = 'MO' . now()->timestamp . rand(1000, 9999);

                $response = Http::withHeaders([
                    "Content-Type"  => "application/json",
                    "Authorization" => "O-Bearer " . $this->accessToken,
                ])->post($redemptionUrl, [
                    "merchantOrderId" => $merchantOrderId,
                    "amount"          => (int) $subscription->amount,
                    // "expireAt"        =>  $this->getExpiryDate(''),
                    "metaInfo"        => [
                        "udf1" => "Notification for subscription",
                        "udf2" => "Subscription ID: " . $subscription->id,
                        "udf3" => "Installment ID: " . $subscription->installment_id,
                    ],
                    "paymentFlow"     => [
                        "type"                    => "SUBSCRIPTION_REDEMPTION",
                        "merchantSubscriptionId"  => $subscription->mandate_id,
                        "redemptionRetryStrategy" => "STANDARD",
                        "autoDebit"               => true,
                    ],
                ]);

                if ($response->successful()) {
                    // Log and update the subscription
                    // $subscription->update(['last_deduction_at' => now()]);

                    // DB::table('installment_payment_details')->insert([
                    //     'installment_payment_id' => $subscription->installment_id,
                    //     'subscription_id'        => $subscription->id,
                    //     'payment_status'         => 'pending',
                    //     'payment_method'         => 'Phonepe',
                    //     'transaction_ref'        => $merchantOrderId,
                    //     'created_at'             => now(),
                    //     'updated_at'             => now(),
                    // ]);

                    $results['notified'][] = [
                        'subscription_id' => $subscription->id,
                        'merchantOrderId' => $merchantOrderId,
                        'status'          => 'Notification Sent',
                    ];
                } else {
                    $results['failed'][] = [
                        'subscription_id' => $subscription->id,
                        'merchantOrderId' => $merchantOrderId,
                        'status'          => 'Failed',
                        'error'           => $response->body(),
                    ];
                }
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'subscription_id' => $subscription->id,
                    'merchantOrderId' => $merchantOrderId ?? 'N/A',
                    'status'          => 'Error',
                    'error'           => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'message' => 'Notify Process Completed',
            'results' => $results,
        ]);
    }

    public function updateSubscriptionStatusInDB()
    {
        $this->generateAccessToken();
        $results = [];

        // Process mandates in chunks of 100 (you can adjust this number)
        SubscriptionMandate::chunk(100, function ($mandates) use (&$results) {
            foreach ($mandates as $mandate) {
                $transactionId = $mandate->mandate_id;
                $statusUrl     = $this->baseUrl . "/pg/subscriptions/v2/$transactionId/status?details=true";

                try {
                    $response = Http::withHeaders([
                        "Content-Type"  => "application/json",
                        "Authorization" => "O-Bearer " . $this->accessToken,
                    ])->get($statusUrl);

                    if ($response->successful()) {
                        $data      = $response->json();
                        $newStatus = $data['state'] ?? 'PENDING';
                        if (in_array($newStatus, ['ACTIVE', 'REVOKED', 'EXPIRED', 'CANCELLED', 'FAILED', 'PAUSED'])) {
                            $mandate->status = $newStatus;
                            $results[]       = "Mandate $transactionId updated to $newStatus.";
                        }
                        $mandate->save();
                    } else {
                        $results[] = "Failed to fetch status for mandate $transactionId - " . $response->body();
                    }
                } catch (\Exception $e) {
                    $results[] = "Exception for mandate $transactionId: " . $e->getMessage();
                }
            }
        });

        return response()->json(["results" => $results ?: ["No mandates processed."]], 200);
    }

    // Callback Handler Method
    public function handleCallback(Request $request)
    {
        // Log the incoming callback request
        Log::channel('phonepe_webhook')->info('PhonePe Callback Received', ['data' => $request->all()]);

        $type = $request->input('type');
        $data = $request->input('payload');

        if (! is_array($data) || ! isset($data['paymentFlow'])) {
            Log::channel('phonepe_webhook')->warning('Invalid callback structure');
            return response()->json(['message' => 'Invalid Callback Structure'], 400);
        }

        $status         = $data['state'] ?? 'PENDING';
        $mandateId      = $data['paymentFlow']['merchantSubscriptionId'] ?? null;
        $transactionRef = $data['merchantOrderId'] ?? "MO" . now()->timestamp;
        $amountInRupees = $data['amount'] / 100;

        Log::channel('phonepe_webhook')->info('Processing Callback', [
            'mandateId' => $mandateId,
            'status'    => $status,
            'type'      => $type,
        ]);

        if (! $mandateId) {
            Log::channel('phonepe_webhook')->warning('Callback Missing Mandate ID');
            return response()->json(['message' => 'Invalid Callback Data'], 400);
        }

        $subscription = SubscriptionMandate::where('mandate_id', $mandateId)->first();
        if (! $subscription) {
            Log::channel('phonepe_webhook')->error('Subscription Not Found for Mandate ID', ['mandateId' => $mandateId]);
            return response()->json(['message' => 'Subscription Not Found'], 404);
        }

        Log::channel('phonepe_webhook')->info('Subscription Found', [
            'subscription_id' => $subscription->id,
            'installment_id'  => $subscription->installment_id,
        ]);

        if( $status === 'COMPLETED') {
            $subscription->update(['last_deduction_at' => now()]);
        }

        Log::channel('phonepe_webhook')->info('Subscription Last Deduction Date Updated', [
            'subscription_id'   => $subscription->id,
            'last_deduction_at' => now(),
        ]);

        DB::table('installment_payment_details')->insert([
            'installment_payment_id' => $subscription->installment_id,
            'subscription_id'        => $subscription->id,
            'payment_status'         => $status === 'COMPLETED' ? 'paid' :  $status,
            'payment_method'         => 'Phonepe',
            'monthly_payment'        => $amountInRupees,
            'transaction_ref'        => $transactionRef,
            'payment_by'             => 'User',
            'payment_note'           => 'Auto deducted subscription setup',
            'payment_type'           => 1,
            'updated_at'             => now(),
            'created_at'             => now(),
        ]);

        // DB::table('installment_payment_details')->updateOrInsert(
        //     [
        //         'installment_payment_id' => $subscription->installment_id,
        //         'transaction_ref'        => $transactionRef,
        //     ],
        //     [
        //         'payment_status'  => $status === 'COMPLETED' ? 'paid' : 'pending',
        //         'payment_method'  => 'Phonepe',
        //         'monthly_payment' => $amountInRupees,
        //         'payment_by'      => 'User',
        //         'payment_note'    => 'Auto deducted subscription setup',
        //         'payment_type'    => 1,
        //         'updated_at'      => now(),
        //         'created_at'      => now(),
        //     ]
        // );

        Log::channel('phonepe_webhook')->info('Callback Processed Successfully', ['mandateId' => $mandateId]);
        return response()->json(['message' => 'Callback Processed'], 200);
    }

}
