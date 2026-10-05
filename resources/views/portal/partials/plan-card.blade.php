{{-- A loan on offer (daily or weekly) with a ₹10,000 example.
     $plan: frequency, example (FinanceLoan, not saved) --}}

@php
    $example = $plan['example'];
    $weekly = $plan['frequency'] === \App\Models\FinanceLoan::WEEKLY;
@endphp

<div class="portal-card glass plan-card">

    <div class="portal-card-top">
        <span @class(['portal-card-icon', 'icon-3d', 'icon-3d-orange' => ! $weekly, 'icon-3d-purple' => $weekly])><x-icon name="calendar" /></span>
        <div class="portal-card-title">
            <strong data-i18n="{{ $weekly ? 'weekly_loan' : 'daily_loan' }}">{{ $weekly ? 'Weekly loan' : 'Daily loan' }}</strong>
            <small>
                {{ $example->installments }} <span data-i18n="{{ $weekly ? 'weeks_word' : 'days_word' }}">{{ $weekly ? 'weeks' : 'days' }}</span>
                · {{ \App\Models\FinanceLoan::rate($example->interest_rate) }} <span data-i18n="per_year">a year</span>
            </small>
        </div>
    </div>

    <p class="portal-card-note"><span data-i18n="example_loan">Example: loan of</span> <x-rupees :amount="$example->principal" /></p>

    <div class="portal-card-facts">
        <span>
            <small data-i18n="you_receive">You receive</small>
            <strong class="ledger-total-paid"><x-rupees :amount="$example->amountGiven()" /></strong>
        </span>
        <span>
            <small data-i18n="total_to_repay">Total to repay</small>
            <strong><x-rupees :amount="$example->loan_amount" /></strong>
        </span>
        <span>
            <small data-i18n="{{ $weekly ? 'per_week' : 'per_day' }}">{{ $weekly ? 'Per week' : 'Per day' }}</small>
            <strong><x-rupees :amount="$example->installment_amount" /></strong>
        </span>
    </div>

    <p class="portal-card-note">
        <span data-i18n="processing_fee">Processing fee</span> {{ \App\Models\FinanceLoan::rate($example->processing_fee_rate) }}
        + <span data-i18n="gst_word">GST</span> {{ \App\Models\FinanceLoan::rate($example->gst_rate) }}
        · <span data-i18n="no_collateral">No collateral</span>
    </p>

</div>
