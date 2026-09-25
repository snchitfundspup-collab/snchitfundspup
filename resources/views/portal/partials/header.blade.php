{{-- Header for the customer pages: logo → My home, menu button, language,
     theme, and the customer's name menu (change password, log out). --}}

@php
    $me = auth('customer')->user();
@endphp

<header class="header">

    <div class="brand">

        <a href="{{ route('portal.dashboard') }}" class="brand-link" aria-label="SN – go to My home">

            <div class="brand-logo logo-3d">
                <img src="{{ asset('images/sn-chit-funds-logo.png') }}" alt="">
            </div>

            <div>
                <div class="brand-name"><span>SN</span> <span data-i18n="my_account">My Account</span></div>
                <div class="brand-tagline">Chit Funds · Traders</div>
            </div>

        </a>

        <button
            type="button"
            class="header-menu-button"
            onclick="toggleMenu()"
            aria-label="Open menu"
            aria-controls="appMenu"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>

    </div>


    <div class="header-controls">

        @include('portal.partials.call-office', ['compact' => true])

        <div class="language-switch">
            <button type="button" class="language-button active" id="englishButton" onclick="changeLanguage('en')">English</button>
            <button type="button" class="language-button" id="tamilButton" onclick="changeLanguage('ta')">தமிழ்</button>
        </div>

        <div class="theme-switch">
            <button type="button" class="theme-button" id="themeButton" onclick="toggleTheme()">
                <span id="themeIcon" class="theme-icon">
                    <x-icon name="sun" class="theme-sun" />
                    <x-icon name="moon" class="theme-moon" />
                </span>
            </button>
        </div>

        <div class="admin" id="adminDropdown">

            <button
                type="button"
                class="admin-toggle"
                onclick="toggleAdminMenu()"
                aria-haspopup="true"
                aria-expanded="false"
                aria-controls="adminMenu"
            >
                <span class="admin-avatar icon-3d icon-3d-blue">
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($me?->name ?? 'C', 0, 1)) }}
                </span>
                <span class="admin-name">{{ $me?->name }}</span>
                <x-icon name="chevron-down" class="admin-caret" />
            </button>

            <div class="admin-menu" id="adminMenu" role="menu" hidden>

                <div class="admin-menu-user">
                    <strong><x-customer-name :customer="$me" /></strong>
                    <span>{{ $me?->customer_code }} · {{ $me?->phone }}</span>
                </div>

                <a href="{{ route('portal.password.edit') }}" class="admin-menu-item" role="menuitem">
                    <span class="admin-menu-icon icon-3d icon-3d-blue"><x-icon name="lock" /></span>
                    <span data-i18n="passwordTitle">Change Password</span>
                </a>

                <form method="POST" action="{{ route('portal.logout') }}">
                    @csrf
                    <button type="submit" class="admin-menu-item admin-menu-logout" role="menuitem">
                        <span class="admin-menu-icon icon-3d icon-3d-red"><x-icon name="logout" /></span>
                        <span data-i18n="logout">Logout</span>
                    </button>
                </form>

            </div>

        </div>

    </div>

</header>
