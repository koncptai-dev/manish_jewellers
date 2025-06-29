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
            'monthly_payment' => $this->monthly_payment,
            'method' => 'RazorPay', // Replace with your logic for fetching the payment method
            'payment_date' => (new InstallmentPayment())->formatDate($this->created_at),
            'installment' => $installmentIndex ? ($installmentIndex . '/' . 11)  : null,
        ];
    }
}
