{{-- Report page heading, and the date range filters when the report has
     a period. $titleKey, $title, $subtitleKey, $subtitle, $report, $filters, $ranges --}}

<div class="groups-list-header">
    <div>
        <h1 class="groups-title" data-i18n="{{ $titleKey }}">{{ $title }}</h1>
        <p class="groups-subtitle" data-i18n="{{ $subtitleKey }}">{{ $subtitle }}</p>
    </div>
</div>

@if ($filters)
    @include('traders.partials.list-filters', [
        'route' => 'traders.reports.show',
        'routeParams' => ['report' => $report],
        'keep' => [],
        'searchPlaceholder' => null,
    ])
@endif
