@extends('layouts.app')
@section('content')
    <div class="page-wrapper">
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">{{ trans('lang.subscription_list') }}</h3>
            </div>
            <div class="col-md-7 align-self-center">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">{{ trans('lang.dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('lang.subscription_list') }}</li>
                </ol>
            </div>
            <div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive m-t-10">
                                <table id="example24"
                                    class="display nowrap table table-hover table-striped table-bordered table table-striped"
                                    cellspacing="0" width="100%">
                                    <thead>
                                        <tr>
                                            <th>{{ trans('lang.image') }}</th>
                                            <th>{{ trans('lang.plan_name') }}</th>
                                            <th>{{ trans('lang.price') }}</th>
                                            <th>{{ trans('lang.payment_method') }}</th>
                                            <th>{{ __('Zone') }}</th>
                                            <th>{{ trans('lang.active_at') }}</th>
                                            <th>{{ trans('lang.expire_at') }}</th>
                                            <th>{{ trans('lang.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="append_list1">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>
@endsection
@section('scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            $(document.body).on('click', '.redirecttopage', function() {
                var url = $(this).attr('data-url');
                window.location.href = url;
            });
            
            jQuery("#data-table_processing").show();
            
            const table = $('#example24').DataTable({
                pageLength: 10,
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('my-subscriptions.data') }}",
                    type: 'GET',
                    timeout: 30000, // 30 second timeout
                    data: function(d) {
                        // DataTables will automatically send start, length, search, order, etc.
                    },
                    error: function(xhr, error, thrown) {
                        console.error("Error fetching subscription history:", error, xhr);
                        console.error("Response:", xhr.responseText);
                        jQuery('#data-table_processing').hide();
                        if (xhr.status === 0) {
                            alert('Request timeout. Please check your connection and try again.');
                            } else {
                            alert('Error loading subscription history. Please refresh the page.');
                            }
                    }
                },
                order: [
                    [5, 'desc']
                ],
                columnDefs: [{
                        targets: 5,
                        type: 'date',
                        render: function(data) {
                            return data;
                        }
                }, {
                        orderable: false,
                        targets: [0, 7]
                }],
                "language": {
                    "zeroRecords": "{{ trans('lang.no_record_found') }}",
                    "emptyTable": "{{ trans('lang.no_record_found') }}"
                },
                drawCallback: function() {
                    jQuery('#data-table_processing').hide();
                }
            });
        });
    </script>
@endsection
