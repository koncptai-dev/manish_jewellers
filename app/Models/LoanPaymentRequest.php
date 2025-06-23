<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanPaymentRequest extends Model
{
    use HasFactory;

    protected $table = 'loan_payment_requests';

    protected $fillable = [
        'user_id',
        'loan_id',
        'installment_id',
        'loan_amount',
        'no_of_months',
        'no_of_emi',
        'request_date',
        'payment_collect_date',
        'request_type',
        'status',
        'remarks',
    ];

    // Add this to cast 'request_date' to a Carbon instance
    protected $casts = [
        'request_date' => 'datetime',
    ];

    // Relationships
    public function installment()
    {
    return $this->belongsTo(InstallmentPayment::class, 'installment_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }
    public static function createLoanPaymentRequest($loan_amount, $no_of_months, $installmentId, $no_of_emi, $user_id, $loan_id, $request_type,$remarks)
    {
        return self::create([
            'loan_amount' => $loan_amount,
            'no_of_months' => $no_of_months,
            'installment_id' => $installmentId,
            'no_of_emi' => $no_of_emi,
            'user_id' => $user_id,
            'loan_id' => $loan_id,
            'request_type' => $request_type,
            'remarks' => $remarks,
            'request_date' => Carbon::now(),
            'status' => 'pending',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
