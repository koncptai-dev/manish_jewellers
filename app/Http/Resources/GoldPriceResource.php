<?php

namespace App\Http\Resources;

use App\Models\GoldRate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use function PHPUnit\Framework\returnSelf;

class GoldPriceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $price_gram_24k = $this->price_gram_24k;
        $price_gram_22k =  (new GoldRate())->calculate22CaratPrice($price_gram_24k);
        $price_gram_18k = (new GoldRate())->calculate18CaratPrice($price_gram_24k);

        return [
            'date' => $this->date,
            'timestamp' => $this->timestamp,
            'metal' => $this->getMetal($this->metal),
            'exchange' => $this->exchange,
            'currency' => $this->currency,
            'price' => $this->price,
            'prev_close_price' => $this->prev_close_price,
            'change' => [
                'absolute' => $this->ch,
                'percentage' => $this->chp,
            ],
            'price_gram' => [
                '24k' => $price_gram_24k,
                '22k' => $price_gram_22k,
                '18k' => $price_gram_18k,
                '24k_gst_included' => (new GoldRate())->calculatePriceWithMarkup($price_gram_24k, 10,0),
                '22k_gst_included' => (new GoldRate())->calculatePriceWithMarkup($price_gram_22k, 10,0),
                '18k_gst_included' => (new GoldRate())->calculatePriceWithMarkup($price_gram_18k, 10,0),
            ],
        ];
    }
    function getMetal($metal)
    {
        if ($metal == "XAU")
            return "Gold";
        else if ($metal == "XAG")
            return "Silver";
        else if ($metal == "XPT")
            return "Platinum";
        else if ($metal == "XPD")
            return "Palladium";
        else {
            return "Gold";
        }
    }
}
