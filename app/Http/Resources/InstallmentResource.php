<?php

namespace App\Http\Resources;

use App\Models\InstallmentPayment;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class InstallmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $loan = Loan::select(
            'loan_amount',
            DB::raw('SUM(loan_amount) as total_loan')
        )
        ->groupBy('user_id', 'installment_id')
        ->where('installment_id',  $this->id)
        ->where('user_id', $this->user_id)
        ->value('total_loan');

        $startDate = new Carbon($this->start_date);

        $monthsToAdd = match ($this->plan_code) {
            'INR' => 12,
            'SNR' => 18,
            'TNR' => 24,
            default => 0,
        };
    
        $total_balance = $this->details->sum('monthly_payment') - ($loan ?? 0);
        $endDate = $startDate->copy()->addMonths($monthsToAdd)->format('Y-m-d');
        return [
            'id' => $this->id,
            'plan_code' => $this->plan_code ?? "",
            'plan_category' => $this->plan_category ?? "",
            'total_balance' => $total_balance,
            'plan_number' => $this->uuid,
            'plan_start_date' => (new InstallmentPayment())->formatDate($this->start_date),
            'plan_end_date' => $endDate,

            'pending_installments' => 11 -  $this->details->count() ?? 0, // Total pending installments
            'total_gold_purchase' => $this->total_gold_purchase ?? 0,
            'acquired_gold_rate' => $this->acquired_gold_rate ?? 0,
            'monthly_average' => $this->monthly_average ?? 0,

            'payment_done' => $this->details->sum('monthly_payment') ?? 0, // Total payments made
            'payment_remaining' => $this->total_yearly_payment - $this->details->sum('monthly_payment') ?? 0,
            'payment_status' => 'Success',
            'total_installment_paid' =>  $this->details->sum('monthly_payment'),
            'total_withdrawn_amount' => $this->total_withdrawn_amount ?? 0,
            'remaining_withdrawn_amount' => $total_balance - $this->total_withdrawn_amount ?? 0,
            'plan_status' => $this->status  ?? 0,
            'details' => InstallmentDetailResource::collection($this->details->values()->map(function ($detail, $index) {
                $detail->installment_index = $index + 1; // Add an index property
                return $detail;
            })),
        ];
    }
}
