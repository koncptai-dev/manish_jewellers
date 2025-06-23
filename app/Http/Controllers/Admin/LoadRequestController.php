<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\PaymentRequest;

use Yajra\DataTables\Facades\DataTables;
use App\Models\InstallmentPayment;
use App\Models\InstallmentPaymentDetail;
use App\Models\User;
use App\Notifications\PaymentNotification;
use Illuminate\Support\Facades\DB;
use App\Enums\WebConfigKey;
use App\Models\Loan;
use App\Models\LoanInstallmentPaymentDetail;
use App\Models\OfflinePaymentRequests;
use App\Models\LoanPaymentRequest;

class LoadRequestController extends Controller
{
    public function index(Request $request)
    {

        $installments = LoanPaymentRequest::with(['user'])
            ->when(!empty($request['searchValue']), function ($query) use ($request) {
                $searchValue = $request['searchValue'];
                $query->where(function ($q) use ($searchValue) {
                    $q->where('request_type', 'like', '%' . $searchValue . '%')
                        ->orWhere('loan_amount', 'like', '%' . $searchValue . '%')
                        ->orWhere('request_date', 'like', '%' . $searchValue . '%')
                        ->orWhere('remarks', 'like', '%' . $searchValue . '%')
                        ->orWhereHas('user', function ($userQuery) use ($searchValue) {
                            $userQuery->where('name', 'like', '%' . $searchValue . '%');
                        });
                });
            })
            ->paginate(getWebConfig(name: WebConfigKey::PAGINATION_LIMIT));



        return view('admin-views.loan.offline-payment-list', compact('installments'));
    }

    // public function approveLoan($id, Request $request)
    // {
    //     try {
    //         // Fetch the loan payment request
    //         $loanRequest = LoanPaymentRequest::find($id);

    //         if (!$loanRequest) {
    //             return response()->json(['message' => 'Loan Request not found.'], 404);
    //         }

    //         DB::beginTransaction();

    //         if (is_null($loanRequest->installment_id) || $loanRequest->installment_id == 0) {
    //             // Create a new Loan record
    //             $loan = Loan::create([
    //                 'user_id' => $loanRequest->user_id,
    //                 'installment_amount' => $loanRequest->installment_amount,
    //                 'plan_id' => $loanRequest->plan_id ?? null,
    //                 'installment_id' => $loanRequest->installment_id ?? 0,
    //                 'loan_amount' => $loanRequest->loan_amount,
    //                 'no_of_months' => $loanRequest->no_of_months,
    //                 'no_of_emi' => $loanRequest->no_of_emi,
    //                 'loan_date' => $loanRequest->request_date,
    //                 'loan_end_date' => now()->addMonths($loanRequest->no_of_months),
    //             ]);

    //             // Create first installment payment
    //             LoanInstallmentPaymentDetail::create([

    //                 'installment_id' => $loanRequest->installment_id ?? 0,
    //                 'loan_id' => $loan->id,
    //                 'installment_amount' => $loanRequest->installment_amount,
    //                 'request_type' => 'offline',
    //                 'status' => 'approved',
    //                 'remarks' => 'Loan approved and offline payment recorded by admin',
    //             ]);



    //             // Link request to loan
    //             $loanRequest->update(['installment_id' => $loan->installment_id ?? 0]);
    //         } else {
    //             // Just add new installment payment if loan already exists
    //             $loan = Loan::where('id', $loanRequest->loan_id)->first();

    //             if (!$loan) {
    //                 throw new \Exception("Loan not found for this request.");
    //             }

    //             // Add payment detail
    //             LoanInstallmentPaymentDetail::create([
    //                 'user_id' => $loanRequest->user_id,
    //                 'installment_id' => $loanRequest->installment_id,
    //                 'loan_id' => $loan->id,
    //                 'installment_amount' => $loanRequest->installment_amount,
    //                 'loan_amount' => $loanRequest->loan_amount,
    //                 'no_of_months' => $loanRequest->no_of_months,
    //                 'no_of_emi' => $loanRequest->no_of_emi,
    //                 'request_date' => $loanRequest->request_date,
    //                 'payment_collect_date' => now(),
    //                 'request_type' => 'offline',
    //                 'status' => 'approved',
    //                 'remarks' => 'Subsequent offline payment approved',
    //             ]);

    //             // Add payment record
    //             Loan::create([
    //                 'loan_id' => $loan->id,
    //                 'installment_amount' => $loanRequest->installment_amount,
    //                 'payment_date' => now(),
    //                 'status' => 'paid',
    //                 'payment_type' => 'offline',
    //                 'remark' => 'Offline installment accepted',
    //                 'reference' => $loanRequest->transaction_id ?? null,
    //             ]);
    //         }

    //         // Update loan request status
    //         $loanRequest->update([
    //             'status' => 'approved',
    //             'payment_collect_date' => now(),
    //         ]);

    //         DB::commit();

    //         return response()->json(['message' => 'Loan approved and payment recorded successfully.'], 200);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
    //     }
    // }

    public function approve($id)
    {
        // Find the loan payment request by ID
        $loanPaymentRequest = LoanPaymentRequest::findOrFail($id);

        // Check if the loan_id is null or less than 0
        if ($loanPaymentRequest->loan_id <= 0) {
            // Create an entry in both the Loan and LoanInstallmentPaymentDetail tables
            $loan = Loan::create([
                'user_id' => $loanPaymentRequest->user_id,
                'installment_id' => $loanPaymentRequest->installment_id,
                'loan_amount' => $loanPaymentRequest->loan_amount,
                'no_of_months' => $loanPaymentRequest->no_of_months,
                'no_of_emi' => $loanPaymentRequest->no_of_emi,
                'loan_date' => $loanPaymentRequest->request_date,
                'loan_end_date' => now()->addMonths($loanPaymentRequest->no_of_months),
            ]);

            LoanInstallmentPaymentDetail::create([
                'loan_id' => $loan->id,
                'installment_amount' => $loanPaymentRequest->loan_amount / $loanPaymentRequest->no_of_emi,
                'payment_date' => $loanPaymentRequest->request_date,
                'status' => 'pending',
                'payment_type' => 'offline',
            ]);
        } else {
            // Only create an entry in LoanInstallmentPaymentDetail table
            LoanInstallmentPaymentDetail::create([
                'loan_id' => $loanPaymentRequest->loan_id,
                'installment_amount' => $loanPaymentRequest->loan_amount / $loanPaymentRequest->no_of_emi,
                'payment_date' => $loanPaymentRequest->request_date,
                'status' => 'pending',
                'payment_type' => 'offline',
            ]);
        }

        // Update loan payment request status
        $loanPaymentRequest->status = 'approved';
        $loanPaymentRequest->save();

        return response()->json([
            'message' => 'Loan payment request approved successfully.',
        ]);
    }
}
