@extends('layouts.app')

@section('title', 'New Purchase | SN Traders')

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

        <a href="{{ route('traders.purchases.index') }}" class="group-back-link">
            <x-icon name="arrow-left" />
            <span data-i18n="all_purchases">All Purchases</span>
        </a>

        <div class="group-hero-main">
            <span class="group-hero-icon icon-3d icon-3d-blue">
                <x-icon name="package" />
            </span>
            <div class="group-hero-text">
                <h1 class="group-hero-title" data-i18n="new_purchase">New Purchase</h1>
                <p class="group-hero-meta" data-i18n="new_purchase_subtitle">Rice bought from a supplier. Stock goes up by what you enter.</p>
            </div>
        </div>

    </section>


    <section class="group-panel glass payment-step">

        @if ($varieties->isEmpty() || $suppliers->isEmpty())

            <p class="partial-only-note">
                <x-icon name="info" />
                <span>
                    <span data-i18n="purchase_needs_masters">Add at least one rice variety and one supplier first.</span>
                    <a href="{{ route('traders.varieties.index') }}" class="statement-receipt-link" data-i18n="rice_varieties">Rice Varieties</a>
                    ·
                    <a href="{{ route('traders.suppliers.index') }}" class="statement-receipt-link" data-i18n="suppliers">Suppliers</a>
                </span>
            </p>

        @else

        <form method="POST" action="{{ route('traders.purchases.store') }}" class="payment-form">

            @csrf

            <div class="group-form-grid payment-fields">

                <div class="group-field">
                    <label class="field-label" for="supplier_id" data-i18n="supplier_word">Supplier</label>
                    <select id="supplier_id" name="supplier_id" class="input" required>
                        <option value="">Choose supplier…</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((int) old('supplier_id', $selectedSupplier) === $supplier->id)>
                                {{ $supplier->name }}{{ $supplier->place ? ' · '.$supplier->place : '' }}
                            </option>
                        @endforeach
                    </select>
                    <a href="{{ route('traders.suppliers.index') }}" class="statement-receipt-link" data-i18n="add_new_supplier">+ Add a new supplier</a>
                </div>

                <div class="group-field">
                    <label class="field-label" for="purchased_on" data-i18n="expense_date">Date</label>
                    <input type="date" id="purchased_on" name="purchased_on" class="input" required
                        value="{{ old('purchased_on', today(config('app.business_timezone'))->toDateString()) }}"
                        max="{{ today(config('app.business_timezone'))->toDateString() }}">
                </div>

                <div class="group-field">
                    <label class="field-label" for="supplier_bill_no" data-i18n="supplier_bill_no">Supplier's bill no.</label>
                    <input type="text" id="supplier_bill_no" name="supplier_bill_no" class="input" maxlength="60" value="{{ old('supplier_bill_no') }}">
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

            </div>


            <h2 class="member-groups-title" data-i18n="rice_bought">Rice bought</h2>

            @include('traders.partials.bill-lines', ['isSale' => false])


            <div class="group-field group-field-full">
                <label class="field-label" for="notes" data-i18n="notes">Notes</label>
                <input type="text" id="notes" name="notes" class="input" maxlength="1000" value="{{ old('notes') }}">
            </div>

            <div class="form-footer payment-form-footer">
                <button type="submit" class="save-button">
                    <x-icon name="save" />
                    <span data-i18n="save_purchase">Save purchase</span>
                </button>
            </div>

        </form>

        @endif

    </section>

</div>

@endsection
