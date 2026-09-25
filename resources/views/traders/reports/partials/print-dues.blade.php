{{-- Customer Dues for the print page and PDF. --}}

@php
    $bucketLabels = ['b30' => '0–30 days', 'b60' => '31–60 days', 'b90' => '61–90 days', 'over90' => 'Over 90 days'];
    $ageLabels = ['all' => 'Everyone who owes', '30' => 'Unpaid over 30 days', '60' => 'Unpaid over 60 days', '90' => 'Unpaid over 90 days'];
@endphp

<table class="info">
    <tr>
        <td><span class="label">{{ __('As on') }}</span><strong>{{ $today->format('D, d M Y') }}</strong></td>
        <td><span class="label">{{ __('Customers owe') }}</span><strong class="pending"><x-rupees :amount="$summary['total']" /></strong></td>
        <td><span class="label">{{ __('Customers') }}</span><strong>{{ $summary['count'] }}</strong></td>
        <td><span class="label">{{ __('Showing') }}</span><strong>{{ __($ageLabels[$age]) }}</strong></td>
    </tr>
    @if ($search !== '')
        <tr>
            <td colspan="4" class="muted">{{ __('Search') }}: “{{ $search }}”</td>
        </tr>
    @endif
</table>

<table class="grid">
    <thead>
        <tr>
            <th>#</th>
            <th>{{ __('Customer') }}</th>
            <th>{{ __('Phone') }}</th>
            <th>{{ __('Oldest unpaid') }}</th>
            <th>{{ __('Last paid') }}</th>
            @foreach ($bucketLabels as $bucketLabel)
                <th class="amount">{{ __($bucketLabel) }}</th>
            @endforeach
            <th class="amount">{{ __('Balance') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            @php $customer = $row['customer']; @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>
                    <strong>{{ $customer->name }}</strong>
                    @if (filled($customer->remarks))
                        <span class="ident">({{ $customer->remarks }})</span>
                    @endif
                    <span class="code">{{ $customer->customer_code }}</span>
                </td>
                <td class="nowrap">{{ $customer->phone ?: '—' }}</td>
                <td class="nowrap">
                    @if ($row['oldest_unpaid'])
                        {{ \Illuminate\Support\Carbon::parse($row['oldest_unpaid'])->format('d M Y') }}
                        <span class="{{ $row['days'] > 30 ? 'pending' : 'due-open' }}">({{ $row['days'] }} {{ __('days') }})</span>
                    @else
                        —
                    @endif
                </td>
                <td class="nowrap">{{ $row['last_paid'] ? \Illuminate\Support\Carbon::parse($row['last_paid'])->format('d M Y') : '—' }}</td>
                @foreach (array_keys($bucketLabels) as $bucket)
                    <td class="amount">@if ($row['buckets'][$bucket] > 0)<x-rupees :amount="$row['buckets'][$bucket]" />@else — @endif</td>
                @endforeach
                <td class="amount"><strong><x-rupees :amount="$row['balance']" /></strong></td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th colspan="5">{{ __('Total') }} ({{ $rows->count() }})</th>
            @foreach (array_keys($bucketLabels) as $bucket)
                <td class="amount"><x-rupees :amount="$rows->sum(fn ($row) => $row['buckets'][$bucket])" /></td>
            @endforeach
            <td class="amount"><x-rupees :amount="$summary['shown_total']" /></td>
        </tr>
    </tfoot>
</table>
