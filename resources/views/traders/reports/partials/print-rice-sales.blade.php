{{-- Rice Sales for the print page and PDF. --}}

@php
    $speedLabels = ['fast' => 'Fast', 'steady' => 'Steady', 'slow' => 'Slow', 'none' => 'Not sold'];
    $trim = fn ($value) => rtrim(rtrim(number_format((float) $value, 1), '0'), '.');
@endphp

<table class="info">
    <tr>
        <td><span class="label">Period</span><strong>@include('traders.reports.partials.period-text')</strong></td>
        <td><span class="label">Bags sold</span><strong>{{ $summary['bags'] }}</strong></td>
        <td><span class="label">Sales</span><strong><x-rupees :amount="$summary['amount']" /></strong></td>
        <td><span class="label">Invoices</span><strong>{{ $summary['invoices'] }}</strong></td>
        <td><span class="label">Bags a day</span><strong>{{ $trim($summary['per_day']) }}</strong></td>
    </tr>
</table>

<h3 class="list-title">Fastest selling rice</h3>

<table class="grid">
    <thead>
        <tr>
            <th>#</th>
            <th>Rice variety</th>
            <th class="amount">Bags sold</th>
            <th class="amount">Share</th>
            <th class="amount">Sales</th>
            <th class="amount">Avg rate / bag</th>
            <th class="amount">Bags / day</th>
            <th class="amount">In stock</th>
            <th class="amount">Stock lasts</th>
            <th>Speed</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td><strong>{{ $row['name'] }}</strong></td>
                <td class="amount">{{ $row['bags'] }}</td>
                <td class="amount">{{ $row['share'] }}%</td>
                <td class="amount"><x-rupees :amount="$row['amount']" /></td>
                <td class="amount">@if ($row['bags'] > 0)<x-rupees :amount="$row['average_rate']" />@else — @endif</td>
                <td class="amount">{{ $trim($row['per_day']) }}</td>
                <td class="amount {{ $row['stock_bags'] < 0 ? 'pending' : '' }}">{{ $row['stock_bags'] }}</td>
                <td class="amount">{{ $row['days_left'] !== null ? $row['days_left'].' days' : '—' }}</td>
                <td>{{ $speedLabels[$row['speed']] }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th colspan="2">Total</th>
            <td class="amount">{{ $summary['bags'] }}</td>
            <td></td>
            <td class="amount"><x-rupees :amount="$summary['amount']" /></td>
            <td></td>
            <td class="amount">{{ $trim($summary['per_day']) }}</td>
            <td class="amount">{{ $rows->sum('stock_bags') }}</td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>

@if ($topCustomers->isNotEmpty())
    <h3 class="list-title">Top customers</h3>
    <table class="grid">
        <thead>
            <tr>
                <th>#</th>
                <th>Customer</th>
                <th class="amount">Invoices</th>
                <th class="amount">Bags</th>
                <th class="amount">Sales</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($topCustomers as $top)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <strong>{{ $top['customer']->name }}</strong>
                        @if (filled($top['customer']->remarks))
                            <span class="ident">({{ $top['customer']->remarks }})</span>
                        @endif
                        <span class="code">{{ $top['customer']->customer_code }}</span>
                    </td>
                    <td class="amount">{{ $top['invoices'] }}</td>
                    <td class="amount">{{ $top['bags'] }}</td>
                    <td class="amount"><x-rupees :amount="$top['amount']" /></td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
