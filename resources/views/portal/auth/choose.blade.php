@extends('layouts.guest')

@section('title', 'Choose your account | SN Chit Funds & SN Traders')

@push('styles')
    @vite('resources/css/login.css')
@endpush

@section('content')

<div class="login-wrapper">

    <section class="login-card glass">

        <div class="login-heading">

            <div class="badge">
                <span class="badge-icon icon-3d icon-3d-blue">
                    <x-icon name="users" />
                </span>
                <span data-i18n="choose_account">Choose your account</span>
            </div>

            <h1 class="login-title" data-i18n="who_is_signing_in">Who is signing in?</h1>

            <p class="login-description" data-i18n="shared_phone_note">
                This phone number is used by more than one customer.
            </p>

        </div>

        <form method="POST" action="{{ route('portal.choose.store') }}" class="login-form choose-account-list">

            @csrf

            @foreach ($customers as $customer)
                <button type="submit" name="customer" value="{{ $customer->id }}" class="choose-account-button">
                    <x-customer-avatar :customer="$customer" />
                    <span class="choose-account-text">
                        <strong><x-customer-name :customer="$customer" /></strong>
                        <small>{{ $customer->customer_code }}</small>
                    </span>
                    <x-icon name="chevron-right" />
                </button>
            @endforeach

        </form>

    </section>

    <p class="login-footnote">
        <a href="{{ route('portal.login') }}" class="login-switch-link" data-i18n="back_to_login">Back to login</a>
    </p>

</div>

@endsection
