@extends('layouts.app')
@section('content')
    <div class="page-wrapper">
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">{{ trans('lang.subscription_details') }}</h3>
            </div>
            <div class="col-md-7 align-self-center">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">{{ trans('lang.dashboard') }}</a></li>
                    <li class="breadcrumb-item">{{ trans('lang.subscription_details') }}</li>
                </ol>
            </div>
        </div>
        <div class="container-fluid">
            <div class="card-body pb-5 p-0">
                <div class="order_detail" id="order_detail">
                    <div class="order_detail-top">
                        <div class="row">
                            <div class="order_edit-genrl col-lg-7 col-md-12">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="order_detail-top-box">
                                            <div class="form-group row widt-100 gendetail-col">
                                                <label class="col-12 control-label"><strong>{{ trans('lang.plan_name') }}
                                                        : </strong><span id="plan_name"></span></label>
                                            </div>
                                            <div class="form-group row widt-100 gendetail-col">
                                                <label class="col-12 control-label"><strong>{{ trans('lang.plan_price') }}
                                                        : </strong><span id="plan_price"></span></label>
                                            </div>
                                            <div class="form-group row widt-100 gendetail-col">
                                                <label class="col-12 control-label"><strong>{{ trans('lang.item_limit') }}
                                                        : </strong><span id="item_limit"></span></label>
                                            </div>
                                            <div class="form-group row widt-100 gendetail-col">
                                                <label class="col-12 control-label"><strong>{{ trans('lang.order_limit') }}
                                                        : </strong><span id="order_limit"></span></label>
                                            </div>
                                            <div class="form-group row widt-100 gendetail-col">
                                                <label class="col-12 control-label"><strong>{{ trans('lang.active_at') }}
                                                        : </strong><span id="active_at"></span></label>
                                            </div>
                                            <div class="form-group row widt-100 gendetail-col">
                                                <label class="col-12 control-label"><strong>{{ trans('lang.expire_at') }}
                                                        : </strong><span id="expire_at"></span></label>
                                            </div>
                                            <div class="form-group row widt-100 gendetail-col payment_method">
                                                <label class="col-12 control-label"><strong>{{ trans('lang.payment_methods') }}
                                                        : </strong><span id="payment_method"></span></label>
                                            </div>
                                            <div class="form-group row widt-100 gendetail-col">
                                                <label class="col-12 control-label"><strong>{{ __('Zone') }}
                                                        : </strong><span id="zone"></span></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="order_addre-edit col-lg-5 col-md-12">
                                <div class="card">
                                    <div class="card-header bg-white">
                                        <h3>{{ trans('lang.features') }}</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="address order_detail-top-box features">
                                        </div>
                                    </div>
                                </div>
                                <div class="order_addre-edit">
                                    <div class="card mt-4">
                                        <div class="card-header bg-white">
                                            <h3>{{ trans('lang.description') }}</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="address order_detail-top-box">
                                                <p>
                                                    <span id="description"></span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group col-12 text-center btm-btn">
                    <a href="{!! route('my-subscriptions') !!}" class="btn btn-default"><i
                            class="fa fa-undo"></i>{{ trans('lang.back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>
@endsection
@section('style')
@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/printThis/1.15.0/printThis.js"></script>
    <script>
        // Data from controller (MySQL) - NO Firebase
        var planData = @json($planData ?? []);
        var subscription = @json($subscription ?? []);
        var currencyData = @json($currencyData ?? []);
        var currentCurrency = "{{ $currencyData['symbol'] ?? '$' }}";
        var currencyAtRight = {{ ($currencyData['symbolAtRight'] ?? false) ? 'true' : 'false' }};
        var decimal_degits = {{ $currencyData['decimal_degits'] ?? 2 }};
        
        $(document).ready(function() {
            $(document.body).on('click', '.redirecttopage', function() {
                var url = $(this).attr('data-url');
                window.location.href = url;
            });
            
            // Display plan data
            if (planData && planData.name) {
                $('#plan_name').html(planData.name);
            }
            
            // Format and display price
            if (planData && planData.price !== undefined) {
                var price;
                if (currencyAtRight) {
                    price = parseFloat(planData.price).toFixed(decimal_degits) + currentCurrency;
                } else {
                    price = currentCurrency + parseFloat(planData.price).toFixed(decimal_degits);
                }
                $('#plan_price').html(price);
            }
            
            // Display limits
            if (planData) {
                $('#item_limit').html((planData.itemLimit != '-1' && planData.itemLimit != -1) ? planData.itemLimit : "{{trans('lang.unlimited')}}");
                $('#order_limit').html((planData.orderLimit != '-1' && planData.orderLimit != -1) ? planData.orderLimit : "{{trans('lang.unlimited')}}");
            }
            
            // Display dates (already formatted in controller with timezone conversion)
            @if(isset($formattedActiveAt) && !empty($formattedActiveAt))
                $('#active_at').html("{{ $formattedActiveAt }}");
            @endif
            
            @if(isset($formattedExpiryDate))
                @if($formattedExpiryDate === trans('lang.unlimited'))
                    $('#expire_at').html("{{trans('lang.unlimited')}}");
                @else
                    $('#expire_at').html("{{ $formattedExpiryDate }}");
                @endif
            @endif
            
            // Format payment method
            var paymentType = subscription ? (subscription.payment_type || '').toLowerCase() : '';
            var payment_method = '';
            var image = '';
            
            if (paymentType == "stripe") {
                    image = '{{ asset('images/stripe.png') }}';
                payment_method = '<img style="width:100px" alt="image" src="' + image + '">';
            } else if (paymentType == "razorpay") {
                    image = '{{ asset('images/razorpay.png') }}';
                payment_method = '<img style="width:100px" alt="image" src="' + image + '">';
            } else if (paymentType == "paypal") {
                    image = '{{ asset('images/paypal.png') }}';
                payment_method = '<img style="width:100px" alt="image" src="' + image + '">';
            } else if (paymentType == "payfast") {
                    image = '{{ asset('images/payfast.png') }}';
                payment_method = '<img style="width:100px" alt="image" src="' + image + '">';
            } else if (paymentType == "paystack") {
                    image = '{{ asset('images/paystack.png') }}';
                payment_method = '<img style="width:100px" alt="image" src="' + image + '">';
            } else if (paymentType == "flutterwave") {
                    image = '{{ asset('images/flutter_wave.png') }}';
                payment_method = '<img style="width:100px" alt="image" src="' + image + '">';
            } else if (paymentType == "mercadopago") {
                    image = '{{ asset('images/marcado_pago.png') }}';
                payment_method = '<img style="width:100px" alt="image" src="' + image + '">';
            } else if (paymentType == "wallet") {
                    image = '{{ asset('images/foodie_wallet.png') }}';
                payment_method = '<img style="width:100px" alt="image" src="' + image + '">';
            } else if (paymentType == "paytm") {
                    image = '{{ asset('images/paytm.png') }}';
                payment_method = '<img style="width:100px" alt="image" src="' + image + '">';
            } else if (paymentType == "xendit") {
                    image = '{{ asset('images/Xendit.png') }}';
                payment_method = '<img style="width:100px" alt="image" src="' + image + '">';
            } else if (paymentType == "orangepay") {
                    image = '{{ asset('images/orangeMoney.png') }}';
                payment_method = '<img style="width:100px" alt="image" src="' + image + '">';
            } else if (paymentType == "midtrans") {
                    image = '{{ asset('images/midtrans.png') }}';
                payment_method = '<img style="width:100px" alt="image" src="' + image + '">';
                } else {
                payment_method = subscription ? subscription.payment_type : '';
                }
                $('#payment_method').html(payment_method);
            
            // Display features
                var html = '';
            if (planData && planData.features) {
                    const translations = {
                        chatingOption: "{{ trans('lang.chating_option') }}",
                        dineInOption: "{{ trans('lang.dinein_option') }}",
                        generateQrCode: "{{ trans('lang.generate_qr_code') }}",
                        mobileAppAccess: "{{ trans('lang.mobile_app_access') }}"
                    };
                var features = planData.features;
                if (features.chat) html += '<li>' + translations.chatingOption + '</li>';
                if (features.dineIn) html += '<li>' + translations.dineInOption + '</li>';
                if (features.qrCodeGenerate) html += '<li>' + translations.generateQrCode + '</li>';
                if (features.restaurantMobileApp) html += '<li>' + translations.mobileAppAccess + '</li>';
                } 
            
            if (planData && planData.plan_points && Array.isArray(planData.plan_points)) {
                planData.plan_points.forEach(function(list) {
                    html += '<li>' + list + '</li>';
                    });
                }
            
                $('.features').html(html);
            
            // Display description
            if (planData && planData.description) {
                $('#description').html(planData.description);
            }
            
            // Display zone (from controller)
            @if(isset($zoneDisplay) && !empty($zoneDisplay))
                $('#zone').html("{{ $zoneDisplay }}");
            @else
                $('#zone').html('-');
            @endif
        });
    </script>
@endsection
