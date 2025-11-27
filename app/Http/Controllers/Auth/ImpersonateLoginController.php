<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ImpersonateLoginController extends Controller
{
    public function loginAsRestaurant(Request $request)
    {
        $key = $request->key;

        if (!$key) abort(403, 'Missing login token');

        $data = \DB::table('impersonation_tokens')
            ->where('token', $key)
            ->first();

        if (!$data) abort(403, 'Login link expired or invalid');

        if (time() > $data->expires_at) {
            abort(403, 'Login token expired');
        }

        $user = User::find($data->user_id);
        if (!$user) abort(404, 'User not found');

        Auth::login($user);

        // delete token after use (one time login)
        \DB::table('impersonation_tokens')->where('token', $key)->delete();

        return redirect('/dashboard');
    }

}
