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


    public function getGoldPriceApiCall()
    {
    $url = "https://bcast.aaravbullion.in/VOTSBroadcastStreaming/Services/xml/GetLiveRateByTemplateID/aarav?_=" . time();

    try {
        $response = Http::timeout(10)->get($url);

        if ($response->failed()) {
            return null;
        }

        $lines = explode("\n", trim($response->body()));

        foreach ($lines as $line) {
            $line = preg_replace('/\s+/', ' ', trim($line));

            // Match GOLD 999 IMP (AHM) and get the FIRST numeric value after date
            if (stripos($line, 'GOLD 999 IMP (AHM)') !== false) {
                // Split by space and find first big numeric value after words
                $parts = explode(' ', $line);
                foreach ($parts as $part) {
                    if (is_numeric($part) && (float)$part > 10000) {
                        return (float)$part;
                    }
                }
            }
        }

        return null;
    } catch (\Exception $e) {
        return null;
    }
}
}    
