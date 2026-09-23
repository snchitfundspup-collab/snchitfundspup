@props([
    'paginator',
    'label' => 'Pages',
])

{{-- "Showing 1–20 of 45" + ‹ numbered pages › (current page ±2).
     Styles: .groups-pagination in groups.css. Renders nothing when
     everything fits on one page. --}}

@if ($paginator->hasPages())

    <nav
        {{ $attributes->merge(['class' => 'groups-pagination']) }}
        aria-label="{{ $label }}"
    >

        <span class="groups-page-info">
            <span data-i18n="showing">Showing</span>
            <strong>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong>
            <span data-i18n="of">of</span>
            <strong>{{ $paginator->total() }}</strong>
        </span>

        <span class="groups-page-links">

            @if ($paginator->onFirstPage())
                <span class="groups-page-button disabled"><x-icon name="chevron-left" /></span>
            @else
                <a
                    href="{{ $paginator->previousPageUrl() }}"
                    class="groups-page-button"
                    aria-label="Previous page"
                ><x-icon name="chevron-left" /></a>
            @endif

            @foreach ($paginator->getUrlRange(
                max(1, $paginator->currentPage() - 2),
                min($paginator->lastPage(), $paginator->currentPage() + 2)
            ) as $page => $url)

                @if ($page === $paginator->currentPage())
                    <span
                        class="groups-page-button active"
                        aria-current="page"
                    >{{ $page }}</span>
                @else
                    <a
                        href="{{ $url }}"
                        class="groups-page-button"
                    >{{ $page }}</a>
                @endif

            @endforeach

            @if ($paginator->hasMorePages())
                <a
                    href="{{ $paginator->nextPageUrl() }}"
                    class="groups-page-button"
                    aria-label="Next page"
                ><x-icon name="chevron-right" /></a>
            @else
                <span class="groups-page-button disabled"><x-icon name="chevron-right" /></span>
            @endif

        </span>

    </nav>

@endif
