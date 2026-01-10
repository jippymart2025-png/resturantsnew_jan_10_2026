<!doctype html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title id="app_name"><?php echo @$_COOKIE['meta_title']; ?></title>
        <link rel="icon" id="favicon" type="image/x-icon" href="<?php echo str_replace('images/', 'images%2F', @$_COOKIE['favicon']); ?>">
        <!-- Fonts -->
        <link rel="dns-prefetch" href="//fonts.gstatic.com">
        <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
        <!-- Styles -->
        <link href="{{ asset('assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
        <link href="{{ asset('css/style.css') }}" rel="stylesheet">
        <link href="{{ asset('css/animate.css') }}" rel="stylesheet">
        <link href="{{ asset('assets/plugins/select2/dist/css/select2.min.css') }}" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,100..900;1,100..900&display=swap"
            rel="stylesheet">
        <!-- @yield('style') -->
    </head>

    <body>

        <div class="page-wrapper py-5 pl-0">
            <div class="container-fluid">
                <div id="data-table_processing" class="page-overlay" style="display:none;">
                    <div class="overlay-text">
                        <img src="{{ asset('images/spinner.gif') }}">
                    </div>
                </div>
                <div class="col-lg-11 ml-lg-auto mr-lg-auto">
                    <div class="title text-center mb-5">
                        <h2 class="text-primary">{{ trans('lang.business_plans') }}</h2>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex top-title-section pb-4 mb-4 justify-content-between">
                                <div class="d-flex top-title-left align-start-center">
                                    <div class="top-title">
                                        <h3 class="mb-0">{{ trans('lang.choose_your_business_plan') }}</h3>
                                        <p class="mb-0 text-dark-2">
                                            {{ trans('lang.choose_your_business_plan_description') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="row" id="default-plan"></div>
                        </div>
                    </div>
                    <div class="row backBtn d-none">
                        <div class="col-12 text-center"><a href="{{ url('/') }}" class="btn btn-primary">Back</a>
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
            // Data from controller (MySQL) - NO Firebase Firestore
            var currentCurrency = "{{ $currencyData['symbol'] }}";
            var currencyAtRight = {{ $currencyData['symbolAtRight'] ? 'true' : 'false' }};
            var decimal_degits = {{ $currencyData['decimal_degits'] }};
            var userId = "{{ $userId }}";
            var commisionModel = {{ $commisionModel ? 'true' : 'false' }};
            var AdminCommission = "{{ $adminCommission }}";
            var commissionType = "{{ $commissionType }}";
            var subscriptionModel = {{ $subscriptionModel ? 'true' : 'false' }};
            var activeSubscriptionId = "{{ $activeSubscriptionId ?? '' }}";
            var vendorId = "{{ $vendorId ?? '' }}";
            var subscriptionPlans = @json($subscriptionPlans);
            var globalSettings = @json($globalSettings);

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

            $(document).ready(function() {
                jQuery('#data-table_processing').show();

                // Set global settings cookies from MySQL data
                if (globalSettings && globalSettings.store_panel_color) {
                    setCookie('store_panel_color', globalSettings.store_panel_color, 365);
                }
                if (globalSettings && globalSettings.meta_title) {
                    setCookie('meta_title', globalSettings.meta_title, 365);
                    document.title = globalSettings.meta_title;
                }
                if (globalSettings && globalSettings.favicon) {
                    setCookie('favicon', globalSettings.favicon, 365);
                }

                // Check if both models are disabled
                if (commisionModel == false && subscriptionModel == false) {
                    var url = "{{ route('setSubcriptionFlag') }}";
                    $.ajax({
                        type: 'POST',
                        url: url,
                        data: {
                            email: "{{ Auth::user()->email }}",
                            isSubscribed: ""
                        },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(data) {
                            if (data.access) {
                                window.location = "{{ route('home') }}";
                            }
                        }
                    });
                    return;
                }

                // Render subscription plans from MySQL data
                var plans = subscriptionPlans;

                var html = '';
                plans.forEach(function(data) {

                        var activeClass=(data.id==activeSubscriptionId)?
                            '<span class="badge badge-success">{{ trans('lang.active') }}</span>':
                            '';

                        if(data.id=="J0RwvxCWhZzQQD7Kc2Ll") {

                            if(commisionModel) {


                                html+=`<div class="col-md-3 mb-3 pricing-card pricing-card-commission">
                                            <div class="pricing-card-inner">
                                                <div class="pricing-card-top">
                                                    <div class="d-flex align-items-center pb-4">
                                                        <span class="pricing-card-icon mr-4"><img src="${data.image}"></span>
                                                    </div>
                                                    <div class="pricing-card-price">
                                                        <h3 class="text-dark-2">${data.name} ${activeClass}</h3>
                                                        <span class="price-day">${data.description}</span>
                                                    </div>
                                                </div>
                                                <div class="pricing-card-content pt-3 mt-3 border-top">
                                                    <ul class="pricing-card-list text-dark-2">`;
                                html+=
                                    `<li><span class="mdi mdi-check"></span>{{ trans('lang.pay_commission_of') }} ${AdminCommission} {{ trans('lang.on_each_order') }} </li>`
                                if (data.plan_points && Array.isArray(data.plan_points)) {
                                    data.plan_points.forEach(function(list) {
                                        html += `<li><span class="mdi mdi-check"></span>${list}</li>`;
                                });
                                }
                                html+=
                                    `<li><span class="mdi mdi-check"></span>{{ trans('lang.unlimited') }} {{ trans('lang.orders') }}</li>`
                                html+=
                                    `<li><span class="mdi mdi-check"></span>{{ trans('lang.unlimited') }} {{ trans('lang.products') }}</li>`

                                html+=`</ul>
                                                </div>`;
                                var buttonText=(activeClass=='')?
                                    "{{ trans('lang.select_plan') }}":
                                    "{{ trans('lang.renew_plan') }}";

                                html+=`<div class="pricing-card-btm">
                                                    <a href="javascript:void(0)" onClick="saveSubscriptionPlan('${data.id}')" class="btn rounded-full active-btn btn-primary">${buttonText}</a>
                                                </div>`;

                                html+=`</div>
                                </div>`;
                            }
                        } else {
                            if(subscriptionModel) {

                                const translations={
                                    chatingOption: "{{ trans('lang.chating_option') }}",
                                    dineInOption: "{{ trans('lang.dinein_option') }}",
                                    generateQrCode: "{{ trans('lang.generate_qr_code') }}",
                                    mobileAppAccess: "{{ trans('lang.mobile_app_access') }}"
                                };
                                var features=data.features;
                                var buttonText=(activeClass=='')?
                                    "{{ trans('lang.select_plan') }}":
                                    "{{ trans('lang.renew_plan') }}";

                                if(data.type=="free") {

                                    var routeHtml=
                                        `<a href="javascript:void(0)" onClick="saveSubscriptionPlan('${data.id}')" class="btn rounded-full">${buttonText}</a>`
                                } else {
                                    var route=
                                        "{{ route('subscription-plans.checkout', ':id') }}";
                                    route=route.replace(":id",data.id);
                                    var routeHtml=
                                        `<a href="${route}" class="btn rounded-full">${buttonText}</a>`
                                }


                                html+=`<div class="col-md-3 mb-3  pricing-card pricing-card-subscription ${data.name}">
                                    <div class="pricing-card-inner">
                                        <div class="pricing-card-top">
                                        <div class="d-flex align-items-center pb-4">
                                            <span class="pricing-card-icon mr-4"><img src="${data.image}"></span>
                                            <h2 class="text-dark-2">${data.name} ${activeClass}</h2>
                                        </div>
                                        <p class="text-muted">${data.description}</p>
                                        <div class="pricing-card-price">
                                            <h3 class="text-dark-2">${data.type!=="free"? (currencyAtRight? parseFloat(data.price).toFixed(decimal_degits)+currentCurrency:currentCurrency+parseFloat(data.price).toFixed(decimal_degits)):'<span style="color:red;">Free</span>'}</h3>
                                            <span class="price-day">${data.expiryDay==-1? "{{ trans('lang.unlimited') }}":data.expiryDay} {{trans('lang.days')}}</span>
                                        </div>
                                        </div>
                                        <div class="pricing-card-content pt-3 mt-3 border-top">
                                        <ul class="pricing-card-list text-dark-2">
                                            ${features.chat? `<li><span class="mdi mdi-check"></span>${translations.chatingOption}</li>`:`<li><span class="mdi mdi-close"></span>${translations.chatingOption}</li>`}
                                            ${features.dineIn? `<li><span class="mdi mdi-check"></span>${translations.dineInOption}</li>`:`<li><span class="mdi mdi-close"></span>${translations.dineInOption}</li>`}
                                            ${features.qrCodeGenerate? `<li><span class="mdi mdi-check"></span>${translations.generateQrCode}</li>`:`<li><span class="mdi mdi-close"></span>${translations.generateQrCode}</li>`}
                                            ${features.restaurantMobileApp? `<li><span class="mdi mdi-check"></span>${translations.mobileAppAccess}</li>`:`<li><span class="mdi mdi-close"></span>${translations.mobileAppAccess}</li>`}    
                                            <li><span class="mdi mdi-check"></span>${data.orderLimit==-1? "{{ trans('lang.unlimited') }}":data.orderLimit} {{ trans('lang.orders') }}</li>
                                            <li><span class="mdi mdi-check"></span>${data.itemLimit==-1? "{{ trans('lang.unlimited') }}":data.itemLimit} {{ trans('lang.products') }}</li>
                                        </ul>
                                        </div>`;

                                html+=`<div class="pricing-card-btm">${routeHtml}</div>`;

                                html+=`</div>
                                </div>`;
                            }
                        }
                    });

                if (activeSubscriptionId == '') {
                    $('.backBtn').addClass('d-none');
                } else {
                    $('.backBtn').removeClass('d-none');
                }
                
                    $('#default-plan').append(html);
                    jQuery('#data-table_processing').hide();
            });

            function setCookie(cname,cvalue,exdays) {
                const d=new Date();
                d.setTime(d.getTime()+(exdays*24*60*60*1000));
                let expires="expires="+d.toUTCString();
                document.cookie=cname+"="+cvalue+";"+expires+";path=/";
            }

            async function saveSubscriptionPlan(id) {
                // Find plan data from MySQL data passed from controller
                var planData = subscriptionPlans.find(function(p) {
                    return p.id == id;
                });

                if (!planData) {
                    alert('Plan not found');
                    return;
                }

                // Calculate expiry date
                var expiryDate = null;
                if (planData.expiryDay != '-1') {
                    var currentDate = new Date();
                    currentDate.setDate(currentDate.getDate() + parseInt(planData.expiryDay));
                    expiryDate = currentDate.toISOString();
                    }

                // Generate subscription order ID
                var subscriptionOrderId = 'sub_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

                // Save to MySQL via AJAX using finalizeSubscription
                var url = "{{ route('finalize-subscription') }}";
                            $.ajax({
                                type: 'POST',
                                url: url,
                                data: {
                        user_id: userId,
                        plan_id: id,
                        plan_data: JSON.stringify(planData),
                        expiry_date: expiryDate,
                        payment_method: 'free',
                        subscription_order_id: subscriptionOrderId,
                        vendor_id: vendorId || null
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update subscription flag
                            var flagUrl = "{{ route('setSubcriptionFlag') }}";
                            $.ajax({
                                type: 'POST',
                                url: flagUrl,
                                data: {
                                    email: "{{ Auth::user()->email }}",
                                    isSubscribed: 'true'
                                },
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function(data) {
                                    if (data.access) {
                                        window.location.href = "{{ route('home') }}";
                                    }
                                }
                            });
                        } else {
                            alert('Failed to save subscription: ' + (response.message || 'Unknown error'));
                        }
                    },
                    error: function(xhr) {
                        alert('Error saving subscription. Please try again.');
                        console.error('Error:', xhr);
                    }
                });
            }

        </script>

    </body>

</html>
