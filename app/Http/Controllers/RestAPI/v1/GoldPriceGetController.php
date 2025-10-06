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
use App\Http\Resources\GoldMetalPriceResource;

class GoldPriceGetController extends Controller
{
    // public function getGoldPrice()
    // {
    //     $goldRate = (new GoldRate())->getTodayGoldRate();

    //     $apiResponseResource = new GoldPriceResource((object) $goldRate);
    //     // Return the resource directly
    //     return $apiResponseResource;
    // }

    public function getGoldPrice()
    {
        $goldPrice = $this->getGoldPriceApiCall();
        if (!$goldPrice) {
            return response()->json(['error' => 'Gold price not found'], 404);
        }

        // Mock additional data
        $goldData = (object) [
            'price' => (float) $goldPrice,
            'date' => now()->toDateTimeString(),
            'timestamp' => now()->timestamp,
        ];

        // Use the resource to return the response
        return new GoldMetalPriceResource($goldData);
    }


     private function getGoldPriceApiCall()
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
                if (
                preg_match('/GOLD 999 IMP OR SAM IMP \(AHM\)\s+(\d+\.?\d*)/', $line, $matches) ||
                preg_match('/GOLD 999 IMP \(AHM\)\s+(\d+\.?\d*)/', $line, $matches) ||
                preg_match('/GOLD 999 IMP \(AHM\)\s+T\+2\s+(\d+\.?\d*)/', $line, $matches)
                ) {
                    return (float) $matches[1]; // Return the extracted gold price as a float
                }
            }

            return null; // Return null if no price is found
        } catch (\Exception $e) {
            return null; // Return null on failure
        }
    }
}    
