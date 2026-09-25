{{-- Profit & Loss for the print page and PDF. --}}

<table class="info">
    <tr>
        <td><span class="label">{{ __('Period') }}</span><strong>@include('traders.reports.partials.period-text')</strong></td>
        <td><span class="label">{{ __('Sales') }}</span><strong><x-rupees :amount="$summary['sales']" /></strong></td>
        <td><span class="label">{{ __('Gross profit') }}</span><strong><x-rupees :amount="$summary['gross']" /> ({{ $summary['margin'] }}%)</strong></td>
        <td><span class="label">{{ __('Net profit') }}</span><strong class="{{ $summary['net'] < 0 ? 'pending' : '' }}"><x-rupees :amount="$summary['net']" /></strong></td>
    </tr>
</table>

@if ($summary['missing_cost'] !== [])
    <p class="warning">{{ __('No purchase price for :names — its cost is counted as ₹0.', ['names' => implode(', ', $summary['missing_cost'])]) }}</p>
@endif

<h3 class="list-title">{{ __('Profit statement') }}</h3>

<table class="grid">
    <tbody>
        <tr>
            <td>{{ __('Sales') }}</td>
            <td class="amount"><x-rupees :amount="$summary['sales']" /></td>
        </tr>
        <tr>
            <td>{{ __('Less: cost of the rice sold') }}</td>
            <td class="amount">− <x-rupees :amount="$summary['cost']" /></td>
        </tr>
        <tr>
            <th>{{ __('Gross profit') }}</th>
            <th class="amount"><x-rupees :amount="$summary['gross']" /></th>
        </tr>
        <tr>
            <td>{{ __('Less: expenses') }} ({{ $summary['expense_count'] }} {{ __('entries') }})</td>
            <td class="amount">− <x-rupees :amount="$summary['expenses']" /></td>
        </tr>
        <tr>
            <th>{{ __('Net profit') }}</th>
            <th class="amount {{ $summary['net'] < 0 ? 'pending' : '' }}"><x-rupees :amount="$summary['net']" /></th>
        </tr>
        @foreach ($partnerShares as $partner)
            <tr>
                <td>{{ __('Share of :name', ['name' => $partner['name']]) }}</td>
                <td class="amount"><x-rupees :amount="$partner['share']" /></td>
            </tr>
        @endforeach
        <tr>
            <td class="muted">{{ __('Rice purchased in this period (for reference)') }}</td>
            <td class="amount muted"><x-rupees :amount="$summary['purchases']" /></td>
        </tr>
        <tr>
            <td class="muted">{{ __('Rice in stock now, at cost (for reference)') }}</td>
            <td class="amount muted"><x-rupees :amount="$summary['stock_value']" /></td>
        </tr>
    </tbody>
</table>

@if ($rows->isNotEmpty())
    <h3 class="list-title">{{ __('Profit by rice') }}</h3>
    <table class="grid">
        <thead>
            <tr>
                <th>{{ __('Rice variety') }}</th>
                <th class="amount">{{ __('Bags sold') }}</th>
                <th class="amount">{{ __('Sales') }}</th>
                <th class="amount">{{ __('Selling price') }}</th>
                <th class="amount">{{ __('Purchase price') }}</th>
                <th class="amount">{{ __('Cost') }}</th>
                <th class="amount">{{ __('Profit / bag') }}</th>
                <th class="amount">{{ __('Profit') }}</th>
                <th class="amount">{{ __('Margin') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td><strong>{{ $row['name'] }}</strong></td>
                    <td class="amount">{{ $row['bags'] }}</td>
                    <td class="amount"><x-rupees :amount="$row['sales']" /></td>
                    <td class="amount"><x-rupees :amount="$row['sale_rate']" /></td>
                    <td class="amount">@if ($row['cost_rate'] !== null)<x-rupees :amount="$row['cost_rate']" />@else — @endif</td>
                    <td class="amount"><x-rupees :amount="$row['cost']" /></td>
                    <td class="amount">@if ($row['cost_rate'] !== null)<x-rupees :amount="$row['profit_per_bag']" />@else — @endif</td>
                    <td class="amount {{ $row['profit'] < 0 ? 'pending' : '' }}"><x-rupees :amount="$row['profit']" /></td>
                    <td class="amount">{{ $row['margin'] }}%</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th>{{ __('Total') }}</th>
                <td class="amount">{{ $rows->sum('bags') }}</td>
                <td class="amount"><x-rupees :amount="$summary['sales']" /></td>
                <td colspan="2"></td>
                <td class="amount"><x-rupees :amount="$summary['cost']" /></td>
                <td></td>
                <td class="amount"><x-rupees :amount="$summary['gross']" /></td>
                <td class="amount">{{ $summary['margin'] }}%</td>
            </tr>
        </tfoot>
    </table>
@endif
