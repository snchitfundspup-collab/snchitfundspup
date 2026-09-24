@extends('layouts.app')

@section('title', 'Usage | SN '.($business['name'] ?? 'Chit Funds'))

@push('styles')
    @vite([
        'resources/css/dashboard.css',
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/usage.css'
    ])
@endpush

@php
    $timezone = config('app.business_timezone');
    $busiestDay = max(1, $daily->max('views'));
    $busiestMonth = max(1, $monthly->max('views'));
    $devices = ['phone' => ['phone', 'device_phone', 'Phone'], 'tablet' => ['phone', 'device_tablet', 'Tablet'], 'computer' => ['layers', 'device_computer', 'Computer']];
    $businesses = ['chit' => 'Chit Funds', 'traders' => 'Traders'];
    $whoKeys = ['all' => 'status_all', 'staff' => 'staff_word', 'customers' => 'customers_title'];
@endphp

@section('content')

<div class="groups-list-page payments-page usage-page">

    <div class="groups-list-header">
        <div>
            <h1 class="groups-title" data-i18n="menu_usage">Usage</h1>
            <p class="groups-subtitle" data-i18n="usage_subtitle">How much the app is used, day by day and month by month, and who used it recently.</p>
        </div>
    </div>


    <nav class="status-tabs collect-tabs" aria-label="Whose usage">
        @foreach ($whoOptions as $whoKey => $whoLabel)
            <a
                href="{{ route('usage.index', $whoKey === 'all' ? [] : ['who' => $whoKey]) }}"
                @class(['status-tab', 'active' => $who === $whoKey])
            >
                <span data-i18n="{{ $whoKeys[$whoKey] }}">{{ $whoLabel }}</span>
            </a>
        @endforeach
    </nav>


    {{-- =====================================================
         TODAY / THIS MONTH
    ====================================================== --}}

    <section class="dashboard-stats collection-stats usage-stats">

        <div class="stat-tile glass">
            <span class="stat-icon icon-3d icon-3d-green"><x-icon name="calendar" /></span>
            <span class="stat-body">
                <span class="stat-label" data-i18n="usage_today">Today</span>
                <span class="stat-value">{{ number_format($summary['today_views']) }}</span>
                <span class="stat-note">
                    <span data-i18n="pages_opened">pages opened</span> · {{ $summary['today_people'] }}
                    <span data-i18n="{{ $summary['today_people'] === 1 ? 'person_word' : 'people_word' }}">{{ $summary['today_people'] === 1 ? 'person' : 'people' }}</span>
                </span>
            </span>
        </div>

        <div class="stat-tile glass">
            <span class="stat-icon icon-3d icon-3d-blue"><x-icon name="chart" /></span>
            <span class="stat-body">
                <span class="stat-label"><span data-i18n="range_month">This month</span> · {{ $today->format('M Y') }}</span>
                <span class="stat-value">{{ number_format($summary['month_views']) }}</span>
                <span class="stat-note">
                    <span data-i18n="pages_opened">pages opened</span> · {{ $summary['month_people'] }}
                    <span data-i18n="{{ $summary['month_people'] === 1 ? 'person_word' : 'people_word' }}">{{ $summary['month_people'] === 1 ? 'person' : 'people' }}</span>
                </span>
            </span>
        </div>

        <div class="stat-tile glass">
            <span class="stat-icon icon-3d icon-3d-purple"><x-icon name="check" /></span>
            <span class="stat-body">
                <span class="stat-label" data-i18n="active_days">Days used this month</span>
                <span class="stat-value">{{ $summary['month_active_days'] }} / {{ $today->day }}</span>
                <span class="stat-note"><span data-i18n="days_with_use">days the app was opened</span></span>
            </span>
        </div>

        <div class="stat-tile glass">
            <span class="stat-icon icon-3d icon-3d-orange"><x-icon name="users" /></span>
            <span class="stat-body">
                <span class="stat-label" data-i18n="average_per_day">Average a day</span>
                <span class="stat-value">{{ rtrim(rtrim(number_format($summary['average_per_day'], 1), '0'), '.') }}</span>
                <span class="stat-note"><span data-i18n="pages_this_month">pages a day this month</span></span>
            </span>
        </div>

    </section>


    {{-- =====================================================
         DAILY / MONTHLY CHARTS
    ====================================================== --}}

    <div class="usage-charts">

        <section class="group-panel glass">
            <div class="group-panel-header">
                <h2 data-i18n="daily_usage">Daily usage</h2>
                <span class="usage-chart-note" data-i18n="last_30_days">Last 30 days · pages opened</span>
            </div>
            <div class="usage-chart usage-chart-daily" role="img" aria-label="Pages opened each day for the last 30 days">
                @foreach ($daily as $day)
                    <div
                        @class(['usage-bar', 'is-today' => $loop->last, 'is-weekend' => $day['date']->isSunday()])
                        title="{{ $day['date']->format('D, d M') }} — {{ $day['views'] }} pages, {{ $day['people'] }} {{ $day['people'] === 1 ? 'person' : 'people' }}"
                    >
                        <span class="usage-bar-value">{{ $day['views'] ?: '' }}</span>
                        <span class="usage-bar-fill" style="height: {{ $day['views'] / $busiestDay * 100 }}%"></span>
                        <span class="usage-bar-label">{{ $loop->last || $loop->first || $day['date']->day % 5 === 0 ? $day['date']->format('j M') : '' }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="group-panel glass">
            <div class="group-panel-header">
                <h2 data-i18n="monthly_usage">Monthly usage</h2>
                <span class="usage-chart-note" data-i18n="last_12_months">Last 12 months · pages opened</span>
            </div>
            <div class="usage-chart usage-chart-monthly" role="img" aria-label="Pages opened each month for the last 12 months">
                @foreach ($monthly as $month)
                    <div
                        @class(['usage-bar', 'is-today' => $loop->last])
                        title="{{ $month['month']->format('F Y') }} — {{ $month['views'] }} pages, {{ $month['people'] }} {{ $month['people'] === 1 ? 'person' : 'people' }}, {{ $month['active_days'] }} days"
                    >
                        <span class="usage-bar-value">{{ $month['views'] ?: '' }}</span>
                        <span class="usage-bar-fill" style="height: {{ $month['views'] / $busiestMonth * 100 }}%"></span>
                        <span class="usage-bar-label">{{ $month['month']->format('M') }}</span>
                    </div>
                @endforeach
            </div>
        </section>

    </div>


    {{-- =====================================================
         WHO USED THE APP RECENTLY
    ====================================================== --}}

    <section class="group-panel glass payment-step">

        <div class="group-panel-header">
            <h2 data-i18n="who_used_recently">Who used the app recently</h2>
        </div>

        @if ($people->isEmpty())
            <p class="members-empty">
                @if ($who === 'customers')
                    <span data-i18n="no_customer_usage">No customer has used the app yet. Customers will appear here once they can sign in.</span>
                @else
                    <span data-i18n="no_usage_yet">Nothing recorded yet.</span>
                @endif
            </p>
        @else
            <div class="ledger-table-wrapper">
                <table class="ledger-table statement-table">
                    <thead>
                        <tr>
                            <th data-i18n="person_title">Person</th>
                            <th data-i18n="last_seen">Last seen</th>
                            <th data-i18n="last_page">Last page</th>
                            <th class="ledger-col-total" data-i18n="usage_today">Today</th>
                            <th class="ledger-col-total" data-i18n="range_month">This month</th>
                            <th class="ledger-col-total" data-i18n="days_used">Days used</th>
                            <th class="ledger-col-total" data-i18n="total">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($people as $person)
                            @php
                                $log = $person['log'];
                                $seen = $log->created_at->timezone($timezone);
                                [$deviceIcon, $deviceKey, $deviceLabel] = $devices[$log->device] ?? $devices['computer'];
                            @endphp
                            <tr>
                                <td>
                                    @if ($log->customer)
                                        <strong><x-customer-name :customer="$log->customer" /></strong>
                                        <span class="dues-member-code">{{ $log->customer->customer_code }} · <span data-i18n="customer_word">Customer</span></span>
                                    @else
                                        <strong>{{ $log->personName() }}</strong>
                                        <span class="dues-member-code">{{ $log->user?->username }} · <span data-i18n="staff_word">Staff</span></span>
                                    @endif
                                </td>
                                <td class="nowrap">
                                    <strong>{{ $seen->diffForHumans() }}</strong>
                                    <small class="dues-part-paid">{{ $seen->format('d M Y, h:i A') }}</small>
                                </td>
                                <td>
                                    {{ $log->pageLabel() }}
                                    <small class="dues-part-paid">
                                        <span data-i18n="{{ $deviceKey }}">{{ $deviceLabel }}</span>
                                        @if ($log->business) · {{ $businesses[$log->business] ?? $log->business }} @endif
                                    </small>
                                </td>
                                <td class="ledger-col-total">{{ $person['today'] ?: '—' }}</td>
                                <td class="ledger-col-total">{{ $person['month'] ?: '—' }}</td>
                                <td class="ledger-col-total">{{ $person['days'] }}</td>
                                <td class="ledger-col-total"><strong>{{ number_format($person['total']) }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </section>


    {{-- =====================================================
         RECENT ACTIVITY
    ====================================================== --}}

    @if ($activity->isNotEmpty())

        <section class="group-panel glass payment-step">

            <div class="group-panel-header">
                <h2 data-i18n="recent_activity">Recent activity</h2>
            </div>

            <div class="ledger-table-wrapper">
                <table class="ledger-table statement-table">
                    <thead>
                        <tr>
                            <th data-i18n="time_word">Time</th>
                            <th data-i18n="person_title">Person</th>
                            <th data-i18n="page_word">Page</th>
                            <th data-i18n="business_word">Business</th>
                            <th data-i18n="device_word">Device</th>
                            <th data-i18n="ip_address">IP address</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($activity as $entry)
                            @php [$deviceIcon, $deviceKey, $deviceLabel] = $devices[$entry->device] ?? $devices['computer']; @endphp
                            <tr>
                                <td class="nowrap">{{ $entry->created_at->timezone($timezone)->format('d M, h:i A') }}</td>
                                <td>
                                    @if ($entry->customer)
                                        <x-customer-name :customer="$entry->customer" />
                                    @else
                                        {{ $entry->personName() }}
                                    @endif
                                </td>
                                <td>
                                    @if ($entry->event === 'login')
                                        <span class="due-badge due-badge-done" data-i18n="signed_in">Signed in</span>
                                    @else
                                        {{ $entry->pageLabel() }}
                                    @endif
                                </td>
                                <td>{{ $businesses[$entry->business] ?? '—' }}</td>
                                <td data-i18n="{{ $deviceKey }}">{{ $deviceLabel }}</td>
                                <td class="nowrap usage-ip">{{ $entry->ip ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-pagination :paginator="$activity" label="Activity pages" />

        </section>

    @endif

</div>

@endsection
