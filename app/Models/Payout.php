<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payout extends Model
{
    protected $table = 'payouts';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'id';
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'note',
        'amount',
        'withdrawMethod',
        'paidDate',
        'vendorID',
        'adminNote',
        'paymentStatus',
        'payoutResponse',
    ];

    protected $casts = [
        'amount' => 'float',
        'payoutResponse' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function (Payout $payout) {
            if (empty($payout->id)) {
                $payout->id = Str::uuid()->toString();
            }
        });
    }
}


