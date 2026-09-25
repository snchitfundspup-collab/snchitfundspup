{{-- One of the customer's chit seats: group, instalment, where it stands
     (Pending red / Due orange / Part paid orange) and what to pay now.
     $seat: member, group, status (null unless running), total_paid, won --}}

@php
    $group = $seat['group'];
    $status = $seat['status'];

    [$badgeClass, $badgeKey, $badgeLabel] = match (true) {
        $group->isForming() => ['due-badge-done', 'starting_soon', 'Starting soon'],
        $status === null => ['due-badge-ok', 'completed_word', 'Completed'],
        $status['state'] === 'pending' => ['due-badge-due', 'collect_state_pending', 'Pending'],
        $status['state'] === 'partial' => ['due-badge-part', 'ledger_partial', 'Part paid'],
        $status['state'] === 'due' => ['due-badge-month', 'collect_state_due', 'Due'],
        $status['state'] === 'upcoming' => ['due-badge-upcoming', 'collect_state_upcoming', 'Upcoming'],
        default => ['due-badge-ok', 'paid_up', 'Paid up'],
    };
@endphp

<a href="{{ route('portal.groups.show', $seat['member']) }}" class="portal-card glass">

    <div class="portal-card-top">
        <span class="portal-card-icon icon-3d icon-3d-blue"><x-icon name="layers" /></span>
        <div class="portal-card-title">
            <strong>{{ $group->name }}</strong>
            <small>{{ $seat['member']->member_code }} · <x-rupees :amount="$group->amount" /></small>
        </div>
        <span class="due-badge {{ $badgeClass }}" data-i18n="{{ $badgeKey }}">{{ $badgeLabel }}</span>
    </div>

    <div class="portal-card-facts">
        <span>
            <small data-i18n="months_completed">Months completed</small>
            <strong>{{ $seat['months_done'] }} / {{ $group->months }}</strong>
        </span>
        <span>
            <small data-i18n="months_remaining">Months remaining</small>
            <strong>{{ $seat['months_left'] }}</strong>
        </span>
        <span>
            <small data-i18n="monthly_installment">Monthly</small>
            <strong><x-rupees :amount="$group->installment_amount" /></strong>
        </span>
        <span>
            <small data-i18n="total_paid">Total paid</small>
            <strong><x-rupees :amount="$seat['total_paid']" /></strong>
        </span>
        @if ($status && $status['amount_due'] > 0)
            <span>
                <small data-i18n="to_pay_now">To pay now</small>
                <strong @class(['ledger-total-due' => $status['pending'] > 0, 'ledger-total-open' => $status['pending'] === 0])><x-rupees :amount="$status['amount_due']" /></strong>
            </span>
        @elseif ($group->isForming())
            <span>
                <small data-i18n="starts_on">Starts on</small>
                <strong>{{ $group->start_date?->format('d M Y') }}</strong>
            </span>
        @endif
    </div>

    @if ($status && $status['month'] > 0)
        <p class="portal-card-note">
            <span data-i18n="next_due">Next due</span>:
            <span data-i18n="month_number">Month</span> {{ $status['month'] }}
            · {{ $group->dateForMonth($status['month'])->format('d M Y') }}
        </p>
    @endif

    @if ($seat['won'])
        <p class="portal-card-note portal-card-won">
            <x-icon name="trophy" />
            <span data-i18n="you_won">You won</span> <x-rupees :amount="$seat['won']->prizeAmount()" />
            (<span data-i18n="month_number">Month</span> {{ $seat['won']->month_number }})
        </p>
    @endif

</a>
