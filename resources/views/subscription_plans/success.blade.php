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
    <!-- @yield('style') -->
</head>

<body>
    <?php if (isset($_COOKIE['store_panel_color'])) { ?>
    <style type="text/css">
        a,
        a:hover,
        a:focus {
            color:
                <?php    echo $_COOKIE['store_panel_color']; ?>
            ;
        }

        .form-group.default-admin {
            padding: 10px;
            font-size: 14px;
            color: #000;
            font-weight: 600;
            border-radius: 10px;
            box-shadow: 0 0px 6px 0px rgba(0, 0, 0, 0.5);
            margin: 20px 10px 10px 10px;
        }

        .form-group.default-admin .crediantials-field {
            position: relative;
            padding-right: 15px;
            text-align: left;
            padding-top: 5px;
            padding-bottom: 5px;
        }

        .form-group.default-admin .crediantials-field>a {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            margin: auto;
            height: 20px;
        }

        .btn-primary,
        .btn-primary.disabled,
        .btn-primary:hover,
        .btn-primary.disabled:hover {
            background:
                <?php    echo $_COOKIE['store_panel_color']; ?>
            ;
            border: 1px solid<?php    echo $_COOKIE['store_panel_color']; ?>;
        }

        [type="checkbox"]:checked+label::before {
            border-right: 2px solid<?php    echo $_COOKIE['store_panel_color']; ?>;
            border-bottom: 2px solid<?php    echo $_COOKIE['store_panel_color']; ?>;
        }

        .form-material .form-control,
        .form-material .form-control.focus,
        .form-material .form-control:focus {
            background-image: linear-gradient(<?php    echo $_COOKIE['store_panel_color']; ?>,
                    <?php    echo $_COOKIE['store_panel_color']; ?>
                ), linear-gradient(rgba(120, 130, 140, 0.13), rgba(120, 130, 140, 0.13));
        }

        .btn-primary.active,
        .btn-primary:active,
        .btn-primary:focus,
        .btn-primary.disabled.active,
        .btn-primary.disabled:active,
        .btn-primary.disabled:focus,
        .btn-primary.active.focus,
        .btn-primary.active:focus,
        .btn-primary.active:hover,
        .btn-primary.focus:active,
        .btn-primary:active:focus,
        .btn-primary:active:hover,
        .open>.dropdown-toggle.btn-primary.focus,
        .open>.dropdown-toggle.btn-primary:focus,
        .open>.dropdown-toggle.btn-primary:hover,
        .btn-primary.focus,
        .btn-primary:focus,
        .btn-primary:not(:disabled):not(.disabled).active:focus,
        .btn-primary:not(:disabled):not(.disabled):active:focus,
        .show>.btn-primary.dropdown-toggle:focus {
            background:
                <?php    echo $_COOKIE['store_panel_color']; ?>
            ;
            border-color:
                <?php    echo $_COOKIE['store_panel_color']; ?>
            ;
            box-shadow: 0 0 0 0.2rem<?php    echo $_COOKIE['store_panel_color']; ?>;
        }

        .error {
            color: red;
        }
    </style>
    <?php } ?>
    <div class="siddhi-checkout">
        <div class="container position-relative">
            <div id="data-table_processing" class="page-overlay" style="display:none;">
                <div class="overlay-text">
                    <img src="{{asset('images/spinner.gif')}}">
                </div>
            </div>
            <div class="py-5 row">
                <div class="col-md-12 mb-3">
                    <div>
                        <div class="siddhi-cart-item mb-3 rounded shadow-sm bg-white overflow-hidden">
                            <div class="siddhi-cart-item-profile bg-white p-3">
                                <div class="card card-default">
                                    <?php $authorName = @$cart['cart_order']['authorName']; ?>
                                    @if ($message = Session::get('success'))
                                                                        <div
                                                                            class="py-5 linus-coming-soon d-flex justify-content-center align-items-center">
                                                                            <div class="col-md-6">
                                                                                <div class="bg-white rounded text-center p-4 shadow-sm">
                                                                                    <h1 class="display-1 mb-4">🎉</h1>
                                                                                    <h1 class="font-weight-bold"><?php    if (@$authorName) {
        echo @$authorName.',';
    } ?>
                                                                                        {{ trans('lang.subscription_plan_activated_successfully') }}
                                                                                    </h1>
                                                                                    <a href="{{ route('home') }}"
                                                                                        class="btn rounded btn-primary btn-lg btn-block">{{ trans('lang.go_to_home') }}</a>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
   
    @if ($message = Session::get('success'))
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script src="{{ asset('assets/plugins/select2/dist/js/select2.min.js') }}"></script>
        <script src="https://www.gstatic.com/firebasejs/7.2.0/firebase-app.js"></script>
        <script src="https://www.gstatic.com/firebasejs/7.2.0/firebase-storage.js"></script>
        <script src="{{ asset('js/crypto-js.js') }}"></script>
        <script src="{{ asset('js/jquery.cookie.js') }}"></script>
        <script src="{{ asset('js/jquery.validate.js') }}"></script>
        <script type="text/javascript">
            var userId="<?php    echo $id; ?>";
            var subscriptionOrderId = 'sub_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            var vendorId = <?php echo isset($vendorId) && $vendorId ? "'".$vendorId."'" : 'null'; ?>;
            var planId = <?php echo isset($planId) && $planId ? "'".$planId."'" : 'null'; ?>;
            var planData = <?php echo isset($subscriptionPlan) ? json_encode($subscriptionPlan) : 'null'; ?>;
            var payment_method='<?php        echo $payment_method; ?>';
            var transactionId = '<?php echo isset($cart['transaction_id']) ? $cart['transaction_id'] : ''; ?>';
            var paymentDate = '<?php echo isset($cart['payment_date']) ? $cart['payment_date'] : date('Y-m-d H:i:s'); ?>';

            <?php    if (@$cart['payment_status'] == true && ! empty(@$cart['cart_order']['order_json'])) { ?>
            $("#data-table_processing").show();
            var order_json='<?php        echo json_encode($cart['cart_order']['order_json']); ?>';
            order_json=JSON.parse(order_json);
            
            // If planId not set from server, get from order_json
            if(!planId) {
                planId = order_json.planId;
            }

            $(document).ready(function() {
                // Calculate expiry date
                var expiryDay = null;
                if(planData && planData.expiryDay && planData.expiryDay != '-1') {
                    var currentDate = new Date();
                    currentDate.setDate(currentDate.getDate() + parseInt(planData.expiryDay));
                    expiryDay = currentDate.toISOString();
                } else if(order_json.planData && order_json.planData.expiryDay && order_json.planData.expiryDay != '-1') {
                    var currentDate = new Date();
                    currentDate.setDate(currentDate.getDate() + parseInt(order_json.planData.expiryDay));
                    expiryDay = currentDate.toISOString();
                }

                // Prepare plan data for submission
                var finalPlanData = planData ? planData : (order_json.planData ? order_json.planData : {});

                var finalizeData = {
                    user_id: userId,
                    plan_id: planId,
                    plan_data: JSON.stringify(finalPlanData),
                    expiry_date: expiryDay,
                    transaction_id: transactionId,
                    payment_date: paymentDate,
                    payment_method: payment_method,
                    subscription_order_id: subscriptionOrderId,
                    vendor_id: vendorId,
                    _token: $('meta[name="csrf-token"]').attr('content')
                };

                $.ajax({
                    type: 'POST',
                    url: '{{ route("finalize-subscription") }}',
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
                                    email: "<?php        echo Auth::user()->email; ?>",
                                    isSubscribed: 'true'
                                },
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function(data) {
                                    if(data.access) {
                                        $("#data-table_processing").hide();
                                        window.location.href='{{ route('home') }}';
                                    }
                                }
                            });
                        } else {
                            alert('Error: ' + (response.message || 'Failed to finalize subscription'));
                            $("#data-table_processing").hide();
                        }
                    },
                    error: function(xhr) {
                        console.error('Subscription finalization error:', xhr);
                        var errorMsg = 'Error finalizing subscription. Please contact support.';
                        if(xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        alert(errorMsg);
                        $("#data-table_processing").hide();
                    }
                });
            });
            <?php    } ?>
        </script>
    @endif
</body>