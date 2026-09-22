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

        <div class="menu-brand">

            <div class="menu-logo">
                <img
                    src="{{ asset('images/sn-chit-funds-logo.png') }}"
                    alt="SN Chit Funds"
                >
            </div>

            <div>
                <div class="menu-brand-name">
                    SN <span>Chit Funds</span>
                </div>

                <div class="menu-brand-subtitle">
                    Trust · Growth · Together
                </div>
            </div>

        </div>


        <button
            type="button"
            class="menu-close"
            onclick="toggleMenu()"
            aria-label="Close menu"
        >
            ×
        </button>

    </div>


    <div class="menu-content">

        <div class="menu-section-title">
            MAIN
        </div>


        <!-- Home -->

        <a
            href="{{ url('/') }}"
            class="menu-item"
        >
            <span class="menu-item-icon">⌂</span>

            <span class="menu-item-text">
                Home
            </span>
        </a>


        <!-- Customers -->

        <a
            href="{{ route('customers.index') }}"
            class="menu-item"
        >
            <span class="menu-item-icon">♙</span>

            <span class="menu-item-text">
                Customers
            </span>
        </a>


        <!-- Groups -->

        <a
            href="#"
            class="menu-item"
        >
            <span class="menu-item-icon">♙</span>

            <span class="menu-item-text">
                Groups
            </span>
        </a>


        <!-- Payments -->

        <a
            href="#"
            class="menu-item"
        >
            <span class="menu-item-icon">₹</span>

            <span class="menu-item-text">
                Payments
            </span>
        </a>


        <!-- Draws -->

        <a
            href="#"
            class="menu-item"
        >
            <span class="menu-item-icon">◆</span>

            <span class="menu-item-text">
                Draws
            </span>
        </a>


        <div class="menu-section-title menu-section-spaced">
            MANAGEMENT
        </div>


        <!-- Reports -->

        <a
            href="#"
            class="menu-item"
        >
            <span class="menu-item-icon">▤</span>

            <span class="menu-item-text">
                Reports
            </span>
        </a>


        <!-- Settings -->

        <a
            href="#"
            class="menu-item"
        >
            <span class="menu-item-icon">⚙</span>

            <span class="menu-item-text">
                Settings
            </span>
        </a>

    </div>


    <div class="menu-footer">

        <div class="menu-user">

            <div class="menu-user-avatar">
                A
            </div>

            <div class="menu-user-info">

                <strong>
                    Admin
                </strong>

                <span>
                    Administrator
                </span>

            </div>

        </div>


        <button
            type="button"
            class="menu-logout"
        >
            <span>↪</span>
            <span>Logout</span>
        </button>

    </div>

</aside>