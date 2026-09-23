@extends('layouts.app')

@section('title', 'Draw – '.$draw->chitGroup->name.' Month '.$draw->month_number.' | SN Chit Funds')

@push('styles')
    @vite([
        'resources/css/create.css',
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/draws.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/draws.js')
@endpush

@section('content')

@php
    $group = $draw->chitGroup;
    $winner = $draw->winner;
    $customer = $winner->customer;
@endphp

<div class="group-show-page draws-page">


    {{-- =====================================================
         FLASH MESSAGES
    ====================================================== --}}

    @if (session('success'))
        <div class="group-flash group-flash-success no-print" role="status">
            <x-icon name="check" />
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="group-flash group-flash-error no-print" role="alert">
            <x-icon name="info" />
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="group-flash group-flash-error no-print" role="alert">
            <x-icon name="info" />
            {{ $errors->first() }}
        </div>
    @endif


    {{-- =====================================================
         RESULT
    ====================================================== --}}

    <section class="group-hero glass no-print">

        @if ($draw->isVoucherPrinted())
            <a
                href="{{ route('draws.index', ['status' => 'past']) }}"
                class="group-back-link"
            >
                <x-icon name="arrow-left" />
                <span data-i18n="back_to_past_winners">Back to Past Winners</span>
            </a>
        @else
            <a
                href="{{ route('draws.index') }}"
                class="group-back-link"
            >
                <x-icon name="arrow-left" />
                <span data-i18n="back_to_draw_details">Back to Draw Details</span>
            </a>
        @endif

        <div class="group-hero-main">

            <span class="group-hero-icon icon-3d icon-3d-orange">
                <x-icon name="trophy" />
            </span>

            <div class="group-hero-text">

                <div class="group-hero-tags">
                    @if ($draw->isPaidOut())
                        <span class="group-status group-status-running" data-i18n="payout_paid">Paid out</span>
                    @else
                        <span class="group-status group-status-forming" data-i18n="payout_pending">Awaiting payout</span>
                    @endif
                </div>

                <h1 class="group-hero-title">
                    <x-customer-name :customer="$customer" />
                </h1>

                <p class="group-hero-meta">
                    {{ $winner->member_code }} ·
                    <a href="{{ route('groups.show', $group) }}" class="draw-group-link">{{ $group->name }}</a> ·
                    <span data-i18n="month_number">Month</span> {{ $draw->month_number }}
                    ({{ $group->monthPeriodLabel($draw->month_number) }})
                </p>

            </div>

            <div class="draw-prize">
                <span data-i18n="prize_amount">Prize (withdrawal) amount</span>
                <strong><x-rupees :amount="$draw->withdrawal_amount" /></strong>
            </div>

        </div>

    </section>


    <div class="draw-show-grid no-print">


        {{-- -------- draw details -------- --}}

        <section class="group-panel glass">

            <div class="group-panel-header">
                <h2 data-i18n="draw_details">Draw details</h2>
            </div>

            <dl class="member-detail-grid">

                <div>
                    <dt data-i18n="drawn_on">Drawn on</dt>
                    <dd>{{ $draw->drawn_at->format('d M Y, h:i A') }}</dd>
                </div>

                <div>
                    <dt data-i18n="drawn_by">Drawn by</dt>
                    <dd>{{ $draw->drawnBy?->name ?? '—' }}</dd>
                </div>

                <div>
                    <dt data-i18n="phone">Phone</dt>
                    <dd class="member-detail-phone">
                        {{ $customer->phone }}
                        @if ($customer->phone)
                            <a
                                href="tel:{{ preg_replace('/[^\d+]/', '', $customer->phone) }}"
                                class="member-call-button"
                            >
                                <x-icon name="phone" />
                                <span data-i18n="call">Call</span>
                            </a>
                        @endif
                    </dd>
                </div>

                <div>
                    <dt data-i18n="customer_id">Customer ID</dt>
                    <dd>{{ $customer->customer_code }}</dd>
                </div>

            </dl>


            <h3 class="member-groups-title">
                <span data-i18n="in_the_draw">In the draw</span>
                ({{ $draw->participants->count() }})
            </h3>

            <div class="draw-participants">
                @foreach ($draw->participants as $participant)
                    <span @class(['draw-participant', 'is-winner' => $participant->id === $winner->id])>
                        @if ($participant->id === $winner->id)
                            <x-icon name="trophy" />
                        @endif
                        {{ $participant->member_code }} ·
                        <x-customer-name :customer="$participant->customer" />
                    </span>
                @endforeach
            </div>

        </section>


        {{-- -------- payout -------- --}}

        <section class="group-panel glass">

            @if ($draw->isPaidOut())

                <div class="payout-ack">

                    <span class="payout-ack-icon icon-3d icon-3d-green">
                        <x-icon name="check" />
                    </span>

                    <div>
                        <strong data-i18n="payout_ack_title">Prize money handed over</strong>
                        <p>
                            <x-rupees :amount="$draw->payout_amount" />
                            · {{ $draw->payoutMethodLabel() }}@if ($draw->payout_reference) ({{ $draw->payout_reference }})@endif
                            · {{ $draw->paid_at->format('d M Y, h:i A') }}
                            · <span data-i18n="recorded_by">Recorded by</span> {{ $draw->paidBy?->name ?? '—' }}
                        </p>
                    </div>

                </div>

                <p
                    class="voucher-printed-note"
                    id="voucherPrintedNote"
                    @if (! $draw->isVoucherPrinted()) hidden @endif
                >
                    <x-icon name="check" />
                    <span>
                        <span data-i18n="voucher_printed_on">Voucher printed on</span>
                        <span id="voucherPrintedAt">{{ $draw->voucher_printed_at?->format('d M Y, h:i A') }}</span>
                        · <span data-i18n="moved_to_past_winners">moved to Past Winners</span>
                    </span>
                </p>

                <div class="receipt-actions">

                    <button
                        type="button"
                        class="group-action"
                        id="printVoucherButton"
                        data-printed-url="{{ route('draws.voucher.printed', $draw) }}"
                    >
                        <x-icon name="printer" />
                        <span data-i18n="print_voucher">Print voucher</span>
                    </button>

                    <a
                        href="{{ route('draws.voucher.pdf', $draw) }}"
                        class="group-action download-pdf-button"
                        id="downloadVoucherButton"
                    >
                        <x-icon name="download" />
                        <span data-i18n="download_pdf">Download PDF</span>
                    </a>

                </div>

            @else

                <div class="group-panel-header">
                    <h2 data-i18n="record_payout">Record payout</h2>
                </div>

                <p class="draw-hint" data-i18n="record_payout_hint">
                    When the winner receives the prize money, record it here. This marks the draw as paid and makes the voucher for the customer to sign.
                </p>

                <form
                    method="POST"
                    action="{{ route('draws.payout', $draw) }}"
                    class="payout-form"
                >

                    @csrf

                    <div class="group-form-grid">

                        <div class="group-field">
                            <label class="field-label" for="payout_amount" data-i18n="payout_amount">Amount paid (₹)</label>
                            <input
                                type="text"
                                id="payout_amount"
                                name="payout_amount"
                                class="input payment-amount-input"
                                inputmode="numeric"
                                value="{{ old('payout_amount', number_format($draw->withdrawal_amount)) }}"
                                required
                            >
                        </div>

                        <div class="group-field">
                            <label class="field-label" for="paid_at" data-i18n="payment_date_time">Payment date &amp; time</label>
                            <input
                                type="datetime-local"
                                id="paid_at"
                                name="paid_at"
                                class="input"
                                value="{{ old('paid_at') ? str_replace(' ', 'T', old('paid_at')) : now(config('app.business_timezone'))->format('Y-m-d\TH:i') }}"
                                max="{{ now(config('app.business_timezone'))->format('Y-m-d\TH:i') }}"
                                required
                            >
                        </div>

                        <div class="group-field group-field-full">
                            <span class="field-label" data-i18n="payment_method">Payment method</span>
                            <div class="method-options">
                                @foreach (\App\Models\Payment::METHODS as $methodValue => $methodLabel)
                                    <label class="method-option">
                                        <input
                                            type="radio"
                                            name="payout_method"
                                            value="{{ $methodValue }}"
                                            @checked(old('payout_method', 'cash') === $methodValue)
                                        >
                                        <span data-i18n="method_{{ $methodValue }}">{{ $methodLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="group-field">
                            <label class="field-label" for="payout_reference" data-i18n="reference_number">Reference / UPI / cheque no.</label>
                            <input
                                type="text"
                                id="payout_reference"
                                name="payout_reference"
                                class="input"
                                value="{{ old('payout_reference') }}"
                                maxlength="100"
                            >
                        </div>

                        <div class="group-field">
                            <label class="field-label" for="payout_notes" data-i18n="notes">Notes</label>
                            <input
                                type="text"
                                id="payout_notes"
                                name="payout_notes"
                                class="input"
                                value="{{ old('payout_notes') }}"
                                maxlength="1000"
                            >
                        </div>

                    </div>

                    <button
                        type="submit"
                        class="save-button payout-button"
                        onclick="return confirm('Confirm the winner has received the prize money?')"
                    >
                        <x-icon name="check" />
                        <span data-i18n="confirm_payout">Confirm paid &amp; make voucher</span>
                    </button>

                </form>

                <form
                    method="POST"
                    action="{{ route('draws.destroy', $draw) }}"
                    class="draw-cancel-form"
                    onsubmit="return confirm('Cancel this draw? It can then be run again for month {{ $draw->month_number }}.')"
                >
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="group-action receipt-cancel">
                        <x-icon name="x" />
                        <span data-i18n="cancel_draw">Cancel draw (entered by mistake)</span>
                    </button>
                </form>

            @endif

        </section>

    </div>


    {{-- =====================================================
         PAYOUT VOUCHER (printable; given to the customer)
    ====================================================== --}}

    @if ($draw->isPaidOut())
        @include('draws.partials.voucher', ['draw' => $draw])
    @endif

</div>

@endsection
