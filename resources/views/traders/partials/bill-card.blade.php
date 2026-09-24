{{-- A printable rice bill card (sale invoice or purchase bill), on the
     receipt layout: letterhead, number + date, party, rice lines, total.
     $title, $number, $date, $partyLabel, $partyRows [[label, value]],
     $items, $total, $extraRows [[label, amount, class]] --}}

<article class="receipt glass invoice-card">

    <header class="receipt-header">

        <div class="receipt-brand">
            <img class="receipt-logo" src="{{ asset('images/sn-chit-funds-logo.png') }}" alt="SN Traders">
            <div>
                <strong class="receipt-company"><span>SN</span> Traders</strong>
                <span class="receipt-tagline">Quality Rice · Fair Price</span>
            </div>
        </div>

        <div class="receipt-title">
            <strong>{{ $title }}</strong>
        </div>

    </header>


    <div class="receipt-meta">
        <div>
            <span>{{ $numberLabel }}</span>
            <strong>{{ $number }}</strong>
        </div>
        <div class="receipt-meta-right">
            <span data-i18n="expense_date">Date</span>
            <strong>{{ $date }}</strong>
        </div>
    </div>


    <table class="receipt-lines">
        <tbody>
            @foreach ($partyRows as [$rowLabel, $rowValue])
                <tr>
                    <th>{{ $rowLabel }}</th>
                    <td>{!! $rowValue !!}</td>
                </tr>
            @endforeach
        </tbody>
    </table>


    <table class="invoice-items">
        <thead>
            <tr>
                <th>#</th>
                <th data-i18n="rice_variety">Rice</th>
                <th class="text-right" data-i18n="bags_word">Bags</th>
                <th class="text-right" data-i18n="rate_per_bag">Rate / bag</th>
                <th class="text-right" data-i18n="amount_word">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong>{{ $item->variety->name }}</strong></td>
                    <td class="text-right">{{ $item->bags }}</td>
                    <td class="text-right"><x-rupees :amount="$item->rate" /></td>
                    <td class="text-right"><strong><x-rupees :amount="$item->amount" /></strong></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2" data-i18n="total_word">Total</th>
                <td class="text-right">{{ $items->sum('bags') }}</td>
                <td></td>
                <td class="text-right"><strong><x-rupees :amount="$total" /></strong></td>
            </tr>
        </tfoot>
    </table>


    <div class="receipt-amount">
        <div class="receipt-amount-figure">
            <span data-i18n="bill_total">Bill total</span>
            <strong><x-rupees :amount="$total" /></strong>
        </div>
        <p class="receipt-amount-words">
            Rupees {{ \App\Models\Payment::numberInWords((int) floor($total)) }}{{ round($total - floor($total), 2) > 0 ? ' and '.\App\Models\Payment::numberInWords((int) round(($total - floor($total)) * 100)).' Paise' : '' }} Only
        </p>
    </div>


    @if (! empty($extraRows))
        <table class="receipt-lines invoice-summary">
            <tbody>
                @foreach ($extraRows as [$rowLabel, $rowAmount, $rowClass])
                    <tr>
                        <th>{{ $rowLabel }}</th>
                        <td class="text-right"><strong class="{{ $rowClass }}"><x-rupees :amount="$rowAmount" /></strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif


    <footer class="receipt-footer">
        <div>
            <span data-i18n="recorded_by">Recorded by</span>
            <strong>{{ $recordedBy ?? '—' }}</strong>
        </div>
        <div class="receipt-signature">
            <span data-i18n="authorised_signature">Authorised signature</span>
        </div>
    </footer>

    <p class="receipt-thanks">{{ $thanks ?? 'Thank you for your business.' }}</p>

</article>
