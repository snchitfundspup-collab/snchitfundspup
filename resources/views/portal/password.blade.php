@extends('layouts.portal')

@section('title', 'Change Password')

@push('styles')
    @vite([
        'resources/css/create.css',
        'resources/css/password.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/password-toggle.js')
@endpush

@php
    $fields = [
        ['password', 'newPasswordLabel', 'New Password', 'new-password', 'blue'],
        ['password_confirmation', 'confirmPasswordLabel', 'Confirm New Password', 'new-password', 'purple'],
    ];
@endphp

@section('content')

<div class="page-wrapper password-page">

    <section class="hero hero-compact glass">
        <div class="hero-glow"></div>
        <div class="hero-glow-right"></div>
        <div class="hero-content">
            @unless ($customer->mustChangePassword())
                @include('portal.partials.back')
            @endunless
            <h1 class="hero-title" data-i18n="passwordTitle">Change Password</h1>
            <p class="hero-description" data-i18n="portal_password_description">
                Choose your own password. You sign in with your phone number and this password.
            </p>
        </div>
    </section>

    <section class="form-card glass">
        <div class="form-inner">

            @if ($errors->any())
                <div class="alert-error" role="alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($customer->mustChangePassword())
                <div class="portal-password-required" role="alert">
                    <x-icon name="lock" />
                    <div>
                        <strong data-i18n="choose_password_first">Please choose your own password to continue.</strong>
                        <span data-i18n="choose_password_why">For your safety, the password given by the office works only once. Choose a new one (not snchitfunds).</span>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('portal.password.update') }}" class="customer-form">

                @csrf
                @method('PUT')

                @foreach ($fields as [$name, $labelKey, $label, $autocomplete, $colour])
                    <div class="field">
                        <div class="field-icon icon-3d icon-3d-{{ $colour }}">
                            <x-icon name="lock" />
                        </div>
                        <div class="field-content">
                            <label class="field-label" for="{{ $name }}" data-i18n="{{ $labelKey }}">{{ $label }}</label>
                            <div class="password-input-wrap">
                                <input
                                    id="{{ $name }}"
                                    type="password"
                                    name="{{ $name }}"
                                    class="input"
                                    autocomplete="{{ $autocomplete }}"
                                    minlength="6"
                                    required
                                >
                                <button
                                    type="button"
                                    class="password-toggle"
                                    onclick="togglePasswordVisibility(this)"
                                    aria-label="Show password"
                                    aria-controls="{{ $name }}"
                                >
                                    <x-icon name="eye" class="eye-show" />
                                    <x-icon name="eye-off" class="eye-hide" />
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="form-footer">
                    <button type="submit" class="save-button">
                        <x-icon name="save" />
                        <span data-i18n="savePasswordText">Save Password</span>
                    </button>
                </div>

            </form>

        </div>
    </section>

</div>

@endsection
