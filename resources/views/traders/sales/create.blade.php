@extends('layouts.app')

@section('title', 'New Sale | SN Traders')

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

@section('content')

<div class="group-show-page payments-page traders-page">

    @include('traders.partials.flash')


    <section class="group-hero glass">

        <a href="{{ route('traders.sales.index') }}" class="group-back-link">
            <x-icon name="arrow-left" />
            <span data-i18n="all_sales">All Sales</span>
        </a>

        <div class="group-hero-main">
            <span class="group-hero-icon icon-3d icon-3d-green">
                <x-icon name="rupee" />
            </span>
            <div class="group-hero-text">
                <h1 class="group-hero-title" data-i18n="new_sale">New Sale</h1>
                <p class="group-hero-meta" data-i18n="new_sale_subtitle">Rice sold to a customer. Anything not paid now goes on their credit.</p>
            </div>
        </div>

    </section>


    <section class="group-panel glass payment-step">

        @if ($varieties->isEmpty())

            <p class="partial-only-note">
                <x-icon name="info" />
                <span>
                    <span data-i18n="sale_needs_varieties">Add your rice varieties first.</span>
                    <a href="{{ route('traders.varieties.index') }}" class="statement-receipt-link" data-i18n="rice_varieties">Rice Varieties</a>
                </span>
            </p>

        @else

        <form method="POST" action="{{ route('traders.sales.store') }}" class="payment-form">

            @csrf

            <div class="group-form-grid payment-fields">

                <div class="group-field">
                    <label class="field-label" for="customer_id" data-i18n="customer_word">Customer</label>
                    <input type="search" class="input picker-filter" placeholder="Type to find the customer" data-filter-select="customer_id" data-i18n-placeholder="type_to_find_customer" autocomplete="off">
                    <select id="customer_id" name="customer_id" class="input" required>
                        <option value="">Choose customer…</option>
                        @foreach ($customers as $customerOption)
                            <option value="{{ $customerOption->id }}" @selected((int) old('customer_id', $selectedCustomer?->id) === $customerOption->id)>
                                {{ $customerOption->name }}{{ $customerOption->remarks ? ' ('.$customerOption->remarks.')' : '' }} · {{ $customerOption->customer_code }}{{ $customerOption->phone ? ' · '.$customerOption->phone : '' }}
                            </option>
                        @endforeach
                    </select>
                    @if ($selectedCustomer && $customerBalance > 0)
                        <small class="installment-hint is-error">
                            <span data-i18n="already_owes">Already owes</span> <x-rupees :amount="$customerBalance" />
                        </small>
                    @endif
                    <a href="{{ route('customers.create') }}" class="statement-receipt-link" data-i18n="add_new_customer">+ Add a new customer</a>
                </div>

                <div class="group-field">
                    <label class="field-label" for="sold_on" data-i18n="expense_date">Date</label>
                    <input type="date" id="sold_on" name="sold_on" class="input" required
                        value="{{ old('sold_on', today(config('app.business_timezone'))->toDateString()) }}"
                        max="{{ today(config('app.business_timezone'))->toDateString() }}">
                </div>

            </div>


            <h2 class="member-groups-title" data-i18n="rice_sold">Rice sold</h2>

            @if ($order)
                <input type="hidden" name="order_id" value="{{ old('order_id', $order->id) }}">
                <p class="partial-only-note">
                    <x-icon name="info" />
                    <span>
                        <span data-i18n="from_customer_order">From the customer's order</span>
                        <strong>{{ $order->order_number }}</strong> ({{ $order->created_at->timezone(config('app.business_timezone'))->format('d M, h:i A') }}).
                        <span data-i18n="order_prices_today">Prices are today's; change anything before saving.</span>
                        @if ($order->notes)
                            <br><span data-i18n="customer_note">Customer's note:</span> “{{ $order->notes }}”
                        @endif
                    </span>
                </p>
            @endif

            @include('traders.partials.bill-lines', ['isSale' => true, 'prefillLines' => $orderLines])


            <h2 class="member-groups-title" data-i18n="received_now">Received now</h2>

            <div class="group-form-grid payment-fields">

                <div class="group-field">
                    <label class="field-label" for="received_amount" data-i18n="amount_rupees">Amount (₹)</label>
                    <div class="received-row">
                        <input type="text" id="received_amount" name="received_amount" class="input money-input payment-amount-input" inputmode="decimal" value="{{ old('received_amount') }}" placeholder="0" autocomplete="off">
                        <button type="button" class="group-action" id="receivedFull" data-i18n="full_word">Full</button>
                    </div>
                    <small class="installment-hint" id="receivedHint"></small>
                </div>

                <div class="group-field">
                    <span class="field-label" data-i18n="payment_method">Payment method</span>
                    <div class="method-options">
                        @foreach (\App\Models\Payment::METHODS as $methodValue => $methodLabel)
                            <label class="method-option">
                                <input type="radio" name="received_method" value="{{ $methodValue }}" @checked(old('received_method', 'cash') === $methodValue)>
                                <span data-i18n="method_{{ $methodValue }}">{{ $methodLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="group-field">
                    <label class="field-label" for="received_reference" data-i18n="reference_number">Reference / UPI / cheque no.</label>
                    <input type="text" id="received_reference" name="received_reference" class="input" maxlength="100" value="{{ old('received_reference') }}">
                </div>

                <div class="group-field">
                    <label class="field-label" for="notes" data-i18n="notes">Notes</label>
                    <input type="text" id="notes" name="notes" class="input" maxlength="1000" value="{{ old('notes') }}">
                </div>

            </div>

            <div class="form-footer payment-form-footer">
                <button type="submit" class="save-button">
                    <x-icon name="save" />
                    <span data-i18n="save_invoice">Save &amp; get invoice</span>
                </button>
            </div>

        </form>

        @endif

    </section>

</div>

@endsection
