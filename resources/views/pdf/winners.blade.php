@extends('pdf.layout')

@section('title', 'Winners Report – '.($selectedGroup?->name ?? 'All groups'))

@section('page_margin', '10mm')

@section('font_size', '9px')

@section('doc_title', 'Winners Report')

@section('doc_subtitle', now(config('app.business_timezone'))->format('d M Y, h:i A'))

@section('styles')
    .info td {
        padding: 2px 16px 8px 0;
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
        font-size: 11px;
    }

    .group-title {
        margin: 10px 0 4px;
        font-size: 12px;
        font-weight: bold;
    }

    .group-title small {
        font-size: 8px;
        font-weight: normal;
        color: #4b5563;
    }

    .grid {
        border: 1px solid #111827;
        page-break-inside: auto;
    }

    .grid th,
    .grid td {
        border: 1px solid #9ca3af;
        padding: 4px 5px;
        text-align: left;
        vertical-align: top;
    }

    .grid thead th,
    .grid tfoot th,
    .grid tfoot td {
        background: #f1f3f6;
        font-weight: bold;
    }

    .grid tr {
        page-break-inside: avoid;
    }

    .code {
        display: block;
        color: #c2410c;
        font-weight: bold;
        font-size: 7.5px;
    }

    .amount {
        text-align: right !important;
        font-weight: bold;
        white-space: nowrap;
    }

    .paid {
        color: #166534;
        font-weight: bold;
    }

    .pending {
        color: #c2410c;
        font-weight: bold;
    }

    .meta {
        display: block;
        font-size: 7px;
        color: #4b5563;
    }
@endsection

@section('content')

    <table class="info">
        <tr>
            <td><span class="label">Group</span><strong>{{ $selectedGroup?->name ?? 'All groups' }}</strong></td>
            <td><span class="label">Draws held</span><strong>{{ $drawCount }}</strong></td>
            <td><span class="label">Total prize</span><strong><x-rupees :amount="$totalPrize" /></strong></td>
            <td><span class="label">Paid out</span><strong><x-rupees :amount="$totalPaidOut" /></strong></td>
        </tr>
    </table>

    @foreach ($reportGroups as $reportGroup)

        <div class="group-title">
            {{ $reportGroup->name }}
            <small>· <x-rupees :amount="$reportGroup->amount" /> · {{ $reportGroup->draws->count() }} of {{ $reportGroup->months }} months drawn</small>
        </div>

        <table class="grid">

            <thead>
                <tr>
                    <th>Month</th>
                    <th>Period</th>
                    <th>Winner</th>
                    <th>Phone</th>
                    <th class="amount">Prize</th>
                    <th>Drawn on</th>
                    <th>Payout</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($reportGroup->draws as $draw)
                    <tr>
                        <td><strong>M{{ $draw->month_number }}</strong></td>
                        <td>{{ $reportGroup->monthPeriodLabel($draw->month_number) }}</td>
                        <td>
                            <strong>{{ $draw->winner->customer->name }}</strong>
                            @if (filled($draw->winner->customer->remarks))
                                <span class="ident">({{ $draw->winner->customer->remarks }})</span>
                            @endif
                            <span class="code">{{ $draw->winner->member_code }}</span>
                        </td>
                        <td>{{ $draw->winner->customer->phone ?: '—' }}</td>
                        <td class="amount"><x-rupees :amount="$draw->prizeAmount()" /></td>
                        <td>{{ $draw->drawn_at->format('d M Y') }}</td>
                        <td>
                            @if ($draw->isPaidOut())
                                <span class="paid">Paid out</span>
                                <span class="meta">{{ $draw->voucher_number }} · {{ $draw->paid_at->format('d M Y') }} · {{ $draw->payoutMethodLabel() }}</span>
                            @else
                                <span class="pending">Awaiting payout</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr>
                    <th colspan="4">Total</th>
                    <td class="amount"><x-rupees :amount="$reportGroup->draws->sum(fn ($draw) => $draw->prizeAmount())" /></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>

        </table>

    @endforeach

@endsection
