{{-- Day Book for the print page and PDF. --}}

<table class="info">
    <tr>
        <td><span class="label">{{ __('Period') }}</span><strong>@include('traders.reports.partials.period-text')</strong></td>
        <td><span class="label">{{ __('Sales') }}</span><strong><x-rupees :amount="$summary['sales']" /></strong></td>
        <td><span class="label">{{ __('Money in') }}</span><strong><x-rupees :amount="$summary['received']" /></strong></td>
        <td><span class="label">{{ __('Money out') }}</span><strong><x-rupees :amount="$summary['purchases'] + $summary['expenses']" /></strong></td>
        <td><span class="label">{{ __('Net') }}</span><strong class="{{ $summary['net'] < 0 ? 'pending' : '' }}"><x-rupees :amount="$summary['net']" /></strong></td>
    </tr>
</table>

<table class="grid">
    <thead>
        <tr>
            <th>{{ __('Date') }}</th>
            <th class="amount">{{ __('Sales') }}</th>
            <th class="amount">{{ __('Invoices') }}</th>
            @foreach ($methods as $methodLabel)
                <th class="amount">{{ __($methodLabel) }}</th>
            @endforeach
            <th class="amount">{{ __('Money in') }}</th>
            <th class="amount">{{ __('Purchases') }}</th>
            <th class="amount">{{ __('Expenses') }}</th>
            <th class="amount">{{ __('Net') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td class="nowrap">{{ \Illuminate\Support\Carbon::parse($row['date'])->format('D, d M Y') }}</td>
                <td class="amount"><x-rupees :amount="$row['sales']" /></td>
                <td class="amount">{{ $row['invoices'] }}</td>
                @foreach (array_keys($methods) as $method)
                    <td class="amount"><x-rupees :amount="$row['by_method'][$method]" /></td>
                @endforeach
                <td class="amount"><x-rupees :amount="$row['received']" /></td>
                <td class="amount"><x-rupees :amount="$row['purchases']" /></td>
                <td class="amount"><x-rupees :amount="$row['expenses']" /></td>
                <td class="amount {{ $row['net'] < 0 ? 'pending' : '' }}"><x-rupees :amount="$row['net']" /></td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th>{{ __('Total') }} ({{ $rows->count() }} {{ __('days') }})</th>
            <td class="amount"><x-rupees :amount="$summary['sales']" /></td>
            <td class="amount">{{ $summary['invoices'] }}</td>
            @foreach (array_keys($methods) as $method)
                <td class="amount"><x-rupees :amount="$summary['by_method'][$method]" /></td>
            @endforeach
            <td class="amount"><x-rupees :amount="$summary['received']" /></td>
            <td class="amount"><x-rupees :amount="$summary['purchases']" /></td>
            <td class="amount"><x-rupees :amount="$summary['expenses']" /></td>
            <td class="amount"><x-rupees :amount="$summary['net']" /></td>
        </tr>
    </tfoot>
</table>

<p class="muted">{{ __('Money in is what customers paid that day (at a sale or later). Money out is rice purchased plus expenses.') }}</p>
