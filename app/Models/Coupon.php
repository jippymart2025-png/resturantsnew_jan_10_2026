<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $table = 'coupons';

    public $timestamps = false;

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'isPublic' => 'boolean',
        'isEnabled' => 'boolean',
        'usageLimit' => 'integer',
        'usedCount' => 'integer',
        'item_value' => 'integer',
    ];

    public function scopeRestaurant($query)
    {
        return $query->where('cType', 'restaurant');
    }
}


