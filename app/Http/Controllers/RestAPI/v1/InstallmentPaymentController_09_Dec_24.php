<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InstallmentPaymentResource;
use App\Models\InstallmentPayment;
use App\Utils\Helpers;
use Illuminate\Http\Request;

class InstallmentPaymentController extends Controller
{
    /**
     * Store installment payment data.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    // public function add(Request $request)
    // {
    //     // Get user information
    //     $user = Helpers::getCustomerInformation($request);

    //     if (!$user || $user === 'offline') {
    //         return response()->json(['error' => 'Unauthorized user or guest not allowed'], 401);
    //     }

    //     // Validate request
    //     $request->validate([
    //         'plan_code' => 'required|string',
    //         'plan_category' => 'required|string',
    //         'monthly_payment' => 'required|numeric',
    //         'total_yearly_payment' => 'required|numeric',
    //         'purchase_gold_weight' => 'required|numeric',
    //         'total_gold_purchase' => 'required|numeric',
    //         'start_date' => 'required|date',
    //     ]);

    //     // Store data
    //     $installmentPayment = InstallmentPayment::create([
    //         'user_id' => $user->id,
    //         'plan_code' => $request->plan_code,
    //         'plan_category' => $request->plan_category,
    //         'monthly_payment' => $request->monthly_payment,
    //         'total_yearly_payment' => $request->total_yearly_payment,
    //         'purchase_gold_weight' => $request->purchase_gold_weight,
    //         'total_gold_purchase' => $request->total_gold_purchase,
    //         'start_date' => $request->start_date,
    //     ]);

    //     return response()->json(['success' => true, 'data' => $installmentPayment], 201);
    // }

    public function add(Request $request)
    {
        // Get user information
        $user = Helpers::getCustomerInformation($request);

        if (!$user || $user === 'offline') {
            return response()->json(['error' => 'Unauthorized user or guest not allowed'], 401);
        }

        // Validate request
        $request->validate([
            'plan_code' => 'required|string',
            'plan_category' => 'required|string',
            'total_yearly_payment' => 'required|numeric',
            'total_gold_purchase' => 'required|numeric',
            'start_date' => 'required|date',
            'details' => 'required|array', // Ensure details are provided
            'details.*.monthly_payment' => 'required|numeric',
            'details.*.purchase_gold_weight' => 'required|numeric',
        ]);

        // Prepare main installment payment data
        $data = [
            'user_id' => $user->id,
            'plan_code' => $request->plan_code,
            'plan_category' => $request->plan_category,
            'total_yearly_payment' => $request->total_yearly_payment,
            'total_gold_purchase' => $request->total_gold_purchase,
            'start_date' => $request->start_date,
        ];

        // Prepare details data
        $details = $request->details;

        // Use the model method to create the data
        $installmentPayment = InstallmentPayment::createWithDetails($data, $details);

        // return response()->json(['success' => true, 'data' => $installmentPayment], 200);
        return response()->json(['success' => true, 'data' => new InstallmentPaymentResource($installmentPayment)], 200);
    }
}
