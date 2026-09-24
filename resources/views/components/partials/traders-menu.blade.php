{{-- Side menu for SN Traders (rice purchase and sales). --}}

@php
    $menuGroup = function (string $id, string $icon, string $colour, string $i18n, string $label, array $items, array|string $active) {
        return compact('id', 'icon', 'colour', 'i18n', 'label', 'items', 'active');
    };

    $groups = [
        $menuGroup('tradersSalesSubmenu', 'rupee', 'green', 'menu_sales', 'Sales', [
            ['traders.sales.create', 'plus', 'new_sale', 'New Sale'],
            ['traders.sales.index', 'chart', 'all_sales', 'All Sales'],
        ], 'traders.sales.*'),
        $menuGroup('tradersPurchasesSubmenu', 'package', 'blue', 'menu_purchases', 'Purchases', [
            ['traders.purchases.create', 'plus', 'new_purchase', 'New Purchase'],
            ['traders.purchases.index', 'chart', 'all_purchases', 'All Purchases'],
        ], 'traders.purchases.*'),
        $menuGroup('tradersCreditSubmenu', 'users', 'orange', 'menu_customer_credit', 'Customer Credit', [
            ['traders.receipts.create', 'rupee', 'receive_payment', 'Receive Payment'],
            ['traders.balances.index', 'info', 'customer_balances', 'Customer Balances'],
            ['traders.receipts.index', 'chart', 'all_receipts', 'All Receipts'],
        ], ['traders.receipts.*', 'traders.balances.*', 'traders.accounts.*']),
    ];

    $reports = $menuGroup('tradersReportsSubmenu', 'chart', 'purple', 'menu_reports', 'Reports', [
        ['traders.reports.show', 'chart', 'rice_sales_report', 'Rice Sales', ['report' => 'rice-sales']],
        ['traders.reports.show', 'scale', 'profit_loss', 'Profit & Loss', ['report' => 'profit']],
        ['traders.reports.show', 'info', 'customer_dues', 'Customer Dues', ['report' => 'dues']],
        ['traders.reports.customer', 'users', 'customer_statement', 'Customer Statement'],
        ['traders.reports.show', 'calendar', 'day_book', 'Day Book', ['report' => 'day-book']],
    ], 'traders.reports.*');

    $management = $menuGroup('tradersExpensesSubmenu', 'wallet', 'green', 'menu_expenses', 'Expenses', [
        ['traders.expenses.create', 'plus', 'add_expense', 'Add Expense'],
        ['traders.expenses.index', 'wallet', 'all_expenses', 'All Expenses'],
        ['traders.expenses.balance', 'scale', 'balance_sheet', 'Balance Sheet'],
    ], ['traders.expenses.*']);
@endphp


<div class="menu-section-title" data-i18n="menu_main">
    MAIN
</div>


<a
    href="{{ route('traders.dashboard') }}"
    @class(['menu-item', 'active' => request()->routeIs('traders.dashboard')])
>
    <span class="menu-item-icon icon-3d icon-3d-orange">
        <x-icon name="home" />
    </span>
    <span class="menu-item-text" data-i18n="menu_home">Home</span>
</a>


@foreach (array_merge($groups, ['stock', $reports]) as $group)

    @if ($group === 'stock')

        <a
            href="{{ route('traders.stock') }}"
            @class(['menu-item', 'active' => request()->routeIs('traders.stock*')])
        >
            <span class="menu-item-icon icon-3d icon-3d-purple">
                <x-icon name="layers" />
            </span>
            <span class="menu-item-text" data-i18n="menu_stock">Stock</span>
        </a>

        @continue

    @endif

    @include('components.partials.menu-group', ['group' => $group])

@endforeach


<div class="menu-section-title menu-section-spaced" data-i18n="menu_masters">
    MASTERS
</div>


<a
    href="{{ route('traders.varieties.index') }}"
    @class(['menu-item', 'active' => request()->routeIs('traders.varieties.*')])
>
    <span class="menu-item-icon icon-3d icon-3d-green">
        <x-icon name="package" />
    </span>
    <span class="menu-item-text" data-i18n="rice_varieties">Rice Varieties</span>
</a>

<a
    href="{{ route('traders.suppliers.index') }}"
    @class(['menu-item', 'active' => request()->routeIs('traders.suppliers.*')])
>
    <span class="menu-item-icon icon-3d icon-3d-blue">
        <x-icon name="user-check" />
    </span>
    <span class="menu-item-text" data-i18n="suppliers">Suppliers</span>
</a>

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
