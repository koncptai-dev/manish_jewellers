<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstallmentPaymentDetail extends Model
{
    use HasFactory;
    // Fillable attributes for mass assignment
    protected $fillable = [
        'installment_payment_id',
        'monthly_payment',
        'purchase_gold_weight',
        'payment_status',
        'payment_method',
        'transaction_ref',
        'payment_by'
    ];

    /**
     * Relationship to the InstallmentPayment model.
     */
    public function installmentPayment()
    {
        return $this->belongsTo(InstallmentPayment::class, 'installment_payment_id');
    }

    public function payment()
    {
        return $this->belongsTo(InstallmentPayment::class, 'installment_payment_id');
    }
}
