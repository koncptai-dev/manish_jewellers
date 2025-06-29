<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        $installmentIndex = $this->installment_index ?? null;


        return [
            'payment_amount' => $this->installment_amount,
            'payment_date' => $this->formatDate($this->created_at),
            'payment_status' => $this->status,
            'payment_type' => $this->payment_type,
         

            // "purchase_gold_weight" => $this->purchase_gold_weight,
        ];
    }
    public function formatDate($date)
    {
        return Carbon::parse($date)->format('F jS, Y g:i A');
    }
}
