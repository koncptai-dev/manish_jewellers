<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InstallmentPayment extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'plan_code',
        'plan_amount',
        'plan_category',
        'total_yearly_payment',
        'total_gold_purchase',
        'start_date',
        'uuid',
    ];

    public function details()
    {
        return $this->hasMany(InstallmentPaymentDetail::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Boot method to auto-generate UUID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid(); // Generate UUID
            }
        });
    }
    /**
     * Method to store an installment payment with its details.
     *
     * @param array $data
     * @param array $details
     * @return self
     */
    public static function createWithDetails(array $data, array $details)
    {
        // Create the installment payment record

        $installmentPayment = self::create($data);

        // Add details if provided
        if (!empty($details)) {
            foreach ($details as $detail) {
                $installmentPayment->details()->create($detail);
            }
        }

        return $installmentPayment;
    }

    public static function getUserInstallmentList($userId)
    {
        return (new InstallmentPayment())->where('user_id', $userId)->get();
    }

    /**
     * Format a date to a desired format.
     *
     * @param string|\Illuminate\Support\Carbon|null $date
     * @return string|null
     */

    public function formatDate($date)
    {
        if (!$date instanceof \DateTime) {
            $date = Carbon::parse($date); // Convert string to Carbon
        }
        return $date->format('Y-m-d');
    }
    public function formatTime($date)
    {
        if (!$date instanceof \DateTime) {
            $date = Carbon::parse($date); // Convert string to Carbon
        }
        return $date->format('H:i:s'); // Returns time in 24-hour format
    }


    /**
     * Method to store an installment payment with its details.
     *
     * @param array $data
     * @param array $details
     * @return self
     */
    // public static function updateWithDetails($request, array $details)
    // {
    //     $installmentPayment = self::find($request['installment_payment_id']);

    //     $installmentPayment->total_gold_purchase = $request['total_gold_purchase'];
    //     $installmentPayment->save();

    //     if (!empty($details)) {
    //         foreach ($details as $detail) {
    //             $installmentPayment->details()->create($detail);
    //         }
    //     }

    //     return $installmentPayment;
    // }

    public static function updateWithDetails($request, array $details)
    {
        // Fetch the Installment Payment master record
        $installmentPayment = self::find($request['installment_payment_id']);

        // Add new details to the installment_payment_details table
        if (!empty($details)) {
            foreach ($details as $detail) {
                $installmentPayment->details()->create($detail);
            }
        }

        // Calculate the sum of purchase_gold_weight from the related details
        $totalGoldPurchase = $installmentPayment->details()->sum('purchase_gold_weight');

        // Update the total_gold_purchase in the master table
        $installmentPayment->total_gold_purchase = $totalGoldPurchase;
        $installmentPayment->save();

        return $installmentPayment;
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class, 'installment_id');
    }

    // Accessor to calculate total withdrawn amount on the fly
    public function getTotalWithdrawnAmountAttribute()
    {
        return $this->withdrawals()->sum('amount');
    }
}
