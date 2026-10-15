{{-- Where a loan or chit seat stands today, for the Agent Statement.
     Loans use the Micro Finance badge; chit seats: Pending (red), Due /
     Part paid (orange), Upcoming, Paid up, Forming. $state --}}
@if (in_array($state, ['overdue', 'due', 'not_started', 'closed', 'on_track'], true))
    @include('finance.partials.state-badge', ['state' => $state])
@else
    @php
        [$badgeClass, $badgeKey, $badgeLabel] = match ($state) {
            'pending' => ['due-badge-due', 'collect_state_pending', 'Pending'],
            'partial' => ['due-badge-part', 'collect_state_partial', 'Part paid'],
            'upcoming' => ['due-badge-upcoming', 'collect_state_upcoming', 'Upcoming'],
            'clear' => ['due-badge-ok', 'collect_state_clear', 'Paid up'],
            'forming' => ['due-badge-upcoming', 'forming_word', 'Forming'],
            default => ['due-badge-month', 'collect_state_due', 'Due'],
        };
    @endphp
    <span class="due-badge {{ $badgeClass }}" data-i18n="{{ $badgeKey }}">{{ $badgeLabel }}</span>
@endif
