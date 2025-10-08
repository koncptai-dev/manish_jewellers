<?php

namespace App\Http\Resources;

use App\Models\GoldRate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoldMetalPriceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $adjustment = \App\Models\AdjustedGoldRate::first(); // Fetch the first record for adjustment

        if($adjustment){
            if ($adjustment->adjust_type === 'add') {
                    $this->price += $adjustment->amount;
                } elseif ($adjustment->adjust_type === 'subtract') {
                    $this->price -= $adjustment->amount;
                }
        }
        $price_gram_24k = $this->price;
        $price_gram_22k =  (new GoldRate())->calculate22CaratPrice($price_gram_24k);
        $price_gram_18k = (new GoldRate())->calculate18CaratPrice($price_gram_24k);

        // Calculate 1-gram rates by dividing 10-gram rates by 10
        $price_1gram_24k = $price_gram_24k / 10;
        $price_1gram_22k = $price_gram_22k / 10;
        $price_1gram_18k = $price_gram_18k / 10;

        return [
            'date' => $this->date,
            'timestamp' => $this->timestamp,
            'metal' => "Gold",
            'currency' => "INR",
            'price' => $this->price,
            '1_gram' => [
                '24k_gst_included' => round($price_1gram_24k, 2),
                '22k_gst_included' => round($price_1gram_22k, 2),
                '18k_gst_included' => round($price_1gram_18k, 2),
            ],
            '10_gram' => [
                '24k_gst_included' => $price_gram_24k,
                '22k_gst_included' => $price_gram_22k,
                '18k_gst_included' => $price_gram_18k,
            ],
            'price_gram' => [

                '24k_gst_included' => $price_gram_24k,
                '22k_gst_included' => $price_gram_22k,
                '18k_gst_included' => $price_gram_18k,
            ],
        ];
    }
}
