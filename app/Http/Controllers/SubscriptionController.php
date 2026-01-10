<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\VendorUsers;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WalletTransaction;
use App\Models\Setting;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;
use Illuminate\Http\Request;
use Session;
use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\XenditSdkException;
use GuzzleHttp\Client;
class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function show()
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
        
        // Get admin commission settings from MySQL
        $adminCommissionSetting = Setting::where('document_name', 'AdminCommission')->first();
        // fields is already cast to array in Setting model, so use it directly
        $commissionData = $adminCommissionSetting ? ($adminCommissionSetting->fields ?? []) : [];
        $commisionModel = $commissionData['isEnabled'] ?? false;
        $adminCommission = $commissionData['fix_commission'] ?? 0;
        $commissionType = $commissionData['commissionType'] ?? 'Percent';
        
        // Get restaurant/subscription model settings from MySQL
        $restaurantSetting = Setting::where('document_name', 'restaurant')->first();
        // fields is already cast to array in Setting model, so use it directly
        $restaurantData = $restaurantSetting ? ($restaurantSetting->fields ?? []) : [];
        $subscriptionModel = $restaurantData['subscription_model'] ?? false;
        
        // Get active subscription for user from MySQL
        $activeSubscription = DB::table('subscription_history')
            ->where('user_id', $userId)
            ->orderBy('createdAt', 'desc')
            ->first();
        $activeSubscriptionId = null;
        if ($activeSubscription) {
            $planData = json_decode($activeSubscription->subscription_plan, true);
            $activeSubscriptionId = $planData['id'] ?? null;
        }
        
        // Get vendor ID if exists
        $vendorId = null;
        $vendor = DB::table('vendors')->where('author', $userId)->first();
        if ($vendor) {
            $vendorId = $vendor->id;
        }
        
        // Get all active subscription plans from MySQL
        $subscriptionPlans = DB::table('subscription_plans')
            ->where('isEnable', 1)
            ->orderBy('place', 'asc')
            ->get()
            ->map(function($plan) {
                $plan->plan_points = json_decode($plan->plan_points, true) ?? [];
                $plan->features = json_decode($plan->features, true) ?? [];
                return $plan;
            });
        
        // Get global settings from MySQL
        $globalSettings = Setting::where('document_name', 'globalSettings')->first();
        // fields is already cast to array in Setting model, so use it directly
        $globalSettingsData = $globalSettings ? ($globalSettings->fields ?? []) : [];
        
        return view("subscription_plans.show", [
            'userId' => $userId,
            'currencyData' => $currencyData,
            'commisionModel' => $commisionModel,
            'adminCommission' => $adminCommission,
            'commissionType' => $commissionType,
            'subscriptionModel' => $subscriptionModel,
            'activeSubscriptionId' => $activeSubscriptionId,
            'vendorId' => $vendorId,
            'subscriptionPlans' => $subscriptionPlans,
            'globalSettings' => $globalSettingsData
        ]);
    }
    public function checkout($id)
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
        
        $planId = $id;
        
        // Get currency data from MySQL
        $currency = Currency::where('isActive', 1)->first();
        $currencyData = [
            'symbol' => $currency->symbol ?? '$',
            'symbolAtRight' => $currency->symbolAtRight ?? false,
            'decimal_degits' => $currency->decimal_degits ?? 2
        ];
        
        // Get admin commission settings from MySQL
        $adminCommissionSetting = Setting::where('document_name', 'AdminCommission')->first();
        // fields is already cast to array in Setting model, so use it directly
        $commissionData = $adminCommissionSetting ? ($adminCommissionSetting->fields ?? []) : [];
        $commisionModel = $commissionData['isEnabled'] ?? false;
        $adminCommission = $commissionData['fix_commission'] ?? 0;
        $commissionType = $commissionData['commissionType'] ?? 'Percent';
        
        // Get selected plan from MySQL
        $planData = DB::table('subscription_plans')
            ->where('id', $planId)
            ->where('isEnable', 1)
            ->first();
        
        if (!$planData) {
            return redirect()->route('subscription-plan.show')->with('error', 'Subscription plan not found.');
        }
        
        // Parse plan data
        $planData->plan_points = json_decode($planData->plan_points ?? '[]', true) ?? [];
        $planData->features = json_decode($planData->features ?? '{}', true) ?? [];
        
        // Get active subscription for user from MySQL
        $activeSubscription = DB::table('subscription_history')
            ->where('user_id', $userId)
            ->orderBy('createdAt', 'desc')
            ->first();
        $activeSubscriptionData = null;
        if ($activeSubscription) {
            $activeSubscriptionData = json_decode($activeSubscription->subscription_plan, true);
        }
        
        // Get vendor ID if exists
        $vendorId = null;
        $vendor = DB::table('vendors')->where('author', $userId)->first();
        if ($vendor) {
            $vendorId = $vendor->id;
        }
        
        // Get user wallet amount from MySQL
        $walletAmount = $user->wallet_amount ?? 0;
        
        // Get all payment gateway settings from MySQL
        $razorpaySettings = Setting::where('document_name', 'razorpaySettings')->first();
        // fields is already cast to array in Setting model, so use it directly
        $razorpaySettingsData = $razorpaySettings ? ($razorpaySettings->fields ?? []) : [];
        
        $stripeSettings = Setting::where('document_name', 'stripeSettings')->first();
        $stripeSettingsData = $stripeSettings ? ($stripeSettings->fields ?? []) : [];
        
        $paypalSettings = Setting::where('document_name', 'paypalSettings')->first();
        $paypalSettingsData = $paypalSettings ? ($paypalSettings->fields ?? []) : [];
        
        $walletSettings = Setting::where('document_name', 'walletSettings')->first();
        $walletSettingsData = $walletSettings ? ($walletSettings->fields ?? []) : [];
        
        $payFastSettings = Setting::where('document_name', 'payFastSettings')->first();
        $payFastSettingsData = $payFastSettings ? ($payFastSettings->fields ?? []) : [];
        
        $payStackSettings = Setting::where('document_name', 'payStackSettings')->first();
        $payStackSettingsData = $payStackSettings ? ($payStackSettings->fields ?? []) : [];
        
        $flutterWaveSettings = Setting::where('document_name', 'flutterWaveSettings')->first();
        $flutterWaveSettingsData = $flutterWaveSettings ? ($flutterWaveSettings->fields ?? []) : [];
        
        $MercadoPagoSettings = Setting::where('document_name', 'MercadoPagoSettings')->first();
        $MercadoPagoSettingsData = $MercadoPagoSettings ? ($MercadoPagoSettings->fields ?? []) : [];
        
        $XenditSettings = Setting::where('document_name', 'XenditSettings')->first();
        $XenditSettingsData = $XenditSettings ? ($XenditSettings->fields ?? []) : [];
        
        $Midtrans_settings = Setting::where('document_name', 'Midtrans_settings')->first();
        $Midtrans_settingsData = $Midtrans_settings ? ($Midtrans_settings->fields ?? []) : [];
        
        $OrangePaySettings = Setting::where('document_name', 'OrangePaySettings')->first();
        $OrangePaySettingsData = $OrangePaySettings ? ($OrangePaySettings->fields ?? []) : [];
        
        return view("subscription_plans.checkout", [
            'userId' => $userId,
            'planId' => $planId,
            'planData' => $planData,
            'currencyData' => $currencyData,
            'commisionModel' => $commisionModel,
            'adminCommission' => $adminCommission,
            'commissionType' => $commissionType,
            'activeSubscriptionData' => $activeSubscriptionData,
            'vendorId' => $vendorId,
            'walletAmount' => $walletAmount,
            'razorpaySettings' => $razorpaySettingsData,
            'stripeSettings' => $stripeSettingsData,
            'paypalSettings' => $paypalSettingsData,
            'walletSettings' => $walletSettingsData,
            'payFastSettings' => $payFastSettingsData,
            'payStackSettings' => $payStackSettingsData,
            'flutterWaveSettings' => $flutterWaveSettingsData,
            'MercadoPagoSettings' => $MercadoPagoSettingsData,
            'XenditSettings' => $XenditSettingsData,
            'Midtrans_settings' => $Midtrans_settingsData,
            'OrangePaySettings' => $OrangePaySettingsData,
        ]);
    }
    public function orderProccessing(Request $request)
    {
        $cart_order = $request->all();
        $email = Auth::user()->email;
        $cart = Session::get('cart', []);
        $cart['cart_order'] = $cart_order;
        Session::put('cart', $cart);
        Session::save();
        $res = array('status' => true);
        echo json_encode($res);
        exit;
    }
    public function proccesstopay(Request $request)
    {
        $email = Auth::user()->email;
        $user = VendorUsers::where('email', $email)->first();
        $cart = Session::get('cart', []);
        if (@$cart['cart_order']) {
            if ($cart['cart_order']['payment_method'] == 'razorpay') {
                $razorpaySecret = $cart['cart_order']['razorpaySecret'];
                $razorpayKey = $cart['cart_order']['razorpayKey'];
                $authorName = $cart['cart_order']['order_json']['authorName'];
                $total_pay = $cart['cart_order']['total_pay'];
                $amount = 0;
                $formatted_price =  $cart['cart_order']['currencyData']['symbol'].number_format($total_pay,$cart['cart_order']['currencyData']['decimal_degits']) ;
                return view('subscription_plans.razorpay', ['is_checkout' => 1, 'cart' => $cart, 'id' => $user->uuid, 'email' => $email, 'amount' => $total_pay,'authorName'=>$authorName, 'razorpaySecret' => $razorpaySecret, 'razorpayKey' => $razorpayKey, 'cart_order' => $cart['cart_order'], 'formatted_price' => $formatted_price]);
            } 
            else if ($cart['cart_order']['payment_method'] == 'payfast') {
                $payfast_merchant_key = $cart['cart_order']['payfast_merchant_key'];
                $payfast_merchant_id = $cart['cart_order']['payfast_merchant_id'];
                $payfast_isSandbox = $cart['cart_order']['payfast_isSandbox'];
                $payfast_return_url = route('success');
                $payfast_notify_url = route('notify');
                $payfast_cancel_url = route('subscription-plan.show');
                $authorName = $cart['cart_order']['order_json']['authorName'];
                $total_pay = $cart['cart_order']['total_pay'];
                $formatted_price =  $cart['cart_order']['currencyData']['symbol'].number_format($total_pay,$cart['cart_order']['currencyData']['decimal_degits']) ;
                $amount = 0;
                $token = uniqid();
                Session::put('payfast_payment_token', $token);
                Session::save();
                $payfast_return_url = $payfast_return_url . '?token=' . $token;
                $amount = number_format($total_pay, 2, '.', '');
                $data = [
                    'merchant_id' => $payfast_merchant_id,
                    'merchant_key' => $payfast_merchant_key,
                    'return_url' => $payfast_return_url,
                    'cancel_url' => $payfast_cancel_url,
                    'notify_url' => $payfast_notify_url,
                    'name_first' => $authorName,
                    'm_payment_id' => $token,
                    'amount' => $amount,
                    'item_name' => "Item",
                ];
                $signature = $this->generateSignature($data);
                $data['signature'] = $signature;
                $pfHost = $payfast_isSandbox == "true" ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
                return view('subscription_plans.payfast', [
                    'amount'=>$amount,
                    'pfHost'=>$pfHost,
                    'data' => $data,
                    'payfast_merchant_key' => $payfast_merchant_key, 
                    'payfast_merchant_id' => $payfast_merchant_id,  
                    'payfast_return_url' => $payfast_return_url,
                    'payfast_notify_url' => $payfast_notify_url, 
                    'payfast_cancel_url' => $payfast_cancel_url,
                    'item_name' => "Item",
                    'formatted_price' => $formatted_price,
                ]);
            } 
            else if ($cart['cart_order']['payment_method'] == 'xendit') {
                $xendit_enable = $cart['cart_order']['xendit_enable'];
                $xendit_apiKey = $cart['cart_order']['xendit_apiKey'];
                if (isset($xendit_enable) && $xendit_enable == true) {
                    $total_pay = $cart['cart_order']['total_pay'];
                    $currency = $cart['cart_order']['currencyData']['code'];
                    $fail_url = route('subscription-plan.show');
                    $success_url = route('success');
                    Configuration::setXenditKey($xendit_apiKey);
                    $token = uniqid();
                    $success_url = $success_url . '?xendit_token=' . $token;
                    Session::put('xendit_payment_token', $token);
                    Session::save();
                    $apiInstance = new InvoiceApi();
                    $create_invoice_request = new CreateInvoiceRequest([
                        'external_id' => $token,
                        'description' => '#' . $token . ' Order place',
                        'amount' => (int) ($total_pay) * 1000,
                        'invoice_duration' => 300,
                        'currency' => 'IDR',
                        'success_redirect_url' => $success_url,
                        'failure_redirect_url' => $fail_url
                    ]);
                    try {
                        $result = $apiInstance->createInvoice($create_invoice_request);
                        return redirect($result['invoice_url']);
                    } catch (XenditSdkException $e) {
                        return response()->json([
                            'message' => 'Exception when calling InvoiceApi->createInvoice: ' . $e->getMessage(),
                            'error' => $e->getFullError(),
                        ], 500);
                    }
                }
            } 
            else if ($cart['cart_order']['payment_method'] == 'midtrans') {
                $midtrans_enable = $cart['cart_order']['midtrans_enable'];
                $midtrans_serverKey = $cart['cart_order']['midtrans_serverKey'];
                $midtrans_isSandbox = $cart['cart_order']['midtrans_isSandbox'];
                if (isset($midtrans_enable) && isset($midtrans_serverKey) && $midtrans_enable == true) {
                    if ($midtrans_isSandbox == true)
                        $url = 'https://api.sandbox.midtrans.com/v1/payment-links';
                    else
                        $url = 'https://api.midtrans.com/v1/payment-links';
                    $total_pay = $cart['cart_order']['total_pay'];
                    $currency = $cart['cart_order']['currencyData']['code'];
                    $fail_url = route('subscription-plan.show');
                    $success_url = route('success');
                    $token = uniqid();
                    $success_url = $success_url . '?midtrans_token=' . $token;
                    Session::put('midtrans_payment_token', $token);
                    Session::save();
                    $payload = [
                        'transaction_details' => [
                            'order_id' => $token,
                            'gross_amount' => (int) ($total_pay) * 1000,
                        ],
                        'usage_limit' => 1,
                        'callbacks' => [
                                'error' => $fail_url,
                                'unfinish' => $fail_url,
                                'close' => $fail_url,
                                'finish' => $success_url,
                            ]
                    ];
                    try {
                        $client = new Client();
                        $response = $client->post($url, [
                            'headers' => [
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json',
                                'Authorization' => 'Basic ' . base64_encode($midtrans_serverKey)
                            ],
                            'body' => json_encode($payload)
                        ]);
                        $responseBody = json_decode($response->getBody(), true);
                        if (isset($responseBody['payment_url'])) {
                            return redirect($responseBody['payment_url']);
                        } else {
                            return response()->json(['error' => 'Failed to generate payment link'], 500);
                        }
                    } catch (\Exception $e) {
                        return response()->json(['error' => $e->getMessage()], 500);
                    }
                }
            } 
            else if ($cart['cart_order']['payment_method'] == 'orangepay') {
                $orangepay_enable = $cart['cart_order']['orangepay_enable'];
                $orangepay_isSandbox = $cart['cart_order']['orangepay_isSandbox'];
                Session::put('orangepay_isSandbox', $orangepay_isSandbox);
                Session::save();
                $orangepay_clientId = $cart['cart_order']['orangepay_clientId'];
                $orangepay_clientSecret = $cart['cart_order']['orangepay_clientSecret'];
                $orangepay_merchantKey = $cart['cart_order']['orangepay_merchantKey'];
                $token = $this->getAccessToken($orangepay_clientId, $orangepay_clientSecret);
                Session::put('orangepay_access_token', $token);
                Session::save();
                if (isset($token) && $token != null && isset($orangepay_enable) && isset($orangepay_clientId) && $orangepay_enable == true) {
                    if ($orangepay_isSandbox == true)
                        $url = 'https://api.orange.com/orange-money-webpay/dev/v1/webpayment';
                    else
                    $url = 'https://api.orange.com/orange-money-webpay/cm/v1/webpayment';
                    $total_pay = $cart['cart_order']['total_pay'];
                    $currency = ($orangepay_isSandbox == true) ? 'OUV' : $cart['cart_order']['currencyData']['code'];
                    $orangepay_token = uniqid();
                    $fail_url = route('subscription-plan.show');
                    $success_url = route('success');
                    $success_url = $success_url . '?orangepay_token=' . $orangepay_token;
                    $notify_url = $success_url . '?orangepay_token=' . $orangepay_token;
                    Session::put('orangepay_payment_token', $orangepay_token);
                    Session::save();
                    $payload = [
                        'merchant_key' => $orangepay_merchantKey,
                        'currency' => $currency,
                        'order_id' => $orangepay_token,
                        'amount' => (int) ($total_pay),
                        'return_url' => $success_url,
                        'cancel_url' => $fail_url,
                        'notif_url' => $notify_url,
                        'lang' => 'en',
                        'reference' => $orangepay_token,
                    ];
                    try {
                        $client = new Client();
                        $response = $client->post($url, [
                            'headers' => [
                                'Authorization' => 'Bearer ' . $token,
                                'Content-Type' => 'application/json',
                            ],
                            'body' => json_encode($payload),
                        ]);
                        $responseBody = json_decode($response->getBody(), true);
                        if (isset($responseBody['payment_url'])) {
                            Session::put('orangepay_payment_check_token', $responseBody['pay_token']);
                            Session::save();
                            return redirect($responseBody['payment_url']);
                        } else {
                            return response()->json(['error' => 'Payment request failed']);
                        }
                    } catch (\Exception $e) {
                        return response()->json(['error' => $e->getMessage()]);
                    }
                }
            } 
            else if ($cart['cart_order']['payment_method'] == 'paystack') {
                $paystack_public_key = $cart['cart_order']['paystack_public_key'];
                $paystack_secret_key = $cart['cart_order']['paystack_secret_key'];
                $paystack_isSandbox = $cart['cart_order']['paystack_isSandbox'];
                $authorName = $cart['cart_order']['order_json']['authorName'];
                $email = $cart['cart_order']['order_json']['authorEmail'];
                $total_pay = $cart['cart_order']['total_pay'];
                $amount = 0;
                $fail_url = route('subscription-plan.show');
                $success_url = route('success');
                \Paystack\Paystack::init($paystack_secret_key);
                $payment = \Paystack\Transaction::initialize([
                    'email' => $email,
                    'amount' => (int) ($total_pay * 100),
                    'callback_url' => $success_url, 
                    'metadata' => [
                        'custom_fields' => [
                            ['display_name' => 'Author Name', 'variable_name' => 'author_name', 'value' => $authorName],
                            ['display_name' => 'Failure URL', 'variable_name' => 'fail_url', 'value' => $fail_url],
                        ]
                    ]
                ]);
                Session::put('paystack_authorization_url', $payment->authorization_url);
                Session::put('paystack_access_code', $payment->access_code);
                Session::put('paystack_reference', $payment->reference);
                Session::save();
                if ($payment->authorization_url) {
                    $script = "<script>window.location = '" . $payment->authorization_url . "';</script>";
                    echo $script;
                    exit;
                } else {
                    $script = "<script>window.location = '" . url('') . "';</script>";
                    echo $script;
                    exit;
                }
            } 
            else if ($cart['cart_order']['payment_method'] == 'flutterwave') {
                $currency = "USD";
                if (@$cart['cart_order']['currencyData']['code']) {
                    $currency = $cart['cart_order']['currencyData']['code'];
                }
                $flutterWave_secret_key = $cart['cart_order']['flutterWave_secret_key'];
                $flutterWave_public_key = $cart['cart_order']['flutterWave_public_key'];
                $flutterWave_isSandbox = $cart['cart_order']['flutterWave_isSandbox'];
                $flutterWave_encryption_key = $cart['cart_order']['flutterWave_encryption_key'];
                $authorName = $cart['cart_order']['order_json']['authorName'];
                $total_pay = $cart['cart_order']['total_pay'];
                $formatted_price =  $cart['cart_order']['currencyData']['symbol'].number_format($total_pay,$cart['cart_order']['currencyData']['decimal_degits']) ;
                $email = $cart['cart_order']['order_json']['authorEmail'];
                Session::put('flutterwave_pay', 1);
                Session::save();
                $token = uniqid();
                Session::put('flutterwave_pay_tx_ref', $token);
                Session::save();
                return view('subscription_plans.flutterwave', ['is_checkout' => 1, 'cart' => $cart, 'id' => $user->uuid, 'email' => $email, 'authorName' => $authorName, 'amount' => $total_pay, 'flutterWave_secret_key' => $flutterWave_secret_key, 'flutterWave_public_key' => $flutterWave_public_key, 'flutterWave_isSandbox' => $flutterWave_isSandbox, 'flutterWave_encryption_key' => $flutterWave_encryption_key, 'token' => $token, 'cart_order' => $cart['cart_order'], 'currency' => $currency, 'formatted_price' => $formatted_price]);
            }
             else if ($cart['cart_order']['payment_method'] == 'mercadopago') {
                $currency = "USD";
                if (@$cart['cart_order']['currencyData']['code']) {
                    $currency = $cart['cart_order']['currencyData']['code'];
                }
                $mercadopago_public_key = $cart['cart_order']['mercadopago_public_key'];
                $mercadopago_access_token = $cart['cart_order']['mercadopago_access_token'];
                $mercadopago_isSandbox = $cart['cart_order']['mercadopago_isSandbox'];
                $mercadopago_isEnabled = $cart['cart_order']['mercadopago_isEnabled'];
                $id = $cart['cart_order']['id'];
                $total_pay = $cart['cart_order']['total_pay'];
                $items['title'] = $id;
                $items['quantity'] = 1;
                $items['unit_price'] = floatval($total_pay);
                $fields[] = $items;
                $item['items'] = $fields;
                $item['back_urls']['failure'] = route('subscription-plan.show');
                $item['back_urls']['pending'] = route('notify');
                $item['back_urls']['success'] = route('success');
                $item['auto_return'] = 'all';
                Session::put('mercadopago_pay', 1);
                Session::save();
                $url = "https://api.mercadopago.com/checkout/preferences";
                $data = array('Accept: application/json', 'Authorization:Bearer ' . $mercadopago_access_token);
                $post_data = json_encode($item);
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization:Bearer " . $mercadopago_access_token));
                $response = curl_exec($ch);
                $mercadopago = json_decode($response);
                Session::put('mercadopago_preference_id', $mercadopago->id);
                Session::save();
                if ($mercadopago === null) {
                    die(curl_error($ch));
                }
                $authorName = $cart['cart_order']['order_json']['authorName'];
                $total_pay = $cart['cart_order']['total_pay'];
                if ($mercadopago_isSandbox == "true") {
                    $payment_url = $mercadopago->sandbox_init_point;
                } else {
                    $payment_url = $mercadopago->init_point;
                }
                echo "<script>location.href = '" . $payment_url . "';</script>";
                exit;
            } 
            else if ($cart['cart_order']['payment_method'] == 'stripe') {
                $stripeKey = $cart['cart_order']['stripeKey'];
                $stripeSecret = $cart['cart_order']['stripeSecret'];
                $authorName = $cart['cart_order']['order_json']['authorName'];
                $total_pay = $cart['cart_order']['total_pay'];
                $stripeSecret = $cart['cart_order']['stripeSecret'];
                $stripeKey = $cart['cart_order']['stripeKey'];
                $isStripeSandboxEnabled = $cart['cart_order']['isStripeSandboxEnabled'];
                $amount = 0;
                $formatted_price =  $cart['cart_order']['currencyData']['symbol'].number_format($total_pay,$cart['cart_order']['currencyData']['decimal_degits']) ;
                return view('subscription_plans.stripe', ['is_checkout' => 1, 'cart' => $cart, 'id' => $user->uuid, 'email' => $email, 'authorName' => $authorName, 'amount' => $total_pay, 'stripeSecret' => $stripeSecret, 'stripeKey' => $stripeKey, 'cart_order' => $cart['cart_order'], 'formatted_price' => $formatted_price]);
            }
            else if ($cart['cart_order']['payment_method'] == 'paypal') {
                $paypalKey = $cart['cart_order']['paypalKey'];
                $paypalSecret = $cart['cart_order']['paypalSecret'];
                $authorName = $cart['cart_order']['order_json']['authorName'];
                $total_pay = $cart['cart_order']['total_pay'];
                $paypalSecret = $cart['cart_order']['paypalSecret'];
                $paypalKey = $cart['cart_order']['paypalKey'];
                $ispaypalSandboxEnabled = $cart['cart_order']['ispaypalSandboxEnabled'];
                $authorName = $cart['cart_order']['order_json']['authorName'];
                $total_pay = $cart['cart_order']['total_pay'];
                $amount = 0;
                $formatted_price =  $cart['cart_order']['currencyData']['symbol'].number_format($total_pay,$cart['cart_order']['currencyData']['decimal_degits']) ;
                return view('subscription_plans.paypal', ['is_checkout' => 1, 'cart' => $cart, 'id' => $user->uuid, 'email' => $email, 'authorName' => $authorName, 'amount' => $total_pay, 'paypalSecret' => $paypalSecret, 'paypalKey' => $paypalKey, 'cart_order' => $cart['cart_order'], 'formatted_price' => $formatted_price]);
            }
        } else {
            return redirect()->route('checkout');
        }
    }
    public function processStripePayment(Request $request)
    {
        $email = Auth::user()->email;
        $input = $request->all();
        $cart = Session::get('cart', []);
        if (@$cart['cart_order'] && $input['token_id']) {
            if ($cart['cart_order']['stripeKey'] && $cart['cart_order']['stripeSecret']) {
                $currency = "usd";
                if (@$cart['cart_order']['currency']) {
                    $currency = $cart['cart_order']['currency'];
                }
                $stripeSecret = $cart['cart_order']['stripeSecret'];
                $stripe = new \Stripe\StripeClient($stripeSecret);
                $description = env('APP_NAME', 'Foodie');
                try {
                    $charge = $stripe->paymentIntents->create([
                        'amount' => ($cart['cart_order']['total_pay'] * 1000),
                        'currency' => $currency,
                        'description' => $description,
                    ]);
                    $cart['payment_status'] = true;
                    $cart['transaction_id'] = $charge->id ?? null;
                    $cart['payment_date'] = date('Y-m-d H:i:s');
                    Session::put('cart', $cart);
                    Session::put('success', 'Payment successful');
                    Session::save();
                    $res = array('status' => true, 'data' => $charge, 'message' => 'success');
                    echo json_encode($res);
                    exit;
                } catch (Exception $e) {
                    $cart['payment_status'] = false;
                    Session::put('cart', $cart);
                    Session::put('error', $e->getMessage());
                    Session::save();
                    $res = array('status' => false, 'message' => $e->getMessage());
                    echo json_encode($res);
                    exit;
                }
            }
        }
    }
    public function success()
    {
        $cart = Session::get('cart', []);
        $order_json = array();
        $email = Auth::user()->email;
        $user = VendorUsers::where('email', $email)->first();
        if (isset($_GET['xendit_token'])) {
            $xendit_payment = Session::get('xendit_payment_token');
            if ($xendit_payment == $_GET['xendit_token']) {
                $cart['payment_status'] = true;
                $cart['transaction_id'] = $_GET['xendit_token'] ?? null;
                $cart['payment_date'] = date('Y-m-d H:i:s');
                Session::put('cart', $cart);
                Session::put('success', 'Payment successful');
                Session::save();
            }
        }
        if (isset($_GET['midtrans_token'])) {
            $midtrans_payment = Session::get('midtrans_payment_token');
            if ($midtrans_payment === $_GET['midtrans_token']) {
                $cart['payment_status'] = true;
                $cart['transaction_id'] = $_GET['midtrans_token'] ?? null;
                $cart['payment_date'] = date('Y-m-d H:i:s');
                Session::put('cart', $cart);
                Session::put('success', 'Payment successful');
                Session::save();
            }
        }
        if (isset($_GET['orangepay_token'])) {
            $orangepay_token = Session::get('orangepay_payment_token');
            if ($orangepay_token === $_GET['orangepay_token']) {
                $orangepay_access_token = Session::get('orangepay_access_token');
                $payToken = session('orangepay_payment_check_token');
                $orangepay_isSandbox = session('orangepay_isSandbox');
                $fail_url = route('pay');
                if (! $payToken && ! $orangepay_access_token) {
                    return response()->json(['error' => 'Payment token not found in session']);
                }
                $url = ($orangepay_isSandbox == false) ? 'https://api.orange.com/orange-money-webpay/cm/v1/transactionstatus' : 'https://api.orange.com/orange-money-webpay/dev/v1/transactionstatus';
                try {
                    $client = new Client();
                    $payload = [
                        'pay_token' => $payToken
                    ];
                    $response = $client->post($url, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $orangepay_access_token,
                            'Content-Type' => 'application/json',
                        ],
                        'body' => json_encode($payload),
                    ]);
                    $responseBody = json_decode($response->getBody(), true);
                    if (isset($responseBody['status']) && $responseBody['status'] == 'SUCCESS') {
                        $cart['payment_status'] = true;
                        $cart['transaction_id'] = $payToken ?? $_GET['orangepay_token'] ?? null;
                        $cart['payment_date'] = date('Y-m-d H:i:s');
                        Session::put('cart', $cart);
                        Session::put('success', 'Payment successful');
                        Session::save();
                    } else {
                        return redirect($fail_url);
                    }
                } catch (\Exception $e) {
                    return response()->json(['error' => $e->getMessage()]);
                }
            }
        }
        if (isset($_GET['token'])) {
            $payfast_payment = Session::get('payfast_payment_token');
            if ($payfast_payment == $_GET['token']) {
                $cart['payment_status'] = true;
                $cart['transaction_id'] = $_GET['token'] ?? null;
                $cart['payment_date'] = date('Y-m-d H:i:s');
                Session::put('cart', $cart);
                Session::put('success', 'Payment successful');
                Session::save();
            }
        }
        if (isset($_GET['reference'])) {
            $paystack_reference = Session::get('paystack_reference');
            $paystack_access_code = Session::get('paystack_access_code');
            if ($paystack_reference == $_GET['reference']) {
                $cart['payment_status'] = true;
                $cart['transaction_id'] = $_GET['reference'] ?? null;
                $cart['payment_date'] = date('Y-m-d H:i:s');
                Session::put('cart', $cart);
                Session::put('success', 'Payment successful');
                Session::save();
            }
        }
        if (isset($_GET['transaction_id']) && isset($_GET['tx_ref']) && isset($_GET['status'])) {
            $flutterwave_pay_tx_ref = Session::get('flutterwave_pay_tx_ref');
            if ($_GET['status'] == 'successful' && $flutterwave_pay_tx_ref == $_GET['tx_ref']) {
                $cart['payment_status'] = true;
                $cart['transaction_id'] = $_GET['transaction_id'] ?? null;
                $cart['payment_date'] = date('Y-m-d H:i:s');
                Session::put('cart', $cart);
                Session::put('success', 'Payment successful');
                Session::save();
            } else {
                return redirect()->route('checkout');
            }
        }
        if (isset($_GET['preference_id']) && isset($_GET['payment_id']) && isset($_GET['status'])) {
            $mercadopago_preference_id = Session::get('mercadopago_preference_id');
            if ($_GET['status'] == 'approved' && $mercadopago_preference_id == $_GET['preference_id']) {
                $cart['payment_status'] = true;
                $cart['transaction_id'] = $_GET['payment_id'] ?? null;
                $cart['payment_date'] = date('Y-m-d H:i:s');
                Session::put('cart', $cart);
                Session::put('success', 'Payment successful');
                Session::save();
            } else {
                return redirect()->route('checkout');
            }
        }
        $payment_method = (@$cart['cart_order']['payment_method']) ? $cart['cart_order']['payment_method'] : '';
        
        // Get user's vendor ID if exists
        $vendorId = null;
        if (isset($cart['cart_order']['order_json'])) {
            $orderJson = $cart['cart_order']['order_json'];
            $planId = $orderJson['planId'] ?? null;
            
            // Get user's vendor ID
            $authUser = Auth::user();
            $vendorId = $authUser->vendorID ?? null;
            
            // Get subscription plan data from MySQL
            $subscriptionPlan = null;
            if ($planId) {
                $subscriptionPlan = DB::table('subscription_plans')->where('id', $planId)->first();
            }
        } else {
            $subscriptionPlan = null;
            $planId = null;
        }
        
        return view('subscription_plans.success', [
            'cart' => $cart, 
            'id' => $user->uuid, 
            'email' => $email, 
            'payment_method' => $payment_method,
            'vendorId' => $vendorId,
            'subscriptionPlan' => $subscriptionPlan,
            'planId' => $planId ?? null
        ]);
    }
    public function razorpaypayment(Request $request)
    {
        $input = $request->all();
        $email = Auth::user()->email;
        $user = VendorUsers::where('email', $email)->first();
        $cart = Session::get('cart', []);
        $api_secret = $cart['cart_order']['razorpaySecret'];
        $api_key = $cart['cart_order']['razorpayKey'];
        $api = new Api($api_key, $api_secret);
        $payment = $api->payment->fetch($input['razorpay_payment_id']);
        if (count($input) && ! empty($input['razorpay_payment_id'])) {
            try {
                $response = $api->payment->fetch($input['razorpay_payment_id'])->capture(array('amount' => $payment['amount']));
                $cart['payment_status'] = true;
                $cart['transaction_id'] = $input['razorpay_payment_id'] ?? null;
                $cart['payment_date'] = date('Y-m-d H:i:s');
                Session::put('cart', $cart);
                Session::save();
            } catch (Exception $e) {
                return $e->getMessage();
                Session::put('error', $e->getMessage());
                return redirect()->back();
            }
        }
        Session::put('success', 'Payment successful');
        return redirect()->route('success');
    }
    function generateSignature($data)
    {
        $getString = http_build_query($data, '', '&', PHP_QUERY_RFC3986);
        return md5($getString);
    }
    public function processPaypalPayment(Request $request)
    {
        $email = Auth::user()->email;
        $input = $request->all();
       $cart = Session::get('cart', []);
        if (@$cart['cart_order']) {
            if ($cart['cart_order']) {
                $cart['payment_status'] = true;
                // PayPal transaction ID would be in request if available
                $cart['transaction_id'] = $request->input('transaction_id') ?? $request->input('paymentId') ?? null;
                $cart['payment_date'] = date('Y-m-d H:i:s');
                Session::put('cart', $cart);
                Session::put('success', 'Payment successful');
                Session::save();
                $res = array('status' => true, 'data' => array(), 'message' => 'success');
                echo json_encode($res);
                exit;
            }
        }
        $cart['payment_status'] = false;
        Session::put('cart', $cart);
        Session::put('error', 'Faild Payment');
        Session::save();
        $res = array('status' => false, 'message' => 'Faild Payment');
        echo json_encode($res);
        exit;
    }
    private function getAccessToken($clientId, $clientSecret)
    {
        $authUrl = 'https://api.orange.com/oauth/v3/token';
        $client = new Client();
        try {
            $response = $client->post($authUrl, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'form_params' => [
                    'grant_type' => 'client_credentials',
                ],
            ]);
            $body = json_decode($response->getBody(), true);
            return $body['access_token'] ?? null;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Finalize subscription payment - MySQL based
     */
    public function finalizeSubscription(Request $request)
    {
        try {
            // Log for testing - remove after verification
            \Log::info('=== FINALIZE SUBSCRIPTION CALLED (MySQL) ===', [
                'user_id' => $request->input('user_id'),
                'plan_id' => $request->input('plan_id'),
                'transaction_id' => $request->input('transaction_id'),
                'payment_method' => $request->input('payment_method'),
                'timestamp' => now()->toDateTimeString()
            ]);
            
            $user = Auth::user();
            $userId = $request->input('user_id'); // Firebase ID / UUID
            $planId = $request->input('plan_id');
            $planData = $request->input('plan_data'); // JSON string or array
            $expiryDay = $request->input('expiry_date');
            $transactionId = $request->input('transaction_id');
            $paymentDate = $request->input('payment_date');
            $paymentMethod = $request->input('payment_method', '');
            $subscriptionOrderId = $request->input('subscription_order_id');
            $vendorId = $request->input('vendor_id');
            $walletAmount = $request->input('wallet_amount');
            $walletTransactionId = $request->input('wallet_transaction_id');

            DB::beginTransaction();

            // Update user subscription
            $userUpdateData = [
                'subscriptionPlanId' => $planId,
                'subscription_plan' => is_string($planData) ? json_decode($planData, true) : $planData,
                'subscriptionExpiryDate' => $expiryDay,
            ];

            // Find user by firebase_id or uuid
            $userToUpdate = User::where('firebase_id', $userId)->first();
            
            // If not found, try finding via vendor_users table
            if (!$userToUpdate) {
                $vendorUser = VendorUsers::where('uuid', $userId)->first();
                if ($vendorUser) {
                    $userToUpdate = User::find($vendorUser->user_id);
                }
            }
            
            // Fallback: find by email from authenticated user
            if (!$userToUpdate && Auth::check()) {
                $userToUpdate = Auth::user();
            }

            if ($userToUpdate) {
                $userToUpdate->update($userUpdateData);
                
                // Update wallet if provided
                if ($walletAmount !== null) {
                    $userToUpdate->update(['wallet_amount' => $walletAmount]);
                }
            }

            // Update vendor if vendorId is provided
            if ($vendorId) {
                $vendor = Vendor::find($vendorId);
                if ($vendor) {
                    $vendorUpdate = [
                        'subscriptionPlanId' => $planId,
                        'subscription_plan' => is_string($planData) ? json_decode($planData, true) : $planData,
                        'subscriptionExpiryDate' => $expiryDay,
                        'subscriptionTotalOrders' => isset($planData['orderLimit']) ? $planData['orderLimit'] : (is_string($planData) ? json_decode($planData, true)['orderLimit'] ?? null : null),
                    ];

                    if ($transactionId) {
                        $vendorUpdate['subscriptionTransactionId'] = $transactionId;
                    }
                    if ($paymentDate) {
                        $vendorUpdate['subscriptionPaymentDate'] = $paymentDate;
                    }

                    $vendor->update($vendorUpdate);
                }
            }

            // Create subscription history record
            $subscriptionHistoryId = $subscriptionOrderId ?: uniqid('sub_');
            
            // Build insert data - check if new columns exist (migration might not be run yet)
            $historyData = [
                'id' => $subscriptionHistoryId,
                'user_id' => $userId,
                'expiry_date' => $expiryDay,
                'createdAt' => now()->toDateTimeString(),
                'subscription_plan' => is_string($planData) ? $planData : json_encode($planData),
                'payment_type' => $paymentMethod,
            ];
            
            // Extract plan_id from planData if available
            $planIdFromData = null;
            if (is_string($planData)) {
                $decodedPlan = json_decode($planData, true);
                $planIdFromData = $decodedPlan['id'] ?? null;
            } elseif (is_array($planData)) {
                $planIdFromData = $planData['id'] ?? null;
            }
            
            // Add new columns only if they exist (safe for backward compatibility)
            try {
                $columns = DB::select("SHOW COLUMNS FROM subscription_history");
                $columnNames = array_column($columns, 'Field');
                
                // Store plan_id separately for easier querying
                if (in_array('plan_id', $columnNames) && $planIdFromData) {
                    $historyData['plan_id'] = $planIdFromData;
                }
                
                if (in_array('vendor_id', $columnNames) && $vendorId) {
                    $historyData['vendor_id'] = $vendorId;
                }
                if (in_array('transaction_id', $columnNames)) {
                    $historyData['transaction_id'] = $transactionId;
                }
                if (in_array('payment_date', $columnNames)) {
                    $historyData['payment_date'] = $paymentDate ?: ($paymentMethod !== 'free' ? now()->toDateTimeString() : null);
                }
                if (in_array('bill_status', $columnNames)) {
                    $historyData['bill_status'] = $paymentMethod === 'free' ? 'paid' : ($transactionId ? 'paid' : 'unpaid');
                }
                // Store zone from vendor if available
                if (in_array('zone', $columnNames) && $vendorId) {
                    $vendor = Vendor::find($vendorId);
                    if ($vendor) {
                        $historyData['zone'] = $vendor->zoneId ?? $vendor->zone_slug ?? null;
                    }
                }
                // Set updated_at on insert
                if (in_array('updated_at', $columnNames)) {
                    $historyData['updated_at'] = now()->toDateTimeString();
                }
            } catch (\Exception $e) {
                // If column check fails, just insert without new columns (backward compatible)
                \Log::warning('Could not check subscription_history columns: ' . $e->getMessage());
            }
            
            DB::table('subscription_history')->insert($historyData);

            // Create wallet transaction if wallet payment
            if ($walletTransactionId && $walletAmount !== null) {
                WalletTransaction::create([
                    'id' => $walletTransactionId,
                    'user_id' => $userId,
                    'amount' => abs($request->input('wallet_debit_amount', 0)),
                    'date' => now()->toDateTimeString(),
                    'isTopUp' => false,
                    'subscription_id' => $subscriptionHistoryId,
                    'payment_method' => 'Wallet',
                    'payment_status' => 'success',
                    'note' => 'subscription amount debited',
                ]);
            }

            DB::commit();

            // Log success for testing
            \Log::info('=== SUBSCRIPTION FINALIZED SUCCESSFULLY (MySQL) ===', [
                'subscription_id' => $subscriptionHistoryId,
                'vendor_id' => $vendorId,
                'transaction_id' => $transactionId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription finalized successfully',
                'subscription_id' => $subscriptionHistoryId,
                'source' => 'mysql', // For testing - confirms MySQL was used
                'vendor_updated' => $vendorId ? true : false,
                'transaction_id_stored' => $transactionId ? true : false
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to finalize subscription: ' . $e->getMessage()
            ], 500);
        }
    }
}