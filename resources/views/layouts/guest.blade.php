<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    {{-- Laravel CSRF Token --}}
    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        @yield('title', 'SN Chit Funds')
    </title>


    {{-- GLOBAL CSS + JS --}}

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])


    {{-- PAGE-SPECIFIC CSS --}}

    @stack('styles')

</head>


<body class="guest-body">

    {{-- ANIMATED BACKGROUND (same as the signed-in layout — see app.css) --}}

    <div class="background-shape shape-orange"></div>
    <div class="background-shape shape-blue"></div>
    <div class="background-shape shape-purple"></div>
    <div class="background-shape shape-bottom"></div>


    {{-- LANGUAGE + THEME (reuses the header controls handled by app.js) --}}

    <div class="guest-controls">

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
                aria-label="Toggle theme"
            >
                <span id="themeIcon" class="theme-icon">
                    <x-icon name="sun" class="theme-sun" />
                    <x-icon name="moon" class="theme-moon" />
                </span>
            </button>

        </div>

    </div>


    {{-- PAGE CONTENT --}}

    <main class="animate-page">
        @yield('content')
    </main>


    {{-- PAGE-SPECIFIC JS --}}

    @stack('scripts')

</body>

</html>
