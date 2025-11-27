<?php
/**
 * File name: RestaurantController.php
 * Last modified: 2020.04.30 at 08:21:08
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2020
 *
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\VendorUsers;


class UserController extends Controller
{

	public function __construct()

    {
        $this->middleware('auth');
    }


    public function profile()
    {
        $user = Auth::user();

        if (!$user) {
            abort(404, 'User not found');
        }

        $id = $user->id; // or $user->vendorID if needed

        return view('users.profile', [
            'id'   => $id,
            'user' => $user,
            'placeholderImage' => asset('images/placeholder.png'),
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            abort(404, 'User not found');
        }

        // Validate minimal fields
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'phone'      => 'required|string|max:20',
            'photo'      => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Update basic fields
        $user->firstName   = $request->first_name;
        $user->lastName    = $request->last_name;
        $user->phoneNumber = $request->phone;

        // ----------- 🔥 IMAGE UPLOAD (Laravel Storage) -----------
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('users/profile', 'public');
            $user->profilePictureURL = asset('storage/' . $path);
        }

        // ----------- 🔥 BANK DETAILS (JSON stored in DB) -----------
        $user->userBankDetails = json_encode([
            'bankName'     => $request->bank_name,
            'branchName'   => $request->branch_name,
            'holderName'   => $request->holder_name,
            'accountNumber'=> $request->account_number,
            'otherDetails' => $request->other_information,
        ]);

        $user->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Profile updated successfully',
            'image'   => $user->profilePictureURL ?? null
        ]);
    }

    public function restaurant()
  {
   	  $user = Auth::user();

      if (!$user) {
          abort(404, 'User not found');
      }

      // Get firebase_id from authenticated user
      $firebaseId = Auth::id();

      // Try to find VendorUsers record with firebase_id
      $exist = VendorUsers::where('firebase_id', $firebaseId)->first();

      // Use uuid if exists, otherwise fallback to _id or user id
      $id = ($exist && isset($exist->uuid)) ? $exist->uuid : ($user->_id ?? $user->id ?? null);

      if (!$id) {
          abort(404, 'Restaurant ID not found');
      }

      return view('restaurant.myrestaurant')->with('id', $id);
  }

}
