@extends('layouts.portal')

@section('title', 'Loan Plans')

@push('styles')
    @vite('resources/css/finance.css')
@endpush

@push('scripts')
    @vite('resources/js/finance.js')
@endpush

@section('content')

<div class="groups-list-page portal-page">

    <div class="groups-list-header">
        <div>
            @include('portal.partials.back')
            <h1 class="groups-title" data-i18n="loan_plans">Loan Plans</h1>
            <p class="groups-subtitle" data-i18n="loan_plans_subtitle">Small loans repaid every day or every week. See what you receive and what you repay, then call the office to apply.</p>
        </div>
        <div class="ledger-actions">
            @include('portal.partials.call-office')
        </div>
    </div>

    <div class="portal-grid">
        @foreach ($plans as $plan)
            @include('portal.partials.plan-card')
        @endforeach
    </div>


    {{-- calculator: the same working-out as the office uses --}}
    <section class="group-panel glass payment-step" id="loanCalculator"
        data-fee="{{ $rates['fee'] }}" data-gst="{{ $rates['gst'] }}" data-interest="{{ $rates['interest'] }}"
        data-daily="{{ $rates['installments']['daily'] }}" data-weekly="{{ $rates['installments']['weekly'] }}">

        <div class="group-panel-header">
            <h2 data-i18n="loan_calculator">Loan calculator</h2>
        </div>

        <div class="group-form-grid payment-fields">
            <div class="group-field">
                <label class="field-label" for="calcAmount" data-i18n="loan_amount_rupees">Loan amount (₹)</label>
                <input type="text" id="calcAmount" class="input money-input" inputmode="numeric" value="10000" autocomplete="off">
            </div>
            <div class="group-field">
                <span class="field-label" data-i18n="repayment_word">Repayment</span>
                <div class="method-options">
                    <label class="method-option"><input type="radio" name="calcFrequency" value="daily" checked> <span data-i18n="frequency_daily">Daily</span></label>
                    <label class="method-option"><input type="radio" name="calcFrequency" value="weekly"> <span data-i18n="frequency_weekly">Weekly</span></label>
                </div>
            </div>
        </div>

        <div class="finance-breakdown" aria-live="polite">
            <span><small data-i18n="fee_and_gst">Fee + GST</small><strong id="calcCharges">—</strong></span>
            <span><small data-i18n="you_receive">You receive</small><strong class="finance-in-hand" id="calcInHand">—</strong></span>
            <span><small data-i18n="interest_word">Interest</small><strong id="calcInterest">—</strong></span>
            <span><small data-i18n="total_to_repay">Total to repay</small><strong id="calcTotal">—</strong></span>
            <span><small data-i18n="installment_word">Instalment</small><strong id="calcInstallment">—</strong></span>
        </div>

        <p class="portal-card-note" data-i18n="calculator_note">An estimate. The office confirms your loan before you sign.</p>

    </section>

</div>

@endsection
