@extends('layouts.front-end.app')

@section('title', translate('sign_in'))

@push('css_or_js')
    <link rel="stylesheet"
          href="{{ theme_asset(path: 'public/assets/front-end/plugin/intl-tel-input/css/intlTelInput.css') }}">
@endpush

@section('content')
<div class="container py-4 py-lg-5 my-4 text-align-direction">
        <div class="row justify-content-center">
            <div class="col-md-6 login-card">
                <div class="d-flex justify-content-center align-items-center flex-column">
                    <img src="{{ theme_asset(path: 'public/assets/front-end/img/icons/user-vector.svg') }}"
                         alt="" class="w-70px">
                    <h2 class="text-center font-bold text-capitalize fs-20 my-4 fs-18-mobile">
                        {{ translate('Sign_In') }}
                    </h2>
                </div>
                <div class="position-relative">
                    <div class="row justify-content-center align-items-center g-4 ">
                            <div class="col-md-12">
                                <form autocomplete="off"
                                    action="{{ route('customer.auth.login') }}"
                                    method="post"
                                    data-recaptcha="skip"
                                    class="customer-centralize-login-form"
                                    data-firebase-auth="{{ $web_config['firebase_otp_verification_status'] ? 'active': 'deactivate' }}"
                                >
                                    @csrf
                                    <input type="hidden" name="login_type" value="otp-login">
                                    @include("web-views.customer-views.auth.partials._email")

                                    <button class="btn btn--primary btn-block btn-shadow font-semi-bold" type="submit">
                                        {{ translate('Get_OTP') }}
                                    </button>
                                </form>
                            </div>

                     
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection