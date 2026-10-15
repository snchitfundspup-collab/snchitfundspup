{{-- Agent Statement for the print page and PDF. --}}
@php
    $referenceHeading = $isFinance ? __('Loan No.') : __('Group · Seat');
    $amountHeading = $isFinance ? __('Loan amount') : __('Chit value');
    $stateLabels = [
        'overdue' => [__('Overdue'), 'pending'], 'due' => [__('Due today'), 'due-open'], 'not_started' => [__('Not started'), ''],
        'closed' => [__('Closed'), ''], 'on_track' => [__('On track'), ''], 'pending' => [__('Pending'), 'pending'],
        'partial' => [__('Part paid'), 'due-open'], 'upcoming' => [__('Upcoming'), ''], 'clear' => [__('Paid up'), ''], 'forming' => [__('Forming'), ''],
    ];
@endphp

<table class="info">
    <tr>
        <td><span class="label">{{ __('Agent') }}</span><strong>{{ $agent->name }}</strong></td>
        <td><span class="label">{{ __('Period') }}</span><strong>@include('traders.reports.partials.period-text')</strong></td>
        <td><span class="label">{{ __('Customers brought in') }}</span><strong>{{ $summary['customers'] }} ({{ $summary['joined'] }}) · <x-rupees :amount="$summary['joined_amount']" /></strong></td>
        <td><span class="label">{{ __('Collected') }}</span><strong><x-rupees :amount="$summary['collected']" /> ({{ $summary['receipts'] }})</strong></td>
    </tr>
</table>

<h3 class="list-title">{{ __('Customers brought in') }}</h3>

<table class="grid">
    <thead>
        <tr>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Customer') }}</th>
            <th>{{ $referenceHeading }}</th>
            <th class="amount">{{ $amountHeading }}</th>
            <th class="amount">{{ __('Collected') }}</th>
            <th class="amount">{{ $isFinance ? __('Balance') : __('Due now') }}</th>
            <th>{{ __('Status') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($joined as $row)
            <tr>
                <td>{{ $row['date']->format('d M Y') }}</td>
                <td>{{ $row['customer']->name }}@if (filled($row['customer']->remarks)) ({{ $row['customer']->remarks }})@endif · {{ $row['customer']->customer_code }}</td>
                <td>{{ $row['reference'] }}</td>
                <td class="amount"><x-rupees :amount="$row['amount']" /></td>
                <td class="amount"><x-rupees :amount="$row['collected']" /></td>
                <td class="amount"><x-rupees :amount="$row['left']" /></td>
                <td class="{{ $stateLabels[$row['state']][1] ?? '' }}">{{ $stateLabels[$row['state']][0] ?? $row['state'] }}</td>
            </tr>
        @empty
            <tr><td colspan="7">{{ __('No new customers in this period.') }}</td></tr>
        @endforelse
    </tbody>
    @if ($joined->isNotEmpty())
        <tfoot>
            <tr>
                <th colspan="3">{{ __('Total') }}</th>
                <td class="amount"><x-rupees :amount="$summary['joined_amount']" /></td>
                <td class="amount"><x-rupees :amount="$summary['joined_collected']" /></td>
                <td class="amount"><x-rupees :amount="$summary['left']" /></td>
                <td></td>
            </tr>
        </tfoot>
    @endif
</table>

<h3 class="list-title">{{ __('Month-by-month') }}</h3>

<table class="grid">
    <thead>
        <tr>
            <th>{{ __('Month') }}</th>
            <th class="amount">{{ __('Joined') }}</th>
            <th class="amount">{{ $amountHeading }}</th>
            <th class="amount">{{ __('Receipts') }}</th>
            <th class="amount">{{ __('Collected') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($months as $month)
            <tr>
                <td>{{ $month['month']->format('F Y') }}</td>
                <td class="amount">{{ $month['joined'] ?: '—' }}</td>
                <td class="amount">@if ($month['joined_amount'] > 0)<x-rupees :amount="$month['joined_amount']" />@else — @endif</td>
                <td class="amount">{{ $month['receipts'] ?: '—' }}</td>
                <td class="amount">@if ($month['collected'] > 0)<x-rupees :amount="$month['collected']" />@else — @endif</td>
            </tr>
        @empty
            <tr><td colspan="5">{{ __('Nothing for this agent in this period') }}</td></tr>
        @endforelse
    </tbody>
    @if ($months->isNotEmpty())
        <tfoot>
            <tr>
                <th>{{ __('Total') }}</th>
                <td class="amount">{{ $summary['joined'] }}</td>
                <td class="amount"><x-rupees :amount="$summary['joined_amount']" /></td>
                <td class="amount">{{ $summary['receipts'] }}</td>
                <td class="amount"><strong><x-rupees :amount="$summary['collected']" /></strong></td>
            </tr>
        </tfoot>
    @endif
</table>

<h3 class="list-title">{{ __('Collections day by day') }}</h3>

<table class="grid">
    <thead>
        <tr>
            <th>{{ __('Time') }}</th>
            <th>{{ __('Receipt No.') }}</th>
            <th>{{ __('Customer') }}</th>
            <th>{{ $referenceHeading }}</th>
            <th>{{ __('Method') }}</th>
            <th class="amount">{{ __('Amount') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($days as $day)
            <tr>
                <th colspan="5">{{ $day['date']->format('D, d M Y') }} · {{ $day['receipts'] }} {{ __('Receipts') }}</th>
                <th class="amount"><x-rupees :amount="$day['amount']" /></th>
            </tr>
            @foreach ($day['rows'] as $row)
                <tr>
                    <td>{{ $row['at']->format('h:i A') }}</td>
                    <td>{{ $row['number'] }}</td>
                    <td>{{ $row['customer']->name }}@if (filled($row['customer']->remarks)) ({{ $row['customer']->remarks }})@endif</td>
                    <td>{{ $row['reference'] }}</td>
                    <td>{{ $row['method'] }}</td>
                    <td class="amount"><x-rupees :amount="$row['amount']" /></td>
                </tr>
            @endforeach
        @empty
            <tr><td colspan="6">{{ __('Nothing collected in this period.') }}</td></tr>
        @endforelse
    </tbody>
    @if ($days->isNotEmpty())
        <tfoot>
            <tr>
                <th colspan="5">{{ __('Total') }}</th>
                <td class="amount"><strong><x-rupees :amount="$summary['collected']" /></strong></td>
            </tr>
        </tfoot>
    @endif
</table>
