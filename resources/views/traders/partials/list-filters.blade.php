{{-- Date range buttons + From / To + extra filters + search, for the
     Traders lists (sales, purchases, receipts). Live filtering by
     payments.js via #paymentFilterForm / #paymentsResults / #paymentRanges.
     $route, $filters, $ranges, $keep, $searchPlaceholder, $extra (html) --}}

<nav class="status-tabs payment-ranges" id="paymentRanges" aria-label="Date range">
    @foreach ($ranges as $rangeKey => $quick)
        <a
            href="{{ route($route, $keep + ['range' => $rangeKey]) }}"
            @class(['status-tab', 'active' => $filters['range'] === $rangeKey])
        >
            <span data-i18n="{{ $quick['i18n'] }}">{{ $quick['label'] }}</span>
        </a>
    @endforeach
</nav>

<form
    method="GET"
    action="{{ route($route) }}"
    class="payment-filters payment-filters-grid"
    id="paymentFilterForm"
    role="search"
>

    <label class="payment-filter">
        <span data-i18n="from_date">From</span>
        <input type="date" name="from" class="input payment-filter-select" value="{{ $filters['from'] }}">
    </label>

    <label class="payment-filter">
        <span data-i18n="to_date">To</span>
        <input type="date" name="to" class="input payment-filter-select" value="{{ $filters['to'] }}">
    </label>

    {!! $extra ?? '' !!}

    <div class="member-search payment-filter-search">
        <span class="member-search-icon"><x-icon name="search" /></span>
        <input
            type="search"
            name="q"
            class="member-search-input"
            value="{{ $filters['q'] }}"
            placeholder="{{ $searchPlaceholder }}"
            autocomplete="off"
        >
    </div>

    <a
        href="{{ route($route) }}"
        class="group-action"
        id="paymentFilterClear"
        @if ($keep === [] && $filters['range'] === 'month') hidden @endif
    >
        <x-icon name="x" />
        <span data-i18n="clear">Clear</span>
    </a>

</form>
