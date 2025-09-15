<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InstallmentPayment extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'plan_code',
        'plan_category',
        'total_yearly_payment',
        'total_gold_purchase',
        'start_date',
        'uuid',
        'acquired_gold_rate'
    ];

    public function details()
    {
        return $this->hasMany(InstallmentPaymentDetail::class);
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
}
