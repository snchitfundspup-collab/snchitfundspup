{{-- Day Book for the print page and PDF. --}}

<table class="info">
    <tr>
        <td><span class="label">{{ __('Period') }}</span><strong>@include('traders.reports.partials.period-text')</strong></td>
        <td><span class="label">{{ __('Lent (in hand)') }}</span><strong><x-rupees :amount="$summary['out']" /> ({{ $summary['loans'] }})</strong></td>
        <td><span class="label">{{ __('Fee + GST') }}</span><strong><x-rupees :amount="$summary['cut']" /></strong></td>
        <td><span class="label">{{ __('Collected') }}</span><strong><x-rupees :amount="$summary['in']" /> ({{ $summary['collections'] }})</strong></td>
        <td><span class="label">{{ __('Net cash') }}</span><strong class="{{ $summary['net'] < 0 ? 'pending' : '' }}"><x-rupees :amount="$summary['net']" /></strong></td>
    </tr>
</table>

<h3 class="list-title">{{ __('Day totals') }}</h3>

<table class="grid">
    <thead>
        <tr>
            <th>{{ __('Date') }}</th>
            <th class="amount">{{ __('Lent (in hand)') }}</th>
            <th class="amount">{{ __('Fee + GST') }}</th>
            <th class="amount">{{ __('Collected') }}</th>
            <th class="amount">{{ __('Net cash') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($days as $day)
            <tr>
                <td>{{ \Illuminate\Support\Carbon::parse($day['date'])->format('D, d M Y') }}</td>
                <td class="amount">@if ($day['out'] > 0)<x-rupees :amount="$day['out']" />@else — @endif</td>
                <td class="amount">@if ($day['cut'] > 0)<x-rupees :amount="$day['cut']" />@else — @endif</td>
                <td class="amount">@if ($day['in'] > 0)<x-rupees :amount="$day['in']" />@else — @endif</td>
                <td class="amount {{ $day['in'] - $day['out'] < 0 ? 'pending' : '' }}"><x-rupees :amount="$day['in'] - $day['out']" /></td>
            </tr>
        @empty
            <tr><td colspan="5">{{ __('Nothing lent or collected in this period') }}</td></tr>
        @endforelse
    </tbody>
    @if ($days->isNotEmpty())
        <tfoot>
            <tr>
                <th>{{ __('Total') }}</th>
                <td class="amount"><x-rupees :amount="$summary['out']" /></td>
                <td class="amount"><x-rupees :amount="$summary['cut']" /></td>
                <td class="amount"><x-rupees :amount="$summary['in']" /></td>
                <td class="amount"><strong><x-rupees :amount="$summary['net']" /></strong></td>
            </tr>
        </tfoot>
    @endif
</table>

@if ($entries->isNotEmpty())
    <h3 class="list-title">{{ __('All entries') }}</h3>
    <table class="grid">
        <thead>
            <tr>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Entry') }}</th>
                <th>{{ __('Customer') }}</th>
                <th>{{ __('Details') }}</th>
                <th class="amount">{{ __('Out') }}</th>
                <th class="amount">{{ __('In') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($entries as $entry)
                <tr>
                    <td>{{ \Illuminate\Support\Carbon::parse($entry['date'])->format('d M Y') }}</td>
                    <td>{{ __($entry['type'] === 'loan' ? 'Loan' : 'Receipt') }} {{ $entry['number'] }}</td>
                    <td>{{ $entry['customer']->name }}@if (filled($entry['customer']->remarks)) ({{ $entry['customer']->remarks }})@endif</td>
                    <td>{{ $entry['details'] }}</td>
                    <td class="amount">@if ($entry['out'] > 0)<x-rupees :amount="$entry['out']" />@endif</td>
                    <td class="amount">@if ($entry['in'] > 0)<x-rupees :amount="$entry['in']" />@endif</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
