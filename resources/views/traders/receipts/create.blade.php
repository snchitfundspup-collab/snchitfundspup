@extends('layouts.app')

@section('title', 'Receive Payment | SN Traders')

@push('styles')
    @vite([
        'resources/css/create.css',
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/traders.js')
@endpush

@php
    $owing = $customers->keyBy(fn ($row) => $row['customer']->id);
@endphp

@section('content')

<div class="group-show-page payments-page traders-page">

    @include('traders.partials.flash')


    <section class="group-hero glass">

        <a href="{{ route('traders.balances.index') }}" class="group-back-link">
            <x-icon name="arrow-left" />
            <span data-i18n="customer_balances">Customer Balances</span>
        </a>

        <div class="group-hero-main">
            <span class="group-hero-icon icon-3d icon-3d-orange">
                <x-icon name="rupee" />
            </span>
            <div class="group-hero-text">
                <h1 class="group-hero-title" data-i18n="receive_payment">Receive Payment</h1>
                <p class="group-hero-meta" data-i18n="receive_payment_subtitle">Money a customer pays against their rice credit.</p>
            </div>
        </div>

    </section>


    <section class="group-panel glass payment-step">

        <form method="POST" action="{{ route('traders.receipts.store') }}" class="payment-form">

            @csrf

            <div class="group-form-grid payment-fields">

                <div class="group-field group-field-full">
                    <label class="field-label" for="customer_id" data-i18n="customer_word">Customer</label>
                    <input type="search" class="input picker-filter" placeholder="Type to find the customer" data-filter-select="customer_id" autocomplete="off">
                    <select id="customer_id" name="customer_id" class="input" required>
                        <option value="">Choose customer…</option>
                        @if ($owing->isNotEmpty())
                            <optgroup label="Customers who owe">
                                @foreach ($owing as $row)
                                    <option value="{{ $row['customer']->id }}" @selected((int) old('customer_id', $selectedCustomer?->id) === $row['customer']->id)>
                                        {{ $row['customer']->name }}{{ $row['customer']->remarks ? ' ('.$row['customer']->remarks.')' : '' }} · {{ $row['customer']->customer_code }} — owes ₹{{ number_format($row['balance'], 2) }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                        <optgroup label="All customers">
                            @foreach ($allCustomers as $customerOption)
                                @continue($owing->has($customerOption->id))
                                <option value="{{ $customerOption->id }}" @selected((int) old('customer_id', $selectedCustomer?->id) === $customerOption->id)>
                                    {{ $customerOption->name }}{{ $customerOption->remarks ? ' ('.$customerOption->remarks.')' : '' }} · {{ $customerOption->customer_code }}
                                </option>
                            @endforeach
                        </optgroup>
                    </select>
                    @if ($selectedCustomer)
                        <small class="installment-hint">
                            @if ($customerBalance > 0)
                                <span class="due-pending"><span data-i18n="owes_word">Owes</span> <x-rupees :amount="$customerBalance" /></span>
                            @elseif ($customerBalance < 0)
                                <span data-i18n="advance_word">Advance</span> <x-rupees :amount="-$customerBalance" />
                            @else
                                <span data-i18n="nothing_owed">Nothing owed</span>
                            @endif
                        </small>
                    @endif
                </div>

                <div class="group-field">
                    <label class="field-label" for="amount" data-i18n="amount_rupees">Amount (₹)</label>
                    <input type="text" id="amount" name="amount" class="input money-input payment-amount-input" inputmode="decimal" required autocomplete="off"
                        value="{{ old('amount', $customerBalance > 0 ? number_format($customerBalance, 2, '.', '') : '') }}" placeholder="Enter amount">
                </div>

                <div class="group-field">
                    <label class="field-label" for="received_at" data-i18n="payment_date_time">Date &amp; time</label>
                    <input type="datetime-local" id="received_at" name="received_at" class="input" required
                        value="{{ old('received_at') ? str_replace(' ', 'T', old('received_at')) : now(config('app.business_timezone'))->format('Y-m-d\TH:i') }}"
                        max="{{ now(config('app.business_timezone'))->format('Y-m-d\TH:i') }}">
                </div>

                <div class="group-field">
                    <span class="field-label" data-i18n="payment_method">Payment method</span>
                    <div class="method-options">
                        @foreach (\App\Models\Payment::METHODS as $methodValue => $methodLabel)
                            <label class="method-option">
                                <input type="radio" name="method" value="{{ $methodValue }}" @checked(old('method', 'cash') === $methodValue)>
                                <span data-i18n="method_{{ $methodValue }}">{{ $methodLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="group-field">
                    <label class="field-label" for="reference" data-i18n="reference_number">Reference / UPI / cheque no.</label>
                    <input type="text" id="reference" name="reference" class="input" maxlength="100" value="{{ old('reference') }}">
                </div>

                <div class="group-field group-field-full">
                    <label class="field-label" for="notes" data-i18n="notes">Notes</label>
                    <input type="text" id="notes" name="notes" class="input" maxlength="1000" value="{{ old('notes') }}">
                </div>

            </div>

            <div class="form-footer payment-form-footer">
                <button type="submit" class="save-button">
                    <x-icon name="save" />
                    <span data-i18n="save_payment">Save &amp; get receipt</span>
                </button>
            </div>

        </form>

    </section>

</div>

@endsection
