<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfflinePaymentRequests extends Model
{
    use HasFactory;

    protected $table = 'offline_payment_requests';

    protected $fillable = [
        'user_id',
        'plan_amount',
        'plan_code',
        'status',
        'remarks',
        'plan_category',
        'total_yearly_payment',
        'total_gold_purchase',
        'start_date',
        // 'end_date',
        'request_date',
        'payment_collect_date',
        'total_yearly_payment',
        'installment_id',
        // 'no_of_months'
    ];

    public function storeData($user_id, $plan_amount, $plan_code, $plan_category, $total_yearly_payment, $total_gold_purchase, $start_date, $installment_id, $request_date, $no_of_months, $remarks, $acquired_gold_rate)
    {
        $end_date = date('Y-m-d', strtotime($start_date . ' +1 year'));

        $payment = new OfflinePaymentRequests();
        $payment->plan_code = $plan_code;
        $payment->plan_amount = $plan_amount;
        $payment->plan_category = $plan_category;
        $payment->total_yearly_payment = $total_yearly_payment;
        $payment->total_gold_purchase = $total_gold_purchase;
        $payment->user_id = $user_id;
        $payment->start_date = $start_date;
        $payment->acquired_gold_rate = $acquired_gold_rate;
        if ($plan_code === 'INR') {
            $payment->end_date =  Carbon::parse($start_date)->addYear();
        } elseif ($plan_code === 'SNR') {
            $payment->end_date = Carbon::parse($start_date)->addMonths(18);
        } elseif ($plan_code === 'TNR') {
            $payment->end_date = Carbon::parse($start_date)->addYears(2);
        }
        // $payment->end_date = $end_date;
        $payment->installment_id = $installment_id;
        $payment->request_date = $request_date;
        $payment->no_of_months = $no_of_months;
        $payment->remarks = $remarks;
        $payment->status = 'pending'; // Add status if required
        $payment->save();

        return $payment;
    }
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');
    }
}
