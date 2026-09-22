<header class="header">

    <div class="brand">

        <div class="brand-logo">
            <img
                src="{{ asset('images/sn-chit-funds-logo.png') }}"
                alt="SN Chit Funds"
            >
        </div>
        
        <div>
            <div class="brand-name">
                <span>SN</span> Chit Funds
            </div>

            <div class="brand-tagline">
                Trust · Growth · Together
            </div>
        </div>
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
                <span id="themeIcon">☀</span>
            </button>

        </div>


        <div class="admin">

            <div class="admin-avatar">
                <span>♙</span>
            </div>

            <span>
                Admin
            </span>

        </div>

    </div>

</header>