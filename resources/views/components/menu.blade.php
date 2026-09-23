<!-- Menu Button -->



<!-- Menu Overlay -->

<div
    id="menuOverlay"
    class="menu-overlay"
    onclick="toggleMenu()"
></div>


<!-- Side Menu -->

<aside
    id="appMenu"
    class="app-menu"
    aria-hidden="true"
>

    <div class="menu-header">

        <a
            href="{{ route('dashboard') }}"
            class="menu-brand brand-link"
            aria-label="SN Chit Funds – go to Home"
        >

            <div class="menu-logo logo-3d">
                <img
                    src="{{ asset('images/sn-chit-funds-logo.png') }}"
                    alt=""
                >
            </div>

            <div>
                <div class="menu-brand-name">
                    <span>SN</span> Chit Funds
                </div>

                <div class="menu-brand-subtitle" data-i18n="brand_tagline">
                    Trust · Growth · Together
                </div>
            </div>

        </a>


        <button
            type="button"
            class="menu-close"
            onclick="toggleMenu()"
            aria-label="Close menu"
        >
            <x-icon name="x" />
        </button>

    </div>


    <div class="menu-content">

        <div class="menu-section-title" data-i18n="menu_main">
            MAIN
        </div>


        <!-- Home (Dashboard) -->

        <a
            href="{{ route('dashboard') }}"
            @class(['menu-item', 'active' => request()->routeIs('dashboard')])
        >
            <span class="menu-item-icon icon-3d icon-3d-orange">
                <x-icon name="home" />
            </span>

            <span class="menu-item-text" data-i18n="menu_home">
                Home
            </span>
        </a>


        <!-- Customers (with sub menu) -->

        @php
            $isCustomersSection = request()->routeIs('customers.*');
        @endphp

        <div @class(['menu-group', 'open' => $isCustomersSection])>

            <button
                type="button"
                @class(['menu-item', 'menu-group-toggle', 'active' => $isCustomersSection])
                onclick="toggleMenuGroup(this)"
                aria-expanded="{{ $isCustomersSection ? 'true' : 'false' }}"
                aria-controls="customersSubmenu"
            >
                <span class="menu-item-icon icon-3d icon-3d-blue">
                    <x-icon name="users" />
                </span>

                <span class="menu-item-text" data-i18n="menu_customers">
                    Customers
                </span>

                <x-icon name="chevron-down" class="menu-group-caret" />
            </button>


            <div
                class="menu-submenu"
                id="customersSubmenu"
            >

                <a
                    href="{{ route('customers.index') }}"
                    @class(['menu-subitem', 'active' => request()->routeIs('customers.index')])
                >
                    <x-icon name="users" />
                    <span data-i18n="menu_all_customers">All Customers</span>
                </a>

                <a
                    href="{{ route('customers.create') }}"
                    @class(['menu-subitem', 'active' => request()->routeIs('customers.create')])
                >
                    <x-icon name="user-plus" />
                    <span data-i18n="add_customer">Add Customer</span>
                </a>

            </div>

        </div>


        <!-- Groups -->

        @php
            $isGroupsSection = request()->routeIs('groups.*');
        @endphp

        <div @class(['menu-group', 'open' => $isGroupsSection])>

            <button
                type="button"
                @class(['menu-item', 'menu-group-toggle', 'active' => $isGroupsSection])
                onclick="toggleMenuGroup(this)"
                aria-expanded="{{ $isGroupsSection ? 'true' : 'false' }}"
                aria-controls="groupsSubmenu"
            >
                <span class="menu-item-icon icon-3d icon-3d-purple">
                    <x-icon name="layers" />
                </span>

                <span class="menu-item-text" data-i18n="menu_groups">
                    Groups
                </span>

                <x-icon name="chevron-down" class="menu-group-caret" />
            </button>


            <div
                class="menu-submenu"
                id="groupsSubmenu"
            >

                <a
                    href="{{ route('groups.index') }}"
                    @class(['menu-subitem', 'active' => request()->routeIs('groups.index', 'groups.show', 'groups.edit')])
                >
                    <x-icon name="layers" />
                    <span data-i18n="menu_all_groups">All Groups</span>
                </a>

                <a
                    href="{{ route('groups.create') }}"
                    @class(['menu-subitem', 'active' => request()->routeIs('groups.create')])
                >
                    <x-icon name="plus" />
                    <span data-i18n="add_group">Add Group</span>
                </a>

            </div>

        </div>


        <!-- Payments -->

        @php
            $isPaymentsSection = request()->routeIs('payments.*');
        @endphp

        <div @class(['menu-group', 'open' => $isPaymentsSection])>

            <button
                type="button"
                @class(['menu-item', 'menu-group-toggle', 'active' => $isPaymentsSection])
                onclick="toggleMenuGroup(this)"
                aria-expanded="{{ $isPaymentsSection ? 'true' : 'false' }}"
                aria-controls="paymentsSubmenu"
            >
                <span class="menu-item-icon icon-3d icon-3d-green">
                    <x-icon name="rupee" />
                </span>

                <span class="menu-item-text" data-i18n="menu_payments">
                    Payments
                </span>

                <x-icon name="chevron-down" class="menu-group-caret" />
            </button>


            <div
                class="menu-submenu"
                id="paymentsSubmenu"
            >

                <a
                    href="{{ route('payments.create') }}"
                    @class(['menu-subitem', 'active' => request()->routeIs('payments.create')])
                >
                    <x-icon name="rupee" />
                    <span data-i18n="collect_payment">Collect Payment</span>
                </a>

                <a
                    href="{{ route('payments.index') }}"
                    @class(['menu-subitem', 'active' => request()->routeIs('payments.index', 'payments.show')])
                >
                    <x-icon name="chart" />
                    <span data-i18n="all_payments">All Payments</span>
                </a>

                <a
                    href="{{ route('payments.ledger') }}"
                    @class(['menu-subitem', 'active' => request()->routeIs('payments.ledger')])
                >
                    <x-icon name="layers" />
                    <span data-i18n="payment_ledger">Payment Ledger</span>
                </a>

            </div>

        </div>


        <!-- Draws -->

        @php
            $isDrawsSection = request()->routeIs('draws.*');
            $drawTab = request()->routeIs('draws.index') ? (string) request('status') : null;
        @endphp

        <div @class(['menu-group', 'open' => $isDrawsSection])>

            <button
                type="button"
                @class(['menu-item', 'menu-group-toggle', 'active' => $isDrawsSection])
                onclick="toggleMenuGroup(this)"
                aria-expanded="{{ $isDrawsSection ? 'true' : 'false' }}"
                aria-controls="drawsSubmenu"
            >
                <span class="menu-item-icon icon-3d icon-3d-orange">
                    <x-icon name="trophy" />
                </span>

                <span class="menu-item-text" data-i18n="menu_draws">
                    Draws
                </span>

                <x-icon name="chevron-down" class="menu-group-caret" />
            </button>


            <div
                class="menu-submenu"
                id="drawsSubmenu"
            >

                <a
                    href="{{ route('draws.create') }}"
                    @class(['menu-subitem', 'active' => request()->routeIs('draws.create')])
                >
                    <x-icon name="trophy" />
                    <span data-i18n="run_draw">Run Draw</span>
                </a>

                <a
                    href="{{ route('draws.index') }}"
                    @class(['menu-subitem', 'active' => in_array($drawTab, ['', 'all'], true) || request()->routeIs('draws.show')])
                >
                    <x-icon name="layers" />
                    <span data-i18n="draw_details_menu">Draw Details</span>
                </a>

                <a
                    href="{{ route('draws.index', ['status' => 'pending']) }}"
                    @class(['menu-subitem', 'active' => $drawTab === 'pending'])
                >
                    <x-icon name="rupee" />
                    <span data-i18n="pending_payouts">Pending Payouts</span>
                </a>

                <a
                    href="{{ route('draws.index', ['status' => 'past']) }}"
                    @class(['menu-subitem', 'active' => $drawTab === 'past'])
                >
                    <x-icon name="user-check" />
                    <span data-i18n="past_winners">Past Winners</span>
                </a>

                <a
                    href="{{ route('draws.winners') }}"
                    @class(['menu-subitem', 'active' => request()->routeIs('draws.winners')])
                >
                    <x-icon name="printer" />
                    <span data-i18n="winners_report">Winners Report</span>
                </a>

            </div>

        </div>


        <div class="menu-section-title menu-section-spaced" data-i18n="menu_management">
            MANAGEMENT
        </div>


        <!-- Reports -->

        <a
            href="#"
            class="menu-item"
        >
            <span class="menu-item-icon icon-3d icon-3d-blue">
                <x-icon name="chart" />
            </span>

            <span class="menu-item-text" data-i18n="menu_reports">
                Reports
            </span>
        </a>


        <!-- Settings -->

        <a
            href="{{ route('password.edit') }}"
            @class(['menu-item', 'active' => request()->routeIs('password.*')])
        >
            <span class="menu-item-icon icon-3d icon-3d-purple">
                <x-icon name="settings" />
            </span>

            <span class="menu-item-text" data-i18n="menu_settings">
                Settings
            </span>
        </a>

    </div>


    <div class="menu-footer">

        <div class="menu-user">

            <div class="menu-user-avatar icon-3d icon-3d-orange">
                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()?->name ?? 'A', 0, 1)) }}
            </div>

            <div class="menu-user-info">

                <strong>
                    {{ auth()->user()?->name ?? 'Admin' }}
                </strong>

                <span data-i18n="administrator">
                    Administrator
                </span>

            </div>

        </div>


        <form
            method="POST"
            action="{{ route('logout') }}"
        >
            @csrf

            <button
                type="submit"
                class="menu-logout"
            >
                <x-icon name="logout" />
                <span data-i18n="logout">Logout</span>
            </button>
        </form>

    </div>

</aside>