<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class SilverRate extends Model
{
    use HasFactory;
    protected $table = 'silver_rates';

    protected $fillable = [
        'metal',
        'currency',
        'price',
    ];

    //Get Today Silver Price
    public function getTodaySilverRate()
    {
        return SilverRate::latest('id')->first();      
    }

    public function getTodaySilverRateUsingAPI()
    {
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
                    preg_match('/SILVER\s+999\s+\(AHM\)\s+PETI\s+30kg\s+(\d+\.?\d*)/', $line, $matches)
                ) {
                    return (float) $matches[1]; // Returns 153676
                }
            }

            return null; // Return null if no price is found
        } catch (\Exception $e) {
            return null; // Return null on failure
        }
    }
}
