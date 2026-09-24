@extends('layouts.app')

@section('title', 'Pending & Due | SN Chit Funds')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css'
    ])
@endpush

@php
    $query = array_filter(['type' => $type === 'all' ? null : $type, 'group' => $group?->id]);
@endphp

@section('content')

<div class="groups-list-page payments-page dues-page">


    {{-- =====================================================
         HEADER
    ====================================================== --}}

    <div class="groups-list-header">

        <div>
            <h1
                class="groups-title"
                data-i18n="pending_and_due"
            >
                Pending &amp; Due
            </h1>

            <p class="groups-subtitle">
                <span data-i18n="dues_report_subtitle">Who still has to pay, as on</span>
                {{ $today->format('D, d M Y') }}.
            </p>
        </div>

        <div class="ledger-actions">

            <a
                href="{{ route('reports.dues.print', $query) }}"
                class="group-action"
                target="_blank"
                rel="noopener"
            >
                <x-icon name="printer" />
                <span data-i18n="print_report">Print report</span>
            </a>

            <a
                href="{{ route('reports.dues.pdf', $query) }}"
                class="add-group-button"
            >
                <x-icon name="download" />
                <span data-i18n="download_pdf">Download PDF</span>
            </a>

        </div>

    </div>


    {{-- =====================================================
         FILTERS: which list, which group
    ====================================================== --}}

    <form
        method="GET"
        action="{{ route('reports.dues') }}"
        class="payment-filters payment-filters-grid"
    >

        <nav class="status-tabs collect-tabs" aria-label="Which list">
            @foreach ([
                'all' => ['status_all', 'All', null],
                'pending' => ['collect_state_pending', 'Pending', $pending->count()],
                'due' => ['collect_state_due', 'Due', $due->count()],
            ] as $tabType => [$tabKey, $tabLabel, $tabCount])
                <a
                    href="{{ route('reports.dues', array_filter(['type' => $tabType === 'all' ? null : $tabType, 'group' => $group?->id])) }}"
                    @class(['status-tab', 'collect-tab-'.$tabType, 'active' => $type === $tabType])
                    @if ($type === $tabType) aria-current="page" @endif
                >
                    <span data-i18n="{{ $tabKey }}">{{ $tabLabel }}</span>
                    @if ($tabCount !== null)
                        <span class="status-tab-count">{{ $tabCount }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        @if ($type !== 'all')
            <input type="hidden" name="type" value="{{ $type }}">
        @endif

        <label class="payment-filter">
            <span data-i18n="group_word">Group</span>
            <select
                name="group"
                class="input payment-filter-select"
                onchange="this.form.submit()"
            >
                <option value="" data-i18n="all_groups">All groups</option>
                @foreach ($groups as $option)
                    <option
                        value="{{ $option->id }}"
                        @selected($group?->id === $option->id)
                    >{{ $option->name }}</option>
                @endforeach
            </select>
        </label>

    </form>


    {{-- =====================================================
         TOTALS
    ====================================================== --}}

    <section class="payment-summary-bar glass dues-summary">

        @if ($type !== 'due')
            <div class="payment-summary-main">
                <span class="payment-summary-range" data-i18n="collect_state_pending">Pending</span>
                <strong class="payment-summary-total dues-total-pending"><x-rupees :amount="$pending->sum('amount')" /></strong>
                <span class="payment-summary-count">{{ $pending->count() }} <span data-i18n="members_pending">members past their due date</span></span>
            </div>
        @endif

        @if ($type !== 'pending')
            <div class="payment-summary-main">
                <span class="payment-summary-range" data-i18n="collect_state_due">Due</span>
                <strong class="payment-summary-total dues-total-due"><x-rupees :amount="$due->sum('amount')" /></strong>
                <span class="payment-summary-count">{{ $due->count() }} <span data-i18n="members_due">members due this month</span></span>
            </div>
        @endif

        @if ($group)
            <span class="payment-method-total">{{ $group->name }}</span>
        @endif

    </section>


    {{-- =====================================================
         THE LISTS
    ====================================================== --}}

    @foreach (['pending' => $pending, 'due' => $due] as $listName => $rows)

        @continue($type !== 'all' && $type !== $listName)

        <section class="group-panel glass dues-list">

            <div class="group-panel-header">
                <h2>
                    @if ($listName === 'pending')
                        <span class="due-badge due-badge-due" data-i18n="collect_state_pending">Pending</span>
                        <span data-i18n="pending_list_title">Past the due date</span>
                    @else
                        <span class="due-badge due-badge-month" data-i18n="collect_state_due">Due</span>
                        <span data-i18n="due_list_title">Due this month</span>
                    @endif
                    ({{ $rows->count() }})
                </h2>
            </div>

            @if ($rows->isEmpty())

                <p class="members-empty" data-i18n="nobody_on_list">Nobody on this list.</p>

            @else

                <div class="ledger-table-wrapper">

                    <table class="ledger-table statement-table dues-table">

                        <thead>
                            <tr>
                                <th>#</th>
                                <th data-i18n="group_members">Member</th>
                                <th data-i18n="group_word">Group</th>
                                <th data-i18n="month_number">Month</th>
                                <th data-i18n="due_date">Due date</th>
                                @if ($listName === 'pending')
                                    <th class="ledger-col-total" data-i18n="days_overdue">Days overdue</th>
                                @endif
                                <th class="ledger-col-total" data-i18n="amount_rupees">Amount</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($rows as $row)
                                @php $member = $row['member']; @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <a
                                            href="{{ route('payments.create', ['customer' => $member->customer_id, 'member' => $member->id]) }}#collect"
                                            class="dues-member-link"
                                        >
                                            <strong><x-customer-name :customer="$member->customer" /></strong>
                                        </a>
                                        <span class="dues-member-code">{{ $member->member_code }} · {{ $member->customer->phone ?: '—' }}</span>
                                    </td>
                                    <td>{{ $row['group'] }}</td>
                                    <td>
                                        {{ implode(', ', $row['months']) }}
                                        @if ($row['part_paid'] > 0)
                                            <small class="dues-part-paid">(<x-rupees :amount="$row['part_paid']" /> <span data-i18n="paid">paid</span>)</small>
                                        @endif
                                    </td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($row['due_date'])->format('d M Y') }}</td>
                                    @if ($listName === 'pending')
                                        <td class="ledger-col-total">{{ $row['days_overdue'] }}</td>
                                    @endif
                                    <td @class(['ledger-col-total', 'ledger-total-due' => $listName === 'pending', 'ledger-total-open' => $listName === 'due'])>
                                        <strong><x-rupees :amount="$row['amount']" /></strong>
                                    </td>
                                    <td class="dues-actions">
                                        @if ($member->customer->phone)
                                            <a
                                                href="tel:{{ preg_replace('/[^\d+]/', '', $member->customer->phone) }}"
                                                class="member-call-button collect-call-button"
                                                aria-label="Call {{ $member->customer->name }}"
                                                title="Call {{ $member->customer->phone }}"
                                            >
                                                <x-icon name="phone" />
                                                <span data-i18n="call">Call</span>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot>
                            <tr>
                                <th colspan="{{ $listName === 'pending' ? 6 : 5 }}" data-i18n="total">Total</th>
                                <td @class(['ledger-col-total', 'ledger-total-due' => $listName === 'pending', 'ledger-total-open' => $listName === 'due'])>
                                    <strong><x-rupees :amount="$rows->sum('amount')" /></strong>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>

                    </table>

                </div>

            @endif

        </section>

    @endforeach


    {{-- totals by group (all groups only) --}}

    @if ($group === null && $byGroup->count() > 1)

        <section class="group-panel glass dues-list">

            <div class="group-panel-header">
                <h2 data-i18n="by_group">By group</h2>
            </div>

            <div class="ledger-table-wrapper">
                <table class="ledger-table statement-table">
                    <thead>
                        <tr>
                            <th data-i18n="group_word">Group</th>
                            @if ($type !== 'due')
                                <th class="ledger-col-total" data-i18n="collect_state_pending">Pending</th>
                            @endif
                            @if ($type !== 'pending')
                                <th class="ledger-col-total" data-i18n="collect_state_due">Due</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($byGroup as $groupTotals)
                            <tr>
                                <td>{{ $groupTotals['name'] }}</td>
                                @if ($type !== 'due')
                                    <td @class(['ledger-col-total', 'ledger-total-due' => $groupTotals['pending_amount'] > 0])>
                                        <x-rupees :amount="$groupTotals['pending_amount']" /> <small>({{ $groupTotals['pending_count'] }})</small>
                                    </td>
                                @endif
                                @if ($type !== 'pending')
                                    <td @class(['ledger-col-total', 'ledger-total-open' => $groupTotals['due_amount'] > 0])>
                                        <x-rupees :amount="$groupTotals['due_amount']" /> <small>({{ $groupTotals['due_count'] }})</small>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </section>

    @endif

</div>

@endsection
