{{-- Balance sheet body for the print page and PDF. --}}

@php
    $periodText = $range === 'all'
        ? 'All time'
        : \Illuminate\Support\Carbon::parse($from)->format('d M Y').' – '.\Illuminate\Support\Carbon::parse($to)->format('d M Y');
@endphp

<table class="info">
    <tr>
        <td><span class="label">Period</span><strong>{{ $periodText }}</strong></td>
        <td><span class="label">Total spent</span><strong><x-rupees :amount="$total" /></strong></td>
        <td><span class="label">Partners</span><strong>{{ $partners->count() }}</strong></td>
        <td><span class="label">Each partner's share</span><strong><x-rupees :amount="$share" /></strong></td>
    </tr>
</table>

@if ($range !== 'all')
    <p class="muted">These balances cover this period only.</p>
@endif

<h3 class="list-title">Partners</h3>

<table class="grid">
    <thead>
        <tr>
            <th>Partner</th>
            <th class="amount">Paid for expenses</th>
            <th class="amount">Equal share</th>
            <th class="amount">Settlements given</th>
            <th class="amount">Settlements received</th>
            <th class="amount">Net put in</th>
            <th class="amount">Balance</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($balances as $row)
            <tr>
                <td><strong>{{ $row['partner']->name }}</strong></td>
                <td class="amount"><x-rupees :amount="$row['paid']" /> <span class="muted">({{ $row['items'] }})</span></td>
                <td class="amount"><x-rupees :amount="$row['share']" /></td>
                <td class="amount"><x-rupees :amount="$row['given']" /></td>
                <td class="amount"><x-rupees :amount="$row['received']" /></td>
                <td class="amount"><x-rupees :amount="$row['net_put_in']" /></td>
                <td class="amount">
                    @if ($row['balance'] >= 0.01)
                        <span class="receive"><x-rupees :amount="$row['balance']" /> to receive</span>
                    @elseif ($row['balance'] <= -0.01)
                        <span class="pending"><x-rupees :amount="-$row['balance']" /> to pay</span>
                    @else
                        Settled
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th>Total</th>
            <td class="amount"><x-rupees :amount="$total" /></td>
            <td class="amount"><x-rupees :amount="$total" /></td>
            <td class="amount"><x-rupees :amount="$balances->sum('given')" /></td>
            <td class="amount"><x-rupees :amount="$balances->sum('received')" /></td>
            <td class="amount"><x-rupees :amount="$balances->sum('net_put_in')" /></td>
            <td></td>
        </tr>
    </tfoot>
</table>

<h3 class="list-title">Who pays whom</h3>

@if ($transfers === [])
    <p class="muted">All square — nobody owes anybody.</p>
@else
    <table class="grid">
        <tbody>
            @foreach ($transfers as $transfer)
                <tr>
                    <td><strong>{{ $transfer['from']->name }}</strong> pays <strong>{{ $transfer['to']->name }}</strong></td>
                    <td class="amount"><x-rupees :amount="$transfer['amount']" /></td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if ($settlements->isNotEmpty())
    <h3 class="list-title">Settlements</h3>
    <table class="grid">
        <thead>
            <tr>
                <th>Date</th>
                <th>From</th>
                <th>To</th>
                <th>Method</th>
                <th>Reference</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($settlements as $settlement)
                <tr>
                    <td class="nowrap">{{ $settlement->settled_on->format('d M Y') }}</td>
                    <td>{{ $settlement->fromUser->name }}</td>
                    <td>{{ $settlement->toUser->name }}</td>
                    <td>{{ $settlement->methodLabel() }}</td>
                    <td>{{ $settlement->reference ?: '—' }}</td>
                    <td class="amount"><x-rupees :amount="$settlement->amount" /></td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<table class="signatures">
    <tr>
        @foreach ($partners as $partner)
            <td>{{ $partner->name }}</td>
        @endforeach
    </tr>
</table>
