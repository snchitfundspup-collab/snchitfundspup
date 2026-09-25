{{-- Side menu for the customer pages: Home, then SN Chit Funds and SN
     Traders, each with a few simple items. --}}

@php
    $sections = [
        ['menu_chit_funds', 'SN Chit Funds', [
            ['portal.groups', 'layers', 'blue', 'my_groups', 'My Groups', 'portal.groups*'],
            ['portal.upcoming', 'calendar', 'purple', 'upcoming_groups', 'Upcoming Groups', 'portal.upcoming*'],
        ]],
        ['menu_traders', 'SN Traders', [
            ['portal.rice', 'package', 'green', 'rice_and_order', 'Rice & Order', 'portal.rice'],
            ['portal.orders', 'check', 'orange', 'my_orders', 'My Orders', 'portal.orders*'],
            ['portal.bills', 'rupee', 'purple', 'my_bills', 'Bills & Statement', 'portal.bills*'],
        ]],
    ];
@endphp

<div id="menuOverlay" class="menu-overlay" onclick="toggleMenu()"></div>

<aside id="appMenu" class="app-menu" aria-hidden="true">

    <div class="menu-header">

        <a href="{{ route('portal.dashboard') }}" class="menu-brand brand-link" aria-label="SN – go to My home">
            <div class="menu-logo logo-3d">
                <img src="{{ asset('images/sn-chit-funds-logo.png') }}" alt="">
            </div>
            <div>
                <div class="menu-brand-name"><span>SN</span> <span data-i18n="my_account">My Account</span></div>
                <div class="menu-brand-subtitle">Chit Funds · Traders</div>
            </div>
        </a>

        <button type="button" class="menu-close" onclick="toggleMenu()" aria-label="Close menu">
            <x-icon name="x" />
        </button>

    </div>


    <div class="menu-content">

        <a href="{{ route('portal.dashboard') }}" @class(['menu-item', 'active' => request()->routeIs('portal.dashboard')])>
            <span class="menu-item-icon icon-3d icon-3d-orange"><x-icon name="home" /></span>
            <span class="menu-item-text" data-i18n="menu_home">Home</span>
        </a>

        @foreach ($sections as [$sectionKey, $sectionLabel, $items])

            <div class="menu-section-title menu-section-spaced" data-i18n="{{ $sectionKey }}">{{ $sectionLabel }}</div>

            @foreach ($items as [$itemRoute, $itemIcon, $itemColour, $itemKey, $itemLabel, $itemActive])
                <a href="{{ route($itemRoute) }}" @class(['menu-item', 'active' => request()->routeIs($itemActive)])>
                    <span class="menu-item-icon icon-3d icon-3d-{{ $itemColour }}"><x-icon :name="$itemIcon" /></span>
                    <span class="menu-item-text" data-i18n="{{ $itemKey }}">{{ $itemLabel }}</span>
                </a>
            @endforeach

        @endforeach

        <div class="menu-section-title menu-section-spaced" data-i18n="menu_account">ACCOUNT</div>

        <a href="tel:{{ preg_replace('/[^\d+]/', '', (string) config('app.office_phone')) }}" class="menu-item">
            <span class="menu-item-icon icon-3d icon-3d-green"><x-icon name="phone" /></span>
            <span class="menu-item-text"><span data-i18n="call_office">Call office</span> <small class="menu-item-phone">{{ config('app.office_phone') }}</small></span>
        </a>

        <a href="{{ route('portal.password.edit') }}" @class(['menu-item', 'active' => request()->routeIs('portal.password.*')])>
            <span class="menu-item-icon icon-3d icon-3d-blue"><x-icon name="lock" /></span>
            <span class="menu-item-text" data-i18n="passwordTitle">Change Password</span>
        </a>

        <form method="POST" action="{{ route('portal.logout') }}">
            @csrf
            <button type="submit" class="menu-item menu-item-button">
                <span class="menu-item-icon icon-3d icon-3d-red"><x-icon name="logout" /></span>
                <span class="menu-item-text" data-i18n="logout">Logout</span>
            </button>
        </form>

    </div>

</aside>
