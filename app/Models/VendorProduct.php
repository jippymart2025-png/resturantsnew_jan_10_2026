<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorProduct extends Model
{
    protected $table = 'vendor_products';

    public $timestamps = false;

    protected $primaryKey = 'id';

    public $incrementing = false;

//    protected $fillable = [
//        'id',
//        'vendorID',
//        'name',
//        'price',
//        'disPrice',
//        'publish',
//    ];

    protected $guarded = [];

    protected $casts = [
        'publish' => 'boolean',
        'isAvailable' => 'boolean',
        'addOnsTitle' => 'array',
        'addOnsPrice' => 'array',
        'photos' => 'array',
        'product_specification' => 'array',
        'available_days' => 'array', // Auto-converts array to JSON when saving, JSON to array when reading (like workingHours in Vendor model)
        'available_timings' => 'array',// Auto-converts array to JSON when saving, JSON to array when reading (like workingHours in Vendor model)
        'options' => 'array', // Add this line
        'master_product_id',

    ];

    public function category()
    {
        return $this->belongsTo(VendorCategory::class, 'categoryID');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendorID');
    }
}

