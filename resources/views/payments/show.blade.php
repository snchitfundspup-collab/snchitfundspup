@extends('layouts.app')

@section('title', 'Receipt '.$payment->receipt_number.' | SN Chit Funds')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css'
    ])
@endpush

@section('content')

@php
    $member = $payment->member;
    $balanceNow = $member->balanceDue();
    $hasPending = $member->collectionStatus()['pending'] > 0;
@endphp

<div class="group-show-page payments-page">


    @if (session('success'))
        <div class="group-flash group-flash-success no-print" role="status">
            <x-icon name="check" />
            {{ session('success') }}
        </div>
    @endif


    {{-- =====================================================
         ACTIONS
    ====================================================== --}}

    <div class="receipt-actions no-print">

        <a
            href="{{ route('payments.create', session('collect_tab') === 'due' ? ['tab' => 'due'] : []) }}"
            class="add-group-button next-member-button"
        >
            <span data-i18n="next_member">Next member</span>
            <x-icon name="arrow-right" />
        </a>

        <button
            type="button"
            class="group-action"
            onclick="window.print()"
        >
            <x-icon name="printer" />
            <span data-i18n="print_receipt">Print</span>
        </button>

        <a
            href="{{ route('payments.receipt.pdf', $payment) }}"
            class="group-action download-pdf-button"
        >
            <x-icon name="download" />
            <span data-i18n="download_pdf">Download PDF</span>
        </a>

        <form
            method="POST"
            action="{{ route('payments.destroy', $payment) }}"
            onsubmit="return confirm('Cancel receipt {{ $payment->receipt_number }}? The ₹{{ number_format($payment->amount) }} will be taken off the months it covered.')"
        >
            @csrf
            @method('DELETE')

            <button
                type="submit"
                class="group-action receipt-cancel"
            >
                <x-icon name="x" />
                <span data-i18n="cancel_receipt">Cancel receipt</span>
            </button>
        </form>

    </div>


    {{-- =====================================================
         RECEIPT
    ====================================================== --}}

    <article class="receipt glass">

        {{-- company header: colour logo + name (kept in colour when printed) --}}

        <header class="receipt-header">

            <div class="receipt-brand">

                <img
                    class="receipt-logo"
                    src="{{ asset('images/sn-chit-funds-logo.png') }}"
                    alt="SN Chit Funds"
                >

                <div>
                    <strong class="receipt-company"><span>SN</span> Chit Funds</strong>
                    <span class="receipt-tagline">Trust · Growth · Together</span>
                </div>

            </div>

            <div class="receipt-title">
                <strong data-i18n="payment_receipt">Payment Receipt</strong>
            </div>

        </header>


        {{-- receipt number + date & time --}}

        <div class="receipt-meta">

            <div>
                <span data-i18n="receipt_no">Receipt No.</span>
                <strong>{{ $payment->receipt_number }}</strong>
            </div>

            <div class="receipt-meta-right">
                <span data-i18n="date_time">Date &amp; time</span>
                <strong>{{ $payment->paid_at->format('d M Y, h:i A') }}</strong>
            </div>

        </div>


        {{-- details --}}

        <table class="receipt-lines">

            <tbody>

                <tr>
                    <th data-i18n="received_from">Received from</th>
                    <td><x-customer-name :customer="$payment->customer" /></td>
                </tr>

                <tr>
                    <th data-i18n="customer_id">Customer ID</th>
                    <td>{{ $payment->customer->customer_code }}</td>
                </tr>

                <tr>
                    <th data-i18n="phone">Phone</th>
                    <td>{{ $payment->customer->phone }}</td>
                </tr>

                <tr>
                    <th data-i18n="group_word">Group</th>
                    <td>{{ $payment->chitGroup->name }}</td>
                </tr>

                <tr>
                    <th data-i18n="member_id_here">Member ID</th>
                    <td>{{ $member->member_code }}</td>
                </tr>

                <tr>
                    <th data-i18n="for_months">For</th>
                    <td>
                        @foreach ($payment->allocations as $allocation)
                            <span class="receipt-month">
                                <span data-i18n="month_number">Month</span> {{ $allocation->month_number }}
                                ({{ $payment->chitGroup->monthPeriodLabel($allocation->month_number) }}):
                                <x-rupees :amount="$allocation->amount" />
                                @if ($allocation->amount < $payment->chitGroup->installment_amount)
                                    <small>(<span data-i18n="part">part</span>)</small>
                                @endif
                            </span>
                        @endforeach
                    </td>
                </tr>

                <tr>
                    <th data-i18n="payment_method">Payment method</th>
                    <td>
                        {{ $payment->methodLabel() }}
                        @if ($payment->reference)
                            · {{ $payment->reference }}
                        @endif
                    </td>
                </tr>

                @if ($payment->notes)
                    <tr>
                        <th data-i18n="notes">Notes</th>
                        <td>{{ $payment->notes }}</td>
                    </tr>
                @endif

            </tbody>

        </table>


        {{-- amount box --}}

        <div class="receipt-amount">

            <div class="receipt-amount-figure">
                <span data-i18n="amount_received">Amount received</span>
                <strong><x-rupees :amount="$payment->amount" /></strong>
            </div>

            <p class="receipt-amount-words">
                {{ $payment->amountInWords() }}
            </p>

        </div>


        {{-- footer --}}

        <footer class="receipt-footer">

            <div>
                <span data-i18n="balance_due_now">Balance due now</span>
                <strong @class(['is-due' => $hasPending, 'is-open' => ! $hasPending && $balanceNow > 0])><x-rupees :amount="$balanceNow" /></strong>
            </div>

            <div>
                <span data-i18n="recorded_by">Recorded by</span>
                <strong>{{ $payment->recorder?->name ?? '—' }}</strong>
            </div>

            <div class="receipt-signature">
                <span class="receipt-signature-line"></span>
                <span data-i18n="authorised_signature">Authorised signature</span>
            </div>

        </footer>

        <p
            class="receipt-thanks"
            data-i18n="receipt_thanks"
        >
            Thank you for your payment.
        </p>

    </article>

</div>

@endsection
