{{-- Balance sheet body for the print page and PDF. --}}

@php
    $periodText = $range === 'all'
        ? __('All time')
        : \Illuminate\Support\Carbon::parse($from)->format('d M Y').' – '.\Illuminate\Support\Carbon::parse($to)->format('d M Y');
@endphp

<table class="info">
    <tr>
        <td><span class="label">{{ __('Period') }}</span><strong>{{ $periodText }}</strong></td>
        <td><span class="label">{{ __('Total spent') }}</span><strong><x-rupees :amount="$total" /></strong></td>
        <td><span class="label">{{ __('Partners') }}</span><strong>{{ $partners->count() }}</strong></td>
        <td><span class="label">{{ __('Each partner\'s share') }}</span><strong><x-rupees :amount="$share" /></strong></td>
    </tr>
</table>

@if ($range !== 'all')
    <p class="muted">{{ __('These balances cover this period only.') }}</p>
@endif

<h3 class="list-title">{{ __('Partners') }}</h3>

<table class="grid">
    <thead>
        <tr>
            <th>{{ __('Partner') }}</th>
            <th class="amount">{{ __('Paid for expenses') }}</th>
            <th class="amount">{{ __('Equal share') }}</th>
            <th class="amount">{{ __('Settlements given') }}</th>
            <th class="amount">{{ __('Settlements received') }}</th>
            <th class="amount">{{ __('Net put in') }}</th>
            <th class="amount">{{ __('Balance') }}</th>
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
                        <span class="receive"><x-rupees :amount="$row['balance']" /> {{ __('to receive') }}</span>
                    @elseif ($row['balance'] <= -0.01)
                        <span class="pending"><x-rupees :amount="-$row['balance']" /> {{ __('to pay') }}</span>
                    @else
                        {{ __('Settled') }}
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th>{{ __('Total') }}</th>
            <td class="amount"><x-rupees :amount="$total" /></td>
            <td class="amount"><x-rupees :amount="$total" /></td>
            <td class="amount"><x-rupees :amount="$balances->sum('given')" /></td>
            <td class="amount"><x-rupees :amount="$balances->sum('received')" /></td>
            <td class="amount"><x-rupees :amount="$balances->sum('net_put_in')" /></td>
            <td></td>
        </tr>
    </tfoot>
</table>

<h3 class="list-title">{{ __('Who pays whom') }}</h3>

@if ($transfers === [])
    <p class="muted">{{ __('All square — nobody owes anybody.') }}</p>
@else
    <table class="grid">
        <tbody>
            @foreach ($transfers as $transfer)
                <tr>
                    <td><strong>{{ $transfer['from']->name }}</strong> {{ __('pays') }} <strong>{{ $transfer['to']->name }}</strong></td>
                    <td class="amount"><x-rupees :amount="$transfer['amount']" /></td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if ($settlements->isNotEmpty())
    <h3 class="list-title">{{ __('Settlements') }}</h3>
    <table class="grid">
        <thead>
            <tr>
                <th>{{ __('Date') }}</th>
                <th>{{ __('From') }}</th>
                <th>{{ __('To') }}</th>
                <th>{{ __('Method') }}</th>
                <th>{{ __('Reference') }}</th>
                <th class="amount">{{ __('Amount') }}</th>
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
