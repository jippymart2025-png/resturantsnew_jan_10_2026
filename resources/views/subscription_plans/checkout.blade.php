<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title id="app_name"><?php echo @$_COOKIE['meta_title']; ?></title>
    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    <!-- Styles -->
    <link href="{{ asset('assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/select2/dist/css/select2.min.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,100..900;1,100..900&display=swap"rel="stylesheet">
    <!-- @yield('style') -->
</head>

<body>

    <div class="page-wrapper pl-0 p-5">

        <div class="subscription-checkout">

            <div class="container position-relative">

                <div id="data-table_processing" class="page-overlay" style="display:none;">
                    <div class="overlay-text">
                        <img src="{{asset('images/spinner.gif')}}">
                    </div>
                </div>

                <div class="subscription-section">
                    <div class="subscription-section-inner">
                        <div class="card border">
                            <div class="card-header border-0">
                                <div class="d-flex align-items-center">
                                    <a href="{{ route('subscription-plan.show') }}">
                                    <span class="mdi mdi-arrow-left mr-3 text-dark-2"></span>
                                    </a>
                                    <h6 class="text-dark-2 h6 mb-0">{{ trans('lang.shift_to_plan') }}</h6>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row" id="plan-details"></div>
                                <div class="pay-method-section pt-4">
                                    <h6 class="text-dark-2 h6 mb-3 pb-3">{{ trans('lang.pay_via_online') }}</h6>
                                    <div class="row">
                                        <div class="col-md-4 d-none" id="wallet_box">
                                            <div class="pay-method-box d-flex align-items-center">
                                                <div class="pay-method-icon">
                                                    <img src="{{ asset('images/wallet_icon_ic.png') }}">
                                                </div>
                                                <div class="form-check">
                                                    <h6 class="text-dark-2 h6 mb-0">{{ trans('lang.my_wallet') }} <b class="ml-2">(<span id="wallet_amount"></span>)</b></h6>
                                                    <input type="radio" id="wallet" name="payment_method" value="wallet" checked=""> 
                                                    <label class="control-label mb-0" for="wallet"></label>
                                                </div>
                                                <div class="input-box">
                                                    <input type="hidden" id="user_wallet_amount">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 d-none" id="paypal_box">
                                            <div class="pay-method-box d-flex align-items-center">
                                                <div class="pay-method-icon">
                                                    <img src="{{ asset('images/paypal_ic.png') }}">
                                                </div>
                                                <div class="form-check">
                                                    <input type="radio" id="paypal" name="payment_method"
                                                        value="paypal">
                                                    {{ trans('lang.pay_pal') }} <label class="control-label mb-0" for="paypal"></label>
                                                </div>
                                                <div class="input-box">
                                                    <input type="hidden" id="ispaypalSandboxEnabled">
                                                    <input type="hidden" id="paypalKey">
                                                    <input type="hidden" id="paypalSecret">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 d-none" id="stripe_box">
                                            <div class="pay-method-box d-flex align-items-center">
                                                <div class="pay-method-icon">
                                                    <img src="{{ asset('images/stripe_ic.png') }}">
                                                </div>
                                                <div class="form-check">
                                                    <input type="radio" id="stripe" name="payment_method"
                                                        value="stripe">
                                                    {{ trans('lang.stripe') }} <label class="control-label mb-0" for="stripe"></label>
                                                </div>
                                                <div class="input-box">
                                                    <input type="hidden" id="isStripeSandboxEnabled">
                                                    <input type="hidden" id="stripeKey">
                                                    <input type="hidden" id="stripeSecret">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 d-none" id="paystack_box">
                                            <div class="pay-method-box d-flex align-items-center">
                                                <div class="pay-method-icon">
                                                    <img src="{{ asset('images/paystack_ic.png') }}">
                                                </div>
                                                <div class="form-check">
                                                    <input type="radio" id="paystack" name="payment_method"
                                                        value="paystack">
                                                    {{ trans('lang.pay_stack') }} <label class="control-label mb-0" for="paystack"></label>
                                                </div>
                                                <div class="input-box">
                                                    <input type="hidden" id="paystack_isEnabled">
                                                    <input type="hidden" id="paystack_isSandbox">
                                                    <input type="hidden" id="paystack_public_key">
                                                    <input type="hidden" id="paystack_secret_key">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 d-none" id="razorpay_box">
                                            <div class="pay-method-box d-flex align-items-center">
                                                <div class="pay-method-icon">
                                                    <img src="{{ asset('images/razorpay_ic.png') }}">
                                                </div>
                                                <div class="form-check">
                                                    <input type="radio" id="razorpay" name="payment_method"
                                                        value="razorpay">
                                                    {{ trans('lang.razorpay') }} <label class="control-label mb-0" for="razorpay"></label>
                                                </div>
                                                <div class="input-box">
                                                    <input type="hidden" id="isEnabled">
                                                    <input type="hidden" id="isSandboxEnabled">
                                                    <input type="hidden" id="razorpayKey">
                                                    <input type="hidden" id="razorpaySecret">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 d-none" id="payfast_box">
                                            <div class="pay-method-box d-flex align-items-center">
                                                <div class="pay-method-icon">
                                                    <img src="{{ asset('images/payfast_ic.png') }}">
                                                </div>
                                                <div class="form-check">
                                                    <input type="radio" id="payfast" name="payment_method"
                                                        value="payfast">
                                                    {{ trans('lang.pay_fast') }} <label class="control-label mb-0" for="payfast"></label>
                                                </div>
                                                <div class="input-box">
                                                    <input type="hidden" id="payfast_isEnabled">
                                                    <input type="hidden" id="payfast_isSandbox">
                                                    <input type="hidden" id="payfast_merchant_key">
                                                    <input type="hidden" id="payfast_merchant_id">
                                                    <input type="hidden" id="payfast_notify_url">
                                                    <input type="hidden" id="payfast_return_url">
                                                    <input type="hidden" id="payfast_cancel_url">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 d-none" id="mercadopago_box">
                                            <div class="pay-method-box d-flex align-items-center">
                                                <div class="pay-method-icon">
                                                    <img src="{{ asset('images/mercado_pago_ic.png') }}">
                                                </div>
                                                <div class="form-check">
                                                    <input type="radio" id="mercadopago" name="payment_method"
                                                        value="mercadopago">
                                                    {{ trans('lang.mercadopago') }} <label class="control-label mb-0"
                                                        for="mercadopago"></label>
                                                </div>
                                                <div class="input-box">
                                                    <input type="hidden" id="mercadopago_isEnabled">
                                                    <input type="hidden" id="mercadopago_isSandbox">
                                                    <input type="hidden" id="mercadopago_public_key">
                                                    <input type="hidden" id="mercadopago_access_token">
                                                    <input type="hidden" id="title">
                                                    <input type="hidden" id="quantity">
                                                    <input type="hidden" id="unit_price">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 d-none" id="flutterWave_box">
                                            <div class="pay-method-box d-flex align-items-center">
                                                <div class="pay-method-icon">
                                                    <img src="{{ asset('images/flutterwave_ic.png') }}">
                                                </div>
                                                <div class="form-check">
                                                    <input type="radio" id="flutterwave" name="payment_method"
                                                        value="flutterwave">
                                                    {{ trans('lang.flutter_wave') }} <label class="control-label mb-0"
                                                        for="flutterwave"></label>
                                                </div>
                                                <div class="input-box">
                                                    <input type="hidden" id="flutterWave_isEnabled">
                                                    <input type="hidden" id="flutterWave_isSandbox">
                                                    <input type="hidden" id="flutterWave_encryption_key">
                                                    <input type="hidden" id="flutterWave_public_key">
                                                    <input type="hidden" id="flutterWave_secret_key">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 d-none" id="paytm_box">
                                            <div class="pay-method-box d-flex align-items-center">
                                                <div class="pay-method-icon">
                                                    <img src="{{ asset('images/paytm_ic.png') }}">
                                                </div>
                                                <div class="form-check">
                                                    <input type="radio" id="paytm" name="payment_method"
                                                        value="paytm">
                                                    {{ trans('lang.paytm') }} <label class="control-label mb-0" for="paytm"></label>
                                                </div>
                                                <div class="input-box">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 d-none" id="midtrans_box">
                                            <div class="pay-method-box d-flex align-items-center">
                                                <div class="pay-method-icon">
                                                    <img src="{{ asset('images/mindtrans_ic.png') }}">
                                                </div>
                                                <div class="form-check">
                                                    <input type="radio" id="midtrans" name="payment_method"
                                                        value="midtrans">
                                                    {{ trans('lang.midtrans') }} <label class="control-label mb-0" for="midtrans"></label>
                                                </div>
                                                <div class="input-box">
                                                    <input type="hidden" id="midtrans_enable">
                                                    <input type="hidden" id="midtrans_serverKey">
                                                    <input type="hidden" id="midtrans_image">
                                                    <input type="hidden" id="midtrans_isSandbox">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 d-none" id="orangepay_box">
                                            <div class="pay-method-box d-flex align-items-center">
                                                <div class="pay-method-icon">
                                                    <img src="{{ asset('images/orangemoney_ic.png') }}">
                                                </div>
                                                <div class="form-check">
                                                    <input type="radio" id="orangepay" name="payment_method"
                                                        value="orangepay">
                                                    {{ trans('lang.orangepay') }} <label class="control-label mb-0"
                                                        for="orangepay"></label>
                                                </div>
                                                <div class="input-box">
                                                    <input type="hidden" id="orangepay_auth">
                                                    <input type="hidden" id="orangepay_clientId">
                                                    <input type="hidden" id="orangepay_clientSecret">
                                                    <input type="hidden" id="orangepay_image">
                                                    <input type="hidden" id="orangepay_isSandbox">
                                                    <input type="hidden" id="orangepay_merchantKey">
                                                    <input type="hidden" id="orangepay_cancelUrl">
                                                    <input type="hidden" id="orangepay_notifyUrl">
                                                    <input type="hidden" id="orangepay_returnUrl">
                                                    <input type="hidden" id="orangepay_enable">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 d-none" id="xendit_box">
                                            <div class="pay-method-box d-flex align-items-center">
                                                <div class="pay-method-icon">
                                                    <img src="{{ asset('images/xendit_ic.png') }}">
                                                </div>
                                                <div class="form-check">
                                                    <input type="radio" id="xendit" name="payment_method"
                                                        value="xendit">
                                                    {{ trans('lang.xendit') }} <label class="control-label mb-0" for="xendit"></label>
                                                </div>
                                                <div class="input-box">
                                                    <input type="hidden" id="xendit_enable">
                                                    <input type="hidden" id="xendit_apiKey">
                                                    <input type="hidden" id="xendit_image">
                                                    <input type="hidden" id="xendit_isSandbox">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="card-footer border-top">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h6 class="text-dark-2 h6 mb-0 font-weight-semibold">{{ trans('lang.pay') }} <span id="subtotal"></span></h6>
                                    <div class="edit-form-group btm-btn text-right">
                                        <div class="card-block-active-plan d-none">
                                            <a href="{{ route('home') }}" class="btn btn-default rounded-full mr-2">{{ trans('lang.cancel_plan') }}</a>
                                            <button type="button" class="btn-primary btn rounded-full" onclick="finalCheckout()">{{ trans('lang.proceed_to_pay') }}</button>
                                        </div>
                                        <div class="card-block-new-plan d-none">
                                            <a href="{{ route('subscription-plan.show') }}" class="btn btn-default rounded-full mr-2">{{ trans('lang.cancel') }}</a>
                                            <button type="button" class="btn-primary btn rounded-full" onclick="finalCheckout()">{{ trans('lang.choose_plan') }}</button>
                                        </div>
                                        <div class="input-box">
                                            <input type="hidden" id="sub_total">
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="{{ asset('assets/plugins/select2/dist/js/select2.min.js') }}"></script>
    {{-- Keep Firebase Storage only for images --}}
    <script src="https://www.gstatic.com/firebasejs/7.2.0/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/7.2.0/firebase-storage.js"></script>
    <script src="{{ asset('js/crypto-js.js') }}"></script>
    <script src="{{ asset('js/jquery.cookie.js') }}"></script>
    <script src="{{ asset('js/jquery.validate.js') }}"></script>

    <script type="text/javascript">
        jQuery('#data-table_processing').show();

        // Data from controller (MySQL) - NO Firebase Firestore
        var currentCurrency = "{{ $currencyData['symbol'] }}";
        var currencyAtRight = {{ $currencyData['symbolAtRight'] ? 'true' : 'false' }};
        var decimal_degits = {{ $currencyData['decimal_degits'] }};
        var currencyData = @json($currencyData);
        var userId = "{{ $userId }}";
        var planId = "{{ $planId }}";
        var planData = @json($planData);
        var vendorId = "{{ $vendorId ?? '' }}";
        var wallet_amount = {{ $walletAmount ?? 0 }};
        var commisionModel = {{ $commisionModel ? 'true' : 'false' }};
        var AdminCommission = "{{ $adminCommission }}";
        var commissionType = "{{ $commissionType }}";
        var activeSubscriptionData = @json($activeSubscriptionData);

        // Format admin commission
        if (commisionModel && commissionType == "Percent") {
            AdminCommission = AdminCommission + '%';
        } else if (commisionModel) {
            if (currencyAtRight) {
                AdminCommission = parseFloat(AdminCommission).toFixed(decimal_degits) + currentCurrency;
            } else {
                AdminCommission = currentCurrency + parseFloat(AdminCommission).toFixed(decimal_degits);
            }
        }

        // Calculate expiry date
        var expiryDay = null;
        if (planData && planData.expiryDay != '-1') {
            var currentDate = new Date();
            currentDate.setDate(currentDate.getDate() + parseInt(planData.expiryDay));
            expiryDay = currentDate.toISOString();
        }

        // Display plan price
        if (planData && planData.price) {
            if (currencyAtRight) {
                var html = parseFloat(planData.price).toFixed(decimal_degits) + currentCurrency;
            } else {
                var html = currentCurrency + parseFloat(planData.price).toFixed(decimal_degits);
            }
            $('#subtotal').html(html);
            $('#sub_total').val(planData.price);
        }

        // Show plan details
        if (planData) {
            showPlanDetail(activeSubscriptionData, planData);
        }

        async function showPlanDetail(activePlan='', choosedPlan) {
            
            let html = '';
            
            let choosedPlan_price = currencyAtRight ? parseFloat(choosedPlan.price).toFixed(decimal_degits) + currentCurrency
            : currentCurrency + parseFloat(choosedPlan.price).toFixed(decimal_degits);

            if(activePlan){

                $(".card-block-active-plan").removeClass('d-none');

                let activePlan_price = currencyAtRight ? parseFloat(activePlan.price).toFixed(decimal_degits) + currentCurrency
                : currentCurrency + parseFloat(activePlan.price).toFixed(decimal_degits);

                html += ` 
                <div class="col-md-8">
                    <div class="subscription-card-left"> 
                        <div class="row align-items-center">
                            <div class="col-md-5">
                                <div class="subscription-card text-center">
                                    <div class="d-flex align-items-center pb-3 justify-content-center">
                                        <span class="pricing-card-icon mr-4"><img src="${activePlan.image}"></span>
                                        <h2 class="text-dark-2 mb-0 font-weight-semibold">${activePlan.id == "J0RwvxCWhZzQQD7Kc2Ll" ? "{{trans('lang.commission')}}" : activePlan.name}</h2>
                                    </div>
                                    <h3 class="text-dark-2">${activePlan.id == "J0RwvxCWhZzQQD7Kc2Ll" ? AdminCommission + " {{trans('lang.plan')}}" : activePlan_price}</h3>
                                    <p>${activePlan.id == "J0RwvxCWhZzQQD7Kc2Ll" ? "{{ trans('lang.free') }}" : activePlan.expiryDay==-1? "{{ trans('lang.unlimited') }}": activePlan.expiryDay + "{{trans('lang.days')}}" }   </p>
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                                <img src="{{asset('images/left-right-arrow.png')}}">
                            </div>
                            <div class="col-md-5">
                                <div class="subscription-card text-center">
                                    <div class="d-flex align-items-center pb-3 justify-content-center">
                                        <span class="pricing-card-icon mr-4"><img src="${choosedPlan.image}"></span>
                                        <h2 class="text-dark-2 mb-0 font-weight-semibold">${choosedPlan.name}
                                        </h2>
                                    </div>
                                    <h3 class="text-dark-2">${choosedPlan_price}</h3>
                                    <p>${choosedPlan.expiryDay==-1 ? "{{ trans('lang.unlimited') }}" : choosedPlan.expiryDay} {{trans('lang.days')}}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="subscription-card-right">
                        <div
                            class="d-flex justify-content-between align-items-center py-3 px-3 text-dark-2">
                            <span class="font-weight-medium">Validity</span>
                            <span class="font-weight-semibold">${choosedPlan.expiryDay==-1 ? "{{ trans('lang.unlimited') }}" : choosedPlan.expiryDay} {{trans('lang.days')}}</span>
                        </div>
                        <div
                            class="d-flex justify-content-between align-items-center py-3 px-3 text-dark-2">
                            <span class="font-weight-medium">Price</span>
                            <span class="font-weight-semibold">${choosedPlan_price}</span>
                        </div>
                        <div
                            class="d-flex justify-content-between align-items-center py-3 px-3 text-dark-2">
                            <span class="font-weight-medium">{{trans("lang.bill_status")}}</span>
                            <span class="font-weight-semibold">{{trans("lang.migrate_to_new_plan")}}</span>
                        </div>
                    </div>
                </div>`;

            }else{

                $(".card-block-new-plan").removeClass('d-none');

                html += ` 
                <div class="col-md-8">
                    <div class="subscription-card-left"> 
                        <div class="row align-items-center">
                            <div class="col-md-12">
                                <div class="subscription-card text-center">
                                    <div class="d-flex align-items-center pb-3 justify-content-center">
                                        <span class="pricing-card-icon mr-4"><img src="${choosedPlan.image}"></span>
                                        <h2 class="text-dark-2 mb-0 font-weight-semibold">${choosedPlan.name}
                                        </h2>
                                    </div>
                                    <h3 class="text-dark-2">${choosedPlan_price}</h3>
                                    <p>${choosedPlan.expiryDay==-1 ? "{{ trans('lang.unlimited') }}" : choosedPlan.expiryDay} {{trans('lang.days')}}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="subscription-card-right">
                        <div
                            class="d-flex justify-content-between align-items-center py-3 px-3 text-dark-2">
                            <span class="font-weight-medium">Validity</span>
                            <span class="font-weight-semibold">${choosedPlan.expiryDay==-1 ? "{{ trans('lang.unlimited') }}" : choosedPlan.expiryDay} {{trans('lang.days')}}</span>
                        </div>
                        <div
                            class="d-flex justify-content-between align-items-center py-3 px-3 text-dark-2">
                            <span class="font-weight-medium">Price</span>
                            <span class="font-weight-semibold">${choosedPlan_price}</span>
                        </div>
                        <div
                            class="d-flex justify-content-between align-items-center py-3 px-3 text-dark-2">
                            <span class="font-weight-medium">{{trans("lang.bill_status")}}</span>
                            <span class="font-weight-semibold">{{trans("lang.migrate_to_new_plan")}}</span>
                        </div>
                    </div>
                </div>`;
            }
            $("#plan-details").html(html);
            jQuery('#data-table_processing').hide();
        }

        // Payment gateway settings from MySQL (passed from controller)
        var razorpaySettings = @json($razorpaySettings ?? []);
        var stripeSettings = @json($stripeSettings ?? []);
        var paypalSettings = @json($paypalSettings ?? []);
        var walletSettings = @json($walletSettings ?? []);
        var payFastSettings = @json($payFastSettings ?? []);
        var payStackSettings = @json($payStackSettings ?? []);
        var flutterWaveSettings = @json($flutterWaveSettings ?? []);
        var MercadoPagoSettings = @json($MercadoPagoSettings ?? []);
        var XenditSettings = @json($XenditSettings ?? []);
        var Midtrans_settings = @json($Midtrans_settings ?? []);
        var OrangePaySettings = @json($OrangePaySettings ?? []);

        var authorName = '';
        var authorEmail = '';

        $(document).ready(function() {
            var today = new Date().toISOString().slice(0, 16);
            getUserDetails();
        });

        async function getUserDetails() {
            // Razorpay settings from MySQL
            if (razorpaySettings && razorpaySettings.isEnabled) {
                $("#isEnabled").val(razorpaySettings.isEnabled);
                $("#isSandboxEnabled").val(razorpaySettings.isSandboxEnabled || false);
                $("#razorpayKey").val(razorpaySettings.razorpayKey || '');
                $("#razorpaySecret").val(razorpaySettings.razorpaySecret || '');
                if (razorpaySettings.isEnabled) {
                    $("#razorpay_box").removeClass('d-none');
                }
            }

            // Stripe settings from MySQL
            if (stripeSettings && stripeSettings.isEnabled) {
                $("#isStripeSandboxEnabled").val(stripeSettings.isSandboxEnabled || false);
                $("#stripeKey").val(stripeSettings.stripeKey || '');
                $("#stripeSecret").val(stripeSettings.stripeSecret || '');
                if (stripeSettings.isEnabled) {
                    $("#stripe_box").removeClass('d-none');
                }
            }

            // PayPal settings from MySQL
            if (paypalSettings && paypalSettings.isEnabled) {
                if (paypalSettings.isLive) {
                    $("#ispaypalSandboxEnabled").val(false);
                } else {
                    $("#ispaypalSandboxEnabled").val(true);
                }
                $("#paypalKey").val(paypalSettings.paypalClient || '');
                $("#paypalSecret").val(paypalSettings.paypalSecret || '');
                if (paypalSettings.isEnabled) {
                    $("#paypal_box").removeClass('d-none');
                }
            }

            // Wallet settings from MySQL
            if (walletSettings && walletSettings.isEnabled) {
                $("#walletenabled").val(true);
                $("#wallet_box").removeClass('d-none');
            } else {
                $("#walletenabled").val(false);
                $("#wallet_box").addClass('d-none');
            }

            // PayFast settings from MySQL
            if (payFastSettings && payFastSettings.isEnable) {
                $("#payfast_isEnabled").val(payFastSettings.isEnable);
                $("#payfast_isSandbox").val(payFastSettings.isSandbox || false);
                $("#payfast_merchant_id").val(payFastSettings.merchant_id || '');
                $("#payfast_merchant_key").val(payFastSettings.merchant_key || '');
                $("#payfast_return_url").val(payFastSettings.return_url || '');
                $("#payfast_cancel_url").val(payFastSettings.cancel_url || '');
                $("#payfast_notify_url").val(payFastSettings.notify_url || '');
                if (payFastSettings.isEnable) {
                    $("#payfast_box").removeClass('d-none');
                }
            }

            // PayStack settings from MySQL
            if (payStackSettings && payStackSettings.isEnable) {
                $("#paystack_isEnabled").val(payStackSettings.isEnable);
                $("#paystack_isSandbox").val(payStackSettings.isSandbox || false);
                $("#paystack_public_key").val(payStackSettings.publicKey || '');
                $("#paystack_secret_key").val(payStackSettings.secretKey || '');
                if (payStackSettings.isEnable) {
                    $("#paystack_box").removeClass('d-none');
                }
            }

            // FlutterWave settings from MySQL
            if (flutterWaveSettings && flutterWaveSettings.isEnable) {
                $("#flutterWave_isEnabled").val(flutterWaveSettings.isEnable);
                $("#flutterWave_isSandbox").val(flutterWaveSettings.isSandbox || false);
                $("#flutterWave_encryption_key").val(flutterWaveSettings.encryptionKey || '');
                $("#flutterWave_secret_key").val(flutterWaveSettings.secretKey || '');
                $("#flutterWave_public_key").val(flutterWaveSettings.publicKey || '');
                if (flutterWaveSettings.isEnable) {
                    $("#flutterWave_box").removeClass('d-none');
                }
            }

            // MercadoPago settings from MySQL
            if (MercadoPagoSettings && MercadoPagoSettings.isEnabled) {
                $("#mercadopago_isEnabled").val(MercadoPagoSettings.isEnabled);
                $("#mercadopago_isSandbox").val(MercadoPagoSettings.isSandboxEnabled || false);
                $("#mercadopago_public_key").val(MercadoPagoSettings.PublicKey || '');
                $("#mercadopago_access_token").val(MercadoPagoSettings.AccessToken || '');
                if (MercadoPagoSettings.isEnabled) {
                    $("#mercadopago_box").removeClass('d-none');
                }
            }

            // Xendit settings from MySQL
            if (XenditSettings && XenditSettings.enable) {
                $("#xendit_enable").val(XenditSettings.enable);
                $("#xendit_apiKey").val(XenditSettings.apiKey || '');
                $("#xendit_image").val(XenditSettings.image || '');
                $("#xendit_isSandbox").val(XenditSettings.isSandbox || false);
                if (XenditSettings.enable) {
                    $("#xendit_box").removeClass('d-none');
                }
            }

            // Midtrans settings from MySQL
            if (Midtrans_settings && Midtrans_settings.enable) {
                $("#midtrans_enable").val(Midtrans_settings.enable);
                $("#midtrans_serverKey").val(Midtrans_settings.serverKey || '');
                $("#midtrans_image").val(Midtrans_settings.image || '');
                $("#midtrans_isSandbox").val(Midtrans_settings.isSandbox || false);
                if (Midtrans_settings.enable) {
                    $("#midtrans_box").removeClass('d-none');
                }
            }

            // OrangePay settings from MySQL
            if (OrangePaySettings && OrangePaySettings.enable) {
                $("#orangepay_enable").val(OrangePaySettings.enable);
                $("#orangepay_auth").val(OrangePaySettings.auth || '');
                $("#orangepay_image").val(OrangePaySettings.image || '');
                $("#orangepay_isSandbox").val(OrangePaySettings.isSandbox || false);
                $("#orangepay_clientId").val(OrangePaySettings.clientId || '');
                $("#orangepay_clientSecret").val(OrangePaySettings.clientSecret || '');
                $("#orangepay_merchantKey").val(OrangePaySettings.merchantKey || '');
                $("#orangepay_notifyUrl").val(OrangePaySettings.notifyUrl || '');
                $("#orangepay_returnUrl").val(OrangePaySettings.returnUrl || '');
                $("#orangepay_cancelUrl").val(OrangePaySettings.cancelUrl || '');
                if (OrangePaySettings.enable) {
                    $("#orangepay_box").removeClass('d-none');
                }
            }

            // Get user details from MySQL (wallet amount already passed from controller)
            if (wallet_amount > 0) {
                $("#wallet").attr('disabled', false);
                $("#user_wallet_amount").val(wallet_amount);
                var wallet_balance = 0;
                if (currencyAtRight) {
                    wallet_balance = parseFloat(wallet_amount).toFixed(decimal_degits) + "" + currentCurrency;
                } else {
                    wallet_balance = currentCurrency + "" + parseFloat(wallet_amount).toFixed(decimal_degits);
                }
                $("#wallet_amount").html(wallet_balance);
            }
            
            // Get author name and email from authenticated user
            authorName = "{{ Auth::user()->firstName ?? '' }} {{ Auth::user()->lastName ?? '' }}";
            authorEmail = "{{ Auth::user()->email }}";
        }

        async function finalCheckout() {

            var payment_method = $('input[name="payment_method"]:checked').val();
            if (payment_method == false || payment_method == undefined || payment_method == '') {
                alert("{{ trans('lang.select_payment_option') }}");
                return false;
            }

            var total_pay = $('#sub_total').val();
            if (total_pay == 0 || total_pay == '' || total_pay == "0") {
                return false;
            }

            var now = new Date();
            var order_json = {
                userId: userId,
                planId: planId,
                authorName: authorName,
                authorEmail: authorEmail
            };

            if (payment_method == "razorpay") {
                var razorpayKey = $("#razorpayKey").val();
                var razorpaySecret = $("#razorpaySecret").val();

                $.ajax({
                    type: 'POST',
                    url: "<?php echo route('payment-proccessing'); ?>",
                    data: {
                        _token: '<?php echo csrf_token(); ?>',
                        order_json: order_json,
                        razorpaySecret: razorpaySecret,
                        razorpayKey: razorpayKey,
                        payment_method: payment_method,
                        total_pay: total_pay,
                        currencyData: currencyData
                    },
                    success: function(data) {
                        window.location.href =
                            "<?php echo route('pay-subscription'); ?>";
                    }
                });

            } else if (payment_method == "mercadopago") {

                var mercadopago_public_key = $("#mercadopago_public_key").val();
                var mercadopago_access_token = $("#mercadopago_access_token").val();
                var mercadopago_isSandbox = $("#mercadopago_isSandbox").val();
                var mercadopago_isEnabled = $("#mercadopago_isEnabled").val();
                $.ajax({
                    type: 'POST',
                    url: "<?php echo route('payment-proccessing'); ?>",
                    data: {
                        _token: '<?php echo csrf_token(); ?>',
                        order_json: order_json,
                        mercadopago_public_key: mercadopago_public_key,
                        mercadopago_access_token: mercadopago_access_token,
                        payment_method: payment_method,
                        id: id_order,
                        total_pay: total_pay,
                        currencyData: currencyData,
                        mercadopago_isSandbox: mercadopago_isSandbox,
                        mercadopago_isEnabled: mercadopago_isEnabled,
                    },
                    success: function(data) {
                        data = JSON.parse(data);
                        $('#cart_list').html(data.html);
                        window.location.href =
                            "<?php echo route('pay-subscription'); ?>";
                    }
                });

            } else if (payment_method == "stripe") {

                var stripeKey = $("#stripeKey").val();
                var stripeSecret = $("#stripeSecret").val();
                var isStripeSandboxEnabled = $("#isStripeSandboxEnabled").val();

                $.ajax({
                    type: 'POST',
                    url: "<?php echo route('payment-proccessing'); ?>",
                    data: {
                        _token: '<?php echo csrf_token(); ?>',
                        order_json: order_json,
                        stripeKey: stripeKey,
                        stripeSecret: stripeSecret,
                        payment_method: payment_method,
                        total_pay: total_pay,
                        isStripeSandboxEnabled: isStripeSandboxEnabled,
                        currencyData: currencyData
                    },
                    success: function(data) {
                        data = JSON.parse(data);
                        $('#cart_list').html(data.html);
                        window.location.href =
                            "<?php echo route('pay-subscription'); ?>";
                    }
                });

            } else if (payment_method == "paypal") {

                var paypalKey = $("#paypalKey").val();
                var paypalSecret = $("#paypalSecret").val();
                var ispaypalSandboxEnabled = $("#ispaypalSandboxEnabled").val();

                $.ajax({
                    type: 'POST',
                    url: "<?php echo route('payment-proccessing'); ?>",
                    data: {
                        _token: '<?php echo csrf_token(); ?>',
                        order_json: order_json,
                        paypalKey: paypalKey,
                        paypalSecret: paypalSecret,
                        payment_method: payment_method,
                        total_pay: total_pay,
                        ispaypalSandboxEnabled: ispaypalSandboxEnabled,
                        currencyData: currencyData
                    },
                    success: function(data) {
                        data = JSON.parse(data);
                        $('#cart_list').html(data.html);
                        window.location.href =
                            "<?php echo route('pay-subscription'); ?>";
                    }
                });

            } else if (payment_method == "payfast") {

                var payfast_merchant_key = $("#payfast_merchant_key").val();
                var payfast_merchant_id = $("#payfast_merchant_id").val();
                var payfast_return_url = $("#payfast_return_url").val();
                var payfast_notify_url = $("#payfast_notify_url").val();
                var payfast_cancel_url = $("#payfast_cancel_url").val();
                var payfast_isSandbox = $("#payfast_isSandbox").val();

                $.ajax({
                    type: 'POST',
                    url: "<?php echo route('payment-proccessing'); ?>",
                    data: {
                        _token: '<?php echo csrf_token(); ?>',
                        order_json: order_json,
                        payfast_merchant_key: payfast_merchant_key,
                        payfast_merchant_id: payfast_merchant_id,
                        payment_method: payment_method,
                        total_pay: total_pay,
                        payfast_isSandbox: payfast_isSandbox,
                        payfast_return_url: payfast_return_url,
                        payfast_notify_url: payfast_notify_url,
                        payfast_cancel_url: payfast_cancel_url,
                        currencyData: currencyData

                    },
                    success: function(data) {
                        data = JSON.parse(data);
                        $('#cart_list').html(data.html);
                        window.location.href =
                            "<?php echo route('pay-subscription'); ?>";

                    }
                });

            } else if (payment_method == "paystack") {

                var paystack_public_key = $("#paystack_public_key").val();
                var paystack_secret_key = $("#paystack_secret_key").val();
                var paystack_isSandbox = $("#paystack_isSandbox").val();
                $.ajax({
                    type: 'POST',
                    url: "<?php echo route('payment-proccessing'); ?>",
                    data: {
                        _token: '<?php echo csrf_token(); ?>',
                        order_json: order_json,
                        payment_method: payment_method,
                        total_pay: total_pay,
                        paystack_isSandbox: paystack_isSandbox,
                        paystack_public_key: paystack_public_key,
                        paystack_secret_key: paystack_secret_key,
                        currencyData: currencyData

                    },
                    success: function(data) {
                        data = JSON.parse(data);
                        $('#cart_list').html(data.html);
                        window.location.href =
                            "<?php echo route('pay-subscription'); ?>";
                    }
                });

            } else if (payment_method == "flutterwave") {

                var flutterwave_isenabled = $("#flutterWave_isEnabled").val();
                var flutterWave_encryption_key = $("#flutterWave_encryption_key").val();
                var flutterWave_public_key = $("#flutterWave_public_key").val();
                var flutterWave_secret_key = $("#flutterWave_secret_key").val();
                var flutterWave_isSandbox = $("#flutterWave_isSandbox").val();
                $.ajax({
                    type: 'POST',
                    url: "<?php echo route('payment-proccessing'); ?>",
                    data: {
                        _token: '<?php echo csrf_token(); ?>',
                        order_json: order_json,
                        payment_method: payment_method,
                        total_pay: total_pay,
                        flutterWave_isSandbox: flutterWave_isSandbox,
                        flutterWave_public_key: flutterWave_public_key,
                        flutterWave_secret_key: flutterWave_secret_key,
                        flutterwave_isenabled: flutterwave_isenabled,
                        flutterWave_encryption_key: flutterWave_encryption_key,
                        currencyData: currencyData
                    },
                    success: function(data) {
                        data = JSON.parse(data);
                        $('#cart_list').html(data.html);
                        window.location.href =
                            "<?php echo route('pay-subscription'); ?>";
                    }
                });

            } else if (payment_method == "xendit") {

                if (!['IDR', 'PHP', 'USD', 'VND', 'THB', 'MYR', 'SGD'].includes(currencyData.code)) {
                    alert("{{ trans('lang.currency_restriction') }}");
                    return false;
                }

                var xendit_enable = $("#xendit_enable").val();
                var xendit_apiKey = $("#xendit_apiKey").val();
                var xendit_image = $("#xendit_image").val();
                var xendit_isSandbox = $("#xendit_isSandbox").val();

                $.ajax({
                    type: 'POST',
                    url: "<?php echo route('payment-proccessing'); ?>",
                    data: {
                        _token: '<?php echo csrf_token(); ?>',
                        order_json: order_json,
                        payment_method: payment_method,
                        total_pay: total_pay,
                        xendit_enable: xendit_enable,
                        xendit_apiKey: xendit_apiKey,
                        xendit_image: xendit_image,
                        xendit_isSandbox: xendit_isSandbox,
                        currencyData: currencyData
                    },
                    success: function(data) {
                        data = JSON.parse(data);
                        $('#cart_list').html(data.html);
                        window.location.href =
                            "<?php echo route('pay-subscription'); ?>";
                    }
                });

            } else if (payment_method == "midtrans") {

                var midtrans_enable = $("#midtrans_enable").val();
                var midtrans_serverKey = $("#midtrans_serverKey").val();
                var midtrans_image = $("#midtrans_image").val();
                var midtrans_isSandbox = $("#midtrans_isSandbox").val();

                $.ajax({
                    type: 'POST',
                    url: "<?php echo route('payment-proccessing'); ?>",
                    data: {
                        _token: '<?php echo csrf_token(); ?>',
                        order_json: order_json,
                        payment_method: payment_method,
                        total_pay: total_pay,
                        midtrans_enable: midtrans_enable,
                        midtrans_serverKey: midtrans_serverKey,
                        midtrans_image: midtrans_image,
                        midtrans_isSandbox: midtrans_isSandbox,
                        currencyData: currencyData
                    },
                    success: function(data) {
                        data = JSON.parse(data);
                        $('#cart_list').html(data.html);
                        window.location.href =
                            "<?php echo route('pay-subscription'); ?>";
                    }
                });

            } else if (payment_method == "orangepay") {

                var orangepay_enable = $("#orangepay_enable").val();
                var orangepay_auth = $("#orangepay_auth").val();
                var orangepay_image = $("#orangepay_image").val();
                var orangepay_isSandbox = $("#orangepay_isSandbox").val();
                var orangepay_clientId = $("#orangepay_clientId").val();
                var orangepay_clientSecret = $("#orangepay_clientSecret").val();
                var orangepay_merchantKey = $("#orangepay_merchantKey").val();
                var orangepay_notifyUrl = $("#orangepay_notifyUrl").val();
                var orangepay_returnUrl = $("#orangepay_returnUrl").val();
                var orangepay_cancelUrl = $("#orangepay_cancelUrl").val();

                $.ajax({
                    type: 'POST',
                    url: "<?php echo route('payment-proccessing'); ?>",
                    data: {
                        _token: '<?php echo csrf_token(); ?>',
                        payment_method: payment_method,
                        total_pay: total_pay,
                        orangepay_enable: orangepay_enable,
                        orangepay_auth: orangepay_auth,
                        orangepay_image: orangepay_image,
                        orangepay_isSandbox: orangepay_isSandbox,
                        orangepay_clientId: orangepay_clientId,
                        orangepay_clientSecret: orangepay_clientSecret,
                        orangepay_merchantKey: orangepay_merchantKey,
                        orangepay_notifyUrl: orangepay_notifyUrl,
                        orangepay_returnUrl: orangepay_returnUrl,
                        orangepay_cancelUrl: orangepay_cancelUrl,
                        currencyData: currencyData
                    },
                    success: function(data) {

                        data = JSON.parse(data);
                        $('#cart_list').html(data.html);
                        window.location.href =
                            "<?php echo route('pay-subscription'); ?>";
                    }
                });

            } else if (payment_method == "wallet") {

                if (wallet_amount < total_pay) {
                    alert("{{ trans('lang.do_not_have_sufficient_amount') }}");
                    return false;
                }
                
                // Generate wallet transaction ID
                var walletId = 'wallet_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                var paymentDate = new Date().toISOString();
                var finalPlanData = planData ? planData : {};
                
                // Calculate expiry date if not already set
                var expiryDate = expiryDay;
                if(!expiryDate && planData && planData.expiryDay && planData.expiryDay != '-1') {
                    var currentDate = new Date();
                    currentDate.setDate(currentDate.getDate() + parseInt(planData.expiryDay));
                    expiryDate = currentDate.toISOString();
                }
                
                var finalizeData = {
                    user_id: userId,
                    plan_id: planId,
                    plan_data: JSON.stringify(finalPlanData),
                    expiry_date: expiryDate,
                    transaction_id: walletId,
                    payment_date: paymentDate,
                    payment_method: payment_method,
                    subscription_order_id: id_order,
                    vendor_id: vendorId,
                    wallet_amount: wallet_amount - total_pay,
                    wallet_transaction_id: walletId,
                    wallet_debit_amount: total_pay,
                    _token: $('meta[name="csrf-token"]').attr('content')
                };

                // ✅ CRITICAL: This uses MySQL to save subscription payment
                // The Firebase code above is ONLY for display data, not payment storage
                $.ajax({
                    type: 'POST',
                    url: '{{ route("finalize-subscription") }}', // MySQL endpoint
                    data: finalizeData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if(response.success) {
                            // Update subscription flag
                            var url="{{ route('setSubcriptionFlag') }}";
                            $.ajax({
                                type: 'POST',
                                url: url,
                                data: {
                                    email: "<?php echo Auth::user()->email?>",
                                    isSubscribed: 'true'
                                },
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function(data) {
                                    if(data.access) {
                                        window.location.href='{{ route('dashboard') }}';
                                    }
                                }
                            });
                        } else {
                            alert('Error: ' + (response.message || 'Failed to process wallet payment'));
                        }
                    },
                    error: function(xhr) {
                        console.error('Wallet payment error:', xhr);
                        var errorMsg = 'Error processing wallet payment. Please contact support.';
                        if(xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        alert(errorMsg);
                    }
                });

            }
        }
    </script>

</body>
