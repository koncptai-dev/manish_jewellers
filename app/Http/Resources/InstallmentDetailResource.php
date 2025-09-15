<?php

namespace App\Http\Resources;

use App\Models\InstallmentPayment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $installmentIndex = $this->installment_index ?? null;

        return [
            'payment_amount' => $this->monthly_payment ?? 0,
            'method' => $this->payment_method ?? "", // Replace with your logic for fetching the payment method
            'Payment_status' => $this->payment_status ?? "",
            'payment_date' => (new InstallmentPayment())->formatDate($this->created_at),
            'payment_time' => (new InstallmentPayment())->formatTime($this->created_at),
            'installment' => $installmentIndex ? ($installmentIndex . '/' . 11)  : 0,
            "purchase_gold_weight" => $this->purchase_gold_weight ?? 0,
            "payment_type" => $this->payment_type ?? "",
            "acquired_gold_rate" => $this->acquired_gold_rate ?? 0,
        ];
    }
}
