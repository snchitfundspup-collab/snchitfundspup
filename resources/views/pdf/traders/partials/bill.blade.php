{{-- PDF body for a rice bill (invoice / purchase): party, lines, totals.
     $numberLabel, $number, $date, $partyRows [[label, value]], $items,
     $total, $extraRows [[label, amount]] --}}

<table class="meta">
    <tr>
        <td><span class="label">{{ $numberLabel }}</span><strong>{{ $number }}</strong></td>
        <td class="right"><span class="label">Date</span><strong>{{ $date }}</strong></td>
    </tr>
</table>

<table class="party">
    @foreach ($partyRows as [$rowLabel, $rowValue])
        <tr>
            <th>{{ $rowLabel }}</th>
            <td>{{ $rowValue }}</td>
        </tr>
    @endforeach
</table>

<table class="items">
    <thead>
        <tr>
            <th>#</th>
            <th>Rice</th>
            <th class="right">Bags</th>
            <th class="right">Rate / bag</th>
            <th class="right">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td><strong>{{ $item->variety->name }}</strong></td>
                <td class="right">{{ $item->bags }}</td>
                <td class="right"><x-rupees :amount="$item->rate" /></td>
                <td class="right"><x-rupees :amount="$item->amount" /></td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th colspan="2">Total</th>
            <td class="right">{{ $items->sum('bags') }}</td>
            <td></td>
            <td class="right"><strong><x-rupees :amount="$total" /></strong></td>
        </tr>
    </tfoot>
</table>

<p class="words">
    Rupees {{ \App\Models\Payment::numberInWords((int) floor($total)) }}{{ round($total - floor($total), 2) > 0 ? ' and '.\App\Models\Payment::numberInWords((int) round(($total - floor($total)) * 100)).' Paise' : '' }} Only
</p>

@if (! empty($extraRows))
    <table class="party">
        @foreach ($extraRows as [$rowLabel, $rowAmount])
            <tr>
                <th>{{ $rowLabel }}</th>
                <td class="right"><strong><x-rupees :amount="$rowAmount" /></strong></td>
            </tr>
        @endforeach
    </table>
@endif

<table class="signature">
    <tr>
        <td>Recorded by: {{ $recordedBy ?? '—' }}</td>
        <td class="right">Authorised signature</td>
    </tr>
</table>
