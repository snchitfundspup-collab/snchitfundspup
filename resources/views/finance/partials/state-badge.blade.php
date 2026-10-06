{{-- Where a loan stands: Overdue (red), Due today (orange), On track,
     Not started, Closed. $state --}}

@php
    [$badgeClass, $badgeKey, $badgeLabel] = match ($state) {
        'overdue' => ['due-badge-due', 'overdue_word', 'Overdue'],
        'due' => ['due-badge-month', 'due_today', 'Due today'],
        'not_started' => ['due-badge-upcoming', 'not_started', 'Not started'],
        'closed' => ['due-badge-done', 'closed_word', 'Closed'],
        default => ['due-badge-ok', 'on_track', 'On track'],
    };
@endphp

<span class="due-badge {{ $badgeClass }}" data-i18n="{{ $badgeKey }}">{{ $badgeLabel }}</span>
