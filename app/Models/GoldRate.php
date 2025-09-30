<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use PhpParser\Node\Stmt\Return_;
use App\Models\AdjustedGoldRate;

use Illuminate\Support\Facades\Http;

class GoldRate extends Model
{
    use HasFactory;

    protected $table = "gold_rates";
    protected $casts = [
        'date' => 'datetime',
    ];

    //Get Today Gold Price
    public function getTodayGoldRate()
    {
        // $curl = curl_init();

        // curl_setopt_array($curl, array(
        //     CURLOPT_URL => 'https://api.metalpriceapi.com/v1/latest?api_key=c009070a756ec61bb4fa40271eda3566&base=INR&currencies=XAU',
        //     CURLOPT_RETURNTRANSFER => true,
        //     CURLOPT_ENCODING => '',
        //     CURLOPT_MAXREDIRS => 10,
        //     CURLOPT_TIMEOUT => 0,
        //     CURLOPT_FOLLOWLOCATION => true,
        //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //     CURLOPT_CUSTOMREQUEST => 'GET',
        // ));

        // $response = curl_exec($curl);

        // curl_close($curl);

        // Check if today's rates are already stored
        // $goldRate =  $this->getTodayRates();

        // if (empty($goldRate)) {
        $apiResponse = $this->getGoldRateDataUsingApi();
    
        $price_gram_24k = $apiResponse;
        $price_gram_22k =  (new GoldRate())->calculate22CaratPrice($price_gram_24k);
        $price_gram_18k = (new GoldRate())->calculate18CaratPrice($price_gram_24k);

        // Calculate 1-gram rates by dividing 10-gram rates by 10
        $price_1gram_24k = $price_gram_24k / 10;
        $price_1gram_22k = $price_gram_22k / 10;
        $price_1gram_18k = $price_gram_18k / 10;

        // Apply adjustment if exists
        $adjustment = AdjustedGoldRate::first(); // Fetch the first record for adjustment
        if ($adjustment) {
            if ($adjustment->adjust_type === 'add') {
                $price_gram_24k += $adjustment->amount;
                $price_1gram_24k += $adjustment->amount;
                $price_1gram_22k += $adjustment->amount;
                $price_1gram_18k += $adjustment->amount;
            } elseif ($adjustment->adjust_type === 'subtract') {
                $price_gram_24k -= $adjustment->amount;
                $price_1gram_24k -= $adjustment->amount;
                $price_1gram_22k -= $adjustment->amount;
                $price_1gram_18k -= $adjustment->amount;
            }
        }

        $data = [
            'timestamp' => now(),
            'metal' => 'Gold',
            'currency' => 'INR',
            'exchange' => 'MCX',
            'price' => $price_gram_24k,
            'price_gram_24k' => $price_1gram_24k,
            'price_gram_22k' => $price_1gram_22k,
            'price_gram_18k' => $price_1gram_18k,
        ];


        $this->storeGoldPrice($data);
        $goldRate =  $this->getTodayRates();
        // }
        return $goldRate;
    }

    public function getTodayRates()
    {
        $today = Carbon::now();
        return GoldRate::orderBy('id', 'desc')->first();
        // return GoldRate::whereDate('created_at', $today)->first();
    }

    // // Call API to get Gold Rates
    // public function getGoldRateDataUsingApi()
    // {
    //     $apiKey = "goldapi-sjsm3zi1nec-io";

    //     $symbol = "XAU";
    //     $curr = "INR";

    //     $maxRetries = 5; // Maximum number of retries
    //     $retryCount = 1;

    //     do {
    //         $date = '/' . date('Ymd', strtotime("-{$retryCount} days"));
    //         $myHeaders = array(
    //             'x-access-token: ' . $apiKey,
    //             'Content-Type: application/json'
    //         );

    //         $curl = curl_init();
    //         $url = "https://www.goldapi.io/api/{$symbol}/{$curr}{$date}";

    //         curl_setopt_array($curl, array(
    //             CURLOPT_URL => $url,
    //             CURLOPT_RETURNTRANSFER => true,
    //             CURLOPT_FOLLOWLOCATION => true,
    //             CURLOPT_HTTPHEADER => $myHeaders
    //         ));

    //         $response = curl_exec($curl);
    //         $error = curl_error($curl);
    //         curl_close($curl);

    //         if ($error) {
    //             echo "<pre>Error: {$error}</pre>";
    //             break;
    //         }

    //         // Decode the JSON response into an array
    //         $apiResponse = json_decode($response, true);

    //         if (isset($apiResponse['error']) && $apiResponse['error'] === 'No data available for this date or pair.') {
    //             $retryCount++;
    //         } else {
    //             return  $apiResponse;
    //             break;
    //         }
    //     } while ($retryCount <= $maxRetries);

    //     if ($retryCount > $maxRetries) {
    //         echo "<pre>No data available for the past {$maxRetries} days.</pre>";
    //     }

    //     // return  $this->getStaticData();
    // }

    private function getGoldRateDataUsingApi()
    {
        // API URL
        $url = "https://bcast.aaravbullion.in/VOTSBroadcastStreaming/Services/xml/GetLiveRateByTemplateID/aarav?_=" . time();

        try {
            // Fetch API response with a timeout
            $response = Http::timeout(10)->get($url);
            
            if ($response->failed()) {
                return null; // Return null instead of JSON response
            }

            // Process the response
            $lines = explode("\n", trim($response->body()));

            foreach ($lines as $line) {
                // Normalize spaces
                $line = preg_replace('/\s+/', ' ', trim($line));

                // Match "GOLD 999 IMP (AHM)" followed by numbers
                if (preg_match('/GOLD 999 IMP OR SAM IMP \(AHM\)\s+(\d+\.?\d*)/', $line, $matches)) {
                    return (float) $matches[1]; // Return the extracted gold price as a float
                }
            }

            return null; // Return null if no price is found
        } catch (\Exception $e) {
            return null; // Return null on failure
        }
    }

    public function getStaticData()
    {
        $apiResponse = [
            'date' => '2024-11-27T10:30:00.000Z',
            'timestamp' => 1732703400000,
            'metal' => 'XAU',
            'exchange' => 'LBMA',
            'currency' => 'INR',
            'price' => 223598.9449,
            'prev_close_price' => 222104.3447,
            'ch' => 1494.6002,
            'chp' => 0.6684,
            'price_gram_24k' => 7188.873,
            'price_gram_22k' => 6589.8003,
            'price_gram_21k' => 6290.2639,
            'price_gram_20k' => 5990.7275,
            'price_gram_18k' => 5391.6548,
            'price_gram_16k' => 4792.582,
            'price_gram_14k' => 4193.5093,
        ];
        return $apiResponse;
    }

    //Store Gold Price
    public function storeGoldPrice($data)
    {
        $goldRate = (new GoldRate());
        $goldRate->timestamp = $data['timestamp'];
        $goldRate->metal = $data['metal'];
        $goldRate->currency = $data['currency'];
        $goldRate->exchange = $data['exchange'];
        $goldRate->price = $data['price'];
        // $goldRate->price_gram_24k = $data['price_gram_24k'];
        $import_duty_rate = 0.09; // 8% import duty
        $goldRate->price_gram_24k = $data['price_gram_24k'] * (1 + $import_duty_rate);
        $goldRate->price_gram_22k = $data['price_gram_22k'];
        $goldRate->price_gram_18k = $data['price_gram_18k'];

        $goldRate->save();
    }

    //Store Gold Price
    public function insertGoldPrice($timestamp, $metal, $currency, $price)
    {
        $goldRate = (new GoldRate());
        $goldRate->timestamp = $timestamp;
        $goldRate->metal = $metal;
        $goldRate->currency = $currency;
        $goldRate->price = $price;
        $goldRate->price_gram_24k = $price;
        $goldRate->save();
    }

    public function calculatePriceWithMarkup($pricePerGram, $grams, $makingChange, $product = null)
    {
       
        // $totalPrice = $pricePerGram * $grams; // Base price

        // $markup = $totalPrice * 0.03;        // Calculate 3% markup GST

        // $finalPrice = $totalPrice + $markup; // Add markup to base price
        // return round($finalPrice, 2);        // Round to 2 decimal places

        // $totalPrice = (($pricePerGram * $grams) + $makingChanrge); //* 1.03;
        // $totalPrice = (($pricePerGram * $grams) + $makingChanrge); //* 1.03;
        $labourCharge = ($pricePerGram * $makingChange / 100) * $grams;
        if($product && $product['discount']> 0 ){
            $discount = getProductDiscount($product, $labourCharge);
             $labourCharge = $labourCharge - $discount;
        }
    
        $totalPrice = ($pricePerGram * $grams) + $labourCharge;

        return $totalPrice;
    }

    public function calculate22CaratPrice($price24Carat)
    {
        $price22Carat = $price24Carat * 0.92; // Calculate 92% of 24-carat price
        return round($price22Carat, 2);       // Round to 2 decimal places
    }

    public function calculate18CaratPrice($price24Carat)
    {
        $price18Carat = $price24Carat * 0.78; // Calculate 78% of 24-carat price
        return round($price18Carat, 2);       // Round to 2 decimal places
    }
}
