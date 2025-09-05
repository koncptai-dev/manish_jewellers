<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InstallmentPaymentHistoryResource;
use App\Http\Resources\InstallmentPaymentResource;
use App\Http\Resources\InstallmentResource;
use App\Models\InstallmentPayment;
use App\Utils\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Library\Payment as PaymentInfo;
use App\Models\Currency;
use App\Models\ShippingAddress;
use App\Utils\CartManager;
use App\Library\Payer;
use App\Library\Receiver;
use Brian2694\Toastr\Facades\Toastr;
use App\Traits\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class InstallmentPaymentController extends Controller
{

    
    public function checkActivePlan(Request $request)
{
    $user = auth()->user();
    
    // $activePlan = DB::table('installment_payments as ip')
    //     ->join('subscription_mandates as sm', function ($join) {
    //         $join->on('ip.id', '=', 'sm.installment_id')
    //              ->where('sm.status', 'ACTIVE');
    //     })
    //     ->select(
    //         DB::raw("'ONLINE' AS plan_type"),
    //         'ip.id',
    //         'ip.uuid',
    //         'ip.plan_code',
    //         'ip.plan_amount',
    //         'ip.plan_category',
    //         'ip.total_yearly_payment',
    //         'ip.total_gold_purchase',
    //         'ip.user_id',
    //         'ip.no_of_months',
    //         'ip.credit_bonus',
    //         'ip.start_date',
    //         'ip.end_date',
    //         'ip.created_at',
    //         'ip.updated_at',
    //         'sm.mandate_id',
    //         'sm.status as mandate_status',
    //         'sm.amount as mandate_amount',
    //         'sm.frequency as mandate_frequency',
    //         'sm.start_time as mandate_start_time',
    //         'sm.end_time as mandate_end_time',
    //         'sm.last_deduction_at as mandate_last_deduction'
    //     )
    //     ->where('ip.user_id', $user->id) // Filter by authenticated user
    //     ->where('ip.start_date', '<=', Carbon::now())
    //     ->where(function ($query) {
    //         $query->whereNull('ip.end_date')
    //             ->orWhere('ip.end_date', '>=', Carbon::now());
    //     })
    //     ->unionAll(
    //         DB::table('offline_payment_requests')
    //             ->select(
    //                 DB::raw("'OFFLINE' AS plan_type"),
    //                 'id',
    //                 DB::raw('NULL AS uuid'),
    //                 'plan_code',
    //                 'plan_amount',
    //                 'plan_category',
    //                 'total_yearly_payment',
    //                 'total_gold_purchase',
    //                 'user_id',
    //                 'no_of_months',
    //                 DB::raw('NULL AS credit_bonus'),
    //                 'start_date',
    //                 'end_date',
    //                 'created_at',
    //                 'updated_at',
    //                 DB::raw('NULL AS mandate_id'),
    //                 DB::raw('NULL AS mandate_status'),
    //                 DB::raw('NULL AS mandate_amount'),
    //                 DB::raw('NULL AS mandate_frequency'),
    //                 DB::raw('NULL AS mandate_start_time'),
    //                 DB::raw('NULL AS mandate_end_time'),
    //                 DB::raw('NULL AS mandate_last_deduction')
    //             )
    //             ->where('user_id', $user->id) // Filter by authenticated user
    //             ->where('start_date', '<=', Carbon::now())
    //             ->where(function ($query) {
    //                 $query->whereNull('end_date')
    //                     ->orWhere('end_date', '>=', Carbon::now());
    //             })
    //             ->where('status', 'ACTIVE')
    //     )
    //     ->get();

 $activePlan = InstallmentPayment::where('installment_payments.user_id', $user->id)
    ->leftjoin('subscription_mandates as sm', function ($join) {
        $join->on('installment_payments.id', '=', 'sm.installment_id')
             ->where('sm.status', 'ACTIVE');
    })
    ->select(
        'installment_payments.*',
        'sm.mandate_id',
        'sm.status as mandate_status',
        'sm.amount as mandate_amount'
    )
    ->get();

    if ($activePlan->isNotEmpty()) {
        return response()->json([
            'status' => true,
            'message' => 'Active installment plan(s) found.',
            'plan' => $activePlan
        ]);
    } else {
        return response()->json([
            'status' => false,
            'message' => 'No active installment plan found.'
        ]);
    }
}

    // public function checkActivePlan(Request $request)
    // {
    //     $user = auth()->user();

    //     $activePlan = DB::table('offline_payment_requests')
    //         ->where('user_id', $user->id)
    //         ->where('status','done')
    //         ->where('start_date', '<=', now())
    //         ->where('end_date', '>=', now())
    //         ->orderBy('start_date', 'desc')
    //         ->first();

    //     if ($activePlan) {
    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Active installment plan found.',
    //             'plan' => $activePlan
    //         ]);
    //     } else {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No active installment plan found.'
    //         ]);
    //     }
    // }

    public function digitalPaymentSuccess(Request $request)
    {
        // $id = '675868d0-76e5-423f-813e-d5f4f83e9578';
        // $test = DB::table('payment_requests')->find($id);

        // $param1 = 'TransactionID123';
        // $param2 = 'Completed';

        // // Call the helper function
        $response = installment_phone_paye_payment_success($request->all());

        // return response()->json(['message' => $response]);
    }

    /**
     * Store installment payment data.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function add(Request $request)
    {
        // Get user information
        $user = Helpers::getCustomerInformation($request);

        if (!$user || $user === 'offline') {
            return response()->json(['error' => 'Unauthorized user or guest not allowed'], 401);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'plan_code' => 'required|string',
            'plan_category' => 'required|string',
            'total_yearly_payment' => 'required|numeric',
            'total_gold_purchase' => 'required|numeric',
            'start_date' => 'required|date',
            'details' => 'required|array', // Ensure details are provided
            'details.*.monthly_payment' => 'required|numeric',
            'details.*.purchase_gold_weight' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }


        // Prepare main installment payment data
        // $data = [
        //     'payment_of' => 'installment-payment',
        //     'user_id' => $user->id,
        //     'plan_code' => $request->plan_code,
        //     'plan_amount' => $request->plan_amount,
        //     'plan_category' => $request->plan_category,
        //     'total_yearly_payment' => $request->total_yearly_payment,
        //     'total_gold_purchase' => $request->total_gold_purchase,
        //     'start_date' => $request->start_date,
        // ];

        // // Prepare details data
        // $details = $request->details;

        // if ($request->id > 0)
        //     // Use the model method to create the data
        //     $installmentPayment = InstallmentPayment::updateWithDetails($request, $details);
        // else
        //     // Use the model method to create the data
        //     $installmentPayment = InstallmentPayment::createWithDetails($data, $details);

        $redirectLink = $this->getCustomerPaymentRequest($request);

        if (in_array($request['payment_request_from'], ['app'])) {
            return response()->json([
                'redirect_link' => $redirectLink,
                'new_user' => isset($orderAdditionalData['new_customer_info']) && $orderAdditionalData['new_customer_info'] != null ? 1 : 0,
            ], 200);
        } else {
            return redirect($redirectLink);
        }

        // return response()->json(['success' => true, 'data' => $installmentPayment], 200);
        // return response()->json(['success' => true, 'data' => new InstallmentPaymentResource($installmentPayment)], 200);
    }

    public function list(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user || $user === 'offline') {
            return response()->json(['error' => 'Unauthorized user or guest not allowed'], 401);
        }

        // Fetch all installment payments with details
        $installments = InstallmentPayment::with(['details' => function ($query) {
        $query->where('payment_status', 'paid');
            }])
            ->where('user_id', $user->id)
            ->whereHas('details', function ($query) {
                $query->where('payment_status', 'paid');
            })
            ->get();

        // Group by user_id and plan_code
        $grouped = $installments->groupBy(function ($item) {
            return $item->user_id . '|' . $item->plan_code;
        });

        // Aggregate grouped data manually
        $mergedInstallments = $grouped->map(function ($group) {
            $first = $group->first();

            // Merge and sum fields
            $merged = clone $first;
            $merged->total_gold_purchase = $group->sum('total_gold_purchase');
            $merged->monthly_average = $group->sum('monthly_average');
            $merged->total_yearly_payment = $group->sum('total_yearly_payment');

            // Merge all details
            $merged->setRelation('details', $group->flatMap->details);

            return $merged;
        })->values();

        return response()->json([
            'success' => true,
            'data' => InstallmentResource::collection($mergedInstallments)
        ], 200);
    }

    /**
     * Store installment payment data.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function nexPayment(Request $request)
    {
        // Get user information
        $user = Helpers::getCustomerInformation($request);

        if (!$user || $user === 'offline') {
            return response()->json(['error' => 'Unauthorized user or guest not allowed'], 401);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'plan_code' => 'required|string',
            'plan_category' => 'required|string',
            'total_yearly_payment' => 'required|numeric',
            'total_gold_purchase' => 'required|numeric',
            'start_date' => 'required|date',
            'details' => 'required|array', // Ensure details are provided
            'details.*.monthly_payment' => 'required|numeric',
            'details.*.purchase_gold_weight' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Prepare details data
        $details = $request->details;

        // Use the model method to create the data
        $installmentPayment = InstallmentPayment::updateWithDetails($request, $details);

        // return response()->json(['success' => true, 'data' => $installmentPayment], 200);
        return response()->json(['success' => true, 'data' => new InstallmentPaymentResource($installmentPayment)], 200);
    }

    public function userInstallmentPaymentHistory(Request $request)
    {
        // Get user information
        $user = Helpers::getCustomerInformation($request);

        if (!$user || $user === 'offline') {
            return response()->json(['error' => 'Unauthorized user or guest not allowed'], 401);
        }

        // Fetch user's installments with related details
        $installments = InstallmentPayment::with('details')->where('user_id', $user->id)->get();

        $details = InstallmentPaymentHistoryResource::collection($installments);

        // Return transformed data
        return response()->json([
            'success' => true,
            'data' => $details
        ], 200);
    }
    public function getCustomerPaymentRequest(Request $request): mixed
    {
        $additionalData = [];

        $user = Helpers::getCustomerInformation($request);

        $additionalData['customer_id'] = $user->id;
        $additionalData['request'] = $user;
        if (in_array($request['payment_request_from'], ['app'])) {

            $additionalData['payment_of'] = "installment_payment";
            $additionalData['installment_payment_id'] = isset($request['id']) ?  $request['id'] : 0;

            $additionalData['plan_code'] = $request['plan_code'];
            $additionalData['plan_amount'] = $request['plan_amount'];
            // $additionalData['order_note'] = $request['order_note'];
            $additionalData['plan_category'] = $request['plan_category'];
            $additionalData['total_yearly_payment'] = $request['total_yearly_payment'];
            $additionalData['total_gold_purchase'] = $request['total_gold_purchase'];
            $additionalData['user_id'] = $user['user_id'];

            $additionalData['plan_category'] = $request['plan_category'];
            $additionalData['payment_request_from'] = $request['payment_request_from'];
            $additionalData['details'] = $request['details'];
        }
        //  else {
        // $additionalData['customer_id'] = $user != 'offline' ? $user->id : $getCustomerID;
        //     // $additionalData['order_note'] = session('order_note') ?? null;
        //     // $additionalData['address_id'] = session('address_id') ?? 0;
        //     // $additionalData['billing_address_id'] = session('billing_address_id') ?? 0;

        //     // $additionalData['coupon_code'] = session('coupon_code') ?? null;
        //     // $additionalData['coupon_discount'] = session('coupon_discount') ?? 0;
        //     $additionalData['payment_request_from'] = $request['payment_mode'] ?? 'web';
        // }
        // $additionalData['new_customer_id'] = $getCustomerID;
        // $additionalData['is_guest_in_order'] = $isGuestUserInOrder;

        $currency_model = getWebConfig(name: 'currency_model');

        if ($currency_model == 'multi_currency') {
            $currency_code = 'USD';
        } else {
            $default = getWebConfig(name: 'system_default_currency');
            $currency_code = Currency::find($default)->code;
        }

        if (in_array($request['payment_request_from'], ['app'])) {
            // $cart_group_ids = CartManager::get_cart_group_ids(request: $request, type: 'checked');
            // $cart_amount = 0;
            // $shippingCostSaved = 0;
            // foreach ($cart_group_ids as $group_id) {
            //     $cart_amount += CartManager::api_cart_grand_total($request, $group_id);
            //     $shippingCostSaved += CartManager::get_shipping_cost_saved_for_free_delivery(groupId: $group_id, type: 'checked');
            // }
            $paymentAmount = $request->plan_amount; //$cart_amount - $request['coupon_discount'] - $shippingCostSaved;
        }
        //  else {
        //     $discount = session()->has('coupon_discount') ? session('coupon_discount') : 0;
        //     $orderWiseShippingDiscount = CartManager::order_wise_shipping_discount();
        //     $shippingCostSaved = CartManager::get_shipping_cost_saved_for_free_delivery(type: 'checked');
        //     $paymentAmount = CartManager::cart_grand_total(type: 'checked') - $discount - $orderWiseShippingDiscount - $shippingCostSaved;
        // }

        $customer = Helpers::getCustomerInformation($request);

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
            if (empty($customer['phone'])) {
                Toastr::error(translate('please_update_your_phone_number'));
                return route('checkout-payment');
            }
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

        $receiverInfo = new Receiver('receiver_name', 'example.png');
        $redirect_link = Payment::generate_link($payer, $paymentInfo, $receiverInfo);

        return $redirect_link;
    }

    public function withdrawList(Request $request, $installment_id)
    {
        $user = Helpers::getCustomerInformation($request);

        if (!$user || $user === 'offline') {
            return response()->json(['error' => 'Unauthorized user or guest not allowed'], 401);
        }

        // Fetch installment by ID
        $installment = InstallmentPayment::findOrFail($installment_id);

        $history = $installment->withdrawals()
            ->orderBy('created_at', 'desc')
            ->get(['amount', 'remarks', 'created_at']);

        // Calculate the current total withdrawn amount for this specific installment
        $currentTotalWithdrawn = $history->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'history' => $history,
                'current_total_withdrawn' => $currentTotalWithdrawn,
            ],
        ], 200);
    }

    public function withdrawRequest(Request $request)
    {
        $user = Helpers::getCustomerInformation($request);

        if (!$user || $user === 'offline') {
            return response()->json(['error' => 'Unauthorized user or guest not allowed'], 401);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'installment_id' => 'required|exists:installment_payments,id',
            'amount' => 'required|numeric|min:1',
            'remarks' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Fetch installment by ID
        $installment = InstallmentPayment::with('details')
        ->findOrFail($request->installment_id);

       $totalPaid = $installment->details()
        ->where('payment_status', 'paid')
        ->sum('monthly_payment');
       
        // Calculate the total withdrawn amount
        $totalWithdrawn = $installment->withdrawals()->sum('amount');

        // Available for withdrawal
        $availableForWithdrawal = $totalPaid - $totalWithdrawn;

        if ($request->amount > $availableForWithdrawal) {
            return response()->json([
                'success' => false,
                'message' => 'Requested amount exceeds available balance for withdrawal.',
            ], 400);
        }

        // Create a new withdrawal record
        $installment->withdrawals()->create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'remarks' => $request->remarks,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal request submitted successfully.',
        ], 200);
    }

}
