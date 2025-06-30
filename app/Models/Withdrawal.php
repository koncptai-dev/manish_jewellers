<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'withdrawals'; // Explicitly set the table name if it's not the plural of the model name

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'installment_id',
        'amount',
        'remarks',
        'status',
    ];

    /**
     * Get the user that owns the withdrawal.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the installment that this withdrawal is associated with.
     */
    public function installment()
    {
        return $this->belongsTo(InstallmentPayment::class);
    }
}