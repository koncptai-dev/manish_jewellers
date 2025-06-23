<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'installment_id',  // Change this to plan_code
        'loan_amount',
        'no_of_months',
        'emi',
        'loan_date',
        'loan_end_date',
        'no_of_emi',
    ];

    /**
     * Get the user associated with the loan.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(LoanInstallmentPaymentDetail::class);
    }

    public function loanPaymentRequests()
    {
        return $this->hasMany(LoanPaymentRequest::class,'loan_id','id');
    }
}
