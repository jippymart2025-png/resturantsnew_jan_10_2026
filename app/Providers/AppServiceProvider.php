<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\Vendor;
use App\Services\SubscriptionPlanService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
//        setcookie('XSRF-TOKEN-AK', bin2hex(env('FIREBASE_APIKEY')), time() + 3600, "/");
//        setcookie('XSRF-TOKEN-AD', bin2hex(env('FIREBASE_AUTH_DOMAIN')), time() + 3600, "/");
//        setcookie('XSRF-TOKEN-DU', bin2hex(env('FIREBASE_DATABASE_URL')), time() + 3600, "/");
//        setcookie('XSRF-TOKEN-PI', bin2hex(env('FIREBASE_PROJECT_ID')), time() + 3600, "/");
//        setcookie('XSRF-TOKEN-SB', bin2hex(env('FIREBASE_STORAGE_BUCKET')), time() + 3600, "/");
//        setcookie('XSRF-TOKEN-MS', bin2hex(env('FIREBASE_MESSAAGING_SENDER_ID')), time() + 3600, "/");
//        setcookie('XSRF-TOKEN-AI', bin2hex(env('FIREBASE_APP_ID')), time() + 3600, "/");
//        setcookie('XSRF-TOKEN-MI', bin2hex(env('FIREBASE_MEASUREMENT_ID')), time() + 3600, "/");
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer(['layouts.*'], function ($view) {
            $currentRoute = request()->route() ? (request()->route()->getName() ?? '') : '';
            $user = Auth::user();
            $vendor = null;
            $vendorId = null;
            $vendorUuid = null;

            if ($user) {
                $vendorUuid = $user->firebase_id ?? $user->_id ?? null;

                if (! empty($user->vendorID)) {
                    $vendorId = $user->vendorID;
                }

                if (! $vendorId && $vendorUuid) {
                    $vendor = Vendor::where('author', $vendorUuid)->first();
                } elseif ($vendorId) {
                    $vendor = Vendor::where('id', $vendorId)->first();
                }
            }

            $settings = Setting::whereIn('document_name', [
                'document_verification_settings',
                'DineinForRestaurant',
                'AdminCommission',
                'restaurant',
                'globalSettings',
            ])->get()->keyBy('document_name');

            $documentSettings = $settings->get('document_verification_settings');
            $dineInSettings = $settings->get('DineinForRestaurant');
            $brandSettings = $settings->get('globalSettings');

            // Check plan expiry (show alert if expiring within 2 days or less)
            // Only show on dashboard and restaurant pages
            $planExpiryAlert = null;
            $planExpiryDaysLeft = null;
            $planType = null;
            
            // Check if current route is dashboard or restaurant page (more flexible route checking)
            // Check route name, URI path, or if it contains 'home', 'restaurant', 'dashboard'
            $currentUri = request()->path();
            $showAlertOnThisPage = in_array($currentRoute, ['home', 'restaurant', 'dashboard']) 
                || $currentRoute === null  // Handle cases where route name might be null
                || str_contains($currentUri, 'restaurant')
                || str_contains($currentUri, 'dashboard')
                || $currentUri === ''  // Home page
                || $currentUri === '/';
            
            if ($showAlertOnThisPage && $vendor && !empty($vendor->subscriptionExpiryDate)) {
                try {
                    // Parse expiry date with multiple format attempts
                    $expiryDateStr = $vendor->subscriptionExpiryDate;
                    $expiryDate = null;
                    
                    // Try different date formats
                    $dateFormats = [
                        'Y-m-d H:i:s',
                        'Y-m-d',
                        'M j, Y g:i A',
                        'Y-m-d\TH:i:s',
                        'Y-m-d\TH:i:s.u\Z',
                    ];
                    
                    foreach ($dateFormats as $format) {
                        try {
                            $expiryDate = Carbon::createFromFormat($format, $expiryDateStr, 'Asia/Kolkata');
                            break;
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                    
                    // If format parsing failed, try Carbon::parse as fallback
                    if (!$expiryDate) {
                        $expiryDate = Carbon::parse($expiryDateStr)->setTimezone('Asia/Kolkata');
                    }
                    
                    $now = Carbon::now('Asia/Kolkata');
                    $daysLeft = $now->diffInDays($expiryDate, false);
                    
                    // Log for debugging (only in non-production or when explicitly enabled)
                    if (config('app.debug', false)) {
                        \Log::debug('Plan Expiry Check', [
                            'vendor_id' => $vendor->id,
                            'expiry_date_raw' => $expiryDateStr,
                            'expiry_date_parsed' => $expiryDate->toDateTimeString(),
                            'days_left' => $daysLeft,
                            'current_route' => $currentRoute,
                            'current_uri' => $currentUri,
                            'show_alert' => $showAlertOnThisPage
                        ]);
                    }
                    
                    // Show alert if plan expires within 2 days or less (and not already expired)
                    if ($daysLeft >= 0 && $daysLeft <= 2) {
                        $planExpiryDaysLeft = $daysLeft;
                        
                        // Get plan type
                        $planInfo = SubscriptionPlanService::getVendorPlanInfo($vendor);
                        $planType = $planInfo['planType'] === 'subscription' ? 'Subscription' : 'Commission';
                        
                        // Format expiry date for display (sanitize for localStorage key)
                        $expiryDateFormatted = $expiryDate->format('M d, Y');
                        $expiryDateKey = $expiryDate->format('Y-m-d'); // Use simple format for localStorage key
                        
                        $planExpiryAlert = [
                            'days_left' => $daysLeft,
                            'plan_type' => $planType,
                            'expiry_date' => $expiryDateFormatted,
                            'expiry_date_key' => $expiryDateKey // For localStorage key
                        ];
                    }
                } catch (\Exception $e) {
                    // Log date parsing errors for debugging
                    \Log::warning('Plan expiry date parsing failed', [
                        'vendor_id' => $vendor->id ?? null,
                        'expiry_date' => $vendor->subscriptionExpiryDate ?? null,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            $view->with([
                'layoutUser' => $user,
                'layoutVendor' => $vendor,
                'layoutVendorUuid' => $vendorUuid,
                'layoutDocumentVerificationRequired' => (bool) data_get($documentSettings?->fields, 'isRestaurantVerification', false),
                'layoutDineInEnabled' => (bool) data_get($dineInSettings?->fields, 'isEnabled', false),
                'layoutBranding' => $brandSettings?->fields ?? [],
                'layoutPlanExpiryAlert' => $planExpiryAlert,
            ]);
        });
    }
}
