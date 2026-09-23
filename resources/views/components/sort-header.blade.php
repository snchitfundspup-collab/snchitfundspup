@props([
    'label',
    'i18n',
    'ascending',
    'descending',
    'current',
])

@php
    /**
     * Clickable table heading. Clicking toggles between the column's
     * ascending and descending sort; any other column starts ascending.
     */
    $isAscending = $current === $ascending;
    $isDescending = $current === $descending;
    $nextSort = $isAscending ? $descending : $ascending;
@endphp

<th @if ($isAscending) aria-sort="ascending" @elseif ($isDescending) aria-sort="descending" @endif>

    <a
        href="{{ request()->fullUrlWithQuery(['sort' => $nextSort, 'page' => null]) }}"
        class="sort-header-link {{ $isAscending || $isDescending ? 'is-active' : '' }}"
        data-sort="{{ $nextSort }}"
    >
        <span data-i18n="{{ $i18n }}">{{ $label }}</span>

        <x-icon
            :name="$isAscending ? 'chevron-up' : ($isDescending ? 'chevron-down' : 'sort')"
            class="sort-header-icon"
        />
    </a>

</th>
