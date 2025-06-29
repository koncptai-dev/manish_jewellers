<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanInstallmentPaymentDetail extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'loan_id',
        'installment_amount',
        'payment_date',
        'status',
        'payment_type',
        'remark',
        'reference',
    ];

    /**
     * Get the loan associated with this installment.
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}
