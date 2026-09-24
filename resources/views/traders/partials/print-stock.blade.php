{{-- Stock per rice variety for the print page and PDF. --}}

@php
    $kg = fn ($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
@endphp

<table class="info">
    <tr>
        <td><span class="label">As on</span><strong>{{ $today->format('D, d M Y') }}</strong></td>
        <td><span class="label">Rice in stock</span><strong>{{ $totalBags }} bags</strong></td>
        <td><span class="label">Varieties</span><strong>{{ $rows->count() }}</strong></td>
    </tr>
</table>

<table class="grid">
    <thead>
        <tr>
            <th>Rice variety</th>
            <th class="amount">Bought (bags)</th>
            <th class="amount">Sold (bags)</th>
            <th class="amount">In stock (bags)</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td><strong>{{ $row['name'] }}</strong> <span class="muted">({{ $kg($row['bag_kg']) }} kg bag)</span></td>
                <td class="amount">{{ $row['purchased_bags'] }}</td>
                <td class="amount">{{ $row['sold_bags'] }}</td>
                <td class="amount {{ $row['stock_bags'] < 0 ? 'pending' : '' }}">{{ $row['stock_bags'] }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th>Total</th>
            <td class="amount">{{ $rows->sum('purchased_bags') }}</td>
            <td class="amount">{{ $rows->sum('sold_bags') }}</td>
            <td class="amount">{{ $totalBags }}</td>
        </tr>
    </tfoot>
</table>
