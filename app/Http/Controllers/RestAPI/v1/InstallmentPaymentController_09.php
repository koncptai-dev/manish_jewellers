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

class InstallmentPaymentController extends Controller
{
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

    public function list(Request $request)
    {
        // Get user information
        $user = Helpers::getCustomerInformation($request);

        if (!$user || $user === 'offline') {
            return response()->json(['error' => 'Unauthorized user or guest not allowed'], 401);
        }

        // Fetch user's installments with related details
        $installments = InstallmentPayment::with('details')->where('user_id', $user->id)->get();

        // Return transformed data
        return response()->json([
            'success' => true,
            'data' => InstallmentResource::collection($installments)
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
}
