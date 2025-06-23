<?php

namespace App\Console\Commands;

use App\Models\GoldRate;
use Illuminate\Console\Command;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchGoldRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:gold-rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            
            // Replace this URL with your API endpoint
            $apiUrl = 'https://api.metalpriceapi.com/v1/latest?api_key=c009070a756ec61bb4fa40271eda3566&base=INR&currencies=XAU';

            // Call the API
            $response = Http::get($apiUrl);

            if ($response->successful()) {
                // Decode the JSON string
                $data = json_decode($response, true);

                // Extract the rate for 1 troy ounce of gold in INR
                $inr_per_troy_ounce = $data['rates']['INRXAU'];

                // Conversion factor: 1 troy ounce = 31.1035 grams
                $troy_ounce_to_gram = 31.1035;

                // Calculate the gold rate per gram in INR
                $inr_per_gram = $inr_per_troy_ounce / $troy_ounce_to_gram;

                (new GoldRate())->insertGoldPrice($data['timestamp'], "Gold", "INR", $inr_per_gram);
                
            } else {
                $this->error('Failed to fetch gold rates. Status: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Error fetching gold rates: ' . $e->getMessage());
            $this->error('An error occurred: ' . $e->getMessage());
        }

        return 0;
    }
}
