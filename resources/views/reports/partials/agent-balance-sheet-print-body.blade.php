{{-- Agent Balance Sheet for the print page and PDF. --}}

<table class="info">
    <tr>
        <td><span class="label">{{ __('Agent') }}</span><strong>{{ $agent?->name }}</strong></td>
        <td><span class="label">{{ __('Period') }}</span><strong>@include('traders.reports.partials.period-text')</strong></td>
        <td><span class="label">{{ __('In hand at the start') }}</span><strong><x-rupees :amount="$summary['opening']" /></strong></td>
        <td><span class="label">{{ __('Money in') }}</span><strong>+ <x-rupees :amount="$summary['in']" /></strong></td>
        <td><span class="label">{{ __('Money out') }}</span><strong>− <x-rupees :amount="$summary['out']" /></strong></td>
        <td><span class="label">{{ __('In hand at the end') }}</span><strong class="{{ $summary['closing'] < 0 ? 'pending' : '' }}"><x-rupees :amount="$summary['closing']" /></strong></td>
    </tr>
</table>

<table class="grid">
    <thead>
        <tr>
            <th>{{ __('Date & time') }}</th>
            <th>{{ __('Entry') }}</th>
            <th>{{ __('Details') }}</th>
            <th class="amount">{{ __('In') }}</th>
            <th class="amount">{{ __('Out') }}</th>
            <th class="amount">{{ __('In hand') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>{{ $row['at']->format('d M Y, h:i A') }}</td>
                <td>{{ $row['label'] }}</td>
                <td>{{ $row['detail'] ?: '—' }}</td>
                <td class="amount">@if ($row['in'] > 0)<x-rupees :amount="$row['in']" />@endif</td>
                <td class="amount">@if ($row['out'] > 0)<x-rupees :amount="$row['out']" />@endif</td>
                <td class="amount {{ $row['balance'] < 0 ? 'pending' : '' }}"><x-rupees :amount="$row['balance']" /></td>
            </tr>
        @empty
            <tr><td colspan="6">{{ __('Nothing in or out in this period.') }}</td></tr>
        @endforelse
    </tbody>
    @if ($rows->isNotEmpty())
        <tfoot>
            <tr>
                <th colspan="3">{{ __('Total') }}</th>
                <td class="amount"><x-rupees :amount="$summary['in']" /></td>
                <td class="amount"><x-rupees :amount="$summary['out']" /></td>
                <td class="amount"><strong><x-rupees :amount="$summary['closing']" /></strong></td>
            </tr>
        </tfoot>
    @endif
</table>
