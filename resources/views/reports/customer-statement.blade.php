@extends('layouts.app')

@section('title', 'Customer Statement'.($customer ? ' – '.$customer->name : '').' | SN Chit Funds')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/payments.js')
@endpush

@section('content')

<div class="groups-list-page payments-page statement-page">


    {{-- =====================================================
         HEADER
    ====================================================== --}}

    <div class="groups-list-header no-print">

        <div>
            @if ($customer)
                <a
                    href="{{ route('reports.customer') }}"
                    class="group-back-link"
                >
                    <x-icon name="arrow-left" />
                    <span data-i18n="find_another_customer">Find another customer</span>
                </a>
            @endif

            <h1
                class="groups-title"
                data-i18n="customer_statement"
            >
                Customer Statement
            </h1>

            <p
                class="groups-subtitle"
                data-i18n="customer_statement_subtitle"
            >
                Every payment a customer made, month by month, for each group.
            </p>
        </div>

        @if ($customer && $seats->isNotEmpty())
            <div class="ledger-actions">

                <button
                    type="button"
                    class="group-action"
                    onclick="window.print()"
                >
                    <x-icon name="printer" />
                    <span data-i18n="print_statement">Print statement</span>
                </button>

                <a
                    href="{{ route('reports.customer.pdf', $customer) }}"
                    class="add-group-button"
                >
                    <x-icon name="download" />
                    <span data-i18n="download_pdf">Download PDF</span>
                </a>

            </div>
        @endif

    </div>


    @if (! $customer)

        {{-- =================================================
             FIND THE CUSTOMER
        ================================================== --}}

        <section class="group-panel glass payment-step">

            <form
                method="GET"
                action="{{ route('reports.customer') }}"
                class="member-search"
                id="paymentSearchForm"
                role="search"
            >

                <span class="member-search-icon">
                    <x-icon name="search" />
                </span>

                <input
                    type="search"
                    name="q"
                    id="paymentSearchInput"
                    class="member-search-input"
                    value="{{ $search }}"
                    placeholder="Type a customer name, ID, phone or identification"
                    data-i18n-placeholder="statement_search_placeholder"
                    autocomplete="off"
                    autofocus
                >

            </form>


            <div
                class="member-search-results"
                id="paymentSearchResults"
            >

                @if ($search === '')

                    <p class="members-empty" data-i18n="statement_search_hint">
                        Start typing a name to find the customer.
                    </p>

                @elseif ($results->isEmpty())

                    <p class="members-empty" data-i18n="statement_search_none">
                        No customers in any group match your search.
                    </p>

                @else

                    @foreach ($results as $result)

                        <a
                            href="{{ route('reports.customer', ['customer' => $result->id]) }}"
                            class="member-result payment-customer-result"
                        >

                            <x-customer-avatar :customer="$result" />

                            <div class="member-card-info">
                                <span class="member-card-code">{{ $result->customer_code }}</span>
                                <strong class="member-card-name"><x-customer-name :customer="$result" /></strong>
                                <span class="member-card-identification">
                                    {{ $result->phone ?: '—' }}
                                    · {{ $result->memberships_count }} <span data-i18n="seats_word">seat(s)</span>
                                </span>
                            </div>

                            <x-icon name="chevron-right" class="payment-result-arrow" />

                        </a>

                    @endforeach

                @endif

            </div>

        </section>

    @else

        {{-- =================================================
             STATEMENT SHEET (this is what prints)
        ================================================== --}}

        <section class="ledger-sheet statement-sheet glass">

            <header class="ledger-print-head">

                <img
                    class="receipt-logo"
                    src="{{ asset('images/sn-chit-funds-logo.png') }}"
                    alt="SN Chit Funds"
                >

                <div>
                    <strong class="receipt-company"><span>SN</span> Chit Funds</strong>
                    <span class="receipt-tagline">Trust · Growth · Together</span>
                </div>

                <div class="ledger-print-title">
                    <strong data-i18n="customer_statement">Customer Statement</strong>
                    <span>{{ now(config('app.business_timezone'))->format('d M Y, h:i A') }}</span>
                </div>

            </header>


            {{-- customer details --}}

            <div class="ledger-group-info statement-customer">

                <div>
                    <span data-i18n="name">Name</span>
                    <strong><x-customer-name :customer="$customer" /></strong>
                </div>

                <div>
                    <span data-i18n="customer_id">Customer ID</span>
                    <strong>{{ $customer->customer_code }}</strong>
                </div>

                <div>
                    <span data-i18n="phone">Phone</span>
                    <strong class="statement-phone">
                        {{ $customer->phone ?: '—' }}
                        @if ($customer->phone)
                            <a
                                href="tel:{{ preg_replace('/[^\d+]/', '', $customer->phone) }}"
                                class="member-call-button no-print"
                                aria-label="Call {{ $customer->name }}"
                            >
                                <x-icon name="phone" />
                                <span data-i18n="call">Call</span>
                            </a>
                        @endif
                    </strong>
                </div>

                @if ($customer->address)
                    <div>
                        <span data-i18n="address">Address</span>
                        <strong>{{ $customer->address }}</strong>
                    </div>
                @endif

                <div>
                    <span data-i18n="total_paid">Total paid</span>
                    <strong class="ledger-total-paid"><x-rupees :amount="$seats->sum('total_paid')" /></strong>
                </div>

                <div>
                    <span data-i18n="collect_state_pending">Pending</span>
                    <strong @class(['ledger-total-due' => $seats->sum('pending') > 0])><x-rupees :amount="$seats->sum('pending')" /></strong>
                </div>

                @if ($seats->contains(fn ($seat) => $seat['member']->wonDraw))
                    <div>
                        <span data-i18n="prizes_won">Prizes won</span>
                        <strong class="statement-prize-total"><x-rupees :amount="$seats->sum(fn ($seat) => $seat['member']->wonDraw?->prizeAmount() ?? 0)" /></strong>
                    </div>
                @endif

            </div>


            @forelse ($seats as $seat)

                @include('reports.partials.statement-seat', ['seat' => $seat])

            @empty

                <p class="members-empty" data-i18n="statement_no_groups">
                    This customer is not in any started group yet.
                </p>

            @endforelse

        </section>

    @endif

</div>

@endsection
