{{-- Stock per rice variety for the print page and PDF. --}}

@php
    $kg = fn ($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
@endphp

<table class="info">
    <tr>
        <td><span class="label">{{ __('As on') }}</span><strong>{{ $today->format('D, d M Y') }}</strong></td>
        <td><span class="label">{{ __('Rice in stock') }}</span><strong>{{ $totalBags }} {{ __('bags') }}</strong></td>
        <td><span class="label">{{ __('Varieties') }}</span><strong>{{ $rows->count() }}</strong></td>
    </tr>
</table>

<table class="grid">
    <thead>
        <tr>
            <th>{{ __('Rice variety') }}</th>
            <th class="amount">{{ __('Bought (bags)') }}</th>
            <th class="amount">{{ __('Sold (bags)') }}</th>
            <th class="amount">{{ __('In stock (bags)') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td><strong>{{ $row['name'] }}</strong> <span class="muted">({{ $kg($row['bag_kg']) }} {{ __('kg bag') }})</span></td>
                <td class="amount">{{ $row['purchased_bags'] }}</td>
                <td class="amount">{{ $row['sold_bags'] }}</td>
                <td class="amount {{ $row['stock_bags'] < 0 ? 'pending' : '' }}">{{ $row['stock_bags'] }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th>{{ __('Total') }}</th>
            <td class="amount">{{ $rows->sum('purchased_bags') }}</td>
            <td class="amount">{{ $rows->sum('sold_bags') }}</td>
            <td class="amount">{{ $totalBags }}</td>
        </tr>
    </tfoot>
</table>
