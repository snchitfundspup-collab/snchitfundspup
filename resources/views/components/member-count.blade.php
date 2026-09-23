@props([
    'total',
    'planned',
    'showStartHint' => true,
])

@php
    /**
     * "18 / 20 members" with a start-readiness hint: under (orange),
     * exact (green) or over (red) the planned number of members.
     */
    $gap = $planned - $total;

    $state = match (true) {
        $gap > 0 => 'under',
        $gap < 0 => 'over',
        default => 'exact',
    };
@endphp

<div {{ $attributes->merge(['class' => "member-count member-count-{$state}"]) }}>

    <span class="member-count-number">
        {{ $total }} / {{ $planned }}
    </span>

    <span
        class="member-count-label"
        data-i18n="group_members"
    >members</span>

    @if ($showStartHint)

        <span class="member-count-hint">
            @if ($state === 'under')
                Add {{ $gap }} more to start
            @elseif ($state === 'over')
                Remove {{ abs($gap) }} to start
            @else
                Ready to start
            @endif
        </span>

    @endif

</div>
