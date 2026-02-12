@extends('layouts.app')

@section('content')
    <style>
        /* =========================================
        DASHBOARD FILTER DROPDOWN – THEMED
        Uses your --theme-color (orange)
        ========================================= */

        /* Filter wrapper */
        /* Default: NO background */
        .order_stats_header form {
            padding: 0;
            background: transparent;
            box-shadow: none;
            border: none;
        }

        /* ONLY when custom range is active */
        .order_stats_header form.custom-active {
            background: #ffffff;
            padding: 12px 16px;
            border-radius: 14px;
            border: 1px solid rgba(0,0,0,0.08);
            box-shadow: 0 6px 16px rgba(0,0,0,0.08);
        }


        /* Dropdown base */
        .order_stats_header select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;

            height: 42px;
            min-width: 190px;

            padding: 8px 40px 8px 14px;
            font-size: 14px;
            font-weight: 600;

            border-radius: 12px;
            border: 2px solid var(--theme-color);
            background-color: #fff;
            color: #333;

            /* Custom arrow */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23ff683a' viewBox='0 0 24 24'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 16px;

            cursor: pointer;
            transition: all 0.25s ease;
        }

        /* Hover */
        .order_stats_header select:hover {
            background-color: rgba(255,104,58,0.05);
        }

        /* Focus (active) */
        .order_stats_header select:focus {
            outline: none;
            box-shadow: 0 0 0 4px rgba(255,104,58,0.25);
        }

        /* Date inputs (custom range) */
        .order_stats_header input[type="date"] {
            height: 42px;
            padding: 8px 12px;
            font-size: 13px;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
        }

        .order_stats_header input[type="date"]:focus {
            border-color: var(--theme-color);
            box-shadow: 0 0 0 3px rgba(255,104,58,0.2);
        }

        /* Apply button */
        .order_stats_header button {
            height: 42px;
            padding: 0 18px;
            font-size: 13px;
            font-weight: 700;
            border-radius: 12px;
            background: linear-gradient(
                135deg,
                var(--theme-color),
                #ff8a65
            );
            color: #fff;
            border: none;
            box-shadow: 0 4px 10px rgba(255,104,58,0.35);
        }

        .order_stats_header button:hover {
            transform: translateY(-1px);
            opacity: 0.95;
        }

        /* Mobile */
        @media (max-width: 768px) {
            .order_stats_header form {
                width: 100%;
            }

            .order_stats_header select,
            .order_stats_header input[type="date"],
            .order_stats_header button {
                width: 100%;
            }
        }
    </style>
    <div id="main-wrapper" class="page-wrapper" style="min-height: 207px;">
        <div class="container-fluid">


            <div class="card mb-3 business-analytics">
                <div class="card-body">
                    <div class="row align-items-center mb-3 order_stats_header">
                        <div class="col-md-6">
                            <h4 class="mb-0">
                                {{ trans('lang.dashboard_business_analytics') }}
                            </h4>
                        </div>

                        <div class="col-md-6 d-flex justify-content-end">
                            <form method="GET"
                                  class="d-flex align-items-center gap-3 flex-wrap
              {{ request('range') === 'custom' ? 'custom-active' : '' }}">

                                <select name="range"
                                        class="form-select form-select-sm shadow-sm"
                                        style="width: 160px"
                                        onchange="this.form.submit()">

                                    <option value="">All Time</option>
                                    <option value="today" {{ request('range')=='today' ? 'selected' : '' }}>Today</option>
                                    <option value="week" {{ request('range')=='week' ? 'selected' : '' }}>This Week</option>
                                    <option value="month" {{ request('range')=='month' ? 'selected' : '' }}>This Month</option>
                                    <option value="year" {{ request('range')=='year' ? 'selected' : '' }}>This Year</option>
                                    <option value="custom" {{ request('range')=='custom' ? 'selected' : '' }}>Custom Range</option>
                                </select>

                                @if(request('range') === 'custom')
                                    <input type="date" name="from" value="{{ request('from') }}"
                                           class="form-control form-control-sm shadow-sm" required>

                                    <input type="date" name="to" value="{{ request('to') }}"
                                           class="form-control form-control-sm shadow-sm" required>

                                    <button class="btn btn-sm btn-dark px-3">Apply</button>
                                @endif

                            </form>
                        </div>
                    </div>

                </div>
                @unless($vendorExists)
                    <div class="alert alert-warning mb-3">
                        {{ __('Complete your restaurant profile to unlock dashboard insights.') }}
                    </div>
                @endunless

                <div class="row business-analytics_list">
                    <div class="col-sm-6 col-lg-4 mb-3">
                        <div class="card-box">
                            <h5>{{ trans('lang.dashboard_total_earnings') }}</h5>
                            <h2>{{ $stats['total_earnings_formatted'] }}</h2>
                            <i class="mdi mdi-cash-usd"></i>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4 mb-3">
                        <div class="card-box">
                            <h5>{{ trans('lang.dashboard_total_orders') }}</h5>
                            <h2>{{ $stats['total_orders'] }}</h2>
                            <i class="mdi mdi-cart"></i>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4 mb-3">
                        <div class="card-box">
                            <h5>{{ trans('lang.dashboard_total_products') }}</h5>
                            <h2>{{ $stats['total_products'] }}</h2>
                            <i class="mdi mdi-buffer"></i>
                        </div>
                    </div>

                    @php
                        $statusCards = [
                            'placed' => ['icon' => 'mdi-lan-pending', 'label' => trans('lang.dashboard_order_placed')],
                            'confirmed' => ['icon' => 'mdi-check-circle', 'label' => trans('lang.dashboard_order_confirmed')],

                            'completed' => ['icon' => 'mdi-check-circle-outline', 'label' => trans('lang.dashboard_order_completed')],
                            'rejected' => ['icon' => 'mdi-close-circle-outline', 'label' => trans('lang.dashboard_order_rejected')],

                            'canceled' => ['icon' => 'mdi-window-close', 'label' => trans('lang.dashboard_order_canceled')],
//                                'shipped' => ['icon' => 'mdi-clipboard-outline', 'label' => trans('lang.dashboard_order_shipped')],
//                                'failed' => ['icon' => 'mdi-alert-circle-outline', 'label' => trans('lang.dashboard_order_failed')],
//                                'pending' => ['icon' => 'mdi-car-connected', 'label' => trans('lang.dashboard_order_pending')],
                        ];
                    @endphp

                    @foreach($statusCards as $key => $card)
                        <div class="col-sm-6 col-lg-3">
                            <div class="order-status">
                                <div class="data">
                                    <i class="mdi {{ $card['icon'] }}"></i>
                                    <h6 class="status">{{ $card['label'] }}</h6>
                                </div>
                                <span class="count">{{ $statusCounts[$key] ?? 0 }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header no-border">
                        <div class="d-flex justify-content-between">
                            <h3 class="card-title">{{ trans('lang.total_sales') }}</h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="position-relative">
                            <canvas id="sales-chart" height="200"></canvas>
                        </div>
                        <div class="d-flex flex-row justify-content-end">
                                <span class="mr-2">
                                    <i class="fa fa-square" style="color:#2EC7D9"></i>
                                    {{ trans('lang.dashboard_this_year') }}
                                </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header no-border">
                        <div class="d-flex justify-content-between">
                            <h3 class="card-title">{{ trans('lang.service_overview') }}</h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="flex-row">
                            <canvas id="visitors" height="222"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header no-border">
                        <div class="d-flex justify-content-between">
                            <h3 class="card-title">{{ trans('lang.sales_overview') }}</h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="flex-row">
                            <canvas id="commissions" height="222"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row daes-sec-sec mb-3">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header no-border d-flex justify-content-between align-items-center">
                        <h3 class="card-title">{{ trans('lang.recent_orders') }}</h3>
                        <div class="card-tools">
                            <a href="{{ route('orders') }}" class="btn btn-tool btn-sm">{{ trans('lang.view_all') }}</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped table-valign-middle" id="orderTable">
                            <thead>
                            <tr>
                                <th>{{ trans('lang.order_id') }}</th>
                                <th>{{ trans('lang.order_user_id') }}</th>
                                <th>{{ trans('lang.order_type') }}</th>
                                <th>{{ __('Amount') }}</th>
                                <th>{{ trans('lang.quantity') }}</th>
                                <th>{{ trans('lang.order_date') }}</th>
                                <th>{{ trans('lang.order_order_status_id') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($recentOrders as $order)
                                <tr>
                                    <td><a href="{{ $order['url'] }}">{{ $order['id'] }}</a></td>
                                    <td>{{ $order['customer'] }}</td>
                                    <td>{{ $order['type'] }}</td>
                                    <td>{{ $order['grand_total'] }}</td>
                                    <td><i class="fa fa-shopping-cart mr-1"></i>{{ $order['products'] }}</td>
                                    <td>{{ $order['date'] }}</td>
                                    <td class="{{ $order['status_class'] }}"><span>{{ $order['status'] }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        {{ trans('lang.no_record_found') }}
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection


@section('scripts')
    <script src="{{ asset('js/chart.js') }}"></script>
    <script>
        const salesLabels = @json($charts['sales']['labels']);
        const salesData = @json($charts['sales']['data']);
        const visitorsData = @json([$charts['visitors']['orders'], $charts['visitors']['products']]);
        const commissionsData = @json($charts['commission']['data']);
        const currencySymbol = @json($currencyMeta['symbol']);

        const ticksStyle = {
            fontColor: '#495057',
            fontStyle: 'bold'
        };

        const mode = 'index';
        const intersect = true;

        new Chart(document.getElementById('sales-chart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: salesLabels,
                datasets: [{
                    backgroundColor: '#2EC7D9',
                    borderColor: '#2EC7D9',
                    data: salesData
                }]
            },
            options: {
                maintainAspectRatio: false,
                tooltips: { mode, intersect },
                hover: { mode, intersect },
                legend: { display: false },
                scales: {
                    yAxes: [{
                        gridLines: {
                            display: true,
                            lineWidth: '4px',
                            color: 'rgba(0, 0, 0, .2)',
                            zeroLineColor: 'transparent'
                        },
                        ticks: Object.assign({
                            beginAtZero: true,
                            callback: (value) => currencySymbol + Number(value).toFixed(0)
                        }, ticksStyle)
                    }],
                    xAxes: [{
                        display: true,
                        gridLines: { display: false },
                        ticks: ticksStyle
                    }]
                }
            }
        });

        new Chart(document.getElementById('visitors'), {
            type: 'doughnut',
            data: {
                labels: [
                    "{{ trans('lang.dashboard_total_orders') }}",
                    "{{ trans('lang.dashboard_total_products') }}"
                ],
                datasets: [{
                    data: visitorsData,
                    backgroundColor: ['#B1DB6F', '#7360ed'],
                    hoverOffset: 4
                }]
            },
            options: { maintainAspectRatio: false }
        });

        new Chart(document.getElementById('commissions'), {
            type: 'doughnut',
            data: {
                labels: @json($charts['commission']['labels']),
                datasets: [{
                    data: commissionsData,
                    backgroundColor: ['#feb84d', '#9b77f8', '#fe95d3'],
                    hoverOffset: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                tooltips: {
                    callbacks: {
                        label: (tooltipItems, data) => {
                            const dataset = data.datasets[tooltipItems.datasetIndex];
                            const value = dataset.data[tooltipItems.index] || 0;
                            return `${data.labels[tooltipItems.index]}: ${currencySymbol}${Number(value).toFixed(2)}`;
                        }
                    }
                }
            }
        });
    </script>

@endsection

