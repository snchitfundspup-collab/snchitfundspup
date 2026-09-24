<header class="header">

    <div class="brand">

        {{-- logo + name → Home (dashboard) --}}

        <a
            href="{{ route($business['home'] ?? 'dashboard') }}"
            class="brand-link"
            aria-label="{{ $business['full_name'] ?? 'SN Chit Funds' }} – go to Home"
        >

            <div class="brand-logo logo-3d">
                <img
                    src="{{ asset('images/sn-chit-funds-logo.png') }}"
                    alt=""
                >
            </div>

            <div>
                <div class="brand-name">
                    <span>SN</span> {{ $business['name'] ?? 'Chit Funds' }}
                </div>

                <div class="brand-tagline" data-i18n="{{ $business['tagline_key'] ?? 'brand_tagline' }}">
                    {{ ($business['key'] ?? 'chit') === 'traders' ? 'Quality Rice · Fair Price' : 'Trust · Growth · Together' }}
                </div>
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
         

        <div class="language-switch">
            

            <button
                type="button"
                class="language-button active"
                id="englishButton"
                onclick="changeLanguage('en')"
            >
                English
            </button>

            <button
                type="button"
                class="language-button"
                id="tamilButton"
                onclick="changeLanguage('ta')"
            >
                தமிழ்
            </button>

        </div>


        <div class="theme-switch">

            <button
                type="button"
                class="theme-button"
                id="themeButton"
                onclick="toggleTheme()"
            >
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

                <span class="admin-avatar icon-3d icon-3d-orange">
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()?->name ?? 'A', 0, 1)) }}
                </span>

                <span class="admin-name">
                    {{ auth()->user()?->name ?? 'Admin' }}
                </span>

                <x-icon name="chevron-down" class="admin-caret" />

            </button>


            <div
                class="admin-menu"
                id="adminMenu"
                role="menu"
                hidden
            >

                <div class="admin-menu-user">

                    <strong>
                        {{ auth()->user()?->name ?? 'Admin' }}
                    </strong>

                    <span>
                        {{ auth()->user()?->username }}
                    </span>

                </div>


                <a
                    href="{{ route('password.edit') }}"
                    class="admin-menu-item"
                    role="menuitem"
                >
                    <span class="admin-menu-icon icon-3d icon-3d-blue">
                        <x-icon name="lock" />
                    </span>
                    <span data-i18n="passwordTitle">Change Password</span>
                </a>


                @can('view-usage')
                    <a
                        href="{{ route('usage.index') }}"
                        class="admin-menu-item"
                        role="menuitem"
                    >
                        <span class="admin-menu-icon icon-3d icon-3d-purple">
                            <x-icon name="chart" />
                        </span>
                        <span data-i18n="menu_usage">Usage</span>
                    </a>
                @endcan


                <form
                    method="POST"
                    action="{{ route('logout') }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="admin-menu-item admin-menu-logout"
                        role="menuitem"
                    >
                        <span class="admin-menu-icon icon-3d icon-3d-red">
                            <x-icon name="logout" />
                        </span>
                        <span data-i18n="logout">Logout</span>
                    </button>
                </form>

            </div>

        </div>

    </div>

</header>