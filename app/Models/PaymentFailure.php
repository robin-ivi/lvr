<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentFailure extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'purpose',
        'amount',
        'currency',
        'razorpay_order_id',
        'razorpay_payment_id',
        'error_code',
        'error_description',
        'error_source',
        'error_step',
        'error_reason',
        'payload',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payload' => 'array',
            'failed_at' => 'datetime',
        ];
    }
}
