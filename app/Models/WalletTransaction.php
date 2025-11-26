<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $table = 'wallet';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'date',
        'subscription_id',
        'note',
        'transactionUser',
        'amount',
        'user_id',
        'payment_status',
        'isTopUp',
        'order_id',
        'payment_method',
    ];

    protected $casts = [
        'amount' => 'float',
        'isTopUp' => 'boolean',
    ];
}


