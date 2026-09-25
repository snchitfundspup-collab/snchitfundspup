{{-- A chit group that is forming: amount, monthly instalment × months,
     start date and seats left. $group (with members_count), $joined,
     $joinRequest (the customer's latest request for it, optional) --}}

@php
    $seatsLeft = max(0, $group->member_count - $group->members_count);
@endphp

<a href="{{ route('portal.upcoming.show', $group) }}" class="portal-card glass">

    <div class="portal-card-top">
        <span class="portal-card-icon icon-3d icon-3d-purple"><x-icon name="calendar" /></span>
        <div class="portal-card-title">
            <strong>{{ $group->name }}</strong>
            <small><span data-i18n="starts_on">Starts on</span> {{ $group->start_date?->format('d M Y') }}</small>
        </div>
        @if ($joined ?? false)
            <span class="due-badge due-badge-ok" data-i18n="you_joined">You joined</span>
        @elseif (($joinRequest ?? null)?->isPending())
            <span class="due-badge due-badge-month" data-i18n="request_sent">Request sent</span>
        @elseif ($seatsLeft > 0)
            <span class="due-badge due-badge-done">{{ $seatsLeft }} <span data-i18n="seats_left">seats left</span></span>
        @else
            <span class="due-badge due-badge-upcoming" data-i18n="group_full">Full</span>
        @endif
    </div>

    <div class="portal-card-facts">
        <span>
            <small data-i18n="chit_amount">Chit amount</small>
            <strong><x-rupees :amount="$group->amount" /></strong>
        </span>
        <span>
            <small data-i18n="monthly_installment">Monthly</small>
            <strong><x-rupees :amount="$group->installment_amount" /></strong>
        </span>
        <span>
            <small data-i18n="months_word">Months</small>
            <strong>{{ $group->months }}</strong>
        </span>
    </div>

</a>
