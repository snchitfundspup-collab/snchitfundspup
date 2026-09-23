{{-- Prize payout voucher: the paper the winner signs when they receive
     the money. Uses the receipt styles (payments.css), so it prints on
     A5 with the colour logo and no backgrounds. --}}

@php
    $group = $draw->chitGroup;
    $customer = $draw->winner->customer;
@endphp

<article class="receipt glass voucher">

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
            <strong data-i18n="payout_voucher">Prize Payout Voucher</strong>
        </div>

    </header>


    <div class="receipt-meta">

        <div>
            <span data-i18n="voucher_no">Voucher No.</span>
            <strong>{{ $draw->voucher_number }}</strong>
        </div>

        <div class="receipt-meta-right">
            <span data-i18n="date_time">Date &amp; time</span>
            <strong>{{ $draw->paid_at->format('d M Y, h:i A') }}</strong>
        </div>

    </div>


    <table class="receipt-lines">
        <tbody>

            <tr>
                <th data-i18n="paid_to">Paid to</th>
                <td><x-customer-name :customer="$customer" /></td>
            </tr>

            <tr>
                <th data-i18n="customer_id">Customer ID</th>
                <td>{{ $customer->customer_code }}</td>
            </tr>

            <tr>
                <th data-i18n="phone">Phone</th>
                <td>{{ $customer->phone }}</td>
            </tr>

            <tr>
                <th data-i18n="group_word">Group</th>
                <td>{{ $group->name }}</td>
            </tr>

            <tr>
                <th data-i18n="member_id_here">Member ID</th>
                <td>{{ $draw->winner->member_code }}</td>
            </tr>

            <tr>
                <th data-i18n="draw_word">Draw</th>
                <td>
                    <span data-i18n="month_number">Month</span> {{ $draw->month_number }}
                    ({{ $group->monthPeriodLabel($draw->month_number) }})
                    · <span data-i18n="drawn_on">Drawn on</span> {{ $draw->drawn_at->format('d M Y') }}
                </td>
            </tr>

            <tr>
                <th data-i18n="payment_method">Payment method</th>
                <td>
                    {{ $draw->payoutMethodLabel() }}
                    @if ($draw->payout_reference)
                        · {{ $draw->payout_reference }}
                    @endif
                </td>
            </tr>

            @if ($draw->payout_notes)
                <tr>
                    <th data-i18n="notes">Notes</th>
                    <td>{{ $draw->payout_notes }}</td>
                </tr>
            @endif

        </tbody>
    </table>


    <div class="receipt-amount">

        <div class="receipt-amount-figure">
            <span data-i18n="amount_paid">Amount paid</span>
            <strong><x-rupees :amount="$draw->payout_amount" /></strong>
        </div>

        <p class="receipt-amount-words">
            {{ $draw->payoutInWords() }}
        </p>

    </div>


    <footer class="receipt-footer voucher-signatures">

        <div class="receipt-signature">
            <span class="receipt-signature-line"></span>
            <span data-i18n="received_by_customer">Received by (customer signature)</span>
        </div>

        <div>
            <span data-i18n="recorded_by">Recorded by</span>
            <strong>{{ $draw->paidBy?->name ?? '—' }}</strong>
        </div>

        <div class="receipt-signature">
            <span class="receipt-signature-line"></span>
            <span data-i18n="authorised_signature">Authorised signature</span>
        </div>

    </footer>

    <p
        class="receipt-thanks"
        data-i18n="voucher_thanks"
    >
        Congratulations on your winning draw.
    </p>

</article>
