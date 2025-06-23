<?php

namespace App\Http\Resources;

use App\Models\InstallmentPayment;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;


class InstallmentPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'plan_code' => $this->plan_code,
            'plan_amount' => $this->plan_amount,
            'plan_category' => $this->plan_category,
            'total_yearly_payment' => $this->total_yearly_payment,
            'total_gold_purchase' => $this->total_gold_purchase,
            'start_date' => $this->formatDate($this->start_date),
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),

            'purchase_date' => (new InstallmentPayment())->formatDate($this->created_at),
            'pending_installments' => 11 -  $this->details->count(), // Total pending installments
           
            'selected_carat' => $this->plan_category,
            'payment_done' => $this->details->sum('monthly_payment'), // Total payments made
            'payment_remaining' => $this->total_yearly_payment - $this->details->sum('monthly_payment'),
            'Payment_status' => 'Success',
            'details' => InstallmentDetailResource::collection($this->details->values()->map(function ($detail, $index) {
                $detail->installment_index = $index + 1; // Add an index property
                return $detail;
            })),
        ];
    }

    /**
     * Format a date to a desired format.
     *
     * @param string|\Illuminate\Support\Carbon|null $date
     * @return string|null
     */
  
    public function formatDate($date)
    {
        if (!$date instanceof \DateTime) {
            $date = Carbon::parse($date); // Convert string to Carbon
        }
        return $date->format('Y-m-d');
    }
}
