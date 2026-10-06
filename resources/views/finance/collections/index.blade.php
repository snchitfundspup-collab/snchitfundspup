@extends('layouts.app')

@section('title', 'Collections | Sri Lakshmi Micro Finance')

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

@section('content')

<div class="groups-list-page payments-page traders-page finance-page">

    <div class="groups-list-header">
        <div>
            <h1 class="groups-title" data-i18n="all_collections">All Collections</h1>
            <p class="groups-subtitle" data-i18n="all_collections_subtitle">Money collected against loans. Opens on today — pick a range for more.</p>
        </div>
        <div class="ledger-actions">
            <a href="{{ route('finance.collect') }}" class="add-group-button">
                <x-icon name="rupee" />
                <span data-i18n="collect_today">Collect</span>
            </a>
        </div>
    </div>

    @include('traders.partials.list-filters', [
        'route' => 'finance.collections.index',
        'keep' => [],
        'searchPlaceholder' => 'Search name, ID, phone, receipt or loan number',
    ])

    <div id="paymentsResults">

        <section class="payment-summary-bar glass">
            <div class="payment-summary-main">
                <span class="payment-summary-range">@include('traders.partials.range-label')</span>
                <strong class="payment-summary-total"><x-rupees :amount="$total" /></strong>
                <span class="payment-summary-count">{{ $count }} <span data-i18n="{{ $count === 1 ? 'receipt_word' : 'receipts_word' }}">{{ $count === 1 ? 'receipt' : 'receipts' }}</span></span>
            </div>
            <div class="payment-summary-actions">
                <a href="{{ route('finance.reports.show', ['report' => 'day-book', 'from' => $filters['from'], 'to' => $filters['to']]) }}" class="group-action">
                    <x-icon name="calendar" />
                    <span data-i18n="day_book">Day Book</span>
                </a>
            </div>
        </section>

        @if ($collections->isEmpty())

            <div class="groups-empty glass">
                <span class="groups-empty-icon icon-3d icon-3d-orange"><x-icon name="rupee" /></span>
                <strong data-i18n="no_collections">No collections in this period</strong>
            </div>

        @else

            <div class="payment-list">
                @foreach ($collections as $collection)
                    <a href="{{ route('finance.collections.show', $collection) }}" class="payment-row glass">
                        <x-customer-avatar :customer="$collection->customer" />
                        <div class="payment-row-main">
                            <strong><x-customer-name :customer="$collection->customer" /></strong>
                            <span>{{ $collection->receipt_number }} · {{ $collection->loan->loan_number }} · {{ $collection->customer->customer_code }}</span>
                        </div>
                        <div class="payment-row-meta">
                            <span>{{ $collection->collected_at->format('d M Y, h:i A') }}</span>
                            <span>{{ $collection->methodLabel() }}</span>
                        </div>
                        <strong class="payment-row-amount"><x-rupees :amount="$collection->amount" /></strong>
                    </a>
                @endforeach
            </div>

            <x-pagination :paginator="$collections" label="Collection pages" />

        @endif

    </div>

</div>

@endsection
