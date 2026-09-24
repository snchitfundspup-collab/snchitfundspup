@extends('layouts.app')

@php
    $isEditing = $expense->exists;
@endphp

@section('title', ($isEditing ? 'Edit Expense' : 'Add Expense').' | SN Chit Funds')

@push('styles')
    @vite([
        'resources/css/create.css',
        'resources/css/groups.css',
        'resources/css/payments.css'
    ])
@endpush

@section('content')

<div class="group-show-page payments-page expense-form-page">


    {{-- =====================================================
         FLASH MESSAGES
    ====================================================== --}}

    @if (session('success'))
        <div class="group-flash group-flash-success" role="status">
            <x-icon name="check" />
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="group-flash group-flash-error" role="alert">
            <x-icon name="info" />
            {{ $errors->first() }}
        </div>
    @endif


    {{-- =====================================================
         HEADER
    ====================================================== --}}

    <section class="group-hero glass">

        <a
            href="{{ route($routePrefix.'expenses.index') }}"
            class="group-back-link"
        >
            <x-icon name="arrow-left" />
            <span data-i18n="back_to_expenses">Back to Expenses</span>
        </a>

        <div class="group-hero-main">

            <span class="group-hero-icon icon-3d icon-3d-green">
                <x-icon name="wallet" />
            </span>

            <div class="group-hero-text">
                <h1 class="group-hero-title">
                    @if ($isEditing)
                        <span data-i18n="edit_expense">Edit Expense</span>
                    @else
                        <span data-i18n="add_expense">Add Expense</span>
                    @endif
                </h1>
                <p class="group-hero-meta" data-i18n="expense_form_subtitle">
                    Business spending paid by a partner. It is shared equally between the partners.
                </p>
            </div>

        </div>

    </section>


    {{-- =====================================================
         FORM
    ====================================================== --}}

    <section class="group-panel glass payment-step">

        <form
            method="POST"
            action="{{ $isEditing ? route($routePrefix.'expenses.update', $expense) : route($routePrefix.'expenses.store') }}"
            class="payment-form expense-form"
        >

            @csrf

            @if ($isEditing)
                @method('PUT')
            @endif


            {{-- WHO PAID --}}

            <span class="field-label" data-i18n="paid_by_partner">Paid by</span>

            <div class="method-options expense-partners">
                @foreach ($partners as $partner)
                    <label class="method-option">
                        <input
                            type="radio"
                            name="paid_by"
                            value="{{ $partner->id }}"
                            @checked((int) old('paid_by', $expense->paid_by) === $partner->id)
                            required
                        >
                        <span>{{ $partner->name }}</span>
                    </label>
                @endforeach
            </div>


            <div class="group-form-grid payment-fields">

                <div class="group-field">
                    <label class="field-label" for="amount" data-i18n="amount_rupees">Amount (₹)</label>
                    <input
                        type="text"
                        id="amount"
                        name="amount"
                        value="{{ old('amount', $expense->amount ? number_format($expense->amount) : '') }}"
                        class="input money-input payment-amount-input"
                        inputmode="numeric"
                        placeholder="Enter amount"
                        autocomplete="off"
                        required
                        autofocus
                    >
                </div>

                <div class="group-field">
                    <label class="field-label" for="spent_on" data-i18n="expense_date">Date</label>
                    <input
                        type="date"
                        id="spent_on"
                        name="spent_on"
                        value="{{ old('spent_on', $expense->spent_on?->toDateString()) }}"
                        max="{{ today(config('app.business_timezone'))->toDateString() }}"
                        class="input"
                        required
                    >
                </div>

                <div class="group-field">
                    <label class="field-label" for="description" data-i18n="spent_for">What was it for?</label>
                    <input
                        type="text"
                        id="description"
                        name="description"
                        value="{{ old('description', $expense->description) }}"
                        class="input"
                        maxlength="200"
                        placeholder="e.g. October office rent"
                        required
                    >
                </div>

                <div class="group-field">
                    <label class="field-label" for="paid_to" data-i18n="paid_to_whom">Paid to (shop / person)</label>
                    <input
                        type="text"
                        id="paid_to"
                        name="paid_to"
                        value="{{ old('paid_to', $expense->paid_to) }}"
                        class="input"
                        maxlength="120"
                    >
                </div>

                <div class="group-field">
                    <label class="field-label" for="reference" data-i18n="bill_number">Bill / reference no.</label>
                    <input
                        type="text"
                        id="reference"
                        name="reference"
                        value="{{ old('reference', $expense->reference) }}"
                        class="input"
                        maxlength="100"
                    >
                </div>

                <div class="group-field group-field-full">
                    <span class="field-label" data-i18n="payment_method">Payment method</span>
                    <div class="method-options">
                        @foreach (\App\Models\Payment::METHODS as $methodValue => $methodLabel)
                            <label class="method-option">
                                <input
                                    type="radio"
                                    name="method"
                                    value="{{ $methodValue }}"
                                    @checked(old('method', $expense->method) === $methodValue)
                                >
                                <span data-i18n="method_{{ $methodValue }}">{{ $methodLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="group-field group-field-full">
                    <label class="field-label" for="notes" data-i18n="notes">Notes</label>
                    <input
                        type="text"
                        id="notes"
                        name="notes"
                        value="{{ old('notes', $expense->notes) }}"
                        class="input"
                        maxlength="1000"
                    >
                </div>

            </div>


            <div class="form-footer payment-form-footer expense-form-footer">

                @unless ($isEditing)
                    <button
                        type="submit"
                        name="add_another"
                        value="1"
                        class="group-action"
                    >
                        <x-icon name="plus" />
                        <span data-i18n="save_and_add_another">Save &amp; add another</span>
                    </button>
                @endunless

                <button type="submit" class="save-button">
                    <x-icon name="save" />
                    @if ($isEditing)
                        <span data-i18n="save_changes">Save Changes</span>
                    @else
                        <span data-i18n="save_expense">Save expense</span>
                    @endif
                </button>

            </div>

        </form>


        @if ($isEditing)
            <form
                method="POST"
                action="{{ route($routePrefix.'expenses.destroy', $expense) }}"
                class="draw-cancel-form"
                onsubmit="return confirm('Delete this expense of ₹{{ number_format($expense->amount) }}?')"
            >
                @csrf
                @method('DELETE')

                <button type="submit" class="group-action receipt-cancel">
                    <x-icon name="trash" />
                    <span data-i18n="delete_expense">Delete this expense</span>
                </button>
            </form>
        @endif

    </section>

</div>

@endsection
