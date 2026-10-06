@extends('layouts.portal')

@section('title', 'Loan '.$loan->loan_number)

@push('styles')
    @vite('resources/css/finance.css')
@endpush

@section('content')

<div class="group-show-page portal-page">

    <section class="group-hero glass">

        <a href="{{ route('portal.loans') }}" class="group-back-link">
            <x-icon name="arrow-left" />
            <span data-i18n="my_loans">My Loans</span>
        </a>

        <div class="group-hero-main">
            <span class="group-hero-icon icon-3d icon-3d-green"><x-icon name="wallet" /></span>
            <div class="group-hero-text">
                @if ($owner)
                    @include('portal.partials.owner')
                @endif
                <h1 class="group-hero-title">{{ $loan->loan_number }}</h1>
                <p class="group-hero-meta">
                    Sri Lakshmi Micro Finance · {{ $loan->loaned_on->format('d M Y') }}
                </p>
            </div>
            @include('finance.partials.state-badge', ['state' => $standing['state']])
        </div>

        <div class="portal-facts portal-key-facts">
            <span>
                <small data-i18n="loan_amount_short">Loan amount</small>
                <strong><x-rupees :amount="$loan->principal" /></strong>
            </span>
            <span>
                <small data-i18n="given_in_hand">Given in hand</small>
                <strong><x-rupees :amount="$loan->amountGiven()" /></strong>
            </span>
            <span>
                <small data-i18n="total_to_repay">Total to repay</small>
                <strong><x-rupees :amount="$loan->loan_amount" /></strong>
            </span>
            <span>
                <small data-i18n="installment_word">Instalment</small>
                <strong>{{ $loan->frequencyLabel() }} <x-rupees :amount="$loan->installment_amount" /></strong>
            </span>
            <span>
                <small data-i18n="installments_paid">Instalments paid</small>
                <strong class="ledger-total-paid">{{ $standing['installments_paid'] }} / {{ $loan->installments }}</strong>
            </span>
            <span>
                <small data-i18n="total_paid">Total paid</small>
                <strong><x-rupees :amount="$loan->collected()" /></strong>
            </span>
            <span>
                <small data-i18n="balance_word">Balance</small>
                <strong><x-rupees :amount="$loan->balance()" /></strong>
            </span>
            @if ($standing['overdue'] > 0)
                <span>
                    <small data-i18n="overdue_word">Overdue</small>
                    <strong class="ledger-total-due"><x-rupees :amount="$standing['overdue']" /></strong>
                </span>
            @endif
            @if ($standing['due_today'] > 0)
                <span>
                    <small data-i18n="due_today">Due today</small>
                    <strong class="ledger-total-open"><x-rupees :amount="$standing['due_today']" /></strong>
                </span>
            @endif
            <span>
                <small data-i18n="last_due_on">Last instalment on</small>
                <strong>{{ $loan->lastDueDate()->format('d M Y') }}</strong>
            </span>
        </div>

        <div class="portal-join-actions">
            <a href="{{ route('portal.loans.passbook', $loan) }}" class="group-action" target="_blank" rel="noopener">
                <x-icon name="printer" />
                <span data-i18n="print_passbook">Print passbook</span>
            </a>
            <a href="{{ route('portal.loans.passbook.pdf', $loan) }}" class="add-group-button">
                <x-icon name="download" />
                <span data-i18n="download_passbook">Download passbook</span>
            </a>
            @include('portal.partials.call-office')
        </div>

    </section>


    {{-- DAY BY DAY (or week by week): each instalment, what is paid and what is left --}}

    @php
        $weekly = $loan->frequency === \App\Models\FinanceLoan::WEEKLY;
        $stateBadges = [
            'paid' => ['due-badge-ok', 'ledger_paid', 'Paid'],
            'partial' => ['due-badge-part', 'ledger_partial', 'Part paid'],
            'overdue' => ['due-badge-due', 'overdue_word', 'Overdue'],
            'due' => ['due-badge-month', 'due_today', 'Due today'],
            'upcoming' => ['due-badge-upcoming', 'collect_state_upcoming', 'Upcoming'],
        ];
    @endphp

    <section class="group-panel glass">

        <div class="group-panel-header">
            <h2 data-i18n="{{ $weekly ? 'week_by_week' : 'day_by_day' }}">{{ $weekly ? 'Week by week' : 'Day by day' }}</h2>
            <span class="group-panel-note">
                <span data-i18n="total_paid">Total paid</span> <x-rupees :amount="$loan->collected()" />
                · <span data-i18n="left_to_pay">Left to pay</span> <x-rupees :amount="$loan->balance()" />
            </span>
        </div>

        <div class="ledger-table-wrapper">
            <table class="ledger-table statement-table portal-table finance-schedule">
                <thead>
                    <tr>
                        <th data-i18n="{{ $weekly ? 'week_word' : 'day_word' }}">{{ $weekly ? 'Week' : 'Day' }}</th>
                        <th data-i18n="due_date">Due date</th>
                        <th class="ledger-col-total" data-i18n="installment_word">Instalment</th>
                        <th class="ledger-col-total" data-i18n="paid">Paid</th>
                        <th data-i18n="collected_on">Collected on</th>
                        <th class="ledger-col-total" data-i18n="left_after">Left after</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($schedule as $row)
                        @php [$badgeClass, $badgeKey, $badgeLabel] = $stateBadges[$row['state']]; @endphp
                        <tr @class(['is-done' => $row['state'] === 'paid', 'is-today' => $row['state'] === 'due'])>
                            <td data-label="{{ $weekly ? 'Week' : 'Day' }}"><strong>{{ $row['number'] }}</strong></td>
                            <td class="nowrap" data-label="Due date"><span class="finance-date-long">{{ $row['due_on']->format('D, d M Y') }}</span><span class="finance-date-short">{{ $row['due_on']->format('D, d M') }}</span></td>
                            @php $paidOn = collect($row['paid_on'])->map(fn ($date) => $date->format('d M'))->implode(', '); @endphp
                            <td class="ledger-col-total" data-label="Instalment"><x-rupees :amount="$row['amount']" /><span class="finance-schedule-paid">@if ($row['paid'] > 0)@if ($row['paid'] < $row['amount'])<span data-i18n="paid">Paid</span> <x-rupees :amount="$row['paid']" /> @endif<span data-i18n="collected_word_short">collected</span> {{ $paidOn }}@endif</span></td>
                            <td @class(['ledger-col-total', 'ledger-total-paid' => $row['paid'] > 0]) data-label="Paid">@if ($row['paid'] > 0)<x-rupees :amount="$row['paid']" />@else — @endif</td>
                            <td class="nowrap finance-paid-on" data-label="Collected on">{{ $paidOn ?: '—' }}</td>
                            <td class="ledger-col-total" data-label="Left after"><span class="finance-left-label" data-i18n="left_after">Left after</span> <x-rupees :amount="$row['balance_after']" /></td>
                            <td><span class="due-badge {{ $badgeClass }}" data-i18n="{{ $badgeKey }}">{{ $badgeLabel }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </section>


    <section class="group-panel glass">

        <div class="group-panel-header">
            <h2 data-i18n="my_receipts">My receipts</h2>
        </div>

        <div class="ledger-table-wrapper">
            <table class="ledger-table statement-table portal-table">
                <thead>
                    <tr>
                        <th data-i18n="date">Date</th>
                        <th data-i18n="receipt_no">Receipt No.</th>
                        <th class="ledger-col-total" data-i18n="paid">Paid</th>
                        <th class="ledger-col-total" data-i18n="balance">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @php $running = $loan->loan_amount; @endphp
                    @forelse ($loan->collections as $collection)
                        @php $running -= $collection->amount; @endphp
                        <tr>
                            <td class="nowrap" data-label="Date">{{ $collection->collected_at->format('d M Y') }}</td>
                            <td class="nowrap" data-label="Receipt">
                                <a href="{{ route('portal.loans.receipt.pdf', $collection) }}" class="statement-receipt-link">
                                    <x-icon name="download" />
                                    {{ $collection->receipt_number }}
                                </a>
                            </td>
                            <td class="ledger-col-total ledger-total-paid" data-label="Paid"><x-rupees :amount="$collection->amount" /></td>
                            <td class="ledger-col-total" data-label="Balance"><strong><x-rupees :amount="max(0, $running)" /></strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="statement-no-payments" data-i18n="no_collections_yet">Nothing collected yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </section>

</div>

@endsection
