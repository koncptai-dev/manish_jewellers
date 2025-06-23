<?php
namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoanResource;
use App\Models\InstallmentPayment;
use App\Models\InstallmentPaymentDetail;
use App\Models\Loan;
use App\Models\LoanPaymentRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoanController extends Controller
{
    //aashir0502
    public function index()
    {
        return view('admin.loans.index'); // Render the Loan List page
    }

    // public function getLoans(Request $request)
    // {
    //     if ($request->ajax()) {
    //         $loans = Loan::with('user') // Fetch the user details
    //             ->select('loans.*');

    //         return DataTables::of($loans)
    //             ->addColumn('user_name', function ($row) {
    //                 return $row->user ? $row->user->name : 'Unknown';
    //             })
    //             ->addColumn('loan_amount', function ($row) {
    //                 return $row->loan_amount;
    //             })
    //             ->addColumn('loan_date', function ($row) {
    //                 return \Carbon\Carbon::parse($row->loan_date)->format('Y-m-d');
    //             })
    //             ->addColumn('loan_end_date', function ($row) {
    //                 return \Carbon\Carbon::parse($row->loan_end_date)->format('Y-m-d');
    //             })
    //             ->addColumn('status', function ($row) {
    //                 return 'Active'; // Modify based on your loan status logic
    //             })
    //             ->make(true);
    //     }

    //     return response()->json(['error' => 'Invalid request'], 400);
    // }

    // public function checkEligibility(Request $request)
    // {
    //     // Get authenticated user
    //     $user = Auth::user();

    //     // Validate input
    //     $request->validate([
    //         'plan_code' => 'required|in:INR,SNR,TNR', // Only allow valid plan codes
    //     ]);

    //     $planCode = $request->plan_code;

    //     // Define dynamic eligibility criteria
    //     $eligibilityCriteria = [
    //         'INR' => ['min_investment' => 600, 'months' => 6],
    //         'SNR' => ['min_investment' => 1000, 'months' => 12],
    //         'TNR' => ['min_investment' => 600, 'months' => 18],
    //     ];

    //     // Get the criteria for the selected plan
    //     if (!isset($eligibilityCriteria[$planCode])) {
    //         return response()->json(['error' => 'Invalid plan code.'], 400);
    //     }

    //     $criteria = $eligibilityCriteria[$planCode];

    //     // Fetch the user's active installment payment plan
    //     $installmentPayment = InstallmentPayment::where('user_id', $user->id)
    //         ->where('plan_code', $planCode)
    //         ->first();

    //     if (!$installmentPayment) {
    //         return response()->json([
    //             'eligible' => false,
    //             'message' => 'No active installment plan found for the user.',
    //         ]);
    //     }

    //     // Check if the plan started at least X months ago
    //     $startDate = Carbon::parse($installmentPayment->start_date);
    //     $monthsSinceStart = $startDate->diffInMonths(Carbon::now());

    //     if ($monthsSinceStart < $criteria['months']) {
    //         return response()->json([
    //             'eligible' => false,
    //             'message' => "Plan must be active for at least {$criteria['months']} months. Currently active for {$monthsSinceStart} months.",
    //         ]);
    //     }

    //     // Fetch total monthly payments from InstallmentPaymentDetail
    //     $totalInvestment = InstallmentPaymentDetail::where('installment_payment_id', $installmentPayment->id)
    //         ->sum('monthly_payment');

    //     // Check if the total investment meets the minimum requirement
    //     if ($totalInvestment < $criteria['min_investment']) {
    //         return response()->json([
    //             'eligible' => false,
    //             'message' => "Total investment is ₹{$totalInvestment}, which is less than the minimum required ₹{$criteria['min_investment']}.",
    //         ]);
    //     }

    //     // Calculate 70% of total investment for loan eligibility
    //     $maxLoanAmount = round($totalInvestment * 0.70, 2);

    //     // If all conditions are satisfied
    //     return response()->json([
    //         'eligible' => true,
    //         'message' => 'You are eligible for a loan.',
    //         'total_investment' => $totalInvestment,
    //         'max_loan_amount' => $maxLoanAmount,
    //     ]);
    // }

    public function request(Request $request)
    {
        // Get authenticated user
        $user = Auth::user();

        // Validate input
        $request->validate([
            'installment_id' => 'required',
        ]);

        // Fetch the user's active installment payment plan
        $installmentPayment = InstallmentPayment::where('user_id', $user->id)
            ->where('id', $request->installment_id)
            ->first();

        if (! $installmentPayment) {
            return response()->json([
                'eligible' => false,
                'message'  => 'No active installment plan found for the user.',
            ]);
        }

        //  // Check if the plan started at least 6 months ago
        // if (Carbon::parse($installmentPayment->start_date)->gt(Carbon::now()->subMonths(6))) {
        //     return response()->json([
        //         'eligible' => false,
        //         'message' => 'Loan is not eligible for this installment plan as it has not been active for at least 6 months.',
        //     ]);
        // }

        // Check if the plan has been active for at least 6 months
        $startDate        = Carbon::parse($installmentPayment->start_date);
        $monthsSinceStart = $startDate->diffInMonths(Carbon::now());

        //Todo Do not remove this live
         if ($monthsSinceStart < 6) {
             return response()->json([
                 'eligible' => false,
                 'message' => "Plan must be active for at least 6 months. Currently active for {$monthsSinceStart} months.",
             ]);
         }

        // Fetch total monthly payments from InstallmentPaymentDetail
        $totalInvestment = InstallmentPaymentDetail::where('installment_payment_id', $installmentPayment->id)
            ->sum('monthly_payment');

        // // Minimum investment required to be eligible for a loan
        // $min_investment = 1000;

        // // Check if total investment meets the minimum required investment
        // if ($totalInvestment < $min_investment) {
        //     return response()->json([
        //         'eligible' => false,
        //         'message' => "Total investment is ₹{$totalInvestment}, which is less than the minimum required ₹{$min_investment}.",
        //     ]);
        // }

        // Calculate 70% of total investment as the max loan amount
        $maxLoanAmount = round($totalInvestment * 0.70, 2);

                                                                           // Calculate remaining duration (remaining months from no_of_months - 6)
        $remaining_months = max($installmentPayment->no_of_months - 6, 1); // Ensure at least 1 month

                                                        // Calculate total EMI (1.5% of loan amount)
        $totalEmi = round(($maxLoanAmount * 0.015), 2); // 1.5% of loan amount

                                                                                // Monthly EMI over the remaining months
        $monthlyEmi = round($maxLoanAmount / $remaining_months, 2) + $totalEmi; // Principal + Interest per month

        // If all conditions are satisfied
        return response()->json([
            'eligible'           => true,
            'message'            => 'You are eligible for a loan.',
            'total_investment'   => $totalInvestment,
            'max_loan_amount'    => $maxLoanAmount,
            'emi_per_month'      => $monthlyEmi,
            'total_emi_interest' => $totalEmi,         // 1.5% of loan amount
            'loan_duration'      => $remaining_months, // Remaining months
        ]);
    }

    public function confirmPayment(Request $request)
    {

        // Validate input
        $request->validate([
            'loan_amount'    => 'required|numeric|min:1',
            'no_of_months'   => 'required|integer|min:1',
            'no_of_emi'      => 'required|integer|min:1',
            'remarks'        => 'nullable|string',
            'installment_id' => 'required',
            'loan_id'        => 'nullable|integer',
        ]);

        $user = Auth::user();

        // Retrieve all data from the request
        $loanAmount    = $request->loan_amount;
        $noOfMonths    = $request->no_of_months;
        $noOfEmi       = $request->no_of_emi;
        $remarks       = $request->remarks ?? 'No remarks provided.'; // Default remarks if not provided
        $installmentId = $request->installment_id;
        $loanId        = $request->loan_id; // Get loan_id from request (nullable)
        $requestType   = $request->type;
        $remarks       = $request->remarks;

        // Insert into the loan_payment_requests table
        try {
            $loanPaymentRequest = LoanPaymentRequest::createLoanPaymentRequest($loanAmount, $noOfMonths, $installmentId, $noOfEmi, $user->id, $loanId, $requestType, $remarks);

            // $loanPaymentRequest = LoanPaymentRequest::create([
            //     'loan_amount' => $loanAmount,
            //     'no_of_months' => $noOfMonths,
            //     'plan_code' => $installmentId,
            //     'no_of_emi' => $noOfEmi,
            //     'user_id' => $user->id,
            //     'loan_id' => $loanId, // Add loan_id here (it can be null)
            //     'request_type' => 'Loan Request',  // You can modify this as needed
            //     'remarks' => $remarks,
            //     'request_date' => Carbon::now(),
            //     'status' => 'pending', // Default status before payment is verified
            //     'created_at' => Carbon::now(),
            //     'updated_at' => Carbon::now(),
            // ]);
            // $loanPaymentRequest = LoanPaymentRequest::createLoanPaymentRequest($validatedData);
        } catch (\Exception $e) {
            return response()->json([
                'error'     => 'Failed to create loan payment request.',
                'exception' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message'              => 'Loan payment request created successfully.',
            'loan_payment_request' => $loanPaymentRequest,
        ]);
    }

    public function list(Request $request)
    {
        // Get user information
        $user = Auth::id();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized user or guest not allowed'], 401);
        }

        // Fetch user's installments with related details

        $installments = Loan::with([
            'loanPaymentRequests' => function ($query) {
                $query->where('status', 'approved');
            }
        ])
        ->where('user_id', $user)
        ->get();
        
        // Return transformed data
        return response()->json([
            'success' => true,
            'data'    => LoanResource::collection($installments),
        ], 200);
    }
}
