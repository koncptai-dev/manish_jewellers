<?php
namespace App\Http\Resources;

use App\Models\OfflinePaymentRequests;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class LoanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $paidEmiCount = $this->details->count();
        $remainingEmi = $this->no_of_emi - $paidEmiCount;

        // EMI per month based on original loan amount
        $emiPerMonth = $this->calculateEMI($this->loan_amount, $this->no_of_emi, 24); // Default to 24% if not set

        $emiAmountRemaining = $emiPerMonth * $remainingEmi;
        // Monthly Payments Chart Data
        $monthlyPayments = DB::table('loan_installment_payment_details')
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(installment_amount) as total_payment')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        // Weekly Payments Chart Data
        $weeklyPayments = DB::table('loan_installment_payment_details')
            ->selectRaw('YEAR(created_at) as year, WEEK(created_at) as week, SUM(installment_amount) as total_payment')
            ->groupBy('year', 'week')
            ->orderBy('year', 'desc')
            ->orderBy('week', 'desc')
            ->get();

        return [
            'id'                   => $this->id,
            'plan_id'              => $this->installment_id,
            'installment_id'       => $this->installment_id,
            'total_balance'        => (OfflinePaymentRequests::where('installment_id', $this->installment_id)
                    ->where('user_id', $this->user_id)
                    ->sum('plan_amount')) - $this->loan_amount,
            'loan_amount'          => $this->loan_amount,
            'no_of_months'         => $this->no_of_months,
            'plan_start_date'      => $this->formatDate($this->loan_date),
            'plan_end_date'        => $this->formatDate($this->loan_end_date),
            'monthly_average'      => $this->loanPaymentRequests->avg('loan_amount'),
            'payment_status'       => $this->loanPaymentRequests->count() == $this->no_of_emi ? 'Paid' : 'Pending',
            'total_no_of_emi'      => $this->no_of_emi,
            'emi_remaining'        => $this->no_of_emi - $this->loanPaymentRequests->count(),
            'emi_paid'             => $this->loanPaymentRequests->count(),
            'emi_amount'           => round($emiPerMonth),
            'emi_amount_remaining' => round($emiAmountRemaining, 2),
            'chart_monthly'        => $monthlyPayments,
            'chart_weekly'         => $weeklyPayments,
            'details'              => loanPaymentRequestsResource::collection($this->loanPaymentRequests->values()->map(function ($detail, $index) {
                $detail->installment_index = $index + 1;
                return $detail;
            })),
        ];
    }

    public function formatDate($date)
    {
        return Carbon::parse($date)->format('F jS, Y g:i A');
    }

    public function calculateEMI($principal, $months, $annualInterestRate)
    {
        $monthlyRate = $annualInterestRate / 12 / 100;
        $numerator   = $principal * $monthlyRate * pow(1 + $monthlyRate, $months);
        $denominator = pow(1 + $monthlyRate, $months) - 1;

        return $denominator == 0 ? $principal : $numerator / $denominator;
    }
}
