@extends('layouts.guest')

@section('title', 'Customer Login | SN Chit Funds & SN Traders')

@push('styles')
    @vite('resources/css/login.css')
@endpush

@section('content')

<div class="login-wrapper">

    <section class="login-card glass">

        <div class="login-glow"></div>

        <div class="login-glow-right"></div>


        {{-- BRAND --}}

        <div class="login-brand">

            <div class="login-logo logo-3d">
                <img src="{{ asset('images/sn-chit-funds-logo.png') }}" alt="SN">
            </div>

            <div class="login-brand-name">
                <span>SN</span> Chit Funds &amp; Traders
            </div>

            <div class="login-brand-tagline" data-i18n="brand_tagline">
                Trust · Growth · Together
            </div>

        </div>


        {{-- HEADING --}}

        <div class="login-heading">

            <div class="badge">
                <span class="badge-icon icon-3d icon-3d-blue">
                    <x-icon name="user" />
                </span>
                <span data-i18n="customer_login">Customer login</span>
            </div>

            <h1 class="login-title" data-i18n="portal_welcome">Welcome</h1>

            <p class="login-description" data-i18n="portal_login_description">
                See your chit groups, payments and receipts, and order rice.
            </p>

        </div>


        @if ($errors->any())
            <div class="login-alert" role="alert">
                {{ $errors->first() }}
            </div>
        @endif


        {{-- FORM --}}

        <form method="POST" action="{{ route('portal.login.store') }}" class="login-form">

            @csrf

            <div class="login-field">

                <label for="phone" class="login-label" data-i18n="phone_number">Phone number</label>

                <div class="login-input-wrap">
                    <span class="login-input-icon icon-3d icon-3d-orange">
                        <x-icon name="phone" />
                    </span>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        class="login-input @error('phone') has-error @enderror"
                        value="{{ old('phone') }}"
                        placeholder="10-digit mobile number"
                        data-i18n-placeholder="phone_placeholder"
                        autocomplete="tel"
                        inputmode="tel"
                        required
                        autofocus
                    >
                </div>

            </div>

            <div class="login-field">

                <label for="password" class="login-label" data-i18n="passwordLabel">Password</label>

                <div class="login-input-wrap">
                    <span class="login-input-icon icon-3d icon-3d-blue">
                        <x-icon name="lock" />
                    </span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="login-input login-input-password"
                        placeholder="Enter your password"
                        data-i18n-placeholder="passwordPlaceholder"
                        autocomplete="current-password"
                        required
                    >
                    <button
                        type="button"
                        class="password-toggle"
                        onclick="togglePasswordVisibility(this)"
                        aria-label="Show password"
                        aria-controls="password"
                    >
                        <x-icon name="eye" class="eye-show" />
                        <x-icon name="eye-off" class="eye-hide" />
                    </button>
                </div>

            </div>

            <button type="submit" class="login-button">
                <span data-i18n="loginButton">Sign In</span>
                <x-icon name="arrow-right" class="login-button-arrow" />
            </button>

            <p class="login-help" data-i18n="portal_login_help">
                First time? Use the password given by the office — you will then choose your own.
            </p>

            <p class="login-help login-forgot">
                <span data-i18n="forgot_password_call">Forgot your password? Call the office to reset it:</span>
                <a href="tel:{{ preg_replace('/[^\d+]/', '', (string) config('app.office_phone')) }}" class="login-call-link">
                    <x-icon name="phone" />
                    {{ config('app.office_phone') }}
                </a>
            </p>

        </form>

    </section>


    <p class="login-footnote">
        <span class="footer-secure">
            <span class="footer-dot">•</span>
            <span data-i18n="footer_secure">Secure</span>
        </span>
        ·
        <span data-i18n="footer_reliable">Reliable</span>
        ·
        <span data-i18n="footer_always">Always With You</span>
    </p>

</div>

@endsection


@push('scripts')
    @vite('resources/js/password-toggle.js')
@endpush
