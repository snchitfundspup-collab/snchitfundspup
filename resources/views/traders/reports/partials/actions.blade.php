{{-- Print / Download PDF for the report as filtered on screen.
     $report, $filters, and for dues $age / $search --}}

@php
    $query = $filters
        ? ($filters['range'] !== '' ? ['range' => $filters['range']] : ['from' => $filters['from'], 'to' => $filters['to']])
        : array_filter(['age' => ($age ?? 'all') === 'all' ? null : $age, 'q' => $search ?? null]);
@endphp

<div class="payment-summary-actions">
    <a href="{{ route('traders.reports.print', ['report' => $report] + $query) }}" class="group-action" target="_blank" rel="noopener">
        <x-icon name="printer" />
        <span data-i18n="print_report">Print report</span>
    </a>
    <a href="{{ route('traders.reports.pdf', ['report' => $report] + $query) }}" class="add-group-button">
        <x-icon name="download" />
        <span data-i18n="download_pdf">Download PDF</span>
    </a>
</div>
