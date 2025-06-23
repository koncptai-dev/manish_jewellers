<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class SubscriptionMandate extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'transaction_id', 'mandate_id', 'status', 'amount', 'frequency', 'start_time', 'end_time' ,'installment_id' , 'last_deduction_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function installment()
    {
        return $this->belongsTo(InstallmentPayment::class, 'installment_id');
    }
}
