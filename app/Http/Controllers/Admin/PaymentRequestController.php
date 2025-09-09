<?php
namespace App\Http\Controllers\Admin;

use App\Enums\WebConfigKey;
use App\Exports\InstallmentPaymentsExport;
use App\Http\Controllers\Controller;
use App\Models\InstallmentPayment;
use App\Models\InstallmentPaymentDetail;
use App\Models\OfflinePaymentRequests;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class PaymentRequestController extends Controller
{
    public function index(Request $request)
    {
        $installments = OfflinePaymentRequests::with(['user'])
            ->when(! empty($request['searchValue']), function ($query) use ($request) {
                $searchValue = $request['searchValue'];
                $query->where(function ($q) use ($searchValue) {
                    $q->where('plan_category', 'like', '%' . $searchValue . '%')
                        ->orWhereHas('user', function ($userQuery) use ($searchValue) {
                            $userQuery->where('name', 'like', '%' . $searchValue . '%');
                        });
                });
            })
            ->paginate(getWebConfig(name: WebConfigKey::PAGINATION_LIMIT)); // Pagination limit
        return view('admin-views.installment.offline-payment-list', compact('installments'));
    }
    public function approve($id, Request $request)
    {
        try {
            // Fetch the payment request
            $paymentRequest = OfflinePaymentRequests::find($id);

            if (! $paymentRequest) {
                return response()->json(['message' => 'PaymentRequest not found.'], 404);
            }

            // Start a transaction
            DB::beginTransaction();

            if (is_null($paymentRequest->installment_id) || $paymentRequest->installment_id == 0) {
                // Create new InstallmentPayment
                $installmentPayment = InstallmentPayment::create([
                    'uuid'                 => Str::uuid(),
                    'plan_code'            => $paymentRequest->plan_code,
                    'plan_category'        => $paymentRequest->plan_category,
                    'total_yearly_payment' => $paymentRequest->total_yearly_payment,
                    'total_gold_purchase'  => $paymentRequest->total_gold_purchase,
                    'start_date'           => $paymentRequest->start_date,
                    'end_date'             => $paymentRequest->end_date,
                    'user_id'              => $paymentRequest->user_id,
                    'no_of_months'         => $paymentRequest->no_of_months ?? null,
                ]);

                // Create InstallmentPaymentDetail
                InstallmentPaymentDetail::create([
                    'installment_payment_id' => $installmentPayment->id,
                    'monthly_payment'        => $paymentRequest->plan_amount ?? 0,
                    'purchase_gold_weight'   => $paymentRequest->total_gold_purchase ?? 0,
                    'payment_status'         => 'paid',
                    'payment_type'           => 'offline',
                    'payment_method'         => 'cash',
                    'transaction_ref'        => $paymentRequest->transaction_id ?? null,
                    'payment_by'             => 'Admin',
                    'payment_note'           => "Offline payment accepted by admin"
                ]);

                // Update payment request
                $paymentRequest->update(['installment_id' => $installmentPayment->id]);

                // Update total yearly payment
                $installmentId      = $installmentPayment->id;
                $totalYearlyPayment = InstallmentPaymentDetail::where('installment_payment_id', $installmentId)
                    ->sum('monthly_payment');

                InstallmentPayment::where('id', $installmentId)->update(['total_yearly_payment' => $totalYearlyPayment]);

            } else {
                // Only insert InstallmentPaymentDetail
                InstallmentPaymentDetail::create([
                    'installment_payment_id' => $paymentRequest->installment_id,
                    'monthly_payment'        => $paymentRequest->plan_amount ?? 0,
                    'purchase_gold_weight'   => $paymentRequest->total_gold_purchase ?? 0,
                    'payment_status'         => 'paid',
                    'payment_type'           => 'offline',
                    'payment_method'         => 'cash',
                    'transaction_ref'        => $paymentRequest->transaction_id ?? null,
                    'payment_by'             => 'Admin',
                    'payment_note'           => "Offline payment accepted by admin"
                ]);
            }

            $paymentRequest->update(['status' => 'done', 'payment_collect_date' => now()]);

            DB::commit();

            return response()->json(['message' => 'Payment approved and status updated successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function userInstallments(Request $request)
    {
        $installments = InstallmentPayment::with('user') // still needed if you return the full model somewhere else
            ->when(! empty($request['searchValue']), function ($query) use ($request) {
                $searchValue = strtolower($request['searchValue']);
                $query->where(function ($q) use ($searchValue) {
                    $q->whereRaw('LOWER(installment_payments.plan_category) LIKE ?', ['%' . $searchValue . '%'])
                        ->orWhereRaw('LOWER(installment_payments.plan_code) LIKE ?', ['%' . $searchValue . '%'])
                        ->orWhereHas('user', function ($userQuery) use ($searchValue) {
                            $userQuery->whereRaw('LOWER(users.name) LIKE ?', ['%' . $searchValue . '%']);
                        });
                });
            })
            ->leftJoin('installment_payment_details', 'installment_payment_details.installment_payment_id', '=', 'installment_payments.id')
            ->leftJoin('users', 'users.id', '=', 'installment_payments.user_id') // ADD this line
            ->select(
                'installment_payments.user_id',
                'installment_payments.id as installment_id', // Include installment ID for reference
                'installment_payments.plan_code',
                'installment_payments.plan_category',
                'users.name as user_name', 
                'installment_payments.status', // Include status for filtering
                'installment_payments.cancel_request',
                'installment_payments.cancellation_reason',
                DB::raw("SUM(CASE WHEN installment_payment_details.payment_status = 'paid' THEN installment_payment_details.monthly_payment ELSE 0 END) as plan_amount"),
            )->has('user')
            ->groupBy(
                'installment_payments.user_id',
                'installment_payments.plan_code',
                'installment_payments.plan_category',
                'users.name' // group by new selected field
            )
            ->paginate(getWebConfig(name: WebConfigKey::PAGINATION_LIMIT));
        return view('admin-views.installment.users-installments-list', compact('installments'));

    }

    public function revenueOverview(Request $request)
    {
        $type = $request->get('type', 'monthly');

        // Set the date format based on the type
        $dateFormat = match ($type) {
            'daily' => '%Y-%m-%d',
            'monthly' => '%Y-%m',
            'yearly' => '%Y',
            default => '%Y-%m'
        };

        // Query the data using the correct date format
        $installments = InstallmentPayment::select(
            DB::raw("DATE_FORMAT(created_at, '$dateFormat') as date"),
            DB::raw('SUM(total_yearly_payment) as total_profit')
        )
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        if ($request->ajax()) {
            return response()->json($installments);
        }

        return view('admin-views.installment.revenue-overview-list', compact('installments'));
    }

    public function withdrawAmount(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount'  => 'required|numeric|min:0',
            'remarks' => 'nullable|string|max:255',
        ]);

        try {
            $user        = User::findOrFail($request->user_id);
            $installment = InstallmentPayment::find($request->installment_id);
            if (! $user) {
                return response()->json(['success' => false, 'message' => 'User not found.'], 404);
            }

            if (! $installment) {
                return response()->json(['success' => false, 'message' => 'Installment record not found.'], 404);
            }

            $amount                = $request->amount;
            $remarks               = $request->remarks;
            $total_invested_amount = $request->plan_amount;

            if ($installment->user_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Access denied: This installment does not belong to the selected user.'], 403);
            }

            $remainingWithdrawable = $total_invested_amount - $installment->total_withdrawn_amount;

            if ($total_invested_amount <= 0) {
                return response()->json(['success' => false, 'message' => 'Total invested amount must be greater than zero.'], 400);
            }
            if ($amount > ($remainingWithdrawable + 0.0001)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Withdrawal amount (₹' . number_format($amount, 2) . ') exceeds the remaining withdrawable balance (₹' . number_format($remainingWithdrawable, 2) . ') for this plan.',
                ], 400);
            }

            $installment->total_withdrawn_amount += $amount;
            $installment->save();

            Withdrawal::create([
                'user_id'        => $request->user_id,
                'installment_id' => $request->installment_id,
                'amount'         => $amount,
                'remarks'        => $remarks,
                'status'         => 'completed',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Amount ₹' . number_format($amount, 2) . ' successfully withdrawn and recorded.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function withdrawalHistory(InstallmentPayment $installment)
    {
        $history = $installment->withdrawals()->orderBy('created_at', 'desc')->get(['amount', 'remarks', 'created_at']);

        // Calculate the current total withdrawn amount for this specific installment
        $currentTotalWithdrawn = $history->sum('amount');

        return response()->json([
            'success'                        => true,
            'history'                        => $history,
            'total_withdrawn_amount_current' => $currentTotalWithdrawn,    // Add this line
            'plan_amount'                    => $installment->plan_amount, // Also send the original plan amount for recalculation
        ]);
    }

    public function cancelPlan(Request $request)
    {
        $request->validate([
            'installment_id' => 'required|exists:installment_payments,id',
        ]);

        try {
            $installment = InstallmentPayment::findOrFail($request->installment_id);

            if ($installment->status != 1) {
                return response()->json(['success' => false, 'message' => 'Plan is already canceled or inactive.']);
            }

            $installment->status = 0; // assuming 0 = canceled
            $installment->save();

            return response()->json([
                'success' => true,
                'message' => 'Plan canceled successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ]);
        }
    }
    public function installmentTransactions()
    {
        $transactions = InstallmentPaymentDetail::with(['installmentPayment.user'])
            ->when(request('searchValue'), function ($query) {
                $searchValue = request('searchValue');
                $query->where(function ($q) use ($searchValue) {
                    $q->whereHas('installmentPayment.user', function ($userQuery) use ($searchValue) {
                        $userQuery->where('name', 'like', '%' . $searchValue . '%');
                    })
                    ->orWhere('transaction_ref', 'like', '%' . $searchValue . '%');
                });
            })
            ->when(request('date'), function ($query) {
                $query->whereDate('created_at', request('date'));
            })
            ->where('payment_by', 'User')
            ->orderBy('created_at', 'desc')
            ->paginate(getWebConfig(name: WebConfigKey::PAGINATION_LIMIT))
            ->appends(request()->except('page'));

        return view('admin-views.installment.installment-transactions', compact('transactions'));
    }

    public function exportCsv(Request $request)
    {
        $searchValue = $request->searchValue;
        $date        = $request->date;

        return Excel::download(new InstallmentPaymentsExport($searchValue, $date), 'installments.csv', \Maatwebsite\Excel\Excel::CSV);
    }

    public function acceptCancel(Request $request)
    {
        $request->validate([
            'installment_id' => 'required|exists:installment_payments,id',
        ]);

        try {
            $installment = InstallmentPayment::findOrFail($request->installment_id);

            if ($installment->cancel_request != 1) {
                return response()->json(['success' => false, 'message' => 'No cancel request to accept.']);
            }

            $installment->status         = 0; // assuming 0 = canceled
            $installment->cancel_request = 0; // reset cancel request
            $installment->save();

            return response()->json([
                'success' => true,
                'message' => 'Cancel request accepted and plan canceled successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ]);
        }
    }

}
