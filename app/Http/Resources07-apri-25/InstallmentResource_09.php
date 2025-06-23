<?php

namespace App\Http\Resources;

use App\Models\InstallmentPayment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plan_code' => $this->plan_code,
            'plan_number' => $this->uuid,
            'purchase_date' => (new InstallmentPayment())->formatDate($this->created_at),
            'pending_installments' => 11 -  $this->details->count(), // Total pending installments
            'total_gold_purchase' => $this->total_gold_purchase,
            'selected_carat' => $this->plan_category,
            'payment_done' => $this->details->sum('monthly_payment'), // Total payments made
            'payment_remaining' => $this->total_yearly_payment - $this->details->sum('monthly_payment'),
            'details' => InstallmentDetailResource::collection($this->details->values()->map(function ($detail, $index) {
                $detail->installment_index = $index + 1; // Add an index property
                return $detail;
            })),
        ];
    }
}
