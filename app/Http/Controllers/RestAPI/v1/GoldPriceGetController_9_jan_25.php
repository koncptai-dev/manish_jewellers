<?php

namespace App\Http\Controllers\RestAPI\v1;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HelpTopic;
use App\Http\Resources\GoldPriceResource;
use App\Models\GoldRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class GoldPriceGetController extends Controller
{
    public function getGoldPrice()
    {
        $goldRate = (new GoldRate())->getTodayGoldRate();

        $apiResponseResource = new GoldPriceResource((object) $goldRate);
        // Return the resource directly
        return $apiResponseResource;
    }

    // public function getGoldPrice()
    // {
    //     $apiKey = "goldapi-sjsm3zi1nec-io";

    //     $symbol = "XAU";
    //     $curr = "INR";
    //     // $date = "/20241126";
    //     $date = '/' . date('Ymd', strtotime('-1 day')); // Yesterday date
    //     // $date = '/' . date('Ymd'); // Today's date

    //     $myHeaders = array(
    //         'x-access-token: ' . $apiKey,
    //         'Content-Type: application/json'
    //     );

    //     $curl = curl_init();

    //     $url = "https://www.goldapi.io/api/{$symbol}/{$curr}{$date}";

    //     curl_setopt_array($curl, array(
    //         CURLOPT_URL => $url,
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_FOLLOWLOCATION => true,
    //         CURLOPT_HTTPHEADER => $myHeaders
    //     ));

    //     $response = curl_exec($curl);
    //     $error = curl_error($curl);
    //     curl_close($curl);

    //     if ($error) {
    //         return response()->json(['error' => $error], 500);
    //     }

    //     // Decode the JSON response into an array
    //     $apiResponse = json_decode($response, true);

    //     // Check if decoding was successful
    //     if (json_last_error() !== JSON_ERROR_NONE) {
    //         return response()->json(['error' => 'Invalid JSON response from API'], 500);
    //     }

    //     // Pass the decoded response to your resource class
    //     $apiResponseResource = new GoldPriceResource((object) $apiResponse);

    //     // Return the resource directly
    //     return $apiResponseResource;
    // }
}
