<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\VendorUsers;
use App\Models\Vendor;
use App\Models\VendorProduct;
use App\Models\RestaurantOrder;
use App\Models\Currency;
use App\Http\Controllers\OrderController;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $range = request('range');
        $from  = request('from');
        $to    = request('to');

        $user = Auth::user();

        // Get firebase id from users table
        $firebaseId = $user->firebase_id;

        // Cache vendor lookup (5 minutes)
        $vendor = \Illuminate\Support\Facades\Cache::remember('vendor_' . $firebaseId, 300, function () use ($firebaseId) {
            return Vendor::where('author', $firebaseId)->select(['id', 'title', 'author'])->first();
        });

        // Cache currency meta (5 minutes)
        $currencyMeta = \Illuminate\Support\Facades\Cache::remember('active_currency_meta', 300, function () {
            $currency = Currency::where('isActive', true)->first();
            return [
                'symbol' => $currency->symbol ?? '₹',
                'symbol_at_right' => (bool) ($currency->symbolAtRight ?? false),
                'decimal_digits' => $currency->decimal_degits ?? 2,
            ];
        });



        // Cache dashboard data (5 minutes) - only load recent orders for dashboard
        $orders = collect();
        $productCount = 0;
        $totalOrders = 0;

        $statusCounts = [
            'placed' => 0,
            'confirmed' => 0,
            'completed' => 0,
            'rejected' => 0,
            'canceled' => 0,
        ];

        if ($vendor) {
            $productCount = VendorProduct::where('vendorID', $vendor->id)->count();

            // 1️⃣ Create query (same query, same select)
            $ordersQuery = RestaurantOrder::where('vendorID', $vendor->id)
                ->select([
                    'id',
                    'vendorID',
                    'status',
                    'products',
                    'author',
                    'createdAt',
                    'discount',
                    'deliveryCharge',
                    'toPayAmount',
                    'ToPay',
                    'adminCommission',
                    'adminCommissionType',
                    'taxSetting',
                    'takeAway',
                ]);

            // 2️⃣ Apply date filter ONLY if selected
            $ordersQuery = $this->applyDateFilter(
                $ordersQuery,
                request('range'),
                request('from'),
                request('to')
            );

            // 3️⃣ Final execution (same ordering)
            $orders = $ordersQuery
                ->orderByDesc('createdAt')
                ->get();
            // ✅ FILTER-BASED TOTAL ORDERS
            $totalOrders = $orders->count();

// ✅ FILTER-BASED STATUS COUNTS
            $statusCounts = [
                'placed' => $orders->where('status', 'Order Placed')->count(),

                'confirmed' => $orders->whereIn('status', [
                    'Order Accepted',
                    'Driver Accepted'
                ])->count(),

                'completed' => $orders->where('status', 'Order Completed')->count(),

                // ✅ ONLY rejected
                'rejected' => $orders->whereIn('status', [
                    'Order Rejected',
                    'Driver Rejected'
                ])->count(),

                // ✅ ONLY cancelled
                'canceled' => $orders->where('status', 'Order Cancelled')->count(),
            ];


        }

        $dashboard = $this->buildDashboardData($orders, $productCount, $currencyMeta);


        return view('home', [
            'stats' => [
                'total_orders' => $totalOrders,
                'total_products' => $dashboard['totals']['total_products'],
                'total_earnings_formatted' => $dashboard['totals']['total_earnings_formatted'],
            ],
            'statusCounts' => $statusCounts,
            'recentOrders' => $dashboard['recent_orders'], // unchanged
            'charts' => $dashboard['charts'],               // unchanged
            'currencyMeta' => $currencyMeta,
            'vendorExists' => (bool) $vendor,
        ]);

    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function welcome()
    {
        return view('welcome');
    }

    public function dashboard()
    {
        return view('dashboard');
    }

    public function users()
    {
        return view('users');
    }

    public function storeFirebaseService(Request $request){
        if(!empty($request->serviceJson) && !Storage::disk('local')->has('firebase/credentials.json')){
            Storage::disk('local')->put('firebase/credentials.json',file_get_contents(base64_decode($request->serviceJson)));
        }
    }

    /**
     * Build dashboard metrics from SQL data.
     */
    protected function buildDashboardData($orders, int $productCount, array $currencyMeta): array
    {
        $statusMap = [
            'placed' => ['Order Placed'],
            'confirmed' => ['Order Accepted', 'Driver Accepted'],
            'completed' => ['Order Completed'],
            'rejected' => ['Order Rejected', 'Driver Rejected'],
            'canceled' => ['Order Cancelled'],
            'failed' => ['Driver Rejected'],
            'pending' => ['Driver Pending'],
        ];

        $statusCounts = array_fill_keys(array_keys($statusMap), 0);
        $totalOrders = $orders->count();

        $earnings = 0;
        $commissionTotal = 0;
        $salesByMonth = array_fill(1, 12, 0);
        $recentOrders = [];

        $currentYear = Carbon::now()->year;

        $completedOrders = $orders->where('status', 'Order Completed');



        $completedEarnings = 0;

        foreach ($completedOrders as $completedOrder) {
            $completedEarnings += app(OrderController::class)
                ->calculateFinalTotal($completedOrder);
        }


        foreach ($orders as $order) {
            $status = trim((string)($order->status ?? ''));

            foreach ($statusMap as $label => $statuses) {
                if (in_array($status, $statuses, true)) {
                    $statusCounts[$label]++;
                    break;
                }
            }

            $parsed = $this->parseOrderTotals($order);



            if (
                $status === 'Order Completed' &&
                $parsed['date'] &&
                $parsed['date']->year === $currentYear
            ) {
                $salesByMonth[(int)$parsed['date']->format('n')] += (float)$parsed['total'];
            }

        }



        $recentOrders = $this->buildRecentOrders($orders, $currencyMeta);

        $totals = [
            'total_orders' => $totalOrders,
            'total_products' => $productCount,
            'total_earnings' => $completedEarnings,
            'total_earnings_formatted' => $this->formatCurrency($completedEarnings, $currencyMeta),
        ];

        $charts = [
            'sales' => [
                'labels' => ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'],
                'data' => array_values($salesByMonth),
            ],
            'visitors' => [
                'orders' => $totalOrders,
                'products' => $productCount,
            ],
            'commission' => [
                'labels' => ['Total Earnings'],
                'data' => [$completedEarnings],

            ],
        ];

        return [
            'totals' => $totals,
            'status_counts' => $statusCounts,
            'recent_orders' => $recentOrders,
            'charts' => $charts,
        ];
    }

    /**
     * Parse order totals and related metadata.
     */
    protected function parseOrderTotals(RestaurantOrder $order): array
    {
        $products = $this->decodeJson($order->products);
        $author = $this->decodeJson($order->author);
        $taxSetting = $this->decodeJson($order->taxSetting);

        $subtotal = 0;
        $productCount = 0;

        foreach ($products as $product) {
            $quantity = (int) ($product['quantity'] ?? 0);
            $price = $product['discountPrice'] ?? $product['price'] ?? 0;
            $extrasRaw = $product['extras_price'] ?? 0;
            $extrasTotal = 0;

            if (is_string($extrasRaw)) {
                // Handle "20,30" or "20, 30"
                $extrasArray = array_filter(array_map('trim', explode(',', $extrasRaw)));
                foreach ($extrasArray as $extra) {
                    $extrasTotal += (float) $extra;
                }
            } else {
                $extrasTotal = (float) $extrasRaw;
            }

            $lineTotal = ((float) $price * $quantity) + $extrasTotal;

            $subtotal += $lineTotal;
            $productCount += $quantity;
        }

        $discount = (float) ($order->discount ?? 0);
        $minPrice = max($subtotal - $discount, 0);

        $tax = 0;
        if (!empty($taxSetting) && isset($taxSetting['tax'])) {
            $taxValue = (float) $taxSetting['tax'];
            $type = $taxSetting['type'] ?? 'percent';
            $tax = $type === 'percent' ? ($minPrice * $taxValue / 100) : $taxValue;
        }

        $delivery = (float) ($order->deliveryCharge ?? 0);

        $total = max($minPrice + $tax + $delivery, 0);

        // ✅ SAME AS ORDER TABLE
        $total = app(\App\Http\Controllers\OrderController::class)
            ->calculateFinalTotal($order);



        $commission = 0;
        $commissionValue = (float) ($order->adminCommission ?? 0);
        if ($commissionValue > 0) {
            $type = $order->adminCommissionType ?? 'Percent';
            $commission = $type === 'Percent' ? ($total * $commissionValue / 100) : $commissionValue;
        }

        return [
            'total' => $total,
            'admin_commission' => $commission,
            'date' => $this->parseDate($order->createdAt),
            'author' => $author,
            'products' => $products,
            'product_count' => $productCount,
            'status' => $order->status ?? '',
            'takeAway' => $order->takeAway ?? null,
        ];
    }

    /**
     * Build recent orders collection.
     */
    protected function buildRecentOrders($orders, array $currencyMeta): array
    {
        return $orders->map(function ($order) use ($currencyMeta) {
            $parsed = $this->parseOrderTotals($order);
            $date = $parsed['date'];
            $author = $parsed['author'];

            $customerName = trim(($author['firstName'] ?? '') . ' ' . ($author['lastName'] ?? ''));
            $customerName = $customerName !== '' ? $customerName : 'N/A';

            return [
                'id' => $order->id,
                'customer' => $customerName,
                'type' => $this->formatOrderType($parsed['takeAway']),
                'grand_total' => $this->formatCurrency($parsed['total'], $currencyMeta),
                'products' => $parsed['product_count'],
                'date' => $date ? $date->format('d M Y h:i A') : '—',
                'timestamp' => $date ? $date->timestamp : 0,
                'status' => $order->status ?? 'N/A',
                'status_class' => $this->statusClass($order->status ?? ''),
                'url' => route('orders.edit', $order->id),
            ];
        })
            ->sortByDesc(function ($order) {
                return $order['timestamp'];
            })
            ->take(10)
            ->map(function ($order) {
                unset($order['timestamp']);
                return $order;
            })
            ->values()
            ->toArray();
    }

    protected function formatOrderType($value): string
    {
        $isTakeAway = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        return $isTakeAway ? 'Take away' : 'Order Delivery';
    }

    protected function statusClass(string $status): string
    {
        return match ($status) {
            'Order Placed' => 'order_placed',
            'Order Accepted', 'Driver Accepted' => 'order_accepted',
            'Order Rejected', 'Order Cancelled' => 'order_rejected',
            'Driver Pending' => 'driver_pending',
            'Driver Rejected' => 'driver_rejected',
            'Order Shipped' => 'order_shipped',
            'In Transit' => 'in_transit',
            'Order Completed' => 'order_completed',
            default => 'order_status_default',
        };
    }

    protected function formatCurrency(float $amount, array $currencyMeta): string
    {
        $formatted = number_format($amount, $currencyMeta['decimal_digits']);
        return $currencyMeta['symbol_at_right']
            ? $formatted . $currencyMeta['symbol']
            : $currencyMeta['symbol'] . $formatted;
    }

    protected function parseDate($value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        // Firebase array timestamps
        if (is_array($value)) {
            if (isset($value['_seconds'])) {
                return Carbon::createFromTimestamp($value['_seconds'], 'Asia/Kolkata');
            }
            if (isset($value['seconds'])) {
                return Carbon::createFromTimestamp($value['seconds'], 'Asia/Kolkata');
            }
        }

        $clean = trim((string) $value);
        $clean = trim($clean, '"');

        // ISO 8601 (2025-11-23T14:21:30.422489Z)
        if (str_contains($clean, 'T')) {
            try {
                return Carbon::parse($clean)->setTimezone('Asia/Kolkata');
            } catch (\Throwable $e) {}
        }

        // Normal SQL datetime (2026-02-04 16:42:08)
        try {
            return Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $clean,
                'Asia/Kolkata'
            );
        } catch (\Throwable $e) {}

        return null;
    }

    protected function decodeJson($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (empty($value)) {
            return [];
        }

        $clean = trim((string) $value);
        $clean = trim($clean, '"');
        $clean = str_replace(['\\"', "\\'"], '"', $clean);

        $decoded = json_decode($clean, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : [];
    }
    protected function applyDateFilter($query, ?string $range, ?string $from, ?string $to)
    {
        if (!$range) {
            return $query;
        }

        $now = Carbon::now();

        // ✅ ONE unified datetime expression (ISO + SQL)
        $dateExpr = "
        CASE
            WHEN createdAt LIKE '%T%'
                THEN STR_TO_DATE(SUBSTRING_INDEX(createdAt, '.', 1), '%Y-%m-%dT%H:%i:%s')
            ELSE STR_TO_DATE(createdAt, '%Y-%m-%d %H:%i:%s')
        END
    ";

        return match ($range) {

            'today' => $query->whereRaw(
                "DATE($dateExpr) = ?",
                [$now->toDateString()]
            ),

            'week' => $query->whereRaw(
                "$dateExpr BETWEEN ? AND ?",
                [
                    $now->copy()->startOfWeek()->format('Y-m-d H:i:s'),
                    $now->copy()->endOfWeek()->format('Y-m-d H:i:s'),
                ]
            ),

            'month' => $query->whereRaw(
                "MONTH($dateExpr) = ? AND YEAR($dateExpr) = ?",
                [$now->month, $now->year]
            ),

            'year' => $query->whereRaw(
                "YEAR($dateExpr) = ?",
                [$now->year]
            ),

            'custom' => ($from && $to)
                ? $query->whereRaw(
                    "$dateExpr BETWEEN ? AND ?",
                    [
                        Carbon::parse($from)->startOfDay()->format('Y-m-d H:i:s'),
                        Carbon::parse($to)->endOfDay()->format('Y-m-d H:i:s'),
                    ]
                )
                : $query,

            default => $query,
        };
    }



}
