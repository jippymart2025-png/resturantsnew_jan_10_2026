<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class AlertController extends Controller
{
    public function forgotPasswordAlert(Request $request)
    {
        try {

            // Validate email
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ],[
                'email.exists' => 'User not found in database.'
            ]);

            $email = $request->input('email');

            // Fetch vendor/user from MySQL
            $user = User::where('email', $email)
                ->where('role', 'vendor')
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found'
                ], 404);
            }

            /** 🔥 Generate Laravel password reset token */
            $token = Password::createToken($user);

            /** 🔗 Build reset URL */
            $resetLink = url('/password/reset/' . $token . '?email=' . urlencode($email));

            /** 📩 Email Body */
            $body =
                "Vendor Forgot Password Alert (MySQL)\n\n" .
                "Email: {$user->email}\n" .
                "Vendor ID: {$user->id}\n" .
                "Vendor Name: {$user->name}\n" .
                "Vendor Phone: {$user->phone}\n\n" .
                "Reset Link: $resetLink\n\n" .
                "Clicked At: " . now()->setTimezone('Asia/Kolkata')->format('d-m-Y h:i A') . "\n" .
                "IP: " . $request->ip() . "\n" .
                "User Agent: " . $request->userAgent() . "\n\n" .
                "Raw Vendor Data:\n" . json_encode($user, JSON_PRETTY_PRINT) . "\n";

            /** 📤 Send email to admin */
            Mail::raw($body, function ($message) {
                $message->to('devjippy@gmail.com')
                    ->subject('Vendor Forgot Password Alert (MySQL)');
            });

            return response()->json(['success' => true, 'message' => 'Alert sent successfully']);

        } catch (\Throwable $e) {

            Log::error("Forgot Password Alert Error: ".$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
