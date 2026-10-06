{{-- Side menu for Sri Lakshmi Micro Finance (loans and collections). --}}

@php
    $menuGroup = function (string $id, string $icon, string $colour, string $i18n, string $label, array $items, array|string $active) {
        return compact('id', 'icon', 'colour', 'i18n', 'label', 'items', 'active');
    };

    $groups = [
        $menuGroup('financeLoansSubmenu', 'wallet', 'green', 'menu_loans', 'Loans', [
            ['finance.loans.create', 'plus', 'new_loan', 'New Loan'],
            ['finance.loans.index', 'chart', 'all_loans', 'All Loans'],
        ], 'finance.loans.*'),
        $menuGroup('financeCollectionsSubmenu', 'rupee', 'orange', 'menu_collections', 'Collections', [
            ['finance.collect', 'check', 'collect_today', 'Collect'],
            ['finance.collections.index', 'chart', 'all_collections', 'All Collections'],
        ], ['finance.collect', 'finance.collections.*']),
    ];

    $reports = $menuGroup('financeReportsSubmenu', 'chart', 'purple', 'menu_reports', 'Reports', [
        ['finance.reports.show', 'wallet', 'outstanding_loans', 'Outstanding Loans', ['report' => 'outstanding']],
        ['finance.reports.show', 'info', 'overdue_loans', 'Overdue Loans', ['report' => 'overdue']],
        ['finance.reports.show', 'calendar', 'day_book', 'Day Book', ['report' => 'day-book']],
        ['finance.reports.show', 'scale', 'profit_loss', 'Profit & Loss', ['report' => 'profit']],
        ['finance.reports.customer', 'users', 'customer_statement', 'Customer Statement'],
    ], 'finance.reports.*');

    $management = $menuGroup('financeExpensesSubmenu', 'wallet', 'green', 'menu_expenses', 'Expenses', [
        ['finance.expenses.create', 'plus', 'add_expense', 'Add Expense'],
        ['finance.expenses.index', 'wallet', 'all_expenses', 'All Expenses'],
    ], ['finance.expenses.*']);
@endphp


<div class="menu-section-title" data-i18n="menu_main">
    MAIN
</div>


<a
    href="{{ route('finance.dashboard') }}"
    @class(['menu-item', 'active' => request()->routeIs('finance.dashboard')])
>
    <span class="menu-item-icon icon-3d icon-3d-orange">
        <x-icon name="home" />
    </span>
    <span class="menu-item-text" data-i18n="menu_home">Home</span>
</a>


<a
    href="{{ route('finance.capital.index') }}"
    @class(['menu-item', 'active' => request()->routeIs('finance.capital.*')])
>
    <span class="menu-item-icon icon-3d icon-3d-blue">
        <x-icon name="scale" />
    </span>
    <span class="menu-item-text" data-i18n="capital_cash">Capital &amp; Cash</span>
</a>


@foreach (array_merge($groups, [$reports]) as $group)
    @include('components.partials.menu-group', ['group' => $group])
@endforeach


<div class="menu-section-title menu-section-spaced" data-i18n="menu_masters">
    MASTERS
</div>


<a
    href="{{ route('customers.index') }}"
    @class(['menu-item', 'active' => request()->routeIs('customers.*')])
>
    <span class="menu-item-icon icon-3d icon-3d-purple">
        <x-icon name="users" />
    </span>
    <span class="menu-item-text" data-i18n="menu_customers">Customers</span>
</a>


<div class="menu-section-title menu-section-spaced" data-i18n="menu_management">
    MANAGEMENT
</div>


@include('components.partials.menu-group', ['group' => $management])


<a
    href="{{ route('password.edit') }}"
    @class(['menu-item', 'active' => request()->routeIs('password.*')])
>
    <span class="menu-item-icon icon-3d icon-3d-purple">
        <x-icon name="settings" />
    </span>
    <span class="menu-item-text" data-i18n="menu_settings">Settings</span>
</a>
