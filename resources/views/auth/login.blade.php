@extends('layouts.guest')

@section('title', 'Admin Login | SN Chit Funds')

@push('styles')
    @vite('resources/css/login.css')
@endpush

@section('content')

<div class="login-wrapper">

    <section class="login-card glass">

        <div class="login-glow"></div>

        <div class="login-glow-right"></div>


        <!-- =====================================================
             BRAND
        ====================================================== -->

        <div class="login-brand">

            <div class="login-logo logo-3d">
                <img
                    src="{{ asset('images/sn-chit-funds-logo.png') }}"
                    alt="SN Chit Funds"
                >
            </div>

            <div class="login-brand-name">
                <span>SN</span> Chit Funds
            </div>

            <div class="login-brand-tagline" data-i18n="brand_tagline">
                Trust · Growth · Together
            </div>

        </div>


        <!-- =====================================================
             HEADING
        ====================================================== -->

        <div class="login-heading">

            <div class="badge">

                <span class="badge-icon icon-3d icon-3d-orange">
                    <x-icon name="shield" />
                </span>

                <span data-i18n="loginBadge">
                    Admin Access
                </span>

            </div>


            <h1
                class="login-title"
                data-i18n="loginTitle"
            >
                Welcome Back
            </h1>


            <p
                class="login-description"
                data-i18n="loginDescription"
            >
                Sign in to manage customers, groups and payments.
            </p>

        </div>


        <!-- =====================================================
             ERRORS
        ====================================================== -->

        @if ($errors->any())

            <div class="login-alert" role="alert">
                {{ $errors->first() }}
            </div>

        @endif


        <!-- =====================================================
             FORM
        ====================================================== -->

        <form
            method="POST"
            action="{{ route('login.store') }}"
            class="login-form"
        >

            @csrf


            <!-- Username -->

            <div class="login-field">

                <label
                    for="username"
                    class="login-label"
                    data-i18n="usernameLabel"
                >
                    Username
                </label>

                <div class="login-input-wrap">

                    <span class="login-input-icon icon-3d icon-3d-orange">
                        <x-icon name="user" />
                    </span>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="login-input @error('username') has-error @enderror"
                        value="{{ old('username') }}"
                        placeholder="Enter your username"
                        data-i18n-placeholder="usernamePlaceholder"
                        autocomplete="username"
                        autocapitalize="none"
                        required
                        autofocus
                    >

                </div>

            </div>


            <!-- Password -->

            <div class="login-field">

                <label
                    for="password"
                    class="login-label"
                    data-i18n="passwordLabel"
                >
                    Password
                </label>

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


            <!-- Remember me -->

            <label class="login-remember">

                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                    @checked(old('remember'))
                >

                <span data-i18n="rememberMe">
                    Keep me signed in
                </span>

            </label>


            <!-- Submit -->

            <button
                type="submit"
                class="login-button"
            >
                <span data-i18n="loginButton">Sign In</span>
                <x-icon name="arrow-right" class="login-button-arrow" />
            </button>

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
