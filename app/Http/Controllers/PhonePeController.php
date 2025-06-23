<?php

namespace App\Http\Controllers;

use App\Models\InstallmentPayment;
use App\Models\InstallmentPaymentDetail;
use App\Models\OfflinePaymentRequests;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Services\PhonePeService;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Traits\Processor;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Razorpay\Api\Api;

class PhonePeController extends Controller
{
    use Processor;

    private PaymentRequest $payment;
    private $user;
    private $phonePeService;

    // public function __construct(PhonePeService $phonePeService)
    // {
    //     $this->phonePeService = $phonePeService;
    // }

    public function __construct(PaymentRequest $payment, User $user, PhonePeService $phonePeService)
    {
        $this->phonePeService = $phonePeService;
        $this->payment = $payment;
        $this->user = $user;
        $config = $this->payment_config('razor_pay', 'payment_config');
        $razor = false;
        if (!is_null($config) && $config->mode == 'live') {
            $razor = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $razor = json_decode($config->test_values);
        }

        if ($razor) {
            $config = array(
                'api_key' => $razor->api_key,
                'api_secret' => $razor->api_secret
            );
            Config::set('razor_config', $config);
        }

        
    }

    // API to initiate payment
    public function initiatePaymentPage(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        $data = $this->payment::where(['id' => $request['payment_id']])->where(['is_paid' => 0])->first();


        if (!isset($data)) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }
        $payer = json_decode($data['payer_information']);

        if ($data['additional_data'] != null) {
            $business = json_decode($data['additional_data']);
            $business_name = $business->business_name ?? "my_business";
            $business_logo = $business->business_logo ?? url('/');
        } else {
            $business_name = "my_business";
            $business_logo = url('/');
        }

        // return view('payment.phonePe', compact('data', 'payer', 'business_logo', 'business_name'));
        // Initiate PhonePe Payment
        $redirectUrl = route('phonePe.payment.callback', ['merchantOrderId' => $data->id]);
        // $response = $this->initiatePayment($data->payment_amount, $redirectUrl);
        $response = $this->phonePeService->initiatePayment($data->payment_amount, $redirectUrl, $data->id);

        if (isset($response['redirectUrl']) && $response['redirectUrl']) {
            return redirect()->to($response['redirectUrl']); // Redirect to PhonePe checkout
        }

        return response()->json(['message' => 'Payment initiation failed.'], 400);
    }

    // API to initiate payment
    public function createPayment(Request $request)
    {
        $request->validate([
            'plan_amount' => 'required|numeric|min:1'
        ]);
        $orderId = "TX_" . uniqid();
        // $redirectUrl = route('phonepe.callback');
        $redirectUrl = route('payment.callback', ['merchantOrderId' => $orderId]);
        $response = $this->phonePeService->initiatePayment($request->plan_amount, $redirectUrl, $orderId);
        // $result = $response->json(); // Decode the JSON response

        // return response()->json([
        //     "redirectUrl" => $response['redirectUrl'] ?? null
        // ]);

        return response()->json($response);
    }


    public function paymentStatus($merchantOrderId)
    {
        $response = $this->phonePeService->paymentCallback($merchantOrderId);
        $payment_platform = session()->get('payment_platform', 'app');

        if($payment_platform == "web") { 
            $isNewCustomerInSession = session('newCustomerRegister');
            session()->forget('newCustomerRegister');
            session()->forget('newRegisterCustomerInfo');
            $orderIds = session('order_ids');
            return view(VIEW_FILE_NAMES['order_complete'], [
                'order_ids' => $orderIds,
                'isNewCustomerInSession' => $isNewCustomerInSession,
            ]);
        }else{
            return $response;
        }
        
    }

    public function paymentStatusUpdate($merchantOrderId){
        return $this->phonePeService->orderPaymentStatusUpdate($merchantOrderId);
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



    public function mobilePhonePeOrderCreate(Request $request)
    {
        $user = Auth::user();
        $amount = $request->input('plan_amount'); // Convert to paise


        // // $payment = (new PaymentRequest)->storeData($user_id, $request->amount, $request->currency, 'pending', $request->remarks);
        // $payment = (new OfflinePaymentRequests())->storeData($user->id, $request->plan_amount, $request->plan_code, $request->plan_category, $request->total_yearly_payment, $request->total_gold_purchase, $request->start_date, $request->installment_id, $request->request_date, $request->no_of_months, $request->remarks);
        // Store payment request
        $paymentRequest = PaymentRequest::createPaymentRequest([
            'payer_id' => $user->id,
            'receiver_id' => 1,
            'payment_amount' => $amount ,
            'gateway_callback_url' => '',
            'success_hook' => 'digital_phone_pay_payment_success',
            'failure_hook' => 'digital_payment_fail',
            'payer_information' => json_encode($user),
            'transaction_id' => null,
            'payment_method' => "PhonePe",
            'additional_data' => json_encode($request->all()),
            'is_paid' => 0,
        ]);

        $merchantOrderId = $paymentRequest->id;

        $result = $this->phonePeService->createPayment($amount, $merchantOrderId);

        return response()->json($result);
        // if ($response->successful() && isset($result['data']['instrumentResponse']['redirectInfo']['url'])) {
        //     return response()->json(['redirect_url' => $result['data']['instrumentResponse']['redirectInfo']['url']]);
        // }

        // return response()->json(['error' => 'Failed to create payment order', 'response' => $result], 500);
    }

    public function mobilePhonePeOrderStatus(Request $request)
    {
        $result = $this->phonePeService->checkOrderStatus($request->merchantOrderId);

        return response()->json($result);
    }
}
