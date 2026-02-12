<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\VendorUsers;
use App\Models\Currency;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MySubscriptionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $email = $user->email;

        // Get userId from restaurant_vendor_users table
        $vendorUser = DB::table('restaurant_vendor_users')->where('email', $email)->first();
        if (!$vendorUser) {
            $userId = $user->firebase_id ?? Auth::id();
        } else {
            $userId = $vendorUser->uuid;
        }

        // Get currency data from MySQL
        $currency = Currency::where('isActive', 1)->first();
        $currencyData = [
            'symbol' => $currency->symbol ?? '$',
            'symbolAtRight' => $currency->symbolAtRight ?? false,
            'decimal_degits' => $currency->decimal_degits ?? 2
        ];

        // Get placeholder image from MySQL
        $placeholderSetting = Setting::where('document_name', 'placeHolderImage')->first();
        $placeholderImage = '';
        if ($placeholderSetting && is_array($placeholderSetting->fields)) {
            $placeholderImage = $placeholderSetting->fields['image'] ?? '';
        }

        return view("my_subscriptions.index", [
            'userId' => $userId,
            'currencyData' => $currencyData,
            'placeholderImage' => $placeholderImage
        ]);
    }

    public function getSubscriptionHistory(Request $request)
    {
        try {
            $user = Auth::user();
            $email = $user->email;

            // Get userId from restaurant_vendor_users table
            $vendorUser = DB::table('restaurant_vendor_users')->where('email', $email)->first();
            if (!$vendorUser) {
                $userId = $user->firebase_id ?? Auth::id();
            } else {
                $userId = $vendorUser->uuid;
            }

            // Get currency data
            $currency = Currency::where('isActive', 1)->first();
            $currencySymbol = $currency->symbol ?? '$';
            $currencyAtRight = $currency->symbolAtRight ?? false;
            $decimalDegits = $currency->decimal_degits ?? 2;

            // Get placeholder image
            $placeholderSetting = Setting::where('document_name', 'placeHolderImage')->first();
            $placeholderImage = '';
            if ($placeholderSetting && is_array($placeholderSetting->fields)) {
                $placeholderImage = $placeholderSetting->fields['image'] ?? '';
            }

            // Get pagination parameters
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            $searchValue = $request->input('search.value', '');
            $orderColumnIndex = $request->input('order.0.column', 4);
            $orderDirection = $request->input('order.0.dir', 'desc');

            // Get vendor ID if user has a vendor
            $vendorId = null;
            $vendor = DB::table('vendors')->where('author', $userId)->first();
            if ($vendor) {
                $vendorId = $vendor->id;
            }

            // Build query - optimized: fetch subscription_history first, then enrich with vendor data
            // Check if vendor_id column exists (migration might not be run yet)
            $hasVendorIdColumn = false;
            try {
                $columns = DB::select("SHOW COLUMNS FROM subscription_history LIKE 'vendor_id'");
                $hasVendorIdColumn = !empty($columns);
            } catch (\Exception $e) {
                // If check fails, assume column doesn't exist
                $hasVendorIdColumn = false;
            }

            $query = DB::table('subscription_history');

            if ($vendorId && $hasVendorIdColumn) {
                // Use vendor_id filter if column exists
                $query->where(function($q) use ($userId, $vendorId) {
                    $q->where('user_id', $userId)
                        ->orWhere('vendor_id', $vendorId);
                });
            } else {
                // Just use user_id filter (vendor_id column doesn't exist yet or no vendor)
                $query->where('user_id', $userId);
            }

            // Check if plan_id column exists for search
            $hasPlanIdColumn = false;
            try {
                $planIdColumns = DB::select("SHOW COLUMNS FROM subscription_history LIKE 'plan_id'");
                $hasPlanIdColumn = !empty($planIdColumns);
            } catch (\Exception $e) {
                $hasPlanIdColumn = false;
            }

            // Check if zone column exists for search
            $hasZoneColumn = false;
            try {
                $zoneColumns = DB::select("SHOW COLUMNS FROM subscription_history LIKE 'zone'");
                $hasZoneColumn = !empty($zoneColumns);
            } catch (\Exception $e) {
                $hasZoneColumn = false;
            }

            // Apply search filter
            if (!empty($searchValue)) {
                $query->where(function($q) use ($searchValue, $hasPlanIdColumn, $hasZoneColumn) {
                    $q->whereRaw("JSON_EXTRACT(subscription_plan, '$.name') LIKE ?", ['%' . $searchValue . '%'])
                        ->orWhereRaw("JSON_EXTRACT(subscription_plan, '$.price') LIKE ?", ['%' . $searchValue . '%'])
                        ->orWhere('payment_type', 'LIKE', '%' . $searchValue . '%')
                        ->orWhere('createdAt', 'LIKE', '%' . $searchValue . '%')
                        ->orWhere('expiry_date', 'LIKE', '%' . $searchValue . '%');

                    // Search by plan_id if column exists
                    if ($hasPlanIdColumn) {
                        $q->orWhere('plan_id', 'LIKE', '%' . $searchValue . '%');
                    }

                    // Search by zone if column exists
                    if ($hasZoneColumn) {
                        $q->orWhere('zone', 'LIKE', '%' . $searchValue . '%');
                    }
                });
            }

            // Get total records
            $totalRecords = $query->count();

            // Apply ordering (updated to include zone column - zone is at index 4)
            $orderableColumns = ['', 'name', 'price', 'payment_type', 'zone', 'createdAt', 'expiry_date', ''];
            $orderByField = $orderableColumns[$orderColumnIndex] ?? 'createdAt';

            if ($orderByField === 'name') {
                $query->orderByRaw("JSON_EXTRACT(subscription_plan, '$.name') " . $orderDirection);
            } elseif ($orderByField === 'price') {
                $query->orderByRaw("CAST(JSON_EXTRACT(subscription_plan, '$.price') AS DECIMAL(10,2)) " . $orderDirection);
            } elseif ($orderByField === 'zone' && $hasZoneColumn) {
                $query->orderBy('zone', $orderDirection);
            } elseif ($orderByField === 'createdAt') {
                $query->orderBy('createdAt', $orderDirection);
            } elseif ($orderByField === 'expiry_date') {
                $query->orderBy('expiry_date', $orderDirection);
            } else {
                $query->orderBy('createdAt', 'desc');
            }

            // Apply pagination
            $subscriptions = $query->skip($start)->take($length)->get();

            // Get vendor IDs from subscriptions to fetch vendor data in one query
            // Check if vendor_id column exists (in case migration not run yet)
            $vendorIds = [];
            if (!empty($subscriptions) && $hasVendorIdColumn) {
                $vendorIds = $subscriptions->pluck('vendor_id')->filter()->unique()->toArray();
            }

            // Also include current vendor's ID to ensure we have vendor data (same as show method fallback)
            if ($vendorId && !in_array($vendorId, $vendorIds)) {
                $vendorIds[] = $vendorId;
            }

            $vendorsData = [];
            if (!empty($vendorIds)) {
                $vendorsData = DB::table('vendors')
                    ->whereIn('id', $vendorIds)
                    ->select('id', 'subscriptionTransactionId', 'subscriptionPaymentDate', 'bill_status', 'subscriptionTotalOrders', 'subscriptionExpiryDate')
                    ->get()
                    ->keyBy('id');
            }

            // Format data for DataTables
            $formattedData = [];
            foreach ($subscriptions as $index => $subscription) {
                // Safely decode subscription_plan JSON
                $planData = [];
                if (!empty($subscription->subscription_plan)) {
                    if (is_string($subscription->subscription_plan)) {
                        $planData = json_decode($subscription->subscription_plan, true) ?? [];
                    } elseif (is_array($subscription->subscription_plan)) {
                        $planData = $subscription->subscription_plan;
                    }
                }

                // CRITICAL: Each subscription should use its OWN dates from subscription_history, not current vendor's dates
                // The vendor table only stores CURRENT subscription, historical subscriptions have their own dates
                // NEVER use vendor data for historical subscriptions - always use subscription_history's own data

                // Get payment date - use subscription_history's own payment_date or createdAt
                $paymentDate = null;
                if (property_exists($subscription, 'payment_date') && $subscription->payment_date) {
                    $paymentDate = $subscription->payment_date;
                } elseif ($subscription->createdAt) {
                    $paymentDate = $subscription->createdAt;
                }

                // Get expiry date - use subscription_history's own expiry_date
                $expiryDateToUse = $subscription->expiry_date ?? null;

                // Get other fields from subscription_history only
                $transactionId = null;
                $billStatus = 'paid';
                if (property_exists($subscription, 'transaction_id') && $subscription->transaction_id) {
                    $transactionId = $subscription->transaction_id;
                }
                if (property_exists($subscription, 'bill_status') && $subscription->bill_status) {
                    $billStatus = $subscription->bill_status;
                }

                // Format price
                $price = $planData['price'] ?? 0;
                if ($currencyAtRight) {
                    $formattedPrice = number_format($price, $decimalDegits) . $currencySymbol;
                } else {
                    $formattedPrice = $currencySymbol . number_format($price, $decimalDegits);
                }

                // Format dates - use payment_date from vendor table if available, otherwise use createdAt
                // This shows when the vendor actually took/purchased the subscription
                // Use Carbon with timezone conversion to ensure correct time display
                $createdAt = '';
                if ($paymentDate) {
                    // Use payment date from subscription_history (when payment was made)
                    // Dates are stored in Asia/Kolkata timezone, so parse as Asia/Kolkata
                    try {
                        $carbonDate = Carbon::createFromFormat('Y-m-d H:i:s', $paymentDate, 'Asia/Kolkata');
                        if (!$carbonDate) {
                            // Fallback: try parsing as-is and set timezone
                            $carbonDate = Carbon::parse($paymentDate)->setTimezone('Asia/Kolkata');
                        }
                        $createdAt = $carbonDate->format('D M d Y h:i:s A');
                    } catch (\Exception $e) {
                        // Fallback to original format if Carbon parsing fails
                        $date = date('D M d Y', strtotime($paymentDate));
                        $time = date('h:i:s A', strtotime($paymentDate));
                        $createdAt = $date . ' ' . $time;
                    }
                } elseif ($subscription->createdAt) {
                    // Fallback to createdAt from subscription_history
                    // Dates are stored in Asia/Kolkata timezone, so parse as Asia/Kolkata
                    try {
                        $carbonDate = Carbon::createFromFormat('Y-m-d H:i:s', $subscription->createdAt, 'Asia/Kolkata');
                        if (!$carbonDate) {
                            // Fallback: try parsing as-is and set timezone
                            $carbonDate = Carbon::parse($subscription->createdAt)->setTimezone('Asia/Kolkata');
                        }
                        $createdAt = $carbonDate->format('D M d Y h:i:s A');
                    } catch (\Exception $e) {
                        // Fallback to original format if Carbon parsing fails
                        $date = date('D M d Y', strtotime($subscription->createdAt));
                        $time = date('h:i:s A', strtotime($subscription->createdAt));
                        $createdAt = $date . ' ' . $time;
                    }
                }

                // Format expiry date - calculate from payment date if not available, to ensure time consistency
                // If expiryDateToUse is already set from subscription_history or vendor data, use it
                // Otherwise, calculate from payment date to ensure same time
                $expiryDate = '';

                // If we don't have expiry date, calculate from payment date to ensure same time
                if (!$expiryDateToUse && $paymentDate && $planData && isset($planData['expiryDay'])) {
                    try {
                        $paymentCarbon = Carbon::parse($paymentDate);
                        $expiryDay = $planData['expiryDay'] ?? '-1';

                        if ($expiryDay !== '-1' && is_numeric($expiryDay) && $expiryDay > 0) {
                            // Calculate expiry date from payment date to ensure same time
                            $calculatedExpiryDate = $paymentCarbon->copy()->addDays((int)$expiryDay);
                            $expiryDateToUse = $calculatedExpiryDate->toDateTimeString();
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Failed to calculate expiry date from payment date in list: ' . $e->getMessage());
                    }
                }

                if ($expiryDateToUse && $expiryDateToUse != '') {
                    try {
                        // Dates are stored in Asia/Kolkata timezone, so parse as Asia/Kolkata
                        $carbonDate = Carbon::createFromFormat('Y-m-d H:i:s', $expiryDateToUse, 'Asia/Kolkata');
                        if (!$carbonDate) {
                            // Fallback: try parsing as-is and set timezone
                            $carbonDate = Carbon::parse($expiryDateToUse)->setTimezone('Asia/Kolkata');
                        }
                        $expiryDate = $carbonDate->format('D M d Y h:i:s A');
                    } catch (\Exception $e) {
                        // Fallback to original format if Carbon parsing fails
                        $date = date('D M d Y', strtotime($expiryDateToUse));
                        $time = date('h:i:s A', strtotime($expiryDateToUse));
                        $expiryDate = $date . ' ' . $time;
                    }
                } else {
                    $expiryDate = trans('lang.unlimited');
                }

                // Return payment_type value only (not HTML) - DataTable will render it client-side
                $paymentType = $subscription->payment_type ?? '';

                // Get zone name if zone exists
                $zoneDisplay = '';
                if (property_exists($subscription, 'zone') && $subscription->zone) {
                    // Try to get zone name from zone table
                    $zone = DB::table('zone')->where('id', $subscription->zone)->orWhere('name', $subscription->zone)->first();
                    $zoneDisplay = $zone ? $zone->name : $subscription->zone;
                }

                // Build HTML row - first record (most recent) is active
                $activeClass = ($index === 0) ? '<span class="badge badge-success">' . trans('lang.active') . '</span>' : '';
                $route = route('my-subscription.show', ['id' => $subscription->id]);

                $formattedData[] = [
                    '<img onerror="this.onerror=null;this.src=\'' . $placeholderImage . '\'" class="rounded" style="width:50px" src="' . ($planData['image'] ?? $placeholderImage) . '" alt="image">',
                    ($planData['name'] ?? '') . ' ' . $activeClass,
                    $formattedPrice,
                    $paymentType,
                    $zoneDisplay ?: '-',
                    $createdAt,
                    $expiryDate,
                    '<span class="action-btn"><a href="' . $route . '"><i class="fa fa-eye"></i></a></span>'
                ];
            }

            $response = [
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $formattedData
            ];

            // Log for debugging (remove in production if not needed)
            \Log::info('Subscription history response', [
                'draw' => $response['draw'],
                'total' => $totalRecords,
                'data_count' => count($formattedData),
                'user_id' => $userId
            ]);

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::error('Error fetching subscription history: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_email' => Auth::user()->email ?? 'unknown'
            ]);

            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Failed to fetch subscription history. Please try again.'
            ], 500);
        }
    }

    private function formatPaymentMethod($paymentType)
    {
        $paymentTypeLower = strtolower($paymentType);
        $imagePath = asset('images/');

        $paymentMethods = [
            'stripe' => $imagePath . 'stripe.png',
            'razorpay' => $imagePath . 'razorpay.png',
            'paypal' => $imagePath . 'paypal.png',
            'payfast' => $imagePath . 'payfast.png',
            'paystack' => $imagePath . 'paystack.png',
            'flutterwave' => $imagePath . 'flutter_wave.png',
            'mercadopago' => $imagePath . 'marcado_pago.png',
            'wallet' => $imagePath . 'foodie_wallet.png',
            'paytm' => $imagePath . 'paytm.png',
            'xendit' => $imagePath . 'Xendit.png',
            'orangepay' => $imagePath . 'orangeMoney.png',
            'midtrans' => $imagePath . 'midtrans.png',
        ];

        if (isset($paymentMethods[$paymentTypeLower])) {
            return '<img style="width:100px" alt="image" src="' . $paymentMethods[$paymentTypeLower] . '">';
        }

        return $paymentType;
    }

    public function show($id)
    {
        $user = Auth::user();
        $email = $user->email;

        // Get userId from restaurant_vendor_users table
        $vendorUser = DB::table('restaurant_vendor_users')->where('email', $email)->first();
        if (!$vendorUser) {
            $userId = $user->firebase_id ?? Auth::id();
        } else {
            $userId = $vendorUser->uuid;
        }

        // Get vendor ID if user has a vendor
        $vendor = DB::table('vendors')->where('author', $userId)->first();
        $vendorId = $vendor ? $vendor->id : null;

        // Check if vendor_id column exists (migration might not be run yet)
        $hasVendorIdColumn = false;
        try {
            $columns = DB::select("SHOW COLUMNS FROM subscription_history LIKE 'vendor_id'");
            $hasVendorIdColumn = !empty($columns);
        } catch (\Exception $e) {
            $hasVendorIdColumn = false;
        }

        // Get subscription from MySQL
        $query = DB::table('subscription_history')
            ->where('subscription_history.id', $id)
            ->where('subscription_history.user_id', $userId);

        // Only filter by vendor_id if column exists and vendorId is available
        if ($hasVendorIdColumn && $vendorId) {
            $query->where(function($q) use ($userId, $vendorId) {
                $q->where('subscription_history.user_id', $userId)
                    ->orWhere('subscription_history.vendor_id', $vendorId);
            });
        }

        $subscription = $query->first();

        if (!$subscription) {
            return redirect()->route('my-subscriptions')->with('error', 'Subscription not found.');
        }

        // Get vendor data separately if vendor_id column exists and subscription has vendor_id
        $vendorData = null;
        if ($hasVendorIdColumn && isset($subscription->vendor_id) && $subscription->vendor_id) {
            $vendorData = DB::table('vendors')
                ->where('id', $subscription->vendor_id)
                ->select('id', 'subscriptionTransactionId', 'subscriptionPaymentDate', 'bill_status', 'subscriptionTotalOrders', 'subscriptionPlanId', 'subscription_plan', 'subscriptionExpiryDate')
                ->first();
        } elseif ($vendorId) {
            // Fallback: if no vendor_id in subscription_history, try to get vendor data by vendorId
            $vendorData = DB::table('vendors')
                ->where('id', $vendorId)
                ->select('id', 'subscriptionTransactionId', 'subscriptionPaymentDate', 'bill_status', 'subscriptionTotalOrders', 'subscriptionPlanId', 'subscription_plan', 'subscriptionExpiryDate')
                ->first();
        }

        // Add vendor data as properties to subscription object for backward compatibility
        if ($vendorData) {
            $subscription->vendor_transaction_id = $vendorData->subscriptionTransactionId ?? null;
            $subscription->vendor_payment_date = $vendorData->subscriptionPaymentDate ?? null;
            $subscription->vendor_bill_status = $vendorData->bill_status ?? null;
            $subscription->vendor_total_orders = $vendorData->subscriptionTotalOrders ?? null;
            $subscription->vendor_plan_id = $vendorData->subscriptionPlanId ?? null;
            $subscription->vendor_plan_data = $vendorData->subscription_plan ?? null;
            $subscription->vendor_expiry_date = $vendorData->subscriptionExpiryDate ?? null;
        }

        // Get currency data
        $currency = Currency::where('isActive', 1)->first();
        $currencyData = [
            'symbol' => $currency->symbol ?? '$',
            'symbolAtRight' => $currency->symbolAtRight ?? false,
            'decimal_degits' => $currency->decimal_degits ?? 2
        ];

        // Parse subscription plan data
        $planData = json_decode($subscription->subscription_plan, true);

        // Format dates with timezone conversion (same as index method)
        // CRITICAL: Each subscription should use its OWN dates from subscription_history, not current vendor's dates
        // The vendor table only stores CURRENT subscription, historical subscriptions have their own dates
        // NEVER use vendor data for historical subscriptions - always use subscription_history's own data
        $formattedActiveAt = '';
        $formattedExpiryDate = '';

        // Get payment date - use subscription_history's own payment_date or createdAt
        $paymentDate = null;
        if (property_exists($subscription, 'payment_date') && $subscription->payment_date) {
            $paymentDate = $subscription->payment_date;
        } elseif ($subscription->createdAt) {
            $paymentDate = $subscription->createdAt;
        }

        if ($paymentDate) {
            try {
                // Dates are stored in Asia/Kolkata timezone, so parse as Asia/Kolkata
                $carbonDate = Carbon::createFromFormat('Y-m-d H:i:s', $paymentDate, 'Asia/Kolkata');
                if (!$carbonDate) {
                    // Fallback: try parsing as-is and set timezone
                    $carbonDate = Carbon::parse($paymentDate)->setTimezone('Asia/Kolkata');
                }
                $formattedActiveAt = $carbonDate->format('D M d Y h:i:s A');
            } catch (\Exception $e) {
                // Fallback
                $date = date('D M d Y', strtotime($paymentDate));
                $time = date('h:i:s A', strtotime($paymentDate));
                $formattedActiveAt = $date . ' ' . $time;
            }
        }

        // Format expiry date - use subscription_history's own expiry_date
        $expiryDateToUse = $subscription->expiry_date ?? null;

        // If we don't have expiry date, calculate from payment date to ensure same time
        if (!$expiryDateToUse && $paymentDate && $planData && isset($planData['expiryDay'])) {
            try {
                $paymentCarbon = Carbon::parse($paymentDate);
                $expiryDay = $planData['expiryDay'] ?? '-1';

                if ($expiryDay !== '-1' && is_numeric($expiryDay) && $expiryDay > 0) {
                    // Calculate expiry date from payment date to ensure same time
                    $calculatedExpiryDate = $paymentCarbon->copy()->addDays((int)$expiryDay);
                    $expiryDateToUse = $calculatedExpiryDate->toDateTimeString();
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to calculate expiry date from payment date: ' . $e->getMessage());
            }
        }

        if ($expiryDateToUse && $expiryDateToUse != '') {
            try {
                // Dates are stored in Asia/Kolkata timezone, so parse as Asia/Kolkata
                $carbonDate = Carbon::createFromFormat('Y-m-d H:i:s', $expiryDateToUse, 'Asia/Kolkata');
                if (!$carbonDate) {
                    // Fallback: try parsing as-is and set timezone
                    $carbonDate = Carbon::parse($expiryDateToUse)->setTimezone('Asia/Kolkata');
                }
                $formattedExpiryDate = $carbonDate->format('D M d Y h:i:s A');
            } catch (\Exception $e) {
                // Fallback
                $date = date('D M d Y', strtotime($expiryDateToUse));
                $time = date('h:i:s A', strtotime($expiryDateToUse));
                $formattedExpiryDate = $date . ' ' . $time;
            }
        } else {
            $formattedExpiryDate = trans('lang.unlimited');
        }

        // Get zone name if zone exists
        $zoneDisplay = null;
        if (property_exists($subscription, 'zone') && $subscription->zone) {
            $zone = DB::table('zone')->where('id', $subscription->zone)->orWhere('name', $subscription->zone)->first();
            $zoneDisplay = $zone ? $zone->name : $subscription->zone;
        }

        return view('my_subscriptions.show', [
            'subscription' => $subscription,
            'planData' => $planData,
            'currencyData' => $currencyData,
            'vendorData' => $vendorData,
            'formattedActiveAt' => $formattedActiveAt,
            'formattedExpiryDate' => $formattedExpiryDate,
            'zoneDisplay' => $zoneDisplay
        ]);
    }
}
