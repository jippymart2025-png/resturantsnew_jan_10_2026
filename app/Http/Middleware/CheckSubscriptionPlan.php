<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckSubscriptionPlan
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Check if user is logged in
        if (Auth::check()) {
            $user = Auth::user();
            
            // Subscription is OPTIONAL - only redirect if explicitly set to 'false'
            // If isSubscribed is NULL, empty, or 'true', allow access
            // This allows vendors to use the system without subscription
            if ($user->isSubscribed === 'false' || $user->isSubscribed === false) {
                // Redirect to the subscription plan page only if explicitly blocked
                return redirect()->route('subscription-plan.show');
            }
            
            // If isSubscribed is NULL, empty, or 'true', allow access (subscription is optional)
        }

        return $next($request);
    }
}
