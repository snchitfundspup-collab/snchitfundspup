@extends('layouts.portal')

@section('title', $group->name)

@php
    $states = [
        'paid' => ['due-badge-ok', 'ledger_paid', 'Paid'],
        'pending' => ['due-badge-due', 'collect_state_pending', 'Pending'],
        'partial' => ['due-badge-part', 'ledger_partial', 'Part paid'],
        'due' => ['due-badge-month', 'collect_state_due', 'Due'],
        'upcoming' => ['due-badge-upcoming', 'collect_state_upcoming', 'Upcoming'],
    ];
@endphp

@section('content')

<div class="group-show-page portal-page">

    {{-- GROUP --}}

    <section class="group-hero glass">

        <a href="{{ route('portal.groups') }}" class="group-back-link">
            <x-icon name="arrow-left" />
            <span data-i18n="my_groups">My Groups</span>
        </a>

        <div class="group-hero-main">
            <span class="group-hero-icon icon-3d icon-3d-blue"><x-icon name="layers" /></span>
            <div class="group-hero-text">
                <h1 class="group-hero-title">{{ $group->name }}</h1>
                <p class="group-hero-meta">
                    {{ $member->member_code }}
                    · <x-rupees :amount="$group->amount" />
                    · <x-rupees :amount="$group->installment_amount" />/<span data-i18n="month_word">month</span> × {{ $group->months }}
                </p>
            </div>
            <div class="ledger-actions">
                <a href="{{ route('portal.statement.print') }}" class="group-action" target="_blank" rel="noopener">
                    <x-icon name="printer" />
                    <span data-i18n="print_statement">Print statement</span>
                </a>
                <a href="{{ route('portal.statement.pdf') }}" class="add-group-button">
                    <x-icon name="download" />
                    <span data-i18n="chit_statement">Chit statement</span>
                </a>
            </div>
        </div>

        {{-- key details at a glance --}}
        <div class="portal-facts portal-key-facts">
            <span>
                <small data-i18n="months_paid">Months paid</small>
                <strong class="ledger-total-paid">{{ $monthsPaid }} / {{ $group->months }}</strong>
            </span>
            <span>
                <small data-i18n="months_pending">Months pending</small>
                <strong @class(['ledger-total-due' => $monthsPending > 0])>{{ $monthsPending }}</strong>
            </span>
            @if ($status)
                <span>
                    <small data-i18n="to_pay_now">To pay now</small>
                    <strong @class(['ledger-total-due' => $status['pending'] > 0, 'ledger-total-open' => $status['pending'] === 0 && $status['amount_due'] > 0])>
                        <x-rupees :amount="$status['amount_due']" />
                    </strong>
                </span>
                @if ($status['month'] > 0)
                    <span>
                        <small data-i18n="next_due">Next due</small>
                        <strong>{{ $group->dateForMonth($status['month'])->format('d M Y') }}</strong>
                    </span>
                @endif
            @endif
            <span>
                <small data-i18n="total_paid">Total paid</small>
                <strong><x-rupees :amount="$member->totalPaid()" /></strong>
            </span>
            <span>
                <small data-i18n="starts_on">Starts on</small>
                <strong>{{ $group->start_date?->format('d M Y') }}</strong>
            </span>
            <span>
                <small data-i18n="ends_on">Ends on</small>
                <strong>{{ $group->start_date ? $group->endDate()->format('d M Y') : '—' }}</strong>
            </span>
        </div>

    </section>


    {{-- PRIZE WON --}}

    @if ($wonDraw)
        <div class="statement-prize portal-prize">
            <span class="statement-prize-icon icon-3d icon-3d-orange"><x-icon name="trophy" /></span>
            <div class="statement-prize-body">
                <strong>
                    <span data-i18n="you_won">You won</span> <x-rupees :amount="$wonDraw->prizeAmount()" />
                    · <span data-i18n="month_number">Month</span> {{ $wonDraw->month_number }}
                    <small>({{ $group->dateForMonth($wonDraw->month_number)->format('d M Y') }})</small>
                </strong>
                <span><span data-i18n="drawn_on">Drawn on</span> {{ $wonDraw->drawn_at->format('d M Y') }}</span>
                @if ($wonDraw->isPaidOut())
                    <span class="statement-prize-paid">
                        <span data-i18n="payout_paid">Paid out</span> {{ $wonDraw->paid_at->format('d M Y') }} · {{ $wonDraw->payoutMethodLabel() }}
                    </span>
                @else
                    <span class="statement-prize-pending" data-i18n="payout_pending">Awaiting payout</span>
                @endif
            </div>
        </div>
    @endif


    {{-- MONTH BY MONTH --}}

    <section class="group-panel glass payment-step">

        <div class="group-panel-header">
            <h2 data-i18n="monthly_payments">Monthly payments</h2>
        </div>

        <div class="ledger-table-wrapper">
            <table class="ledger-table statement-table portal-table">
                <thead>
                    <tr>
                        <th data-i18n="month_number">Month</th>
                        <th data-i18n="due_date">Due date</th>
                        <th class="ledger-col-total" data-i18n="paid">Paid</th>
                        <th class="ledger-col-total" data-i18n="balance">Balance</th>
                        <th data-i18n="status">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($months as $month)
                        @php [$badgeClass, $badgeKey, $badgeLabel] = $states[$month['state']]; @endphp
                        <tr>
                            <td data-label="Month">
                                <strong>{{ $month['month'] }}</strong>
                            </td>
                            <td class="nowrap" data-label="Due date">{{ $month['due_on']->format('d M Y') }}</td>
                            <td class="ledger-col-total" data-label="Paid">@if ($month['paid'] > 0)<x-rupees :amount="$month['paid']" />@else — @endif</td>
                            <td @class(['ledger-col-total', 'ledger-total-due' => $month['state'] === 'pending', 'ledger-total-open' => in_array($month['state'], ['due', 'partial'], true)]) data-label="Balance">
                                @if ($month['balance'] > 0)<x-rupees :amount="$month['balance']" />@else — @endif
                            </td>
                            <td data-label="Status"><span class="due-badge {{ $badgeClass }}" data-i18n="{{ $badgeKey }}">{{ $badgeLabel }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </section>


    {{-- RECEIPTS --}}

    <section class="group-panel glass payment-step">

        <div class="group-panel-header">
            <h2 data-i18n="my_receipts">My receipts</h2>
        </div>

        @if ($payments->isEmpty())
            <p class="members-empty" data-i18n="no_payments_yet">No payments yet</p>
        @else
            <div class="payment-list">
                @foreach ($payments as $payment)
                    <a href="{{ route('portal.receipts.pdf', $payment) }}" class="payment-row glass">
                        <span class="portal-row-icon icon-3d icon-3d-green"><x-icon name="download" /></span>
                        <div class="payment-row-main">
                            <strong>{{ $payment->receipt_number }}</strong>
                            <span>{{ $payment->paid_at->format('d M Y, h:i A') }} · {{ $payment->methodLabel() }}</span>
                        </div>
                        <div class="payment-row-meta">
                            <span>{{ $payment->monthsCoveredLabel() }}</span>
                        </div>
                        <strong class="payment-row-amount"><x-rupees :amount="$payment->amount" /></strong>
                    </a>
                @endforeach
            </div>
        @endif

    </section>


    {{-- WITHDRAWAL PLAN --}}

    @if ($plan->isNotEmpty())
        @include('portal.partials.withdrawal-plan')
    @endif

</div>

@endsection
