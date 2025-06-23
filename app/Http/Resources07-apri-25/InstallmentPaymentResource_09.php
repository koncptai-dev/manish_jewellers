<?php

namespace App\Http\Resources;

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
            'plan_category' => $this->plan_category,
            'total_yearly_payment' => $this->total_yearly_payment,
            'total_gold_purchase' => $this->total_gold_purchase,
            'start_date' => $this->formatDate($this->start_date),
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
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
