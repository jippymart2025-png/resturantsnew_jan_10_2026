@include('auth.default')
@php
    $countries = $countries ?? [];

    if (empty($countries)) {
        $countriesPath = public_path('countriesdata.json');
        if (file_exists($countriesPath)) {
            $rawCountries = file_get_contents($countriesPath) ?: '[]';
            $decodedCountries = json_decode($rawCountries, true) ?? [];
            foreach ($decodedCountries as $country) {
                $phoneCode = (string) ($country['phoneCode'] ?? '');
                if ($phoneCode === '') {
                    continue;
                }
                $countries[$phoneCode] = [
                    'phoneCode' => $country['phoneCode'],
                    'countryName' => $country['countryName'] ?? $country['name'] ?? '',
                    'code' => $country['code'] ?? '',
                ];
            }
        }
    }

    $selectedCountryCode = old('country_code', $selectedCountryCode ?? '91');
    $phone = old('phone', $phone ?? '');
    $prefill = $prefill ?? [
        'firstName' => old('first_name'),
        'lastName' => old('last_name'),
        'email' => old('email'),
    ];
    $loginType = $loginType ?? old('loginType', 'email');
    $uuid = old('uuid', $uuid ?? null);
    $photoURL = old('photoURL', $photoURL ?? null);
    $createdAt = old('createdAt', $createdAt ?? null);
    $isSocialLogin = in_array(strtolower($loginType), ['social', 'google', 'facebook', 'github'], true);
@endphp
<div class="container">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center text-center">
            <h3 class="text-themecolor">{{ trans('lang.sign_up_with_us') }}</h3>
        </div>
        <div class="card-body">
            <div id="data-table_processing" class="page-overlay" style="display:none;">
                <div class="overlay-text">
                    <img src="{{ asset('images/spinner.gif') }}">
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('signup.store') }}" id="signup-form">
                @csrf
                <input type="hidden" name="loginType" value="{{ $loginType }}">
                <input type="hidden" name="uuid" value="{{ $uuid }}">
                <input type="hidden" name="photoURL" value="{{ $photoURL }}">
                <input type="hidden" name="createdAt" value="{{ $createdAt }}">

                <div class="row restaurant_payout_create">
                    <div class="restaurant_payout_create-inner">
                        <fieldset class="form-material">
                            <legend>{{ trans('lang.owner_details') }}</legend>
                            <div class="form-group row width-50">
                                <label class="col-3 control-label">{{ trans('lang.first_name') }}</label>
                                <div class="col-7">
                                    <input type="text"
                                           class="form-control"
                                           name="first_name"
                                           value="{{ old('first_name', $prefill['firstName']) }}"
                                           placeholder="{{ trans('lang.user_first_name_help') }}"
                                           required>
                                </div>
                            </div>
                            <div class="form-group row width-50">
                                <label class="col-3 control-label">{{ trans('lang.last_name') }}</label>
                                <div class="col-7">
                                    <input type="text"
                                           class="form-control"
                                           name="last_name"
                                           value="{{ old('last_name', $prefill['lastName']) }}"
                                           placeholder="{{ trans('lang.user_last_name_help') }}">
                                </div>
                            </div>
                            <div class="form-group row width-50">
                                <label class="col-3 control-label">{{ trans('lang.email') }}</label>
                                <div class="col-7">
                                    <input type="email"
                                           class="form-control"
                                           name="email"
                                           value="{{ old('email', $prefill['email']) }}"
                                           placeholder="{{ trans('lang.user_email_help') }}"
                                           @if ($isSocialLogin) readonly @endif
                                           required>
                                </div>
                            </div>
                            <div class="form-group row width-50">
                                <label class="col-3 control-label">{{ trans('lang.password') }}</label>
                                <div class="col-7">
                                    <input type="password"
                                           class="form-control"
                                           name="password"
                                           placeholder="{{ trans('lang.user_password_help') }}"
                                           required>
                                </div>
                            </div>
                            <div class="form-group form-material row width-50">
                                <label class="col-3 control-label">{{ trans('lang.user_phone') }}</label>
                                <div class="col-12">
                                    <div class="phone-box position-relative" id="phone-box">
                                        <select name="country_code" id="country_selector" class="form-control">
                                            @foreach ($countries as $code => $country)
                                                <option value="{{ $code }}"
                                                        data-flag="{{ strtolower($country['code'] ?? '') }}"
                                                        @if ($selectedCountryCode == $code) selected @endif>
                                                    +{{ $country['phoneCode'] }} {{ $country['countryName'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <input class="form-control mt-2"
                                               id="phone"
                                               type="text"
                                               name="phone"
                                               value="{{ $phone }}"
                                               placeholder="{{ trans('lang.user_phone') }}"
                                               autocomplete="phone"
                                               required>
                                        <div id="error2" class="err text-danger small mt-1"></div>
                                    </div>
                                    @error('country_code')
                                        <span class="text-danger small d-block">{{ $message }}</span>
                                    @enderror
                                    @error('phone')
                                        <span class="text-danger small d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>

                <div class="form-group col-12 text-center">
                    <button type="submit" class="btn btn-primary create_restaurant_btn">
                        <i class="fa fa-save"></i> {{ trans('lang.save') }}
                    </button>
                    <div class="or-line mb-4 ">
                        <span>OR</span>
                    </div>
                    <a href="{{ route('login') }}">
                        <p class="text-center m-0">{{ trans('lang.already_an_account') }} {{ trans('lang.sign_in') }}</p>
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('assets/plugins/select2/dist/css/select2.min.css') }}">
<style>
    .img-flag {
        width: 20px;
        height: 14px;
        margin-right: 6px;
        object-fit: cover;
    }
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="{{ asset('assets/plugins/select2/dist/js/select2.min.js') }}"></script>
<script>
    $(document).ready(function () {
        const flagBase = "{{ asset('flags/120') }}";

        function formatState(state) {
            if (!state.id) {
                return state.text;
            }
            const flag = $(state.element).data('flag');
            if (!flag) {
                return state.text;
            }
            return $('<span><img src="' + flagBase + '/' + flag + '.png" class="img-flag" /> ' + state.text + '</span>');
        }

        $('#country_selector').select2({
            templateResult: formatState,
            templateSelection: formatState,
            placeholder: "{{ __('Select Country') }}"
        });

        $('#phone').on('keypress', function (event) {
            if (event.which < 48 || event.which > 57) {
                $('#error2').text("{{ __('Accept only numbers') }}");
                return false;
            }
            $('#error2').text('');
            return true;
        });

        $('#signup-form').on('submit', function () {
            $('#data-table_processing').show();
        });
    });
</script>
