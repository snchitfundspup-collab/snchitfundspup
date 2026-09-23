@extends('pdf.layout')

@section('title', 'Payment Ledger – '.$group->name)

@section('page_margin', '8mm')

@section('font_size', '8px')

@section('doc_title', 'Payment Ledger')

@section('doc_subtitle', now(config('app.business_timezone'))->format('d M Y, h:i A'))

@section('styles')
    .info td {
        padding: 2px 14px 8px 0;
        vertical-align: top;
        width: auto;
    }

    .info .label {
        display: block;
        font-size: 7px;
        font-weight: bold;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        color: #4b5563;
    }

    .info strong {
        font-size: 10px;
    }

    .grid {
        border: 1px solid #111827;
    }

    .grid th,
    .grid td {
        border: 1px solid #9ca3af;
        padding: 3px 3px;
        text-align: center;
        white-space: nowrap;
    }

    .grid thead th,
    .grid tfoot th,
    .grid tfoot td {
        background: #f1f3f6;
        font-weight: bold;
    }

    .grid .member {
        text-align: left;
        white-space: normal;
    }

    .grid .member small {
        display: block;
        color: #c2410c;
        font-weight: bold;
    }

    .grid .month-date {
        display: block;
        font-weight: normal;
        font-size: 6.5px;
        color: #4b5563;
    }

    .paid {
        background: #dcfce7;
        color: #166534;
        font-weight: bold;
    }

    .partial {
        background: #ffedd5;
        color: #9a3412;
        font-weight: bold;
    }

    .due {
        background: #fee2e2;
        color: #991b1b;
        font-weight: bold;
    }

    .total {
        text-align: right !important;
        font-weight: bold;
    }

    .due-text {
        color: #b91c1c;
    }

    .expected {
        display: block;
        font-weight: normal;
        font-size: 6.5px;
        color: #4b5563;
    }

    .won {
        outline: 1.5px solid #b45309;
    }

    .won-mark {
        color: #b45309;
        font-weight: bold;
    }

    .won .won-mark {
        display: block;
        font-size: 6.5px;
    }

    .prize {
        text-align: right !important;
        font-weight: bold;
        color: #b45309;
    }

    .prize small {
        display: block;
        font-weight: normal;
        font-size: 6.5px;
        color: #4b5563;
    }

    .key {
        margin-top: 6px;
        font-size: 7px;
        color: #4b5563;
    }

    .key span {
        padding: 1px 6px;
        margin-right: 6px;
    }
@endsection

@section('content')

    <table class="info">
        <tr>
            <td><span class="label">Group</span><strong>{{ $group->name }}</strong></td>
            <td><span class="label">Monthly installment</span><strong><x-rupees :amount="$group->installment_amount" /></strong></td>
            <td><span class="label">Months</span><strong>{{ $fromMonth }}–{{ $toMonth }} of {{ $group->months }}</strong></td>
            <td><span class="label">Collected</span><strong><x-rupees :amount="$totalPaid" /></strong></td>
            <td><span class="label">Due now</span><strong class="due-text"><x-rupees :amount="$totalDue" /></strong></td>
        </tr>
    </table>


    <table class="grid">

        <thead>
            <tr>
                <th>#</th>
                <th class="member">Member</th>
                @foreach ($monthNumbers as $month)
                    <th>
                        M{{ $month }}
                        <span class="month-date">{{ $group->dateForMonth($month)->format('j M') }}–<br>{{ $group->monthEndDate($month)->format('j M') }}</span>
                    </th>
                @endforeach
                <th>Paid</th>
                <th>Due now</th>
                <th>Prize won</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="member">
                        <strong>{{ $row['member']->customer->name }}</strong>
                        @if (filled($row['member']->customer->remarks))
                            <span class="ident">({{ $row['member']->customer->remarks }})</span>
                        @endif
                        <small>{{ $row['member']->member_code }}</small>
                    </td>

                    @foreach ($monthNumbers as $month)
                        @php
                            $cell = $row['cells'][$month];
                            $wonThisMonth = $row['won'] && $row['won']->month_number === $month;
                        @endphp
                        <td class="{{ in_array($cell['status'], ['paid', 'partial', 'due'], true) ? $cell['status'] : '' }} {{ $wonThisMonth ? 'won' : '' }}">
                            @if ($wonThisMonth)
                                <span class="won-mark">+{{ \Illuminate\Support\Number::format($row['won']->prizeAmount(), locale: 'en_IN') }}</span>
                            @endif
                            @if ($cell['paid'] > 0)
                                {{ \Illuminate\Support\Number::format($cell['paid'], locale: 'en_IN') }}
                            @elseif ($cell['status'] === 'due')
                                —
                            @endif
                        </td>
                    @endforeach

                    <td class="total"><x-rupees :amount="$row['paid']" /></td>
                    <td class="total {{ $row['balance_due'] > 0 ? 'due-text' : '' }}"><x-rupees :amount="$row['balance_due']" /></td>
                    <td class="prize">
                        @if ($row['won'])
                            <x-rupees :amount="$row['won']->prizeAmount()" />
                            <small>M{{ $row['won']->month_number }} · {{ $row['won']->isPaidOut() ? 'Paid out' : 'Awaiting payout' }}</small>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>

        <tfoot>
            <tr>
                <th></th>
                <th class="member">Total</th>
                @foreach ($monthNumbers as $month)
                    <td>
                        {{ \Illuminate\Support\Number::format($monthTotals[$month]['paid'], locale: 'en_IN') }}
                        <span class="expected">/ {{ \Illuminate\Support\Number::format($monthTotals[$month]['expected'], locale: 'en_IN') }}</span>
                    </td>
                @endforeach
                <td class="total"><x-rupees :amount="$totalPaid" /></td>
                <td class="total due-text"><x-rupees :amount="$totalDue" /></td>
                <td class="prize"><x-rupees :amount="$totalPrizes" /></td>
            </tr>
        </tfoot>

    </table>


    <div class="key">
        <span class="paid">Paid</span>
        <span class="partial">Part paid</span>
        <span class="due">— Due</span>
        <span class="won won-mark">+ Prize money won that month</span>
        Amounts in ₹. Month totals show collected / expected.
    </div>

@endsection
