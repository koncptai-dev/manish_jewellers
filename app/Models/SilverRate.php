<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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

}
