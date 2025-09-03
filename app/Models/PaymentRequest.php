<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRequest extends Model
{
    use HasUuid, HasFactory;

    protected $table = 'payment_requests';

    protected $fillable = [
        'payer_id',
        'receiver_id',
        'payment_amount',
        'gateway_callback_url',
        'success_hook',
        'failure_hook',
        'transaction_id',
        'currency_code',
        'payment_method',
        'additional_data',
        'is_paid',
        'payer_information',
        'external_redirect_link',
        'receiver_information',
        'attribute_id',
        'attribute',
        'payment_platform'
    ];

    public static function createPaymentRequest($data)
    {
        return self::create([
            'payer_id' => $data['payer_id'],
            'receiver_id' => $data['receiver_id'],
            'payment_amount' => $data['payment_amount'],
            'gateway_callback_url' => $data['gateway_callback_url'],
            'success_hook' => $data['success_hook'],
            'failure_hook' => $data['failure_hook'],
            'transaction_id' => null,
            'currency_code' => $data['currency_code'] ?? 'INR',
            'payment_method' => $data['payment_method'] ?? null,
            'additional_data' => $data['additional_data'] ?? null,
            'is_paid' => 0,
            'payer_information' => json_encode($data['payer_information']),
            'external_redirect_link' => $data['external_redirect_link'] ?? null,
            'receiver_information' => $data['receiver_information'] ?? null,
            'attribute_id' => $data['attribute_id'] ?? null,
            'attribute' => $data['attribute'] ?? null,
            'payment_platform' => $data['payment_platform'] ?? null,
            'acquired_gold_rate' => $data['acquired_gold_rate'] ?? null,
        ]);
    }
}
