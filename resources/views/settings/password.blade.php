@extends('layouts.app')

@section('title', 'Change Password | SN Chit Funds')

@push('styles')
    @vite([
        'resources/css/create.css',
        'resources/css/password.css'
    ])
@endpush

@section('content')

<div class="page-wrapper password-page">


    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="hero hero-compact glass">

        <div class="hero-glow"></div>

        <div class="hero-glow-right"></div>


        <div class="hero-content">

            <div class="badge">

                <span class="badge-icon icon-3d icon-3d-purple">
                    <x-icon name="settings" />
                </span>

                <span data-i18n="settingsBadge">
                    Settings
                </span>

            </div>


            <h1
                class="hero-title"
                data-i18n="passwordTitle"
            >
                Change Password
            </h1>


            <p
                class="hero-description"
                data-i18n="passwordDescription"
            >
                Choose a new password for your admin account.
            </p>

        </div>

    </section>


    <!-- =====================================================
         FORM CARD
    ====================================================== -->

    <section class="form-card glass">

        <div class="form-inner">


            <!-- SUCCESS -->

            @if (session('success'))

                <div class="alert-success" role="status">
                    <span data-i18n="passwordChanged">{{ session('success') }}</span>
                </div>

            @endif


            <!-- ERRORS -->

            @if ($errors->any())

                <div class="alert-error" role="alert">

                    <strong data-i18n="errorTitle">
                        Please correct the following:
                    </strong>

                    <ul>

                        @foreach ($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif


            <!-- SIGNED IN AS -->

            <div class="info-box">

                <div class="info-icon icon-3d icon-3d-orange">
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 1)) }}
                </div>


                <div>

                    <div class="info-title">
                        {{ auth()->user()->name }}
                    </div>


                    <div class="info-text">
                        <span data-i18n="usernameLabel">Username</span>:
                        {{ auth()->user()->username }}
                    </div>

                </div>

            </div>


            <!-- =================================================
                 FORM
            ================================================== -->

            <form
                method="POST"
                action="{{ route('password.update') }}"
                class="customer-form"
            >

                @csrf
                @method('PUT')


                <!-- CURRENT PASSWORD -->

                <div class="field">

                    <div class="field-icon icon-3d icon-3d-orange">
                        <x-icon name="lock" />
                    </div>


                    <div class="field-content">

                        <label
                            class="field-label"
                            for="current_password"
                            data-i18n="currentPasswordLabel"
                        >
                            Current Password
                        </label>


                        <div class="password-input-wrap">

                            <input
                                id="current_password"
                                type="password"
                                name="current_password"
                                class="input"
                                placeholder="Enter your current password"
                                data-i18n-placeholder="currentPasswordPlaceholder"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                onclick="togglePasswordVisibility(this)"
                                aria-label="Show password"
                                aria-controls="current_password"
                            >
                                <x-icon name="eye" class="eye-show" />
                                <x-icon name="eye-off" class="eye-hide" />
                            </button>

                        </div>

                    </div>

                </div>


                <!-- NEW PASSWORD -->

                <div class="field">

                    <div class="field-icon icon-3d icon-3d-blue">
                        <x-icon name="key" />
                    </div>


                    <div class="field-content">

                        <label
                            class="field-label"
                            for="password"
                            data-i18n="newPasswordLabel"
                        >
                            New Password
                        </label>


                        <div class="password-input-wrap">

                            <input
                                id="password"
                                type="password"
                                name="password"
                                class="input"
                                placeholder="At least 8 characters"
                                data-i18n-placeholder="newPasswordPlaceholder"
                                autocomplete="new-password"
                                minlength="8"
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

                </div>


                <!-- CONFIRM PASSWORD -->

                <div class="field">

                    <div class="field-icon icon-3d icon-3d-purple">
                        <x-icon name="shield" />
                    </div>


                    <div class="field-content">

                        <label
                            class="field-label"
                            for="password_confirmation"
                            data-i18n="confirmPasswordLabel"
                        >
                            Confirm New Password
                        </label>


                        <div class="password-input-wrap">

                            <input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                class="input"
                                placeholder="Type the new password again"
                                data-i18n-placeholder="confirmPasswordPlaceholder"
                                autocomplete="new-password"
                                minlength="8"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                onclick="togglePasswordVisibility(this)"
                                aria-label="Show password"
                                aria-controls="password_confirmation"
                            >
                                <x-icon name="eye" class="eye-show" />
                                <x-icon name="eye-off" class="eye-hide" />
                            </button>

                        </div>

                    </div>

                </div>


                <!-- FOOTER -->

                <div class="form-footer">

                    <a
                        href="{{ route('customers.index') }}"
                        class="cancel-button"
                    >
                        <x-icon name="x" />
                        <span data-i18n="cancelText">Cancel</span>
                    </a>


                    <button
                        type="submit"
                        class="save-button"
                    >
                        <x-icon name="save" />
                        <span data-i18n="savePasswordText">Save Password</span>
                    </button>

                </div>

            </form>

        </div>

    </section>

</div>

@endsection


@push('scripts')
    @vite('resources/js/password-toggle.js')
@endpush
