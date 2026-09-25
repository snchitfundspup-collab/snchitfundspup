{{-- Pending & Due report body for the print page and PDF: the totals, then
     the Pending list (longest overdue first) and the Due list. --}}

<table class="info">
    <tr>
        <td><span class="label">{{ __('As on') }}</span><strong>{{ $today->format('D, d M Y') }}</strong></td>
        <td><span class="label">{{ __('Group') }}</span><strong>{{ $group?->name ?? __('All running groups') }}</strong></td>
        @if ($type !== 'due')
            <td><span class="label">{{ __('Pending') }}</span><strong class="pending"><x-rupees :amount="$pending->sum('amount')" /></strong> <span class="muted">({{ $pending->count() }})</span></td>
        @endif
        @if ($type !== 'pending')
            <td><span class="label">{{ __('Due') }}</span><strong class="due-open"><x-rupees :amount="$due->sum('amount')" /></strong> <span class="muted">({{ $due->count() }})</span></td>
        @endif
    </tr>
</table>

@foreach (['pending' => $pending, 'due' => $due] as $listName => $rows)

    @continue($type !== 'all' && $type !== $listName)

    <h3 class="list-title {{ $listName === 'pending' ? 'pending' : 'due-open' }}">
        {{ __($listName === 'pending' ? 'Pending — past the due date' : 'Due — due this month') }}
        ({{ $rows->count() }})
    </h3>

    @if ($rows->isEmpty())
        <p class="muted">{{ __('No members.') }}</p>
    @else
        <table class="grid">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('Member') }}</th>
                    <th>{{ __('Phone') }}</th>
                    <th>{{ __('Group') }}</th>
                    <th>{{ __('Month(s)') }}</th>
                    <th>{{ __('Due date') }}</th>
                    @if ($listName === 'pending')
                        <th class="amount">{{ __('Days overdue') }}</th>
                    @endif
                    <th class="amount">{{ __('Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $row['member']->customer->name }}</strong>
                            @if (filled($row['member']->customer->remarks))
                                <span class="ident">({{ $row['member']->customer->remarks }})</span>
                            @endif
                            <span class="code">{{ $row['member']->member_code }}</span>
                        </td>
                        <td class="nowrap">{{ $row['member']->customer->phone ?: '—' }}</td>
                        <td>{{ $row['group'] }}</td>
                        <td class="nowrap">
                            {{ __(count($row['months']) > 1 ? 'Months' : 'Month') }} {{ implode(', ', $row['months']) }}
                            @if ($row['part_paid'] > 0)
                                <span class="muted">(<x-rupees :amount="$row['part_paid']" /> {{ __('paid') }})</span>
                            @endif
                        </td>
                        <td class="nowrap">{{ \Illuminate\Support\Carbon::parse($row['due_date'])->format('d M Y') }}</td>
                        @if ($listName === 'pending')
                            <td class="amount">{{ $row['days_overdue'] }}</td>
                        @endif
                        <td class="amount"><x-rupees :amount="$row['amount']" /></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="{{ $listName === 'pending' ? 7 : 6 }}">{{ __('Total') }} ({{ $rows->count() }})</th>
                    <td class="amount"><x-rupees :amount="$rows->sum('amount')" /></td>
                </tr>
            </tfoot>
        </table>
    @endif

@endforeach

@if ($group === null && $byGroup->count() > 1)
    <h3 class="list-title">{{ __('By group') }}</h3>

    <table class="grid">
        <thead>
            <tr>
                <th>{{ __('Group') }}</th>
                @if ($type !== 'due')
                    <th class="amount">{{ __('Pending') }}</th>
                @endif
                @if ($type !== 'pending')
                    <th class="amount">{{ __('Due') }}</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($byGroup as $groupTotals)
                <tr>
                    <td>{{ $groupTotals['name'] }}</td>
                    @if ($type !== 'due')
                        <td class="amount"><x-rupees :amount="$groupTotals['pending_amount']" /> <span class="muted">({{ $groupTotals['pending_count'] }})</span></td>
                    @endif
                    @if ($type !== 'pending')
                        <td class="amount"><x-rupees :amount="$groupTotals['due_amount']" /> <span class="muted">({{ $groupTotals['due_count'] }})</span></td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
