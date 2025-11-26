@extends('layouts.app')

@section('content')
<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-themecolor">{{ trans('lang.print_order') }}</h3>
        </div>
        <div class="col-md-7 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">{{ trans('lang.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('orders') }}">{{ trans('lang.order_plural') }}</a></li>
                <li class="breadcrumb-item active">{{ trans('lang.print_order') }}</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card" id="printableArea">
            <div class="card-body">
                <div class="text-right mb-3">
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fa fa-print"></i> {{ trans('lang.print_order') }}
                    </button>
                </div>

                <h4 class="mb-3">{{ $details['vendor']['title'] ?? trans('lang.restaurant') }}</h4>
                <p class="mb-0">{{ $details['vendor']['location'] ?? '' }}</p>
                <p>{{ trans('lang.phone') }}: {{ $details['vendor']['phonenumber'] ?? '—' }}</p>

                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <p><strong>{{ trans('lang.order_id') }}:</strong> {{ $order->id }}</p>
                    </div>
                    <div class="col-md-6 text-right">
                        <p><strong>{{ trans('lang.date_created') }}:</strong> {{ $details['created_at'] }}</p>
                    </div>
                </div>

                <p><strong>{{ trans('lang.customer_name') }}:</strong> {{ $details['customer']['name'] }}</p>
                <p><strong>{{ trans('lang.phone') }}:</strong> {{ $details['customer']['phone'] ?? '—' }}</p>
                @if(!empty($details['address']))
                    <p><strong>{{ trans('lang.address') }}:</strong>
                        {{ $details['address']['address'] ?? '' }},
                        {{ $details['address']['locality'] ?? '' }}
                    </p>
                @endif

                <hr>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>{{ trans('lang.item') }}</th>
                                <th>{{ trans('lang.price') }}</th>
                                <th>{{ trans('lang.qty') }}</th>
                                <th>{{ trans('lang.extra') }}</th>
                                <th>{{ trans('lang.total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($details['products'] as $product)
                                <tr>
                                    <td>
                                        <strong>{{ $product['name'] }}</strong>
                                        @if(!empty($product['variant']))
                                            <div class="small text-muted">
                                                @foreach($product['variant'] as $label => $value)
                                                    <div>{{ $label }}: {{ $value }}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if(!empty($product['extras']))
                                            <div class="small text-muted">
                                                {{ trans('lang.extras') }}: {{ implode(', ', $product['extras']) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $product['price'] }}</td>
                                    <td>{{ $product['quantity'] }}</td>
                                    <td>{{ $product['extras_price'] }}</td>
                                    <td>{{ $product['total'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6 offset-md-6">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td>{{ trans('lang.items_price') }}</td>
                                    <td class="text-right">{{ $details['summary']['items_total'] }}</td>
                                </tr>
                                <tr>
                                    <td>{{ trans('lang.addon_cost') }}</td>
                                    <td class="text-right">{{ $details['summary']['addons_total'] }}</td>
                                </tr>
                                <tr>
                                    <td>{{ trans('lang.sub_total') }}</td>
                                    <td class="text-right">{{ $details['summary']['subtotal'] }}</td>
                                </tr>
                                <tr>
                                    <td>{{ trans('lang.coupon_discount') }}</td>
                                    <td class="text-right">- {{ $details['summary']['discount'] }}</td>
                                </tr>
                                <tr>
                                    <td>{{ trans('lang.special_offer') }}</td>
                                    <td class="text-right">- {{ $details['summary']['special_discount'] }}</td>
                                </tr>
                                @foreach($details['summary']['taxes'] as $tax)
                                    <tr>
                                        <td>{{ $tax['label'] }}</td>
                                        <td class="text-right">{{ $tax['amount'] }}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td>{{ trans('lang.deliveryFee') }}</td>
                                    <td class="text-right">{{ $details['summary']['delivery'] }}</td>
                                </tr>
                                <tr>
                                    <td>{{ trans('lang.dm_tips') }}</td>
                                    <td class="text-right">{{ $details['summary']['tip'] }}</td>
                                </tr>
                                <tr class="border-top">
                                    <td><strong>{{ trans('lang.total') }}</strong></td>
                                    <td class="text-right"><strong>{{ $details['summary']['grand_total'] }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <p class="text-center mt-4">{{ trans('lang.thank_you') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection

