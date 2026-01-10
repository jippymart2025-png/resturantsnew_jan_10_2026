<!-- <?php
/**
 * File name: VendorUser.php
 * Last modified: 2020.06.11 at 16:10:52
 * Copyright (c) 2020
 */

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\Permission\Traits\HasRoles;

/**
 * Class User
 * @package App\Models
 * @version July 10, 2018, 11:44 am UTC
 *
 * @property \App\Models\Cart[] cart
 * @property string name
 * @property string email
 * @property string password
 * @property string api_token
 * @property string device_token
 */
class VendorUsers extends Authenticatable
{

    /**
     * Validation rules
     *
     * @var array
     */

    public $table = 'users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

} -->
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Class VendorUsers
 * @package App\Models
 */
class VendorUsers extends Authenticatable
{
    use Notifiable, HasRoles, Billable;

    public $table = 'users';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 
        'email', 
        'password',
        'api_token',
        'device_token',
        'firebase_id',  // Add this
        'uuid',         // Add this
        'user_id',      // Add this if it exists in your table
        'vendorID',
        'firstName',
        'lastName',
        'phoneNumber',
        'role',
        'active',
        'wallet_amount',
        'subscription_plan',
        'subscriptionExpiryDate'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 
        'remember_token',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:6',
    ];

    /**
     * Get the cart items for the user.
     */
    public function cart()
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Check if user is a vendor
     */
    public function isVendor()
    {
        return $this->role === 'vendor' || !empty($this->vendorID);
    }

    /**
     * Scope to find vendor users
     */
    public function scopeVendors($query)
    {
        return $query->where('role', 'vendor');
    }

    /**
     * Scope to find by firebase_id
     */
    public function scopeByFirebaseId($query, $firebaseId)
    {
        return $query->where('firebase_id', $firebaseId);
    }

    /**
     * Scope to find by user_id
     */
    public function scopeByUserId($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}