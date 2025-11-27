<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorCategory extends Model
{
    protected $table = 'vendor_categories';

    public $timestamps = false;

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

//    protected $fillable = [
//        'id',
//        'title',
//        'description',
//        'photo',
//        'publish',
//        'show_in_homepage',
//        'vType',
//        'isActive',
//    ];

     protected $guarded = [];

    protected $casts = [
        'publish' => 'boolean',
        'show_in_homepage' => 'boolean',
        'isActive' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('publish', true);
    }
}

