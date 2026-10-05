@extends('layouts.app')

@section('title', $title.' | Sri Lakshmi Micro Finance')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css',
        'resources/css/finance.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/payments.js')
@endpush

@php
    $headings = [
        'outstanding' => ['outstanding_loans', 'Outstanding Loans', 'outstanding_subtitle', 'Every running loan: what was lent, collected, what is left and what is overdue.'],
        'overdue' => ['overdue_loans', 'Overdue Loans', 'overdue_subtitle', 'Loans with instalments not paid on their date, longest overdue first.'],
        'day-book' => ['day_book', 'Day Book', 'finance_day_book_subtitle', 'Money lent and money collected, day by day.'],
        'profit' => ['profit_loss', 'Profit & Loss', 'finance_profit_subtitle', 'Processing fees and the interest collected, less expenses — the business keeps the profit. GST is kept apart.'],
    ];
    [$titleKey, $titleText, $subtitleKey, $subtitleText] = $headings[$report];
    $query = $filters ? ($filters['range'] !== '' ? ['range' => $filters['range']] : ['from' => $filters['from'], 'to' => $filters['to']]) : [];
@endphp

@section('content')

<div class="groups-list-page payments-page traders-page finance-page">

    <div class="groups-list-header">
        <div>
            <h1 class="groups-title" data-i18n="{{ $titleKey }}">{{ $titleText }}</h1>
            <p class="groups-subtitle" data-i18n="{{ $subtitleKey }}">{{ $subtitleText }}</p>
        </div>
    </div>

    @if ($filters)
        @include('traders.partials.list-filters', [
            'route' => 'finance.reports.show',
            'routeParams' => ['report' => $report],
            'keep' => [],
            'searchPlaceholder' => null,
        ])
    @endif

    <div id="paymentsResults">

        <section class="payment-summary-bar glass">
            @include('finance.reports.partials.summary-'.$report)
            <div class="payment-summary-actions">
                <a href="{{ route('finance.reports.print', ['report' => $report] + $query) }}" class="group-action" target="_blank" rel="noopener">
                    <x-icon name="printer" />
                    <span data-i18n="print_report">Print report</span>
                </a>
                <a href="{{ route('finance.reports.pdf', ['report' => $report] + $query) }}" class="add-group-button">
                    <x-icon name="download" />
                    <span data-i18n="download_pdf">Download PDF</span>
                </a>
            </div>
        </section>

        @include('finance.reports.partials.screen-'.$report)

    </div>

</div>

@endsection
