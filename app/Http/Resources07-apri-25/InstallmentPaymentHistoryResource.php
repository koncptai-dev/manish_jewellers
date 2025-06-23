<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\InstallmentPayment;


class InstallmentPaymentHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray(Request $request): array
    {
        // Master table fields
        $masterData = [
            'id' => $this->id,
            'plan_code' => $this->plan_code,
            'plan_amount' => $this->plan_amount,
            'plan_number' => $this->uuid,
            'purchase_date' => (new InstallmentPayment())->formatDate($this->created_at),
            'pending_installments' => 11 - $this->details->count(), // Total pending installments
            'total_gold_purchase' => $this->total_gold_purchase,
            'selected_carat' => $this->plan_category,
            'payment_done' => $this->details->sum('monthly_payment'), // Total payments made
            'payment_remaining' => $this->total_yearly_payment - $this->details->sum('monthly_payment'),
            'Payment_status' => 'Success',
        ];

        // Add each detail as a separate record combined with master data
        $details = $this->details->values()->map(function ($detail, $index) use ($masterData) {
            return array_merge($masterData, [
                'monthly_payment' => $detail->monthly_payment,
                'Payment_status' => 'Success',
                'method' => 'RazorPay', // Replace with your logic for fetching the payment method
                'payment_date' => (new InstallmentPayment())->formatDate($detail->created_at),
                "purchase_gold_weight" => $detail->purchase_gold_weight,
                'installment' => ($index + 1) . '/' . 11, // e.g., "1/11",
            ]);
        });

        return $details->toArray();
    }
}
