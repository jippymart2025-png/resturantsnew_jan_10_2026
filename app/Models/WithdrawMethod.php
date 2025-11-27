<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawMethod extends Model
{
    protected $table = 'withdraw_method';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'id';
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'flutterwave',
        'userId',
        'paypal',
        'stripe',
        'razorpay',
    ];

    protected $casts = [
        'flutterwave' => 'array',
        'paypal' => 'array',
        'stripe' => 'array',
        'razorpay' => 'array',
    ];
}


