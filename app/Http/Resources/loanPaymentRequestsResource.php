<?php
namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class loanPaymentRequestsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'payment_amount' => $this->loan_amount,
            'payment_date'   => $this->formatDate($this->created_at),
            'payment_status' => $this->status,
            'payment_type'   => $this->payment_type ?? "EMI Payment",

            // "purchase_gold_weight" => $this->purchase_gold_weight,
        ];
    }

    public function formatDate($date)
    {
        return Carbon::parse($date)->format('F jS, Y g:i A');
    }
}
